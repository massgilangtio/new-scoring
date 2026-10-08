from datetime import datetime
from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy import delete, func, select, text, update
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.core.config import get_settings
from app.core.security import hash_password
from app.models.tables import Branch, JobGroup, Permission, Role, RolePermission, User, UserHris
from app.services.audit import write_audit
from app.services.gateway import call_hris_inq_master_pegawai_by_kondisi
from app.services.authorization import (
    ACCESS_MANAGE,
    active_manager_count,
    has_permission,
    role_has_permission,
    visible_branch_id,
)

router = APIRouter(prefix="/api/v1/access", tags=["access"])


class RoleBody(BaseModel):
    code: str = Field(pattern=r"^[A-Za-z0-9_]{2,50}$")
    name: str = Field(min_length=1, max_length=100)
    is_active: bool = True


class RoleUpdate(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    is_active: bool


class PermissionAssignment(BaseModel):
    permission_ids: list[int] = Field(default_factory=list)


class JobGroupBody(BaseModel):
    id_kel_jabatan: str = Field(min_length=1, max_length=50)
    nama_kel_jabatan: str = Field(min_length=1, max_length=200)
    role_id: int | None = None
    is_active: bool = True


class JobGroupUpdate(BaseModel):
    nama_kel_jabatan: str | None = Field(default=None, max_length=200)
    role_id: int | None = None
    is_active: bool = True


class UserCreate(BaseModel):
    username: str = Field(min_length=1, max_length=100)
    password: str = Field(min_length=12, max_length=200)
    full_name: str = Field(min_length=1, max_length=150)
    job_group_id: int
    branch_id: int
    is_active: bool = True


class UserUpdate(BaseModel):
    password: str | None = Field(default=None, min_length=12, max_length=200)
    full_name: str = Field(min_length=1, max_length=150)
    job_group_id: int
    branch_id: int
    is_active: bool


def require_access_manager(user: User = Depends(current_user), db: Session = Depends(get_db)) -> User:
    if not has_permission(db, user, ACCESS_MANAGE):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    return user


def _ensure_branch_allowed(db: Session, actor: User, branch_id: int) -> Branch:
    branch = db.get(Branch, branch_id)
    if branch is None or not branch.is_active:
        raise ApiError(400, "01", "Cabang tidak tersedia")
    scope = visible_branch_id(db, actor)
    if scope is not None and branch.id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")
    return branch


def _active_role(db: Session, role_id: int) -> Role:
    role = db.get(Role, role_id)
    if role is None or not role.is_active:
        raise ApiError(400, "01", "Role tidak tersedia")
    return role


def _active_job_group(db: Session, job_group_id: int) -> JobGroup:
    group = db.get(JobGroup, job_group_id)
    if group is None or not group.is_active:
        raise ApiError(400, "01", "Kelompok jabatan tidak tersedia")
    if group.role_id is None:
        raise ApiError(
            400,
            "01",
            f"Kelompok jabatan '{group.nama_kel_jabatan or group.id_kel_jabatan}' belum dipetakan ke role manapun. Harap petakan role terlebih dahulu di menu Kelompok Jabatan.",
        )
    _active_role(db, group.role_id)
    return group


def _role_keeps_a_manager(db: Session, role_id: int, next_codes: set[str]) -> None:
    if ACCESS_MANAGE in next_codes:
        return
    active_here = db.scalar(select(User.id).where(User.role_id == role_id, User.is_active.is_(True)).limit(1))
    manager_elsewhere = db.scalar(
        select(User.id)
        .join(RolePermission, RolePermission.role_id == User.role_id)
        .join(Permission, Permission.id == RolePermission.permission_id)
        .where(
            User.is_active.is_(True),
            User.role_id != role_id,
            Permission.code == ACCESS_MANAGE,
            Permission.is_active.is_(True),
        )
        .limit(1)
    )
    if active_here is not None and manager_elsewhere is None:
        raise ApiError(400, "01", "Harus tetap ada user aktif yang dapat mengelola akses")


@router.get("/permissions")
def list_permissions(
    _actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    rows = db.scalars(select(Permission).order_by(Permission.code)).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "items": [{"id": row.id, "code": row.code, "name": row.name, "is_active": row.is_active} for row in rows]
        },
    }


