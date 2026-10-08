from fastapi import APIRouter, Depends
from pydantic import BaseModel, ConfigDict, Field
from sqlalchemy import select
from sqlalchemy.exc import DBAPIError
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import ApproverAssignment, ApprovalDecision, CreditScoring, Debtor, Product, Role, ScoringTransaction, User
from app.services.audit import write_audit
from app.services.notifications import notify
from app.services.authorization import has_permission, role_has_permission, visible_branch_id

router = APIRouter(prefix="/api/v1/approvals", tags=["approvals"])
DECISIONS = {"approved", "rejected", "returned"}


class AssignBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    approver_id: int
    reason: str | None = None


class DecisionBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    decision: str
    note: str = Field(min_length=1, max_length=1000)


class RejectPermissionBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    note: str = Field(min_length=1, max_length=1000)


def _scoped(db: Session, actor: User, transaction: ScoringTransaction) -> None:
    scope = visible_branch_id(db, actor)
    if scope is not None and transaction.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")


def _item(transaction: ScoringTransaction, debtor: Debtor, product: Product) -> dict:
    return {
        "id": transaction.id,
        "transaction_no": transaction.transaction_no,
        "status": transaction.status,
        "debtor_name": debtor.full_name,
        "product_name": product.name,
        "assigned_approver_id": transaction.assigned_approver_id,
        "revision_no": transaction.current_revision,
    }


