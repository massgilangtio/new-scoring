from decimal import Decimal
from datetime import UTC, datetime
import uuid

from fastapi import APIRouter, Depends
from pydantic import BaseModel, ConfigDict, Field
from sqlalchemy import delete, func, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import (
    Branch,
    Debtor,
    DynamicField,
    DynamicFieldInput,
    DynamicFieldInputChoice,
    DynamicFieldOption,
    DynamicFieldSnapshotLine,
    Product,
    RescoreRequest,
    ScoringInput,
    ScoringParameterOption,
    ScoringSnapshot,
    ScoringSnapshotLine,
    ScoringTransaction,
    ScoringVersion,
    User,
)
from app.services.audit import write_audit
from app.services.authorization import has_permission, visible_branch_id
from app.services.scoring_engine import ScoringError, calculate_version

router = APIRouter(prefix="/api/v1/transactions", tags=["transactions"])
EDITABLE = {"draft", "returned"}
CHOICE_FIELDS = {"dropdown", "radio", "checkbox"}


class CreateTransactionBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    product_id: int
    debtor_id: int


class AnswerBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    parameter_id: int
    option_id: int


class FieldAnswerBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    field_id: int
    value: str | None = None
    option_ids: list[int] = Field(default_factory=list)


class SaveAnswersBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    answers: list[AnswerBody] = Field(default_factory=list)
    fields: list[FieldAnswerBody] = Field(default_factory=list)


def _submitter(db: Session, user: User) -> None:
    if not has_permission(db, user, "scoring.submit"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")


def _visible(db: Session, actor: User, transaction: ScoringTransaction) -> None:
    scope = visible_branch_id(db, actor)
    if scope is not None and transaction.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")


def _editable(actor: User, transaction: ScoringTransaction) -> None:
    if transaction.created_by != actor.id or transaction.status not in EDITABLE:
        raise ApiError(400, "01", "Transaksi terkunci")


def _active_version(db: Session, product_id: int) -> ScoringVersion:
    version = db.scalar(
        select(ScoringVersion).where(ScoringVersion.product_id == product_id, ScoringVersion.status == "active")
    )
    if version is None:
        raise ApiError(400, "01", "Produk belum memiliki versi scoring aktif")
    return version


@router.get("/options")
def transaction_options(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    _submitter(db, actor)
    products = db.scalars(select(Product).where(Product.is_active.is_(True)).order_by(Product.name)).all()
    available = []
    for product in products:
        version = _lookup_version(db, product.id)
        if version is not None:
            available.append({"id": product.id, "code": product.code, "name": product.name, "version_no": version.version_no})
    debtors = db.scalars(
        select(Debtor).where(Debtor.branch_id == actor.branch_id, Debtor.is_active.is_(True)).order_by(Debtor.full_name)
    ).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "products": available,
            "debtors": [{"id": row.id, "nik": row.nik, "full_name": row.full_name} for row in debtors],
        },
    }


def _lookup_version(db: Session, product_id: int) -> ScoringVersion | None:
    return db.scalar(select(ScoringVersion).where(ScoringVersion.product_id == product_id, ScoringVersion.status == "active"))