@router.get("/roles")
def list_roles(
    _actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    roles = db.scalars(select(Role).order_by(Role.name)).all()
    items = []
    for role in roles:
        perms = db.execute(
            select(Permission.id, Permission.code)
            .join(RolePermission, RolePermission.permission_id == Permission.id)
            .where(RolePermission.role_id == role.id)
            .order_by(Permission.id)
        ).all()
        # Ambil kelompok jabatan yang terhubung ke role ini
        jgs = db.execute(
            select(JobGroup.id, JobGroup.id_kel_jabatan, JobGroup.nama_kel_jabatan)
            .where(JobGroup.role_id == role.id)
            .order_by(JobGroup.id_kel_jabatan)
        ).all()
        job_groups = [
            {"id": int(r[0]), "code": str(r[1]), "name": str(r[2] or r[1])}
            for r in jgs
        ]
        unique_codes = list(dict.fromkeys([jg["code"] for jg in job_groups]))
        items.append(
            {
                "id": role.id,
                "code": role.code,
                "name": role.name,
                "is_active": role.is_active,
                "permission_ids": [int(row[0]) for row in perms],
                "permissions": [str(row[1]) for row in perms],
                "job_groups": job_groups,
                "job_group_codes": unique_codes,
                "job_group_map": ", ".join(unique_codes) if unique_codes else "-",
            }
        )
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/roles")
def create_role(
    body: RoleBody,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    if db.scalar(select(Role.id).where(Role.code == body.code)) is not None:
        raise ApiError(400, "01", "Kode role sudah digunakan")
    role = Role(code=body.code, name=body.name.strip(), is_active=body.is_active)
    db.add(role)
    db.flush()
    write_audit(db, actor, "access.role_created", after_data={"role_id": role.id, "code": role.code})
    db.commit()
    return {"rcode": "00", "message": "Role berhasil disimpan", "result": {"id": role.id}}


@router.patch("/roles/{role_id}")
def update_role(
    role_id: int,
    body: RoleUpdate,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    role = db.get(Role, role_id)
    if role is None:
        raise ApiError(404, "01", "Role tidak ditemukan")
    if role.is_active and not body.is_active:
        _role_keeps_a_manager(db, role.id, set())
    before = {"name": role.name, "is_active": role.is_active}
    role.name = body.name.strip()
    role.is_active = body.is_active
    write_audit(
        db,
        actor,
        "access.role_updated",
        object_id=str(role.id),
        before_data=before,
        after_data={"name": role.name, "is_active": role.is_active},
    )
    db.commit()
    return {"rcode": "00", "message": "Role berhasil diperbarui", "result": {"id": role.id}}


@router.put("/roles/{role_id}/permissions")
def assign_permissions(
    role_id: int,
    body: PermissionAssignment,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    role = db.get(Role, role_id)
    if role is None:
        raise ApiError(404, "01", "Role tidak ditemukan")
    selected = []
    if body.permission_ids:
        selected = list(db.scalars(select(Permission).where(Permission.id.in_(body.permission_ids))).all())
        if len(selected) != len(set(body.permission_ids)):
            raise ApiError(400, "01", "Permission tidak ditemukan")
        if any(not item.is_active for item in selected):
            raise ApiError(400, "01", "Permission tidak aktif")
    next_codes = {item.code for item in selected}
    _role_keeps_a_manager(db, role.id, next_codes)
    db.execute(delete(RolePermission).where(RolePermission.role_id == role.id))
    for item in selected:
        db.add(RolePermission(role_id=role.id, permission_id=item.id))
    write_audit(
        db,
        actor,
        "access.permissions_updated",
        after_data={"role_id": role.id, "permissions": sorted(next_codes)},
    )
    db.commit()
    return {"rcode": "00", "message": "Hak akses role berhasil disimpan", "result": {"permissions": sorted(next_codes)}}


@router.get("/branches")
def list_branches(
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    statement = select(Branch).where(Branch.is_active.is_(True)).order_by(Branch.name)
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(Branch.id == scope)
    rows = db.scalars(statement).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {"items": [{"id": row.id, "code": row.code, "name": row.name} for row in rows]},
    }


def _user_item(user: User, role: Role, branch: Branch, job_group: JobGroup | None = None) -> dict:
    return {
        "id": user.id,
        "username": user.username,
        "full_name": user.full_name,
        "role_id": user.role_id,
        "role_name": role.name,
        "job_group_id": user.job_group_id,
        "job_group_code": job_group.id_kel_jabatan if job_group else None,
        "job_group_name": job_group.nama_kel_jabatan if job_group else None,
        "branch_id": user.branch_id,
        "branch_name": branch.name,
        "is_active": user.is_active,
        "mfa_enabled": user.mfa_enabled,
    }


@router.get("/job-groups")
def list_job_groups(
    _actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    rows = db.execute(
        select(JobGroup, Role)
        .outerjoin(Role, Role.id == JobGroup.role_id)
        .order_by(JobGroup.id_kel_jabatan, JobGroup.nama_kel_jabatan)
    ).all()
    items = [
        {
            "id": group.id,
            "id_kel_jabatan": group.id_kel_jabatan,
            "nama_kel_jabatan": group.nama_kel_jabatan or "-",
            "code": group.id_kel_jabatan,
            "name": group.nama_kel_jabatan or group.id_kel_jabatan,
            "total_pegawai": group.total_pegawai or 0,
            "role_id": group.role_id,
            "role_code": role.code if role else None,
            "role_name": role.name if role else "Belum Dipetakan",
            "is_active": group.is_active,
        }
        for group, role in rows
    ]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


def _sync_kel_jabatan_internal(db: Session) -> int:
    """Sinkronisasi / update tbl_kel_jabatan dari data terbaru tbl_userhris."""
    sync_sql = text("""
        INSERT INTO tbl_kel_jabatan (id_kel_jabatan, nama_kel_jabatan, total_pegawai, is_active, created_at, updated_at)
        SELECT 
            h.id_kel_jabatan,
            COALESCE(NULLIF(h.nama_kel_jabatan, ''), h.nm_jabatan, 'Kelompok ' || h.id_kel_jabatan) AS nama_kel_jabatan,
            COUNT(*) AS total_pegawai,
            true,
            now(),
            now()
        FROM tbl_userhris h
        WHERE h.id_kel_jabatan IS NOT NULL AND TRIM(h.id_kel_jabatan) != ''
        GROUP BY h.id_kel_jabatan, COALESCE(NULLIF(h.nama_kel_jabatan, ''), h.nm_jabatan, 'Kelompok ' || h.id_kel_jabatan)
        ON CONFLICT (id_kel_jabatan, nama_kel_jabatan) 
        DO UPDATE SET 
            total_pegawai = EXCLUDED.total_pegawai,
            updated_at = now();
    """)
    db.execute(sync_sql)
    db.flush()
    total_count = db.scalar(select(func.count(JobGroup.id))) or 0
    return int(total_count)


@router.post("/job-groups/sync")
def sync_job_groups_from_hris(
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    """Sinkronisasi / refresh data tbl_kel_jabatan dari tbl_userhris."""
    total_count = _sync_kel_jabatan_internal(db)
    write_audit(db, actor, "access.job_groups_synced", after_data={"synced_from": "tbl_userhris", "total": total_count})
    db.commit()
    return {"rcode": "00", "message": f"Sinkronisasi kelompok jabatan selesai. Total: {total_count} data.", "result": {"total": total_count}}


@router.post("/job-groups")
def create_job_group(
    body: JobGroupBody,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    if body.role_id is not None:
        _active_role(db, body.role_id)
    group = JobGroup(
        id_kel_jabatan=body.id_kel_jabatan.strip(),
        nama_kel_jabatan=body.nama_kel_jabatan.strip(),
        role_id=body.role_id,
        is_active=body.is_active,
    )
    db.add(group)
    db.flush()
    write_audit(
        db,
        actor,
        "access.job_group_created",
        after_data={"job_group_id": group.id, "id_kel_jabatan": group.id_kel_jabatan, "role_id": group.role_id},
    )
    db.commit()
    return {"rcode": "00", "message": "Kelompok jabatan berhasil disimpan", "result": {"id": group.id}}


@router.patch("/job-groups/{job_group_id}")
def update_job_group(
    job_group_id: int,
    body: JobGroupUpdate,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    group = db.get(JobGroup, job_group_id)
    if group is None:
        raise ApiError(404, "01", "Kelompok jabatan tidak ditemukan")
    if body.role_id is not None:
        _active_role(db, body.role_id)
    before = {"nama_kel_jabatan": group.nama_kel_jabatan, "role_id": group.role_id, "is_active": group.is_active}
    if body.nama_kel_jabatan is not None:
        group.nama_kel_jabatan = body.nama_kel_jabatan.strip()
    group.role_id = body.role_id
    group.is_active = body.is_active
    # Sinkron role user yang memakai kelompok ini jika role_id tidak kosong
    if group.role_id is not None:
        db.execute(update(User).where(User.job_group_id == group.id).values(role_id=group.role_id))
    write_audit(
        db,
        actor,
        "access.job_group_updated",
        object_id=str(group.id),
        before_data=before,
        after_data={"nama_kel_jabatan": group.nama_kel_jabatan, "role_id": group.role_id, "is_active": group.is_active},
    )
    db.commit()
    return {"rcode": "00", "message": "Kelompok jabatan berhasil diperbarui", "result": {"id": group.id}}


@router.get("/users")
def list_users(
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    statement = (
        select(User, Role, Branch, JobGroup)
        .join(Role, Role.id == User.role_id)
        .join(Branch, Branch.id == User.branch_id)
        .outerjoin(JobGroup, JobGroup.id == User.job_group_id)
        .order_by(User.username)
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(User.branch_id == scope)
    items = [
        _user_item(user, role, branch, job_group)
        for user, role, branch, job_group in db.execute(statement).all()
    ]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/users")
def create_user(
    body: UserCreate,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    if db.scalar(select(User.id).where(User.username == body.username.strip())) is not None:
        raise ApiError(400, "01", "Username sudah digunakan")
    group = _active_job_group(db, body.job_group_id)
    _ensure_branch_allowed(db, actor, body.branch_id)
    user = User(
        username=body.username.strip(),
        password_hash=hash_password(body.password),
        full_name=body.full_name.strip(),
        role_id=group.role_id,
        job_group_id=group.id,
        branch_id=body.branch_id,
        is_active=body.is_active,
    )
    db.add(user)
    db.flush()
    write_audit(
        db,
        actor,
        "access.user_created",
        after_data={"user_id": user.id, "role_id": user.role_id, "job_group_id": user.job_group_id},
    )
    db.commit()
    return {"rcode": "00", "message": "User berhasil disimpan", "result": {"id": user.id}}


@router.patch("/users/{user_id}")
def update_user(
    user_id: int,
    body: UserUpdate,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    user = db.get(User, user_id)
    if user is None:
        raise ApiError(404, "01", "User tidak ditemukan")
    scope = visible_branch_id(db, actor)
    if scope is not None and user.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")
    group = _active_job_group(db, body.job_group_id)
    _ensure_branch_allowed(db, actor, body.branch_id)
    remains_manager = body.is_active and role_has_permission(db, group.role_id, ACCESS_MANAGE)
    was_manager = user.is_active and has_permission(db, user, ACCESS_MANAGE)
    if was_manager and not remains_manager and active_manager_count(db, exclude_user_id=user.id) == 0:
        raise ApiError(400, "01", "Harus tetap ada user aktif yang dapat mengelola akses")
    before = {
        "full_name": user.full_name,
        "role_id": user.role_id,
        "job_group_id": user.job_group_id,
        "branch_id": user.branch_id,
        "is_active": user.is_active,
    }
    user.full_name = body.full_name.strip()
    user.role_id = group.role_id
    user.job_group_id = group.id
    user.branch_id = body.branch_id
    user.is_active = body.is_active
    if body.password:
        user.password_hash = hash_password(body.password)
    write_audit(
        db,
        actor,
        "access.user_updated",
        object_id=str(user.id),
        before_data=before,
        after_data={
            "full_name": user.full_name,
            "role_id": user.role_id,
            "job_group_id": user.job_group_id,
            "branch_id": user.branch_id,
            "is_active": user.is_active,
            "password_changed": bool(body.password),
        },
    )
    db.commit()
    return {"rcode": "00", "message": "User berhasil diperbarui", "result": {"id": user.id}}


@router.get("/userhris")
def list_userhris(
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    rows = db.query(UserHris).order_by(UserHris.npp).all()
    items = [
        {
            "userid": u.userid,
            "npp": u.npp,
            "nrik": u.nrik,
            "nama": u.nama,
            "no_hp": u.no_hp,
            "user_email": u.user_email,
            "id_unit_kerja": u.id_unit_kerja,
            "nm_unit_kerja": u.nm_unit_kerja,
            "branchid": u.branchid,
            "id_jabatan": u.id_jabatan,
            "nm_jabatan": u.nm_jabatan,
            "id_kel_jabatan": u.id_kel_jabatan,
            "nama_kel_jabatan": u.nama_kel_jabatan,
            "created_at": u.created_at.strftime("%Y-%m-%d %H:%M:%S") if u.created_at else None,
            "updated_at": u.updated_at.strftime("%Y-%m-%d %H:%M:%S") if u.updated_at else None,
            "stsauth": u.stsauth,
            "secret_key": u.secret_key,
            "stsbest": u.stsbest,
        }
        for u in rows
    ]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/userhris/sync")
def sync_userhris(
    body: dict | None = None,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    userid = ((body or {}).get("userid") or "").strip()
    kondisi = (body or {}).get("kondisi", "")
    settings = get_settings()
    data = call_hris_inq_master_pegawai_by_kondisi(settings, userid=userid, kondisi=kondisi)
    if not data:
        raise ApiError(400, "01", "Gagal mengambil data dari HRIS Gateway")

    existing_all = db.query(UserHris).all()
    existing_by_npp = {str(u.npp).strip(): u for u in existing_all if u.npp}
    existing_by_uid = {str(u.userid).strip(): u for u in existing_all if u.userid}

    inserted_count = 0
    updated_count = 0
    now = datetime.now()

    for item in data:
        raw_npp = str(item.get("nama_login") or item.get("npp") or item.get("userid") or "").strip()
        if not raw_npp:
            continue

        uid = f"u{raw_npp}" if not raw_npp.startswith("u") else raw_npp

        existing = existing_by_npp.get(raw_npp) or existing_by_uid.get(uid) or existing_by_uid.get(raw_npp)

        nrik = item.get("nrik")
        nama = item.get("nama")
        no_hp = item.get("no_hp") or item.get("no_whatsap")
        user_email = item.get("user_email")
        id_unit_kerja = str(item.get("id_unit_kerja") or "")
        nm_unit_kerja = item.get("nm_unit_kerja") or item.get("ukerdef")
        branchid = str(item.get("kd_unit_penempatan") or "001")
        id_jabatan = str(item.get("id_jabatan") or "")
        nm_jabatan = item.get("nm_jabatan") or item.get("jabdef")
        id_kel_jabatan = str(item.get("id_kel_jabatan") or "")
        nama_kel_jabatan = item.get("nama_kel_jabatan")
        password = item.get("password")

        if existing:
            existing.npp = raw_npp
            existing.nrik = nrik
            existing.nama = nama
            existing.no_hp = no_hp
            existing.user_email = user_email
            existing.id_unit_kerja = id_unit_kerja
            existing.nm_unit_kerja = nm_unit_kerja
            existing.branchid = branchid
            existing.id_jabatan = id_jabatan
            existing.nm_jabatan = nm_jabatan
            existing.id_kel_jabatan = id_kel_jabatan
            existing.nama_kel_jabatan = nama_kel_jabatan
            if password:
                existing.password = password
            existing.updated_at = now
            updated_count += 1
        else:
            new_user = UserHris(
                userid=uid,
                npp=raw_npp,
                nrik=nrik,
                nama=nama,
                no_hp=no_hp,
                user_email=user_email,
                id_unit_kerja=id_unit_kerja,
                nm_unit_kerja=nm_unit_kerja,
                branchid=branchid,
                id_jabatan=id_jabatan,
                nm_jabatan=nm_jabatan,
                id_kel_jabatan=id_kel_jabatan,
                nama_kel_jabatan=nama_kel_jabatan,
                password=password,
                stsauth=0,
                stsbest=0,
                secret_key=None,
                created_at=now,
                updated_at=now,
            )
            db.add(new_user)
            existing_by_npp[raw_npp] = new_user
            existing_by_uid[uid] = new_user
            inserted_count += 1

    # Sinkronisasi / update tabel kelompok jabatan (tbl_kel_jabatan) secara otomatis
    total_groups = _sync_kel_jabatan_internal(db)

    write_audit(
        db,
        actor,
        "access.userhris_synced",
        after_data={
            "total": len(data),
            "inserted": inserted_count,
            "updated": updated_count,
            "job_groups_total": total_groups,
        },
    )
    db.commit()
    return {
        "rcode": "00",
        "message": (
            f"Berhasil sinkronisasi {len(data)} pegawai HRIS ({inserted_count} baru, {updated_count} diperbarui) "
            f"dan tabel kelompok jabatan ikut terupdate ({total_groups} kelompok)"
        ),
        "result": {
            "total": len(data),
            "inserted": inserted_count,
            "updated": updated_count,
            "job_groups_total": total_groups,
        },
    }


@router.post("/userhris/{userid}/reset-mfa")
def reset_userhris_mfa(
    userid: str,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    hris = db.get(UserHris, userid)
    if not hris:
        alt = f"u{userid}" if not userid.startswith("u") else userid[1:]
        hris = db.get(UserHris, alt)
    if not hris:
        raise ApiError(404, "01", "Pegawai HRIS tidak ditemukan")

    hris.secret_key = None
    hris.stsauth = 1
    hris.updated_at = datetime.now()

    npp = hris.npp or userid.lstrip("u")
    matched_user = db.scalar(
        select(User).where((User.username == npp) | (User.username == hris.userid))
    )
    if matched_user:
        matched_user.mfa_enabled = True
        matched_user.mfa_secret_encrypted = None
        matched_user.mfa_last_step = None
        matched_user.mfa_confirmed_at = None

    write_audit(
        db,
        actor,
        "access.mfa_reset",
        object_id=hris.userid,
        after_data={"userid": hris.userid, "npp": hris.npp, "mfa_reset": True},
    )
    db.commit()
    return {
        "rcode": "00",
        "message": f"Secret key untuk {hris.nama or hris.userid} berhasil direset (scan QR baru saat login)",
        "result": {"userid": hris.userid, "stsauth": 1},
    }


class ToggleMfaBody(BaseModel):
    stsauth: int = Field(ge=0, le=1)


@router.post("/userhris/{userid}/toggle-mfa")
def toggle_userhris_mfa(
    userid: str,
    body: ToggleMfaBody,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    hris = db.get(UserHris, userid)
    if not hris:
        alt = f"u{userid}" if not userid.startswith("u") else userid[1:]
        hris = db.get(UserHris, alt)
    if not hris:
        raise ApiError(404, "01", "Pegawai HRIS tidak ditemukan")

    hris.stsauth = body.stsauth
    if body.stsauth == 0:
        hris.secret_key = None
    hris.updated_at = datetime.now()

    npp = hris.npp or userid.lstrip("u")
    matched_user = db.scalar(
        select(User).where((User.username == npp) | (User.username == hris.userid))
    )
    if matched_user:
        matched_user.mfa_enabled = (body.stsauth == 1)
        if body.stsauth == 0:
            matched_user.mfa_secret_encrypted = None
            matched_user.mfa_last_step = None
            matched_user.mfa_confirmed_at = None

    action_label = "diaktifkan (pakai MFA)" if body.stsauth == 1 else "dinonaktifkan (tanpa MFA)"
    write_audit(
        db,
        actor,
        "access.mfa_toggle",
        object_id=hris.userid,
        after_data={"userid": hris.userid, "npp": hris.npp, "stsauth": body.stsauth},
    )
    db.commit()
    return {
        "rcode": "00",
        "message": f"MFA untuk {hris.nama or hris.userid} berhasil {action_label}",
        "result": {"userid": hris.userid, "stsauth": body.stsauth},
    }


@router.post("/users/{user_id}/reset-mfa")
def reset_user_mfa_by_id(
    user_id: int,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    target = db.get(User, user_id)
    if not target:
        raise ApiError(404, "01", "User tidak ditemukan")

    target.mfa_enabled = True
    target.mfa_secret_encrypted = None
    target.mfa_last_step = None
    target.mfa_confirmed_at = None

    hris = db.scalar(
        select(UserHris).where(
            (UserHris.npp == target.username) | (UserHris.userid == target.username) | (UserHris.userid == f"u{target.username}")
        )
    )
    if hris:
        hris.secret_key = None
        hris.stsauth = 1
        hris.updated_at = datetime.now()

    write_audit(
        db,
        actor,
        "access.mfa_reset",
        object_id=str(target.id),
        after_data={"user_id": target.id, "username": target.username, "mfa_reset": True},
    )
    db.commit()
    return {
        "rcode": "00",
        "message": f"Secret key untuk user {target.username} berhasil direset",
        "result": {"id": target.id},
    }

