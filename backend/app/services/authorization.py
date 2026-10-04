from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models.tables import Permission, Role, RolePermission, User

ACCESS_MANAGE = "access.manage"
BRANCH_VIEW_ALL = "branch.view_all"
MASTER_MANAGE = "master.manage"
SCORING_CONFIGURE = "scoring.configure"


def permission_codes(db: Session, user: User) -> list[str]:
    rows = db.scalars(
        select(Permission.code)
        .join(RolePermission, RolePermission.permission_id == Permission.id)
        .join(Role, Role.id == RolePermission.role_id)
        .where(
            RolePermission.role_id == user.role_id,
            Permission.is_active.is_(True),
            Role.is_active.is_(True),
        )
        .order_by(Permission.code)
    ).all()
    return list(rows)


def has_permission(db: Session, user: User, code: str) -> bool:
    return code in permission_codes(db, user)


def role_has_permission(db: Session, role_id: int, code: str) -> bool:
    found = db.scalar(
        select(Permission.id)
        .join(RolePermission, RolePermission.permission_id == Permission.id)
        .join(Role, Role.id == RolePermission.role_id)
        .where(
            RolePermission.role_id == role_id,
            Permission.code == code,
            Permission.is_active.is_(True),
            Role.is_active.is_(True),
        )
        .limit(1)
    )
    return found is not None


def visible_branch_id(db: Session, user: User) -> int | None:
    if has_permission(db, user, BRANCH_VIEW_ALL):
        return None
    return user.branch_id


def active_manager_count(db: Session, *, exclude_user_id: int | None = None) -> int:
    statement = (
        select(User.id)
        .join(RolePermission, RolePermission.role_id == User.role_id)
        .join(Permission, Permission.id == RolePermission.permission_id)
        .where(
            User.is_active.is_(True),
            Permission.code == ACCESS_MANAGE,
            Permission.is_active.is_(True),
        )
    )
    if exclude_user_id is not None:
        statement = statement.where(User.id != exclude_user_id)
    return len(set(db.scalars(statement).all()))
