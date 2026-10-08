from fastapi import APIRouter, Depends
from sqlalchemy import func, or_, select
from sqlalchemy.orm import Session

from app.api.deps import get_db, require_permissions
from app.api.errors import ApiError
from app.models.tables import (
    AuditLog,
    Branch,
    Debtor,
    Product,
    ScoringSnapshot,
    ScoringTransaction,
    ScoringVersion,
    User,
)
from app.services.authorization import can_view_score_details, visible_branch_id

router = APIRouter(prefix="/api/v1/reports", tags=["reports"])


def _scope(db: Session, actor: User):
    return visible_branch_id(db, actor)


def _score_fields(snapshot, *, allowed: bool) -> dict:
    if not allowed:
        return {"total_score": None, "result_label": None}
    return {
        "total_score": None if snapshot is None else format(snapshot.total_score, "f"),
        "result_label": None if snapshot is None else snapshot.result_label,
    }


def _latest_snapshot_subquery():
    return (
        select(ScoringSnapshot.transaction_id, func.max(ScoringSnapshot.revision_no).label("revision_no"))
        .group_by(ScoringSnapshot.transaction_id)
        .subquery()
    )


def _paginate(page: int, per_page: int) -> tuple[int, int, int]:
    safe_page = max(1, page)
    safe_per_page = max(1, min(per_page, 500))
    offset = (safe_page - 1) * safe_per_page
    return safe_page, safe_per_page, offset


@router.get("/scoring")
def scoring_report(actor: User = Depends(require_permissions("report.scoring")), db: Session = Depends(get_db)) -> dict:
    latest = _latest_snapshot_subquery()
    statement = (
        select(ScoringTransaction, Debtor, Product, Branch, ScoringSnapshot, ScoringVersion)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .join(Branch, Branch.id == ScoringTransaction.branch_id)
        .join(ScoringVersion, ScoringVersion.id == ScoringTransaction.scoring_version_id)
        .outerjoin(latest, latest.c.transaction_id == ScoringTransaction.id)
        .outerjoin(
            ScoringSnapshot,
            (ScoringSnapshot.transaction_id == ScoringTransaction.id)
            & (ScoringSnapshot.revision_no == latest.c.revision_no),
        )
        .order_by(ScoringTransaction.id.desc())
    )
    scope = _scope(db, actor)
    if scope is not None:
        statement = statement.where(ScoringTransaction.branch_id == scope)
    can_view = can_view_score_details(db, actor)
    items = []
    for transaction, debtor, product, branch, snapshot, version in db.execute(statement).all():
        items.append(
            {
                "transaction_no": transaction.transaction_no,
                "status": transaction.status,
                "debtor_name": debtor.full_name,
                "nik": debtor.nik,
                "product_name": product.name,
                "branch_name": branch.name,
                "version_no": version.version_no,
                **_score_fields(snapshot, allowed=can_view),
            }
        )
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {"items": items, "can_view_score_details": can_view},
    }