@router.get("/unassigned")
def unassigned(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.assign"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    statement = (
        select(ScoringTransaction, Debtor, Product)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .where(ScoringTransaction.status == "waiting_for_approver_assignment")
        .order_by(ScoringTransaction.id.desc())
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(ScoringTransaction.branch_id == scope)
    items = [_item(transaction, debtor, product) for transaction, debtor, product in db.execute(statement).all()]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.get("/inbox")
def inbox(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.approve"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    statement = (
        select(ScoringTransaction, Debtor, Product)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .where(ScoringTransaction.status == "submitted", ScoringTransaction.assigned_approver_id == actor.id)
        .order_by(ScoringTransaction.id.desc())
    )
    items = [_item(transaction, debtor, product) for transaction, debtor, product in db.execute(statement).all()]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.get("/candidates/{transaction_id}")
def candidates(transaction_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.assign"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    transaction = db.get(ScoringTransaction, transaction_id)
    if transaction is None:
        raise ApiError(404, "01", "Transaksi tidak ditemukan")
    _scoped(db, actor, transaction)
    users = db.scalars(select(User).where(User.branch_id == transaction.branch_id, User.is_active.is_(True)).order_by(User.full_name)).all()
    rows = []
    for user in users:
        if not role_has_permission(db, user.role_id, "scoring.approve"):
            continue
        role = db.get(Role, user.role_id)
        rows.append(
            {
                "id": user.id,
                "full_name": user.full_name,
                "username": user.username,
                "role_name": role.name if role else "Approver",
                "label": f"{user.full_name} — {role.name if role else 'Approver'}",
            }
        )
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": rows}}


@router.post("/{transaction_id}/assign")
def assign(transaction_id: int, body: AssignBody, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.assign"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    transaction = db.get(ScoringTransaction, transaction_id)
    if transaction is None:
        raise ApiError(404, "01", "Transaksi tidak ditemukan")
    if transaction.status not in {"waiting_for_approver_assignment", "submitted"}:
        raise ApiError(400, "01", "Penugasan hanya sebelum keputusan")
    if transaction.status == "submitted" and not (body.reason or "").strip():
        raise ApiError(400, "01", "Penugasan ulang wajib menyertakan alasan")
    approver = db.get(User, body.approver_id)
    if approver is None or not approver.is_active or not role_has_permission(db, approver.role_id, "scoring.approve"):
        raise ApiError(400, "01", "Approver tidak tersedia")
    if approver.branch_id != transaction.branch_id:
        raise ApiError(403, "04", "Approver harus berasal dari cabang yang sama")
    try:
        db.add(
            ApproverAssignment(
                transaction_id=transaction.id,
                revision_no=transaction.current_revision,
                approver_id=approver.id,
                reason=(body.reason or "").strip() or None,
                assigned_by=actor.id,
            )
        )
        transaction.assigned_approver_id = approver.id
        transaction.status = "submitted"
        write_audit(
            db,
            actor,
            "scoring.approver_assigned",
            object_type="scoring_transaction",
            object_id=str(transaction.id),
            reason=(body.reason or "").strip() or None,
            after_data={"approver_id": approver.id},
        )
        notify(
            db,
            approver.id,
            "sent_to_approver",
            f"{transaction.transaction_no} menunggu keputusan Anda.",
            transaction.id,
        )
        db.commit()
    except DBAPIError as exc:
        db.rollback()
        raise ApiError(400, "01", "Penugasan approver ditolak") from exc
    return {"rcode": "00", "message": "Approver berhasil ditugaskan", "result": {"id": transaction.id}}


@router.post("/{transaction_id}/decide")
def decide(transaction_id: int, body: DecisionBody, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.approve"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    if body.decision not in DECISIONS:
        raise ApiError(400, "01", "Keputusan tidak dikenali")
    transaction = db.get(ScoringTransaction, transaction_id)
    if transaction is None:
        raise ApiError(404, "01", "Transaksi tidak ditemukan")
    if transaction.status != "submitted" or transaction.assigned_approver_id != actor.id:
        raise ApiError(403, "04", "Transaksi ini bukan antrian Anda")
    note = body.note.strip()
    if not note:
        raise ApiError(400, "01", "Catatan persetujuan wajib diisi")
    try:
        db.add(
            ApprovalDecision(
                transaction_id=transaction.id,
                revision_no=transaction.current_revision,
                approver_id=actor.id,
                decision=body.decision,
                note=note,
            )
        )
        transaction.status = body.decision
        write_audit(
            db,
            actor,
            "scoring.decision_recorded",
            object_type="scoring_transaction",
            object_id=str(transaction.id),
            reason=note,
            after_data={"decision": body.decision},
        )
        notify(
            db,
            transaction.created_by,
            body.decision,
            f"{transaction.transaction_no}: {note}",
            transaction.id,
        )
        db.commit()
    except DBAPIError as exc:
        db.rollback()
        raise ApiError(400, "01", "Keputusan tidak dapat disimpan") from exc
    return {"rcode": "00", "message": "Keputusan berhasil disimpan", "result": {"status": transaction.status}}


@router.get("/duplicate-requests")
def list_duplicate_requests(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not (has_permission(db, actor, "scoring.approve") or has_permission(db, actor, "scoring.assign")):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    statement = (
        select(ScoringTransaction, Debtor, Product, User)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .outerjoin(User, User.id == ScoringTransaction.created_by)
        .where(ScoringTransaction.status == "waiting_duplicate_approval")
        .order_by(ScoringTransaction.id.desc())
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(ScoringTransaction.branch_id == scope)
    rows = db.execute(statement).all()
    items = []
    for tx, debtor, product, creator in rows:
        priors = db.scalars(
            select(CreditScoring)
            .where(
                CreditScoring.debtor_id == debtor.id,
                CreditScoring.product_id == product.id,
                CreditScoring.scoring_no != tx.transaction_no,
            )
            .order_by(CreditScoring.id.desc())
        ).all()
        prior_score = priors[0] if priors else None
        formatted_score = str(int(round(float(prior_score.total_score)))) if (prior_score and prior_score.total_score is not None) else "-"
        items.append({
            "id": tx.id,
            "transaction_no": tx.transaction_no,
            "debtor_name": debtor.full_name,
            "nik": debtor.nik,
            "product_name": product.name,
            "duplicate_reason": tx.duplicate_reason or "-",
            "creator_name": creator.full_name if creator else "-",
            "created_at": tx.created_at.strftime("%d-%m-%Y %H:%M") if tx.created_at else "-",
            "prior_count": len(priors),
            "prior_score": formatted_score,
            "prior_eligibility": prior_score.eligibility_status if prior_score else "-",
            "prior_date": prior_score.created_at.strftime("%d-%m-%Y") if prior_score and prior_score.created_at else "-",
        })
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("/{transaction_id}/duplicate-approve")
def approve_duplicate_permission(
    transaction_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not has_permission(db, actor, "scoring.approve"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    tx = db.get(ScoringTransaction, transaction_id)
    if tx is None or tx.status != "waiting_duplicate_approval":
        raise ApiError(400, "01", "Transaksi tidak menunggu persetujuan duplikasi")
    _scoped(db, actor, tx)
    if tx.created_by == actor.id:
        raise ApiError(403, "04", "Izin tidak dapat disetujui oleh pemohon sendiri")

    new_status = "submitted" if tx.assigned_approver_id else "waiting_for_approver_assignment"
    tx.status = new_status

    cs = db.scalar(select(CreditScoring).where(CreditScoring.scoring_no == tx.transaction_no))
    if cs:
        cs.status = "submitted_to_supervisor" if tx.assigned_approver_id else "waiting_for_assignment"

    write_audit(
        db,
        actor,
        "scoring.duplicate_permission_approved",
        object_type="scoring_transaction",
        object_id=str(tx.id),
        after_data={"status": tx.status, "reason": tx.duplicate_reason},
    )
    notify(
        db,
        tx.created_by,
        "duplicate_approved",
        f"Izin pengajuan ulang {tx.transaction_no} telah disetujui oleh {actor.full_name}.",
        tx.id,
    )
    db.commit()
    return {"rcode": "00", "message": "Izin pengajuan ulang berhasil disetujui. Transaksi masuk ke antrean Persetujuan Scoring."}


@router.post("/{transaction_id}/duplicate-reject")
def reject_duplicate_permission(
    transaction_id: int,
    body: RejectPermissionBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not has_permission(db, actor, "scoring.approve"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    tx = db.get(ScoringTransaction, transaction_id)
    if tx is None or tx.status != "waiting_duplicate_approval":
        raise ApiError(400, "01", "Transaksi tidak menunggu persetujuan duplikasi")
    _scoped(db, actor, tx)
    if tx.created_by == actor.id:
        raise ApiError(403, "04", "Izin tidak dapat ditolak oleh pemohon sendiri")

    tx.status = "rejected"

    cs = db.scalar(select(CreditScoring).where(CreditScoring.scoring_no == tx.transaction_no))
    if cs:
        cs.status = "rejected"

    write_audit(
        db,
        actor,
        "scoring.duplicate_permission_rejected",
        object_type="scoring_transaction",
        object_id=str(tx.id),
        reason=body.note.strip(),
        after_data={"status": tx.status, "rejection_note": body.note.strip()},
    )
    notify(
        db,
        tx.created_by,
        "duplicate_rejected",
        f"Izin pengajuan ulang {tx.transaction_no} ditolak oleh {actor.full_name}: {body.note.strip()}.",
        tx.id,
    )
    db.commit()
    return {"rcode": "00", "message": "Izin pengajuan ulang ditolak. Transaksi masuk ke kategori Batal."}

