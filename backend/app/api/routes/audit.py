from datetime import datetime
from fastapi import APIRouter, Depends
from sqlalchemy import func, or_, select
from sqlalchemy.orm import Session

from app.api.deps import get_db, require_permissions
from app.api.errors import ApiError
from app.models.tables import AuditLog, Branch, User
from app.services.authorization import visible_branch_id

router = APIRouter(prefix="/api/v1/audit", tags=["audit"])


@router.get("")
def list_audit(actor: User = Depends(require_permissions("audit.view")), db: Session = Depends(get_db)) -> dict:
    statement = (
        select(AuditLog, User.username, User.full_name, Branch.code, Branch.name)
        .outerjoin(User, User.id == AuditLog.actor_user_id)
        .outerjoin(Branch, Branch.id == AuditLog.actor_branch_id)
        .order_by(AuditLog.id.desc())
        .limit(100)
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(AuditLog.actor_branch_id == scope)
    rows = db.execute(statement).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "items": [
                {
                    "id": row.id,
                    "occurred_at": row.occurred_at.isoformat() if row.occurred_at else None,
                    "actor_user_id": row.actor_user_id,
                    "actor_username": username or f"User #{row.actor_user_id}",
                    "actor_full_name": full_name or "",
                    "actor_role_name": row.actor_role_name,
                    "actor_branch_id": row.actor_branch_id,
                    "actor_branch_code": branch_code or "",
                    "actor_branch_name": branch_name or "",
                    "action": row.action,
                    "object_type": row.object_type,
                    "object_id": row.object_id,
                    "reason": row.reason,
                    "before": row.before_data,
                    "after": row.after_data,
                }
                for row, username, full_name, branch_code, branch_name in rows
            ]
        },
    }