@router.get("/scoring/datatables")
def scoring_report_datatables(
    page: int = 1,
    per_page: int = 10,
    search: str | None = None,
    status: str | None = None,
    sort_by: str = "id",
    sort_dir: str = "desc",
    actor: User = Depends(require_permissions("report.scoring")),
    db: Session = Depends(get_db),
) -> dict:
    scope = _scope(db, actor)
    latest = _latest_snapshot_subquery()

    def _scoped_count(extra_where=None):
        stmt = select(func.count(ScoringTransaction.id))
        if scope is not None:
            stmt = stmt.where(ScoringTransaction.branch_id == scope)
        if extra_where is not None:
            stmt = stmt.where(extra_where)
        return db.scalar(stmt) or 0

    total = _scoped_count()
    stats = {
        "total": total,
        "approved": _scoped_count(ScoringTransaction.status == "approved"),
        "rejected": _scoped_count(ScoringTransaction.status == "rejected"),
        "waiting_approval": _scoped_count(ScoringTransaction.status == "waiting_for_approver_assignment"),
        "submitted": _scoped_count(ScoringTransaction.status == "submitted"),
    }

    status_stmt = select(ScoringTransaction.status).distinct().order_by(ScoringTransaction.status)
    if scope is not None:
        status_stmt = status_stmt.where(ScoringTransaction.branch_id == scope)
    statuses = [row for row in db.scalars(status_stmt).all() if row]

    filters = []
    if scope is not None:
        filters.append(ScoringTransaction.branch_id == scope)
    if search and search.strip():
        kw = f"%{search.strip()}%"
        filters.append(
            or_(
                ScoringTransaction.transaction_no.ilike(kw),
                Debtor.full_name.ilike(kw),
                Debtor.nik.ilike(kw),
                Product.name.ilike(kw),
                Branch.name.ilike(kw),
                ScoringSnapshot.result_label.ilike(kw),
            )
        )
    if status and status.strip():
        filters.append(ScoringTransaction.status == status.strip())

    filtered_stmt = (
        select(func.count(func.distinct(ScoringTransaction.id)))
        .select_from(ScoringTransaction)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .join(Branch, Branch.id == ScoringTransaction.branch_id)
        .join(ScoringVersion, ScoringVersion.id == ScoringTransaction.scoring_version_id)
        .outerjoin(latest, latest.c.transaction_id == ScoringTransaction.id)
        .outerjoin(
            ScoringSnapshot,
            (ScoringSnapshot.transaction_id == ScoringTransaction.id)
            & (ScoringSnapshot.revision_no == latest.c.revision_no),
        )
    )
    if filters:
        filtered_stmt = filtered_stmt.where(*filters)
    filtered = db.scalar(filtered_stmt) or 0

    query = (
        select(ScoringTransaction, Debtor, Product, Branch, ScoringSnapshot, ScoringVersion)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .join(Branch, Branch.id == ScoringTransaction.branch_id)
        .join(ScoringVersion, ScoringVersion.id == ScoringTransaction.scoring_version_id)
        .outerjoin(latest, latest.c.transaction_id == ScoringTransaction.id)
        .outerjoin(
            ScoringSnapshot,
            (ScoringSnapshot.transaction_id == ScoringTransaction.id)
            & (ScoringSnapshot.revision_no == latest.c.revision_no),
        )
    )
    if filters:
        query = query.where(*filters)

    sort_dir_lower = (sort_dir or "desc").lower()
    col_map = {
        "id": ScoringTransaction.id,
        "transaction_no": ScoringTransaction.transaction_no,
        "debtor_name": Debtor.full_name,
        "nik": Debtor.nik,
        "product_name": Product.name,
        "branch_name": Branch.name,
        "version_no": ScoringVersion.version_no,
        "total_score": ScoringSnapshot.total_score,
        "result_label": ScoringSnapshot.result_label,
        "status": ScoringTransaction.status,
    }
    col = col_map.get(sort_by, ScoringTransaction.id)
    query = query.order_by(col.desc().nulls_last() if sort_dir_lower == "desc" else col.asc().nulls_last())

    safe_page, safe_per_page, offset = _paginate(page, per_page)
    rows = db.execute(query.offset(offset).limit(safe_per_page)).all()
    can_view = can_view_score_details(db, actor)

    items = [
        {
            "id": transaction.id,
            "transaction_no": transaction.transaction_no,
            "status": transaction.status,
            "debtor_name": debtor.full_name,
            "nik": debtor.nik,
            "product_name": product.name,
            "branch_name": branch.name,
            "version_no": version.version_no,
            **_score_fields(snapshot, allowed=can_view),
        }
        for transaction, debtor, product, branch, snapshot, version in rows
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
            "stats": stats,
            "statuses": statuses,
            "can_view_score_details": can_view,
        },
    }


@router.get("/debtors/{debtor_id}")
def debtor_history(debtor_id: int, actor: User = Depends(require_permissions("report.debtors")), db: Session = Depends(get_db)) -> dict:
    debtor = db.get(Debtor, debtor_id)
    if debtor is None:
        raise ApiError(404, "01", "Debitur tidak ditemukan")
    scope = _scope(db, actor)
    if scope is not None and debtor.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")
    rows = db.execute(
        select(ScoringTransaction, Product, ScoringSnapshot)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .outerjoin(ScoringSnapshot, ScoringSnapshot.transaction_id == ScoringTransaction.id)
        .where(ScoringTransaction.debtor_id == debtor.id)
        .order_by(ScoringTransaction.id.desc(), ScoringSnapshot.revision_no.desc())
    ).all()
    can_view = can_view_score_details(db, actor)
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "debtor": {"id": debtor.id, "nik": debtor.nik, "full_name": debtor.full_name},
            "can_view_score_details": can_view,
            "items": [
                {
                    "transaction_no": transaction.transaction_no,
                    "status": transaction.status,
                    "product_name": product.name,
                    **_score_fields(snapshot, allowed=can_view),
                }
                for transaction, product, snapshot in rows
            ],
        },
    }


