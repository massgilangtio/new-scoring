from fastapi import APIRouter, Depends
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
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
from app.services.authorization import visible_branch_id

router = APIRouter(prefix="/api/v1/reports", tags=["reports"])


def _scope(db: Session, actor: User):
    return visible_branch_id(db, actor)


@router.get("/scoring")
def scoring_report(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    latest = (
        select(ScoringSnapshot.transaction_id, func.max(ScoringSnapshot.revision_no).label("revision_no"))
        .group_by(ScoringSnapshot.transaction_id)
        .subquery()
    )
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
                "total_score": None if snapshot is None else format(snapshot.total_score, "f"),
                "result_label": None if snapshot is None else snapshot.result_label,
            }
        )
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.get("/debtors/{debtor_id}")
def debtor_history(debtor_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
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
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "debtor": {"id": debtor.id, "nik": debtor.nik, "full_name": debtor.full_name},
            "items": [
                {
                    "transaction_no": transaction.transaction_no,
                    "status": transaction.status,
                    "product_name": product.name,
                    "total_score": None if snapshot is None else format(snapshot.total_score, "f"),
                    "result_label": None if snapshot is None else snapshot.result_label,
                }
                for transaction, product, snapshot in rows
            ],
        },
    }


@router.get("/products/{product_id}")
def product_history(product_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
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
def parameter_changes(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
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
