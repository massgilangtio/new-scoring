from datetime import datetime, timedelta
from fastapi import APIRouter, Depends, Query
from sqlalchemy import func, or_, select
from sqlalchemy.orm import Session

from app.api.deps import get_db, require_permissions
from app.api.errors import ApiError
from app.models.tables import SystemLog, User

router = APIRouter(prefix="/api/v1/system-logs", tags=["system-logs"])


@router.get("/datatables")
def list_system_logs_datatables(
    page: int = 1,
    per_page: int = 10,
    search: str | None = None,
    date_from: str | None = None,
    date_to: str | None = None,
    level: str | None = None,
    method: str | None = None,
    status_code: int | None = None,
    has_bug: bool | None = None,
    sort_by: str = "id",
    sort_dir: str = "desc",
    actor: User = Depends(require_permissions("system.logs", "audit.view")),
    db: Session = Depends(get_db),
) -> dict:
    # 1. Global KPIs
    total = db.scalar(select(func.count(SystemLog.id))) or 0

    now = datetime.now()
    today_start = datetime(now.year, now.month, now.day)
    today_cnt = db.scalar(select(func.count(SystemLog.id)).where(SystemLog.created_at >= today_start)) or 0
    success_cnt = db.scalar(select(func.count(SystemLog.id)).where(SystemLog.level == "SUCCESS")) or 0
    client_err_cnt = db.scalar(select(func.count(SystemLog.id)).where(SystemLog.level == "WARNING")) or 0
    server_err_cnt = db.scalar(select(func.count(SystemLog.id)).where(SystemLog.level == "ERROR")) or 0

    # 2. Filtering
    filters = []

    if date_from and date_from.strip():
        try:
            df = datetime.fromisoformat(date_from.strip()).replace(hour=0, minute=0, second=0, microsecond=0)
            filters.append(SystemLog.created_at >= df)
        except (ValueError, TypeError):
            pass

    if date_to and date_to.strip():
        try:
            dt = datetime.fromisoformat(date_to.strip()).replace(hour=23, minute=59, second=59, microsecond=999999)
            filters.append(SystemLog.created_at <= dt)
        except (ValueError, TypeError):
            pass

    if level and level.strip():
        filters.append(SystemLog.level == level.strip().upper())

    if method and method.strip():
        filters.append(SystemLog.method == method.strip().upper())

    if status_code is not None and status_code > 0:
        filters.append(SystemLog.status_code == status_code)

    if has_bug is True:
        filters.append(or_(SystemLog.level == "ERROR", SystemLog.traceback.isnot(None)))

    if search and search.strip():
        kw = f"%{search.strip()}%"
        filters.append(
            or_(
                SystemLog.path.ilike(kw),
                SystemLog.method.ilike(kw),
                SystemLog.username.ilike(kw),
                SystemLog.client_ip.ilike(kw),
                SystemLog.error_message.ilike(kw),
                SystemLog.query_params.ilike(kw),
            )
        )

    # 3. Filtered Count
    filtered_stmt = select(func.count(SystemLog.id))
    if filters:
        filtered_stmt = filtered_stmt.where(*filters)
    filtered = db.scalar(filtered_stmt) or 0

    # 4. Sorting & Pagination
    query = select(SystemLog)
    if filters:
        query = query.where(*filters)

    col_map = {
        "id": SystemLog.id,
        "created_at": SystemLog.created_at,
        "level": SystemLog.level,
        "status_code": SystemLog.status_code,
        "method": SystemLog.method,
        "path": SystemLog.path,
        "execution_time_ms": SystemLog.execution_time_ms,
        "username": SystemLog.username,
        "client_ip": SystemLog.client_ip,
    }
    col = col_map.get(sort_by, SystemLog.id)
    if (sort_dir or "desc").lower() == "asc":
        query = query.order_by(col.asc().nulls_last())
    else:
        query = query.order_by(col.desc().nulls_last())

    safe_page = max(1, page)
    safe_per_page = max(1, min(per_page, 500))
    offset = (safe_page - 1) * safe_per_page
    rows = db.execute(query.offset(offset).limit(safe_per_page)).scalars().all()

    items = [
        {
            "id": r.id,
            "created_at": r.created_at.isoformat() if r.created_at else None,
            "level": r.level,
            "status_code": r.status_code,
            "method": r.method,
            "path": r.path,
            "query_params": r.query_params,
            "client_ip": r.client_ip,
            "user_id": r.user_id,
            "username": r.username,
            "execution_time_ms": float(r.execution_time_ms),
            "error_message": r.error_message,
            "has_traceback": bool(r.traceback),
            "has_request_body": bool(r.request_body),
            "has_response_body": bool(r.response_body),
        }
        for r in rows
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
                "success": success_cnt,
                "client_error": client_err_cnt,
                "server_error": server_err_cnt,
            },
        },
    }


@router.get("/{log_id}")
def get_system_log_detail(
    log_id: int,
    actor: User = Depends(require_permissions("system.logs", "audit.view")),
    db: Session = Depends(get_db),
) -> dict:
    row = db.get(SystemLog, log_id)
    if not row:
        raise ApiError(404, "01", "Catatan log tidak ditemukan")

    return {
        "rcode": "00",
        "message": "Detail log berhasil dimuat",
        "result": {
            "id": row.id,
            "created_at": row.created_at.isoformat() if row.created_at else None,
            "level": row.level,
            "status_code": row.status_code,
            "method": row.method,
            "path": row.path,
            "query_params": row.query_params,
            "client_ip": row.client_ip,
            "user_agent": row.user_agent,
            "user_id": row.user_id,
            "username": row.username,
            "execution_time_ms": float(row.execution_time_ms),
            "request_body": row.request_body,
            "response_body": row.response_body,
            "error_message": row.error_message,
            "traceback": row.traceback,
        },
    }


@router.post("/clear")
def clear_system_logs(
    days: int = Query(default=30, ge=0),
    actor: User = Depends(require_permissions("system.logs", "audit.view")),
    db: Session = Depends(get_db),
) -> dict:
    """Clear logs older than specified days (or 0 to clear all)."""
    if days == 0:
        db.query(SystemLog).delete()
    else:
        cutoff = datetime.now() - timedelta(days=days)
        db.query(SystemLog).filter(SystemLog.created_at < cutoff).delete()
    db.commit()

    return {
        "rcode": "00",
        "message": f"Log sistem yang lebih lama dari {days} hari berhasil dibersihkan",
        "result": {},
    }