@router.get("/debtors/{debtor_id}/datatables")
def debtor_history_datatables(
    debtor_id: int,
    page: int = 1,
    per_page: int = 10,
    search: str | None = None,
    status: str | None = None,
    sort_by: str = "id",
    sort_dir: str = "desc",
    actor: User = Depends(require_permissions("report.debtors")),
    db: Session = Depends(get_db),
) -> dict:
    debtor = db.get(Debtor, debtor_id)
    if debtor is None:
        raise ApiError(404, "01", "Debitur tidak ditemukan")
    scope = _scope(db, actor)
    if scope is not None and debtor.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")

    latest = _latest_snapshot_subquery()
    base_where = [ScoringTransaction.debtor_id == debtor.id]

    total = db.scalar(select(func.count(ScoringTransaction.id)).where(*base_where)) or 0
    stats_rows = db.execute(
        select(ScoringTransaction.status, func.count(ScoringTransaction.id))
        .where(*base_where)
        .group_by(ScoringTransaction.status)
    ).all()
    stats = {status_key: int(count) for status_key, count in stats_rows}
    stats["total"] = total

    filters = list(base_where)
    if search and search.strip():
        kw = f"%{search.strip()}%"
        filters.append(
            or_(
                ScoringTransaction.transaction_no.ilike(kw),
                Product.name.ilike(kw),
                ScoringTransaction.status.ilike(kw),
                ScoringSnapshot.result_label.ilike(kw),
            )
        )
    if status and status.strip():
        filters.append(ScoringTransaction.status == status.strip())

    filtered = db.scalar(
        select(func.count(func.distinct(ScoringTransaction.id)))
        .select_from(ScoringTransaction)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .outerjoin(latest, latest.c.transaction_id == ScoringTransaction.id)
        .outerjoin(
            ScoringSnapshot,
            (ScoringSnapshot.transaction_id == ScoringTransaction.id)
            & (ScoringSnapshot.revision_no == latest.c.revision_no),
        )
        .where(*filters)
    ) or 0

    query = (
        select(ScoringTransaction, Product, ScoringSnapshot)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .outerjoin(latest, latest.c.transaction_id == ScoringTransaction.id)
        .outerjoin(
            ScoringSnapshot,
            (ScoringSnapshot.transaction_id == ScoringTransaction.id)
            & (ScoringSnapshot.revision_no == latest.c.revision_no),
        )
        .where(*filters)
    )

    sort_dir_lower = (sort_dir or "desc").lower()
    col_map = {
        "id": ScoringTransaction.id,
        "transaction_no": ScoringTransaction.transaction_no,
        "status": ScoringTransaction.status,
        "product_name": Product.name,
        "total_score": ScoringSnapshot.total_score,
        "result_label": ScoringSnapshot.result_label,
    }
    col = col_map.get(sort_by, ScoringTransaction.id)
    query = query.order_by(col.desc().nulls_last() if sort_dir_lower == "desc" else col.asc().nulls_last())

    safe_page, safe_per_page, offset = _paginate(page, per_page)
    rows = db.execute(query.offset(offset).limit(safe_per_page)).all()
    can_view = can_view_score_details(db, actor)

    items = [
        {
            "id": transaction.id,
            "transaction_no": transaction.transaction_no,
            "status": transaction.status,
            "product_name": product.name,
            **_score_fields(snapshot, allowed=can_view),
        }
        for transaction, product, snapshot in rows
    ]

    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "debtor": {"id": debtor.id, "nik": debtor.nik, "full_name": debtor.full_name},
            "items": items,
            "total": total,
            "filtered": filtered,
            "page": safe_page,
            "per_page": safe_per_page,
            "stats": stats,
            "can_view_score_details": can_view,
        },
    }


