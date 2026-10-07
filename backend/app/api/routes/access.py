from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy import delete, select, update
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
    code: str = Field(pattern=r"^[a-z0-9_]{2,50}$")
    name: str = Field(min_length=1, max_length=100)
    is_active: bool = True


class RoleUpdate(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    is_active: bool


class PermissionAssignment(BaseModel):
    permission_ids: list[int] = Field(default_factory=list)


class JobGroupBody(BaseModel):
    code: str = Field(pattern=r"^[a-z0-9_]{2,50}$")
    name: str = Field(min_length=1, max_length=100)
    role_id: int
    is_active: bool = True


class JobGroupUpdate(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    role_id: int
    is_active: bool


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
        items.append(
            {
                "id": role.id,
                "code": role.code,
                "name": role.name,
                "is_active": role.is_active,
                "permission_ids": [int(row[0]) for row in perms],
                "permissions": [str(row[1]) for row in perms],
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
        "job_group_code": job_group.code if job_group else None,
        "job_group_name": job_group.name if job_group else None,
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
        select(JobGroup, Role).join(Role, Role.id == JobGroup.role_id).order_by(JobGroup.name)
    ).all()
    items = [
        {
            "id": group.id,
            "code": group.code,
            "name": group.name,
            "role_id": group.role_id,
            "role_code": role.code,
            "role_name": role.name,
            "is_active": group.is_active,
        }
        for group, role in rows
    ]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/job-groups")
def create_job_group(
    body: JobGroupBody,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    if db.scalar(select(JobGroup.id).where(JobGroup.code == body.code)) is not None:
        raise ApiError(400, "01", "Kode kelompok jabatan sudah digunakan")
    _active_role(db, body.role_id)
    group = JobGroup(
        code=body.code,
        name=body.name.strip(),
        role_id=body.role_id,
        is_active=body.is_active,
    )
    db.add(group)
    db.flush()
    write_audit(
        db,
        actor,
        "access.job_group_created",
        after_data={"job_group_id": group.id, "code": group.code, "role_id": group.role_id},
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
    _active_role(db, body.role_id)
    before = {"name": group.name, "role_id": group.role_id, "is_active": group.is_active}
    group.name = body.name.strip()
    group.role_id = body.role_id
    group.is_active = body.is_active
    # Sinkron role user yang memakai kelompok ini
    db.execute(update(User).where(User.job_group_id == group.id).values(role_id=group.role_id))
    write_audit(
        db,
        actor,
        "access.job_group_updated",
        object_id=str(group.id),
        before_data=before,
        after_data={"name": group.name, "role_id": group.role_id, "is_active": group.is_active},
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
    userid = (body or {}).get("userid", "1776")
    kondisi = (body or {}).get("kondisi", "")
    settings = get_settings()
    data = call_hris_inq_master_pegawai_by_kondisi(settings, userid=userid, kondisi=kondisi)
    if not data:
        raise ApiError(400, "01", "Gagal mengambil data dari HRIS Gateway")

    synced = []
    for item in data:
        npp = item.get("nama_login") or userid
        uid = f"u{npp}" if not npp.startswith("u") else npp
        existing = db.get(UserHris, uid)
        if not existing:
            existing = UserHris(userid=uid)
            db.add(existing)

        existing.npp = npp
        existing.nrik = item.get("nrik")
        existing.nama = item.get("nama")
        existing.no_hp = item.get("no_hp") or item.get("no_whatsap")
        existing.user_email = item.get("user_email")
        existing.id_unit_kerja = item.get("id_unit_kerja")
        existing.nm_unit_kerja = item.get("nm_unit_kerja")
        existing.branchid = item.get("kd_unit_penempatan") or "001"
        existing.id_jabatan = item.get("id_jabatan")
        existing.nm_jabatan = item.get("nm_jabatan")
        existing.id_kel_jabatan = item.get("id_kel_jabatan")
        existing.nama_kel_jabatan = item.get("nama_kel_jabatan")
        existing.password = item.get("password")
        existing.stsauth = 0
        existing.stsbest = 0
        synced.append(uid)

    db.commit()
    write_audit(
        db,
        actor,
        "access.userhris_synced",
        after_data={"count": len(synced), "synced": synced},
    )
    db.commit()
    return {"rcode": "00", "message": f"Berhasil sinkronisasi {len(synced)} data pegawai HRIS", "result": {"synced": synced}}