@router.get("")
def list_transactions(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not (has_permission(db, actor, "scoring.submit") or has_permission(db, actor, "branch.view_all")):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    statement = (
        select(ScoringTransaction, Debtor, Product)
        .join(Debtor, Debtor.id == ScoringTransaction.debtor_id)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .order_by(ScoringTransaction.id.desc())
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(ScoringTransaction.branch_id == scope)
    items = []
    for transaction, debtor, product in db.execute(statement).all():
        items.append(
            {
                "id": transaction.id,
                "transaction_no": transaction.transaction_no,
                "status": transaction.status,
                "debtor_name": debtor.full_name,
                "product_name": product.name,
            }
        )
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("")
def create_transaction(
    body: CreateTransactionBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    _submitter(db, actor)
    product = db.get(Product, body.product_id)
    debtor = db.get(Debtor, body.debtor_id)
    if product is None or not product.is_active or debtor is None or not debtor.is_active:
        raise ApiError(400, "01", "Produk atau debitur tidak tersedia")
    if debtor.branch_id != actor.branch_id:
        raise ApiError(403, "04", "Debitur berada di luar cabang Anda")
    version = _active_version(db, product.id)
    prior = db.scalar(
        select(func.count(ScoringTransaction.id)).where(
            ScoringTransaction.debtor_id == debtor.id,
            ScoringTransaction.product_id == product.id,
            ScoringTransaction.status != "draft",
        )
    )
    request = db.scalar(
        select(RescoreRequest).where(
            RescoreRequest.debtor_id == debtor.id,
            RescoreRequest.product_id == product.id,
            RescoreRequest.status == "approved",
            RescoreRequest.consumed_transaction_id.is_(None),
        )
    )
    if prior and request is None:
        raise ApiError(400, "01", "NIK dan produk ini sudah memiliki scoring. Ajukan permintaan scoring ulang terlebih dahulu.")
    transaction = ScoringTransaction(
        transaction_no=f"TMP-{uuid.uuid4().hex}",
        debtor_id=debtor.id,
        product_id=product.id,
        scoring_version_id=version.id,
        branch_id=actor.branch_id,
        status="draft",
        created_by=actor.id,
    )
    db.add(transaction)
    db.flush()
    transaction.transaction_no = f"TX{transaction.id:08d}"
    if request is not None:
        request.status = "consumed"
        request.consumed_at = datetime.now(UTC)
        request.consumed_transaction_id = transaction.id
    write_audit(db, actor, "scoring.transaction_created", object_type="scoring_transaction", object_id=str(transaction.id))
    db.commit()
    return {
        "rcode": "00",
        "message": "Pengajuan berhasil dibuat",
        "result": {"id": transaction.id, "transaction_no": transaction.transaction_no, "existing_scoring": int(prior or 0)},
    }


def _transaction_or_404(db: Session, transaction_id: int) -> ScoringTransaction:
    transaction = db.get(ScoringTransaction, transaction_id)
    if transaction is None:
        raise ApiError(404, "01", "Transaksi tidak ditemukan")
    return transaction


@router.get("/{transaction_id}")
def get_transaction(transaction_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    transaction = _transaction_or_404(db, transaction_id)
    _visible(db, actor, transaction)
    debtor = db.get(Debtor, transaction.debtor_id)
    product = db.get(Product, transaction.product_id)
    version = db.get(ScoringVersion, transaction.scoring_version_id)
    from app.api.routes.scoring_config import _detail

    saved = {
        row.scoring_parameter_id: row.scoring_parameter_option_id
        for row in db.scalars(select(ScoringInput).where(ScoringInput.transaction_id == transaction.id)).all()
    }
    field_rows = db.scalars(select(DynamicFieldInput).where(DynamicFieldInput.transaction_id == transaction.id)).all()
    field_answers = []
    for row in field_rows:
        choices = db.scalars(
            select(DynamicFieldInputChoice.dynamic_field_option_id).where(
                DynamicFieldInputChoice.dynamic_field_input_id == row.id
            )
        ).all()
        field_answers.append({"field_id": row.dynamic_field_id, "value": row.value_text, "option_ids": list(choices)})
    snapshot = db.scalar(
        select(ScoringSnapshot)
        .where(ScoringSnapshot.transaction_id == transaction.id)
        .order_by(ScoringSnapshot.revision_no.desc())
        .limit(1)
    )
    snapshot_body = None
    if snapshot is not None:
        lines = db.scalars(select(ScoringSnapshotLine).where(ScoringSnapshotLine.snapshot_id == snapshot.id)).all()
        snapshot_body = {
            "revision_no": snapshot.revision_no,
            "total_score": format(snapshot.total_score, "f"),
            "result_label": snapshot.result_label,
            "lines": [
                {
                    "parameter_name": line.parameter_name,
                    "option_label": line.option_label,
                    "line_score": format(line.line_score, "f"),
                }
                for line in lines
            ],
        }
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "id": transaction.id,
            "transaction_no": transaction.transaction_no,
            "status": transaction.status,
            "editable": transaction.status in EDITABLE and transaction.created_by == actor.id,
            "duplicated_from_id": transaction.duplicated_from_id,
            "debtor": {"id": debtor.id, "nik": debtor.nik, "full_name": debtor.full_name} if debtor else None,
            "product": {"id": product.id, "name": product.name} if product else None,
            "version": _detail(db, version) if version else None,
            "answers": [{"parameter_id": key, "option_id": value} for key, value in saved.items()],
            "field_answers": field_answers,
            "snapshot": snapshot_body,
        },
    }


@router.put("/{transaction_id}/answers")
def save_answers(
    transaction_id: int,
    body: SaveAnswersBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    _submitter(db, actor)
    transaction = _transaction_or_404(db, transaction_id)
    _editable(actor, transaction)
    db.execute(delete(ScoringInput).where(ScoringInput.transaction_id == transaction.id))
    db.execute(delete(DynamicFieldInput).where(DynamicFieldInput.transaction_id == transaction.id))
    db.flush()
    for answer in body.answers:
        option = db.get(ScoringParameterOption, answer.option_id)
        if option is None or option.scoring_parameter_id != answer.parameter_id:
            raise ApiError(400, "01", "Nilai tidak termasuk pada parameter tersebut")
        db.add(
            ScoringInput(
                transaction_id=transaction.id,
                scoring_parameter_id=answer.parameter_id,
                scoring_parameter_option_id=answer.option_id,
            )
        )
    for field_answer in body.fields:
        field = db.get(DynamicField, field_answer.field_id)
        if field is None or field.scoring_version_id != transaction.scoring_version_id:
            raise ApiError(400, "01", "Dynamic field tidak termasuk versi transaksi")
        stored = DynamicFieldInput(
            transaction_id=transaction.id,
            dynamic_field_id=field.id,
            value_text=(field_answer.value or "").strip() or None,
        )
        db.add(stored)
        db.flush()
        if field.field_type in CHOICE_FIELDS:
            for option_id in field_answer.option_ids:
                option = db.get(DynamicFieldOption, option_id)
                if option is None or option.dynamic_field_id != field.id:
                    raise ApiError(400, "01", "Opsi field tidak sesuai")
                db.add(DynamicFieldInputChoice(dynamic_field_input_id=stored.id, dynamic_field_option_id=option.id))
            if field.field_type != "checkbox" and len(field_answer.option_ids) > 1:
                raise ApiError(400, "01", "Field ini hanya boleh satu pilihan")
    db.commit()
    return {"rcode": "00", "message": "Jawaban berhasil disimpan", "result": {}}


def _required_fields_complete(db: Session, transaction: ScoringTransaction) -> None:
    fields = db.scalars(
        select(DynamicField).where(
            DynamicField.scoring_version_id == transaction.scoring_version_id,
            DynamicField.is_active.is_(True),
            DynamicField.is_required.is_(True),
        )
    ).all()
    for field in fields:
        stored = db.scalar(
            select(DynamicFieldInput).where(
                DynamicFieldInput.transaction_id == transaction.id,
                DynamicFieldInput.dynamic_field_id == field.id,
            )
        )
        if stored is None:
            raise ApiError(400, "01", "Semua field wajib harus diisi")
        if field.field_type in CHOICE_FIELDS:
            count = db.scalar(
                select(func.count()).select_from(DynamicFieldInputChoice).where(
                    DynamicFieldInputChoice.dynamic_field_input_id == stored.id
                )
            )
            if not count:
                raise ApiError(400, "01", "Semua field wajib harus diisi")
        elif not (stored.value_text or "").strip():
            raise ApiError(400, "01", "Semua field wajib harus diisi")


@router.post("/{transaction_id}/submit")
def submit_transaction(transaction_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    _submitter(db, actor)
    transaction = _transaction_or_404(db, transaction_id)
    _editable(actor, transaction)
    _required_fields_complete(db, transaction)
    inputs = db.scalars(select(ScoringInput).where(ScoringInput.transaction_id == transaction.id)).all()
    version = db.get(ScoringVersion, transaction.scoring_version_id)
    try:
        result = calculate_version(
            db,
            version,
            [(row.scoring_parameter_id, row.scoring_parameter_option_id) for row in inputs],
        )
    except ScoringError as exc:
        raise ApiError(400, "01", exc.message) from exc
    revision = transaction.current_revision + 1
    snapshot = ScoringSnapshot(
        transaction_id=transaction.id,
        revision_no=revision,
        scoring_version_id=version.id,
        total_score=Decimal(result["total_score"]),
        threshold_id=result["threshold_id"],
        result_label=result["result_label"],
        threshold_min=Decimal(result["threshold_min"]),
        threshold_max=Decimal(result["threshold_max"]) if result["threshold_max"] is not None else None,
        calculated_by=actor.id,
    )
    db.add(snapshot)
    db.flush()
    for line in result["lines"]:
        db.add(
            ScoringSnapshotLine(
                snapshot_id=snapshot.id,
                parameter_name=line["parameter_name"],
                option_label=line["option_label"],
                value=Decimal(line["value"]),
                weight=Decimal(line["weight"]),
                line_score=Decimal(line["line_score"]),
                display_order=line["display_order"],
            )
        )
    field_inputs = db.scalars(select(DynamicFieldInput).where(DynamicFieldInput.transaction_id == transaction.id)).all()
    for stored in field_inputs:
        field = db.get(DynamicField, stored.dynamic_field_id)
        db.add(
            DynamicFieldSnapshotLine(
                snapshot_id=snapshot.id,
                field_label=field.label if field else "",
                field_type=field.field_type if field else "",
                value_text=stored.value_text,
                display_order=field.display_order if field else 0,
            )
        )
    transaction.current_revision = revision
    transaction.assigned_approver_id = None
    transaction.status = "waiting_for_approver_assignment"
    write_audit(
        db,
        actor,
        "scoring.transaction_submitted",
        object_type="scoring_transaction",
        object_id=str(transaction.id),
        after_data={"total_score": result["total_score"], "result_label": result["result_label"]},
    )
    db.commit()
    return {
        "rcode": "00",
        "message": "Pengajuan berhasil dikunci",
        "result": {"transaction_no": transaction.transaction_no, "total_score": result["total_score"],         "result_label": result["result_label"]},
    }


@router.post("/{transaction_id}/duplicate")
def duplicate_transaction(transaction_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    _submitter(db, actor)
    source = _transaction_or_404(db, transaction_id)
    _visible(db, actor, source)
    # Scoring ulang: new draft TX — same debtor/product only. Answers are NOT copied (editable blank form).
    if source.status not in {"approved", "rejected"}:
        raise ApiError(400, "01", "Hanya transaksi disetujui atau ditolak yang dapat di-scoring ulang")
    if source.branch_id != actor.branch_id:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")
    version = _active_version(db, source.product_id)
    source_status = source.status
    clone = ScoringTransaction(
        transaction_no=f"TMP-{uuid.uuid4().hex}",
        debtor_id=source.debtor_id,
        product_id=source.product_id,
        scoring_version_id=version.id,
        branch_id=actor.branch_id,
        status="draft",
        created_by=actor.id,
        duplicated_from_id=source.id,
    )
    db.add(clone)
    db.flush()
    clone.transaction_no = f"TX{clone.id:08d}"
    # Intentionally do not copy ScoringInput / DynamicFieldInput — user re-enters parameters.
    db.refresh(source)
    if source.status != source_status:
        raise ApiError(400, "01", "Transaksi sumber tidak boleh berubah")
    write_audit(
        db,
        actor,
        "scoring.transaction_duplicated",
        object_type="scoring_transaction",
        object_id=str(clone.id),
        after_data={"duplicated_from_id": source.id, "scoring_version_id": version.id, "answers_copied": False},
    )
    db.commit()
    return {
        "rcode": "00",
        "message": "Scoring ulang dibuat. Parameter dikosongkan agar dapat diisi ulang.",
        "result": {"id": clone.id, "transaction_no": clone.transaction_no, "duplicated_from_id": source.id},
    }