@router.get("/products/{product_id}")
def product_history(product_id: int, actor: User = Depends(require_permissions("report.products")), db: Session = Depends(get_db)) -> dict:
    product = db.get(Product, product_id)
    if product is None:
        raise ApiError(404, "01", "Produk tidak ditemukan")
    versions = db.scalars(
        select(ScoringVersion).where(ScoringVersion.product_id == product.id).order_by(ScoringVersion.version_no)
    ).all()
    statement = select(ScoringTransaction.status, func.count(ScoringTransaction.id)).where(
        ScoringTransaction.product_id == product.id
    ).group_by(ScoringTransaction.status)
    scope = _scope(db, actor)
    if scope is not None:
        statement = statement.where(ScoringTransaction.branch_id == scope)

    audit_statement = (
        select(AuditLog)
        .where(AuditLog.object_type == "product", AuditLog.object_id == str(product.id))
        .order_by(AuditLog.id.desc())
    )
    if scope is not None:
        audit_statement = audit_statement.where(AuditLog.actor_branch_id == scope)
    product_logs = db.scalars(audit_statement).all()

    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "product": {
                "id": product.id,
                "code": product.code,
                "name": product.name,
                "is_active": product.is_active,
                "business_unit": product.business_unit,
                "interest_rate": str(product.interest_rate) if product.interest_rate is not None else None,
                "product_type_id": product.product_type_id,
            },
            "versions": [
                {
                    "version_no": row.version_no,
                    "status": row.status,
                    "activated_at": row.activated_at.isoformat() if row.activated_at else None,
                }
                for row in versions
            ],
            "transactions": [{"status": status, "count": int(total)} for status, total in db.execute(statement).all()],
            "audit_logs": [
                {
                    "id": log.id,
                    "occurred_at": log.occurred_at.isoformat() if log.occurred_at else None,
                    "action": log.action,
                    "actor_role_name": log.actor_role_name,
                    "reason": log.reason,
                    "before_data": log.before_data,
                    "after_data": log.after_data,
                }
                for log in product_logs
            ],
        },
    }


@router.get("/parameter-changes")
def parameter_changes(actor: User = Depends(require_permissions("report.changes")), db: Session = Depends(get_db)) -> dict:
    statement = (
        select(AuditLog)
        .where(AuditLog.action.like("scoring.%"))
        .order_by(AuditLog.id.desc())
        .limit(100)
    )
    scope = _scope(db, actor)
    if scope is not None:
        statement = statement.where(AuditLog.actor_branch_id == scope)
    rows = db.scalars(statement).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "items": [
                {
                    "occurred_at": row.occurred_at.isoformat(),
                    "action": row.action,
                    "actor_role_name": row.actor_role_name,
                    "object_type": row.object_type,
                    "object_id": row.object_id,
                }
                for row in rows
            ]
        },
    }


@router.get("/parameter-changes/datatables")
def parameter_changes_datatables(
    page: int = 1,
    per_page: int = 10,
    search: str | None = None,
    action: str | None = None,
    object_type: str | None = None,
    sort_by: str = "id",
    sort_dir: str = "desc",
    actor: User = Depends(require_permissions("report.changes")),
    db: Session = Depends(get_db),
) -> dict:
    scope = _scope(db, actor)
    base_where = [AuditLog.action.like("scoring.%")]
    if scope is not None:
        base_where.append(AuditLog.actor_branch_id == scope)

    total = db.scalar(select(func.count(AuditLog.id)).where(*base_where)) or 0

    actions_stmt = select(AuditLog.action).where(*base_where).distinct().order_by(AuditLog.action)
    object_types_stmt = select(AuditLog.object_type).where(*base_where).distinct().order_by(AuditLog.object_type)
    actions = [row for row in db.scalars(actions_stmt).all() if row]
    object_types = [row for row in db.scalars(object_types_stmt).all() if row]

    query = select(AuditLog).where(*base_where)

    if search and search.strip():
        kw = f"%{search.strip()}%"
        query = query.where(
            or_(
                AuditLog.action.ilike(kw),
                AuditLog.actor_role_name.ilike(kw),
                AuditLog.object_type.ilike(kw),
                AuditLog.object_id.ilike(kw),
            )
        )

    if action and action.strip():
        query = query.where(AuditLog.action == action.strip())
    if object_type and object_type.strip():
        query = query.where(AuditLog.object_type == object_type.strip())

    filtered = db.scalar(select(func.count()).select_from(query.subquery())) or 0

    sort_dir_lower = (sort_dir or "desc").lower()
    col_map = {
        "id": AuditLog.id,
        "occurred_at": AuditLog.occurred_at,
        "action": AuditLog.action,
        "actor_role_name": AuditLog.actor_role_name,
        "object_type": AuditLog.object_type,
        "object_id": AuditLog.object_id,
    }
    col = col_map.get(sort_by, AuditLog.id)
    query = query.order_by(col.desc().nulls_last() if sort_dir_lower == "desc" else col.asc().nulls_last())

    safe_page, safe_per_page, offset = _paginate(page, per_page)
    rows = db.scalars(query.offset(offset).limit(safe_per_page)).all()

    items = [
        {
            "id": row.id,
            "occurred_at": row.occurred_at.isoformat() if row.occurred_at else None,
            "action": row.action,
            "actor_role_name": row.actor_role_name,
            "object_type": row.object_type,
            "object_id": row.object_id,
        }
        for row in rows
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
            "actions": actions,
            "object_types": object_types,
        },
    }
