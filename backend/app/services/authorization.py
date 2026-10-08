from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models.tables import Branch, Permission, Role, RolePermission, User, UserHris

ACCESS_MANAGE = "access.manage"
BRANCH_VIEW_ALL = "branch.view_all"
MASTER_MANAGE = "master.manage"
SCORING_CONFIGURE = "scoring.configure"
SCORING_APPROVE = "scoring.approve"
SCORING_VIEW_SCORE_DETAILS = "scoring.view_score_details"

# Umbrella / implied permission expands (backward compatible).
# scoring.approve implies view_score_details so Approval always sees score metrics;
# the fine-grained code remains assignable on its own via Access → Role.
PERMISSION_EXPAND = {
    ACCESS_MANAGE: ("access.users", "access.roles", "access.permissions"),
    MASTER_MANAGE: ("master.debtors", "master.products", "master.branches"),
    SCORING_CONFIGURE: ("scoring.parameters", "scoring.mapping"),
    SCORING_APPROVE: (SCORING_VIEW_SCORE_DETAILS,),
}


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
    codes = set(rows)
    for umbrella, children in PERMISSION_EXPAND.items():
        if umbrella in codes:
            codes.update(children)
    return sorted(codes)


def has_permission(db: Session, user: User, code: str) -> bool:
    return code in permission_codes(db, user)


def has_any_permission(db: Session, user: User, *codes: str) -> bool:
    owned = set(permission_codes(db, user))
    return any(code in owned for code in codes)


def can_view_score_details(db: Session, user: User) -> bool:
    """True if user may see batas skor / kelayakan / bobot / nilai / skor."""
    return has_permission(db, user, SCORING_VIEW_SCORE_DETAILS)


def redact_score_fields(payload: dict | None, *, allowed: bool) -> dict | None:
    """Strip score-metric keys from a dict when the actor may not view them."""
    if payload is None or allowed:
        return payload
    sensitive = {
        "passing_score",
        "eligibility_status",
        "total_score",
        "result_label",
        "weight",
        "value",
        "total",
        "line_score",
    }
    cleaned = {key: value for key, value in payload.items() if key not in sensitive}
    if "lines" in payload and isinstance(payload["lines"], list):
        cleaned["lines"] = [
            redact_score_fields(line, allowed=False) if isinstance(line, dict) else line
            for line in payload["lines"]
        ]
    return cleaned


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
    # 1. Cari cabang fisik pengguna
    user_branch = None
    if user.branch_id:
        user_branch = db.get(Branch, user.branch_id)

    # Sinkronisasi dengan UserHris jika ada
    hris = db.scalar(
        select(UserHris).where(
            (UserHris.npp == user.username) | (UserHris.userid == user.username) | (UserHris.userid == f"u{user.username}")
        )
    )
    if hris and hris.branchid:
        b_by_code = db.scalar(select(Branch).where(Branch.code == str(hris.branchid).strip()))
        if b_by_code:
            user_branch = b_by_code
            if user.branch_id != b_by_code.id:
                user.branch_id = b_by_code.id
                db.flush()

    branch_code = str(user_branch.code).strip() if user_branch and user_branch.code else ""

    # Aturan Wajib: Jika branchid != 001 (dan bukan PST), batasi HANYA ke cabangnya sendiri!
    if branch_code not in ("001", "PST"):
        return user_branch.id if user_branch else user.branch_id

    # Untuk user di Kantor Pusat (001 / PST), cek izin melihat semua cabang (branch.view_all)
    if has_permission(db, user, BRANCH_VIEW_ALL):
        return None

    return user_branch.id if user_branch else user.branch_id


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
