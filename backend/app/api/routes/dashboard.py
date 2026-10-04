import re
from datetime import UTC, datetime

from fastapi import APIRouter, Depends, Query
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import Debtor, Product, ScoringTransaction, User
from app.services.authorization import has_permission, visible_branch_id

router = APIRouter(prefix="/api/v1/dashboard", tags=["dashboard"])

STATUS_LABELS = {
    "draft": "Draft",
    "submitted": "Menunggu keputusan",
    "waiting_for_approver_assignment": "Menunggu penugasan",
    "approved": "Disetujui",
    "returned": "Dikembalikan",
    "rejected": "Ditolak",
}
IN_PROGRESS = ("draft", "submitted", "waiting_for_approver_assignment", "returned")
MONTHS = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"]


def _shift_month(year: int, month: int, delta: int) -> tuple[int, int]:
    index = year * 12 + (month - 1) + delta
    return index // 12, index % 12 + 1


def _month_bounds(year: int, month: int) -> tuple[datetime, datetime]:
    start = datetime(year, month, 1, tzinfo=UTC)
    next_year, next_month = _shift_month(year, month, 1)
    return start, datetime(next_year, next_month, 1, tzinfo=UTC)


def _percent(current: int, previous: int) -> int | None:
    if previous <= 0:
        return None
    return round((current - previous) * 100 / previous)


def _scoped(query, scope: int | None, start: datetime | None = None, end: datetime | None = None):
    if scope is not None:
        query = query.where(ScoringTransaction.branch_id == scope)
    if start is not None and end is not None:
        query = query.where(ScoringTransaction.created_at >= start, ScoringTransaction.created_at < end)
    return query


def _status_counts(db: Session, scope: int | None, start: datetime | None = None, end: datetime | None = None) -> dict[str, int]:
    query = _scoped(
        select(ScoringTransaction.status, func.count(ScoringTransaction.id)).group_by(ScoringTransaction.status),
        scope,
        start,
        end,
    )
    counts = {status: 0 for status in STATUS_LABELS}
    for status, total in db.execute(query).all():
        counts[status] = int(total)
    return counts


def _summary(counts: dict[str, int]) -> dict[str, int]:
    return {
        "total": sum(counts.values()),
        "in_progress": sum(counts[status] for status in IN_PROGRESS),
        "approved": counts["approved"],
        "rejected": counts["rejected"],
    }


@router.get("")
def dashboard(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
    period: str | None = Query(default=None),
) -> dict:
    scope = visible_branch_id(db, actor)
    start = end = None
    if period:
        if re.fullmatch(r"\d{4}-(0[1-9]|1[0-2])", period) is None:
            raise ApiError(422, "03", "Periode tidak dikenali")
        year, month = (int(part) for part in period.split("-"))
        start, end = _month_bounds(year, month)
    counts = _status_counts(db, scope, start, end)
    now = datetime.now(UTC)
    current_start, current_end = _month_bounds(now.year, now.month)
    previous_year, previous_month = _shift_month(now.year, now.month, -1)
    previous_start, previous_end = _month_bounds(previous_year, previous_month)
    if start is not None:
        compare_year, compare_month = _shift_month(start.year, start.month, -1)
        current_summary = _summary(counts)
        previous_summary = _summary(_status_counts(db, scope, *_month_bounds(compare_year, compare_month)))
    else:
        current_summary = _summary(_status_counts(db, scope, current_start, current_end))
        previous_summary = _summary(_status_counts(db, scope, previous_start, previous_end))
    visible_summary = _summary(counts)
    recent_query = _scoped(
        select(ScoringTransaction, Debtor, Product)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .order_by(ScoringTransaction.id.desc())
        .limit(8),
        scope,
        start,
        end,
    )
    recent = [
        {
            "id": transaction.id,
            "transaction_no": transaction.transaction_no,
            "status": transaction.status,
            "status_label": STATUS_LABELS.get(transaction.status, transaction.status),
            "debtor_name": debtor.full_name,
            "product_name": product.name,
            "created_at": transaction.created_at.isoformat(),
        }
        for transaction, debtor, product in db.execute(recent_query).all()
    ]
    trend_start, _ = _month_bounds(*_shift_month(now.year, now.month, -5))
    month_bucket = func.date_trunc("month", ScoringTransaction.created_at).label("month_bucket")
    trend_rows = db.execute(
        _scoped(
            select(month_bucket, ScoringTransaction.status, func.count(ScoringTransaction.id)).group_by(
                month_bucket, ScoringTransaction.status
            ),
            scope,
            trend_start,
            current_end,
        )
    ).all()
    trend_map: dict[str, dict[str, int]] = {}
    for moment, status, total in trend_rows:
        key = moment.strftime("%Y-%m")
        bucket = trend_map.setdefault(key, {"applications": 0, "approved": 0})
        bucket["applications"] += int(total)
        if status == "approved":
            bucket["approved"] += int(total)
    trend = []
    year, month = _shift_month(now.year, now.month, -5)
    for _ in range(6):
        key = f"{year:04d}-{month:02d}"
        bucket = trend_map.get(key, {"applications": 0, "approved": 0})
        trend.append({"month": key, "label": MONTHS[month - 1], **bucket})
        year, month = _shift_month(year, month, 1)
    product_rows = db.execute(
        _scoped(
            select(Product.name, func.count(ScoringTransaction.id))
            .join(Product, Product.id == ScoringTransaction.product_id)
            .group_by(Product.name)
            .order_by(func.count(ScoringTransaction.id).desc(), Product.name),
            scope,
            start,
            end,
        )
    ).all()
    product_total = sum(int(total) for _, total in product_rows) or 1
    products = [
        {"name": name, "count": int(total), "share": round(int(total) * 100 / product_total)}
        for name, total in product_rows[:5]
    ]
    inbox = 0
    if has_permission(db, actor, "scoring.approve"):
        inbox = int(
            db.scalar(
                select(func.count(ScoringTransaction.id)).where(
                    ScoringTransaction.status == "submitted",
                    ScoringTransaction.assigned_approver_id == actor.id,
                )
            )
            or 0
        )
    unassigned = 0
    if has_permission(db, actor, "scoring.assign"):
        unassigned_query = select(func.count(ScoringTransaction.id)).where(
            ScoringTransaction.status == "waiting_for_approver_assignment"
        )
        if scope is not None:
            unassigned_query = unassigned_query.where(ScoringTransaction.branch_id == scope)
        unassigned = int(db.scalar(unassigned_query) or 0)
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "branch_scope": "all" if scope is None else "own",
            "period": period or "",
            "statuses": [{"code": code, "label": label, "count": counts[code]} for code, label in STATUS_LABELS.items()],
            "summary": visible_summary,
            "changes": {key: _percent(current_summary[key], previous_summary[key]) for key in visible_summary},
            "trend": trend,
            "products": products,
            "inbox_count": inbox,
            "unassigned_count": unassigned,
            "recent": recent,
        },
    }