@router.get("/datatables")
def list_audit_datatables(
    page: int = 1,
    per_page: int = 10,
    search: str | None = None,
    date_from: str | None = None,
    date_to: str | None = None,
    module: str | None = None,
    action: str | None = None,
    object_type: str | None = None,
    actor_filter: str | None = None,
    sort_by: str = "id",
    sort_dir: str = "desc",
    actor: User = Depends(require_permissions("audit.view")),
    db: Session = Depends(get_db),
) -> dict:
    scope = visible_branch_id(db, actor)
    base_where = []
    if scope is not None:
        base_where.append(AuditLog.actor_branch_id == scope)

    # Calculate global stats (filtered by branch scope if applicable)
    total_stmt = select(func.count(AuditLog.id))
    if base_where:
        total_stmt = total_stmt.where(*base_where)
    total = db.scalar(total_stmt) or 0

    now = datetime.now()
    today_start = datetime(now.year, now.month, now.day)
    today_stmt = select(func.count(AuditLog.id)).where(AuditLog.occurred_at >= today_start)
    auth_stmt = select(func.count(AuditLog.id)).where(AuditLog.action.like("auth.%"))
    changes_stmt = select(func.count(AuditLog.id)).where(
        or_(AuditLog.before_data.isnot(None), AuditLog.after_data.isnot(None))
    )
    if base_where:
        today_stmt = today_stmt.where(*base_where)
        auth_stmt = auth_stmt.where(*base_where)
        changes_stmt = changes_stmt.where(*base_where)

    today_cnt = db.scalar(today_stmt) or 0
    auth_cnt = db.scalar(auth_stmt) or 0
    changes_cnt = db.scalar(changes_stmt) or 0

    actions_stmt = select(AuditLog.action).distinct().order_by(AuditLog.action)
    object_types_stmt = select(AuditLog.object_type).distinct().order_by(AuditLog.object_type)
    actors_stmt = (
        select(User.username, User.full_name)
        .join(AuditLog, AuditLog.actor_user_id == User.id)
        .distinct()
        .order_by(User.full_name)
    )
    if base_where:
        actions_stmt = actions_stmt.where(*base_where)
        object_types_stmt = object_types_stmt.where(*base_where)
        actors_stmt = actors_stmt.where(*base_where)

    actions = [row for row in db.scalars(actions_stmt).all() if row]
    object_types = [row for row in db.scalars(object_types_stmt).all() if row]
    actors_list = [
        {"username": u_name, "full_name": f_name or u_name}
        for u_name, f_name in db.execute(actors_stmt).all()
        if u_name
    ]

    filters = list(base_where)

    # Date range filters
    if date_from and date_from.strip():
        try:
            df = datetime.fromisoformat(date_from.strip()).replace(hour=0, minute=0, second=0, microsecond=0)
            filters.append(AuditLog.occurred_at >= df)
        except (ValueError, TypeError):
            pass

    if date_to and date_to.strip():
        try:
            dt = datetime.fromisoformat(date_to.strip()).replace(hour=23, minute=59, second=59, microsecond=999999)
            filters.append(AuditLog.occurred_at <= dt)
        except (ValueError, TypeError):
            pass

    # Module filter (auth, access, master, scoring)
    if module and module.strip():
        mod_prefix = f"{module.strip().lower()}."
        filters.append(AuditLog.action.startswith(mod_prefix))

    # Action filter
    if action and action.strip():
        filters.append(AuditLog.action == action.strip())

    # Object type filter
    if object_type and object_type.strip():
        filters.append(AuditLog.object_type == object_type.strip())

    # Actor filter
    if actor_filter and actor_filter.strip():
        act_kw = actor_filter.strip()
        filters.append(
            or_(
                User.username == act_kw,
                User.full_name.ilike(f"%{act_kw}%"),
                AuditLog.actor_role_name.ilike(f"%{act_kw}%"),
            )
        )

    # Fulltext search
    if search and search.strip():
        kw = f"%{search.strip()}%"
        filters.append(
            or_(
                AuditLog.action.ilike(kw),
                AuditLog.actor_role_name.ilike(kw),
                AuditLog.object_type.ilike(kw),
                AuditLog.object_id.ilike(kw),
                AuditLog.reason.ilike(kw),
                User.username.ilike(kw),
                User.full_name.ilike(kw),
                Branch.code.ilike(kw),
                Branch.name.ilike(kw),
            )
        )

    filtered_stmt = (
        select(func.count(AuditLog.id))
        .outerjoin(User, User.id == AuditLog.actor_user_id)
        .outerjoin(Branch, Branch.id == AuditLog.actor_branch_id)
    )
    if filters:
        filtered_stmt = filtered_stmt.where(*filters)
    filtered = db.scalar(filtered_stmt) or 0

    query = (
        select(AuditLog, User.username, User.full_name, Branch.code, Branch.name)
        .outerjoin(User, User.id == AuditLog.actor_user_id)
        .outerjoin(Branch, Branch.id == AuditLog.actor_branch_id)
    )
    if filters:
        query = query.where(*filters)

    sort_dir_lower = (sort_dir or "desc").lower()
    col_map = {
        "id": AuditLog.id,
        "occurred_at": AuditLog.occurred_at,
        "action": AuditLog.action,
        "actor_role_name": AuditLog.actor_role_name,
        "actor_username": User.username,
        "object_type": AuditLog.object_type,
        "object_id": AuditLog.object_id,
        "reason": AuditLog.reason,
    }
    col = col_map.get(sort_by, AuditLog.id)
    query = query.order_by(col.desc().nulls_last() if sort_dir_lower == "desc" else col.asc().nulls_last())

    safe_page = max(1, page)
    safe_per_page = max(1, min(per_page, 500))
    offset = (safe_page - 1) * safe_per_page
    rows = db.execute(query.offset(offset).limit(safe_per_page)).all()

    items = [
        {
            "id": log.id,
            "occurred_at": log.occurred_at.isoformat() if log.occurred_at else None,
            "actor_user_id": log.actor_user_id,
            "actor_username": username or f"User #{log.actor_user_id}",
            "actor_full_name": full_name or "",
            "actor_role_name": log.actor_role_name,
            "actor_branch_id": log.actor_branch_id,
            "actor_branch_code": branch_code or "",
            "actor_branch_name": branch_name or "",
            "action": log.action,
            "object_type": log.object_type,
            "object_id": log.object_id,
            "reason": log.reason,
            "before": log.before_data,
            "after": log.after_data,
        }
        for log, username, full_name, branch_code, branch_name in rows
    ]

    modules = [
        {"code": "auth", "label": "Autentikasi & Akun"},
        {"code": "access", "label": "Hak Akses & Role"},
        {"code": "master", "label": "Master Data"},
        {"code": "scoring", "label": "Scoring & Transaksi"},
    ]

    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "items": items,
            "total": total,
            "filtered": filtered,
            "page": safe_page,
            "per_page": safe_per_page,
            "stats": {
                "total": total,
                "today": today_cnt,
                "auth": auth_cnt,
                "data_changes": changes_cnt,
            },
            "actions": actions,
            "object_types": object_types,
            "actors": actors_list,
            "modules": modules,
        },
    }


@router.get("/{audit_id}")
def get_audit_detail(
    audit_id: int,
    actor: User = Depends(require_permissions("audit.view")),
    db: Session = Depends(get_db),
) -> dict:
    statement = (
        select(AuditLog, User.username, User.full_name, Branch.code, Branch.name)
        .outerjoin(User, User.id == AuditLog.actor_user_id)
        .outerjoin(Branch, Branch.id == AuditLog.actor_branch_id)
        .where(AuditLog.id == audit_id)
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(AuditLog.actor_branch_id == scope)
    row = db.execute(statement).first()
    if not row:
        raise ApiError(404, "01", "Catatan audit tidak ditemukan")

    log, username, full_name, branch_code, branch_name = row
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "id": log.id,
            "occurred_at": log.occurred_at.isoformat() if log.occurred_at else None,
            "actor_user_id": log.actor_user_id,
            "actor_username": username or f"User #{log.actor_user_id}",
            "actor_full_name": full_name or "",
            "actor_role_name": log.actor_role_name,
            "actor_branch_id": log.actor_branch_id,
            "actor_branch_code": branch_code or "",
            "actor_branch_name": branch_name or "",
            "action": log.action,
            "object_type": log.object_type,
            "object_id": log.object_id,
            "reason": log.reason,
            "before": log.before_data,
            "after": log.after_data,
        },
    }
