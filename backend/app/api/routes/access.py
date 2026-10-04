from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy import delete, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.core.security import hash_password
from app.models.tables import Branch, Permission, Role, RolePermission, User
from app.services.audit import write_audit
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


class UserCreate(BaseModel):
    username: str = Field(min_length=1, max_length=100)
    password: str = Field(min_length=12, max_length=200)
    full_name: str = Field(min_length=1, max_length=150)
    role_id: int
    branch_id: int
    is_active: bool = True


class UserUpdate(BaseModel):
    password: str | None = Field(default=None, min_length=12, max_length=200)
    full_name: str = Field(min_length=1, max_length=150)
    role_id: int
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
        codes = db.scalars(
            select(Permission.code)
            .join(RolePermission, RolePermission.permission_id == Permission.id)
            .where(RolePermission.role_id == role.id)
            .order_by(Permission.code)
        ).all()
        items.append(
            {
                "id": role.id,
                "code": role.code,
                "name": role.name,
                "is_active": role.is_active,
                "permissions": list(codes),
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


def _user_item(user: User, role: Role, branch: Branch) -> dict:
    return {
        "id": user.id,
        "username": user.username,
        "full_name": user.full_name,
        "role_id": user.role_id,
        "role_name": role.name,
        "branch_id": user.branch_id,
        "branch_name": branch.name,
        "is_active": user.is_active,
        "mfa_enabled": user.mfa_enabled,
    }


@router.get("/users")
def list_users(
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    statement = (
        select(User, Role, Branch)
        .join(Role, Role.id == User.role_id)
        .join(Branch, Branch.id == User.branch_id)
        .order_by(User.username)
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(User.branch_id == scope)
    items = [_user_item(user, role, branch) for user, role, branch in db.execute(statement).all()]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/users")
def create_user(
    body: UserCreate,
    actor: User = Depends(require_access_manager),
    db: Session = Depends(get_db),
) -> dict:
    if db.scalar(select(User.id).where(User.username == body.username.strip())) is not None:
        raise ApiError(400, "01", "Username sudah digunakan")
    _active_role(db, body.role_id)
    _ensure_branch_allowed(db, actor, body.branch_id)
    user = User(
        username=body.username.strip(),
        password_hash=hash_password(body.password),
        full_name=body.full_name.strip(),
        role_id=body.role_id,
        branch_id=body.branch_id,
        is_active=body.is_active,
    )
    db.add(user)
    db.flush()
    write_audit(db, actor, "access.user_created", after_data={"user_id": user.id, "role_id": user.role_id})
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
    _active_role(db, body.role_id)
    _ensure_branch_allowed(db, actor, body.branch_id)
    remains_manager = body.is_active and role_has_permission(db, body.role_id, ACCESS_MANAGE)
    was_manager = user.is_active and has_permission(db, user, ACCESS_MANAGE)
    if was_manager and not remains_manager and active_manager_count(db, exclude_user_id=user.id) == 0:
        raise ApiError(400, "01", "Harus tetap ada user aktif yang dapat mengelola akses")
    before = {
        "full_name": user.full_name,
        "role_id": user.role_id,
        "branch_id": user.branch_id,
        "is_active": user.is_active,
    }
    user.full_name = body.full_name.strip()
    user.role_id = body.role_id
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
            "branch_id": user.branch_id,
            "is_active": user.is_active,
            "password_changed": bool(body.password),
        },
    )
    db.commit()
    return {"rcode": "00", "message": "User berhasil diperbarui", "result": {"id": user.id}}
