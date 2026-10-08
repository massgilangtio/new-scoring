import os
import uuid
from datetime import datetime
from decimal import Decimal
from typing import Any

from fastapi import APIRouter, Depends, File, Form, UploadFile
from pydantic import BaseModel, Field
from sqlalchemy import delete, func, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import (
    ApproverAssignment,
    Branch,
    CreditScoring,
    CreditScoringDetail,
    Debtor,
    MasterParameter,
    MasterSubParameter,
    Notification,
    Product,
    ProductParameterMapping,
    ProductParameterMappingItem,
    Role,
    RolePermission,
    Permission,
    ScoringSnapshot,
    ScoringSnapshotLine,
    ScoringTransaction,
    ScoringVersion,
    SystemSetting,
    User,
)
from app.services.audit import write_audit
from app.services.authorization import can_view_score_details, has_permission

router = APIRouter(prefix="/api/v1/scoring/parameters", tags=["scoring-parameters"])

DEFAULT_PASSING_SCORE_KEY = "default_passing_score"
DEFAULT_PASSING_SCORE_FALLBACK = Decimal("350.00")


def _get_default_passing_score(db: Session) -> Decimal:
    setting = db.get(SystemSetting, DEFAULT_PASSING_SCORE_KEY)
    if setting is None or not str(setting.setting_value or "").strip():
        return DEFAULT_PASSING_SCORE_FALLBACK
    try:
        return Decimal(str(setting.setting_value))
    except Exception:
        return DEFAULT_PASSING_SCORE_FALLBACK


def _ensure_active_version(db: Session, product_id: int, actor: User) -> ScoringVersion:
    """Ensure product has an active ScoringVersion so credit flow can bridge into list/approvals/reports."""
    version = db.scalar(
        select(ScoringVersion).where(
            ScoringVersion.product_id == product_id,
            ScoringVersion.status == "active",
        )
    )
    if version is not None:
        return version
    max_no = db.scalar(
        select(func.coalesce(func.max(ScoringVersion.version_no), 0)).where(
            ScoringVersion.product_id == product_id
        )
    ) or 0
    version = ScoringVersion(
        product_id=product_id,
        version_no=int(max_no) + 1,
        status="active",
        activated_at=datetime.now(),
        created_by=actor.id,
    )
    db.add(version)
    db.flush()
    return version


def _bridge_credit_to_transaction(
    db: Session,
    *,
    actor: User,
    scoring: CreditScoring,
    details: list[dict[str, Any]],
    send_to_supervisor: bool,
) -> ScoringTransaction:
    """Mirror credit_scorings into scoring_transactions so Daftar/Approval/Laporan stay usable."""
    version = _ensure_active_version(db, scoring.product_id, actor)
    branch_id = scoring.branch_id or actor.branch_id
    if branch_id is None:
        raise ApiError(400, "01", "Cabang transaksi tidak dapat ditentukan")

    if scoring.status == "waiting_duplicate_approval":
        status = "waiting_duplicate_approval"
        assigned = scoring.supervisor_id
        revision = 1
    elif send_to_supervisor and scoring.supervisor_id:
        status = "submitted"
        assigned = scoring.supervisor_id
        revision = 1
    else:
        status = "draft"
        assigned = None
        revision = 0

    transaction = ScoringTransaction(
        transaction_no=f"TMP-{uuid.uuid4().hex}",
        debtor_id=scoring.debtor_id,
        product_id=scoring.product_id,
        scoring_version_id=version.id,
        branch_id=branch_id,
        branchid=scoring.branchid,
        status=status,
        duplicate_reason=scoring.duplicate_reason,
        created_by=actor.id,
        assigned_approver_id=assigned,
        current_revision=revision,
    )
    db.add(transaction)
    db.flush()
    transaction.transaction_no = scoring.scoring_no

    if revision >= 1:
        snapshot = ScoringSnapshot(
            transaction_id=transaction.id,
            revision_no=revision,
            scoring_version_id=version.id,
            total_score=scoring.total_score,
            threshold_id=None,
            result_label=scoring.eligibility_status,
            threshold_min=scoring.passing_score,
            threshold_max=None,
            calculated_by=actor.id,
        )
        db.add(snapshot)
        db.flush()
        for idx, item in enumerate(details):
            db.add(
                ScoringSnapshotLine(
                    snapshot_id=snapshot.id,
                    parameter_name=item["parameter_name"],
                    option_label=item["sub_parameter_desc"] or item["sub_parameter_code"],
                    value=item["value"],
                    weight=item["weight"],
                    line_score=item["total"],
                    display_order=idx + 1,
                )
            )
        if assigned:
            db.add(
                ApproverAssignment(
                    transaction_id=transaction.id,
                    revision_no=revision,
                    approver_id=assigned,
                    reason="Ditugaskan saat pengajuan scoring kredit",
                    assigned_by=actor.id,
                )
            )
    return transaction


def _resolve_mapping_passing_score(db: Session, mapping: ProductParameterMapping | None) -> Decimal:
    if mapping is not None and getattr(mapping, "passing_score", None) is not None:
        return Decimal(str(mapping.passing_score))
    return _get_default_passing_score(db)


# --- Schemas ---

class SubParameterItem(BaseModel):
    code: str = Field(min_length=1, max_length=50)
    description: str = Field(min_length=1, max_length=255)
    weight: Decimal = Field(ge=0)
    value: Decimal = Field(ge=0)
    total: Decimal = Field(ge=0)
    display_order: int = 0


class CreateMasterParameterBody(BaseModel):
    name: str = Field(min_length=1, max_length=150)
    sub_parameters: list[SubParameterItem] = Field(min_length=1)


class UpdateMasterParameterBody(BaseModel):
    name: str = Field(min_length=1, max_length=150)
    sub_parameters: list[SubParameterItem] = Field(min_length=1)


class MappingItem(BaseModel):
    parameter_id: int | None = None
    parameter_name: str
    code: str
    description: str
    weight: Decimal
    value: Decimal
    total: Decimal
    display_order: int = 0


class CreateMappingBody(BaseModel):
    product_id: int
    version_name: str = Field(min_length=1, max_length=150)
    passing_score: Decimal | None = None
    attachment_name: str | None = None
    attachment_path: str | None = None
    items: list[MappingItem] = Field(min_length=1)


class DebtorUpdateData(BaseModel):
    full_name: str | None = None
    nik: str | None = None
    phone: str | None = None
    address: str | None = None
    gender: str | None = None
    religion: str | None = None
    birth_date: str | None = None
    birth_place: str | None = None
    npwp: str | None = None


class ScoringChoiceItem(BaseModel):
    parameter_name: str
    sub_parameter_code: str
    sub_parameter_desc: str = ""
    # Optional — server resolves trusted values from product mapping items.
    weight: Decimal | None = None
    value: Decimal | None = None
    total: Decimal | None = None


class SaveCreditScoringBody(BaseModel):
    debtor_id: int
    debtor_data: DebtorUpdateData | None = None
    product_id: int
    mapping_id: int | None = None
    total_score: Decimal | None = None  # ignored — recalculated server-side from mapping items
    passing_score: Decimal | None = None  # ignored — resolved from product mapping / global setting
    details: list[ScoringChoiceItem] = Field(default_factory=list)
    send_to_supervisor: bool = False
    supervisor_id: int | None = None
    notes: str | None = None
    branchid: str | None = None
    duplicate_reason: str | None = None


class PassingScoreSettingBody(BaseModel):
    passing_score: Decimal = Field(ge=0, le=1000)
    apply_to_all: bool = False


# --- Helper ---

def _number(value: Decimal | None) -> str:
    if value is None:
        return "0.00"
    return format(value, "f")


# ==========================================
# 1. Konfigurasi Parameter (Master & Sub)
# ==========================================

@router.get("/master")
def list_master_parameters(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    params = db.scalars(
        select(MasterParameter)
        .where(MasterParameter.is_active.is_(True))
        .order_by(MasterParameter.id)
    ).all()

    result = []
    for p in params:
        subs = db.scalars(
            select(MasterSubParameter)
            .where(MasterSubParameter.parameter_id == p.id)
            .order_by(MasterSubParameter.display_order, MasterSubParameter.id)
        ).all()

        result.append({
            "id": p.id,
            "name": p.name,
            "sub_parameters_count": len(subs),
            "sub_parameters": [
                {
                    "id": s.id,
                    "code": s.code,
                    "description": s.description,
                    "weight": _number(s.weight),
                    "value": _number(s.value),
                    "total": _number(s.total),
                    "display_order": s.display_order,
                }
                for s in subs
            ],
            "created_at": p.created_at.isoformat() if p.created_at else None,
        })

    return {"rcode": "00", "message": "Data parameter berhasil ditampilkan", "result": {"items": result}}


@router.post("/master")
def create_master_parameter(
    body: CreateMasterParameterBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    param = MasterParameter(name=body.name.strip())
    db.add(param)
    db.flush()

    for idx, sub in enumerate(body.sub_parameters, start=1):
        # Auto calculate total: weight * value
        total_calc = sub.weight * sub.value
        db.add(
            MasterSubParameter(
                parameter_id=param.id,
                code=sub.code.strip(),
                description=sub.description.strip(),
                weight=sub.weight,
                value=sub.value,
                total=total_calc,
                display_order=sub.display_order or idx,
            )
        )

    write_audit(
        db,
        actor,
        "scoring.master_parameter_created",
        object_type="master_parameter",
        object_id=str(param.id),
        after_data={"name": param.name, "sub_parameters_count": len(body.sub_parameters)},
    )
    db.commit()

    return {"rcode": "00", "message": "Konfigurasi parameter berhasil disimpan", "result": {"id": param.id}}


@router.put("/master/{param_id}")
def update_master_parameter(
    param_id: int,
    body: UpdateMasterParameterBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    param = db.get(MasterParameter, param_id)
    if param is None:
        raise ApiError(404, "01", "Parameter tidak ditemukan")

    before_name = param.name
    param.name = body.name.strip()
    param.updated_at = datetime.now()

    # Re-create sub parameters
    db.execute(delete(MasterSubParameter).where(MasterSubParameter.parameter_id == param.id))
    db.flush()

    for idx, sub in enumerate(body.sub_parameters, start=1):
        total_calc = sub.weight * sub.value
        db.add(
            MasterSubParameter(
                parameter_id=param.id,
                code=sub.code.strip(),
                description=sub.description.strip(),
                weight=sub.weight,
                value=sub.value,
                total=total_calc,
                display_order=sub.display_order or idx,
            )
        )

    write_audit(
        db,
        actor,
        "scoring.master_parameter_updated",
        object_type="master_parameter",
        object_id=str(param.id),
        before_data={"name": before_name},
        after_data={"name": param.name, "sub_parameters_count": len(body.sub_parameters)},
    )
    db.commit()

    return {"rcode": "00", "message": "Konfigurasi parameter berhasil diperbarui", "result": {"id": param.id}}


@router.delete("/master/{param_id}")
def delete_master_parameter(
    param_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    param = db.get(MasterParameter, param_id)
    if param is None:
        raise ApiError(404, "01", "Parameter tidak ditemukan")

    param.is_active = False
    param.updated_at = datetime.now()
    write_audit(
        db,
        actor,
        "scoring.master_parameter_deleted",
        object_type="master_parameter",
        object_id=str(param.id),
        before_data={"name": param.name},
    )
    db.commit()

    return {"rcode": "00", "message": "Parameter berhasil dihapus", "result": {}}


# ==========================================
# 2. Mapping Parameter & Produk
# ==========================================

@router.get("/passing-score-setting")
def get_passing_score_setting(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not has_permission(db, actor, "scoring.configure"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    score = _get_default_passing_score(db)
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {"passing_score": _number(score)},
    }


@router.put("/passing-score-setting")
def update_passing_score_setting(
    body: PassingScoreSettingBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not has_permission(db, actor, "scoring.configure"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")

    score = Decimal(str(body.passing_score)).quantize(Decimal("0.01"))
    setting = db.get(SystemSetting, DEFAULT_PASSING_SCORE_KEY)
    if setting is None:
        setting = SystemSetting(setting_key=DEFAULT_PASSING_SCORE_KEY, setting_value=str(score))
        db.add(setting)
    else:
        setting.setting_value = str(score)
    setting.updated_by = actor.id

    updated_mappings = 0
    if body.apply_to_all:
        mappings = db.scalars(select(ProductParameterMapping)).all()
        for mapping in mappings:
            mapping.passing_score = score
            updated_mappings += 1

    write_audit(
        db,
        actor,
        "scoring.passing_score_setting_updated",
        object_type="system_setting",
        object_id=DEFAULT_PASSING_SCORE_KEY,
        after_data={
            "passing_score": str(score),
            "apply_to_all": body.apply_to_all,
            "updated_mappings": updated_mappings,
        },
    )
    db.commit()

    message = "Batas skor layak global berhasil disimpan"
    if body.apply_to_all:
        message = f"Batas skor layak global disimpan dan diterapkan ke {updated_mappings} mapping produk"

    return {
        "rcode": "00",
        "message": message,
        "result": {
            "passing_score": _number(score),
            "apply_to_all": body.apply_to_all,
            "updated_mappings": updated_mappings,
        },
    }


@router.get("/mappings")
def list_mappings(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    mappings = db.scalars(
        select(ProductParameterMapping)
        .where(ProductParameterMapping.is_active.is_(True))
        .order_by(ProductParameterMapping.id.desc())
    ).all()

    items = []
    for m in mappings:
        product = db.get(Product, m.product_id)
        sub_items = db.scalars(
            select(ProductParameterMappingItem)
            .where(ProductParameterMappingItem.mapping_id == m.id)
            .order_by(ProductParameterMappingItem.display_order, ProductParameterMappingItem.id)
        ).all()

        items.append({
            "id": m.id,
            "product_id": m.product_id,
            "product_code": product.code if product else "-",
            "product_name": product.name if product else "-",
            "version_name": m.version_name,
            "passing_score": _number(getattr(m, "passing_score", None) or Decimal("350.00")),
            "attachment_name": m.attachment_name,
            "attachment_path": m.attachment_path,
            "item_count": len(sub_items),
            "created_at": m.created_at.strftime("%Y-%m-%d %H:%M") if m.created_at else "-",
        })

    return {"rcode": "00", "message": "Data mapping berhasil ditampilkan", "result": {"items": items}}


@router.get("/mappings/{mapping_id}")
def get_mapping_detail(
    mapping_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    mapping = db.get(ProductParameterMapping, mapping_id)
    if mapping is None:
        raise ApiError(404, "01", "Mapping tidak ditemukan")

    product = db.get(Product, mapping.product_id)
    sub_items = db.scalars(
        select(ProductParameterMappingItem)
        .where(ProductParameterMappingItem.mapping_id == mapping.id)
        .order_by(ProductParameterMappingItem.display_order, ProductParameterMappingItem.id)
    ).all()

    return {
        "rcode": "00",
        "message": "Detail mapping berhasil ditampilkan",
        "result": {
            "id": mapping.id,
            "product_id": mapping.product_id,
            "product_code": product.code if product else "-",
            "product_name": product.name if product else "-",
            "version_name": mapping.version_name,
            "passing_score": _number(getattr(mapping, "passing_score", None) or Decimal("350.00")),
            "attachment_name": mapping.attachment_name,
            "attachment_path": mapping.attachment_path,
            "items": [
                {
                    "id": item.id,
                    "parameter_id": item.parameter_id,
                    "parameter_name": item.parameter_name,
                    "code": item.code,
                    "description": item.description,
                    "weight": _number(item.weight),
                    "value": _number(item.value),
                    "total": _number(item.total),
                    "display_order": item.display_order,
                }
                for item in sub_items
            ],
        },
    }


@router.post("/mappings")
def create_mapping(
    body: CreateMappingBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    product = db.get(Product, body.product_id)
    if product is None:
        raise ApiError(404, "01", "Produk tidak ditemukan")

    # Validasi total bobot parameter harus tepat 100, tidak boleh kurang atau lebih
    param_weights: dict[Any, Decimal] = {}
    for item in body.items:
        key = item.parameter_id if item.parameter_id is not None else item.parameter_name.strip()
        if key not in param_weights:
            param_weights[key] = item.weight

    total_weight = sum(param_weights.values(), Decimal("0"))
    if not param_weights or abs(float(total_weight) - 100.0) > 0.01:
        diff = round(abs(100.0 - float(total_weight)), 2)
        ket = f"Kurang {diff}" if float(total_weight) < 100.0 else f"Lebih {diff}"
        raise ApiError(
            400,
            "01",
            f"Nilai bobot parameter harus 100, tidak boleh kurang atau lebih! Saat ini total bobot adalah {total_weight} ({ket}).",
        )

    resolved_score = (
        body.passing_score if body.passing_score is not None else _get_default_passing_score(db)
    )
    mapping = ProductParameterMapping(
        product_id=body.product_id,
        version_name=body.version_name.strip(),
        passing_score=resolved_score,
        attachment_name=body.attachment_name,
        attachment_path=body.attachment_path,
        created_by=actor.id,
    )
    db.add(mapping)
    db.flush()

    for idx, item in enumerate(body.items, start=1):
        db.add(
            ProductParameterMappingItem(
                mapping_id=mapping.id,
                parameter_id=item.parameter_id,
                parameter_name=item.parameter_name.strip(),
                code=item.code.strip(),
                description=item.description.strip(),
                weight=item.weight,
                value=item.value,
                total=item.total,
                display_order=item.display_order or idx,
            )
        )

    write_audit(
        db,
        actor,
        "scoring.mapping_created",
        object_type="product_parameter_mapping",
        object_id=str(mapping.id),
        after_data={
            "product_id": mapping.product_id,
            "version_name": mapping.version_name,
            "items_count": len(body.items),
        },
    )
    db.commit()

    return {"rcode": "00", "message": "Mapping Parameter & Produk berhasil disimpan", "result": {"id": mapping.id}}


@router.post("/mappings/upload")
async def upload_attachment(
    file: UploadFile = File(...),
    actor: User = Depends(current_user),
) -> dict:
    upload_dir = os.path.join(os.getcwd(), "uploads", "mappings")
    os.makedirs(upload_dir, exist_ok=True)

    filename = f"{uuid.uuid4().hex[:12]}_{file.filename}"
    filepath = os.path.join(upload_dir, filename)

    contents = await file.read()
    with open(filepath, "wb") as f:
        f.write(contents)

    return {
        "rcode": "00",
        "message": "Dokumen berhasil diunggah",
        "result": {
            "attachment_name": file.filename,
            "attachment_path": f"/uploads/mappings/{filename}",
        },
    }


@router.delete("/mappings/{mapping_id}")
def delete_mapping(
    mapping_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    mapping = db.get(ProductParameterMapping, mapping_id)
    if mapping is None:
        raise ApiError(404, "01", "Mapping tidak ditemukan")

    before_info = {"product_id": mapping.product_id, "version_name": mapping.version_name}
    db.execute(delete(ProductParameterMappingItem).where(ProductParameterMappingItem.mapping_id == mapping.id))
    db.delete(mapping)
    write_audit(
        db,
        actor,
        "scoring.mapping_deleted",
        object_type="product_parameter_mapping",
        object_id=str(mapping.id),
        before_data=before_info,
    )
    db.commit()

    return {"rcode": "00", "message": "Mapping berhasil dihapus", "result": {}}


@router.put("/mappings/{mapping_id}")
def update_mapping(
    mapping_id: int,
    body: CreateMappingBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    mapping = db.get(ProductParameterMapping, mapping_id)
    if mapping is None:
        raise ApiError(404, "01", "Mapping tidak ditemukan")

    product = db.get(Product, body.product_id)
    if product is None:
        raise ApiError(404, "01", "Produk tidak ditemukan")

    # Validasi total bobot parameter harus tepat 100, tidak boleh kurang atau lebih
    param_weights: dict[Any, Decimal] = {}
    for item in body.items:
        key = item.parameter_id if item.parameter_id is not None else item.parameter_name.strip()
        if key not in param_weights:
            param_weights[key] = item.weight

    total_weight = sum(param_weights.values(), Decimal("0"))
    if not param_weights or abs(float(total_weight) - 100.0) > 0.01:
        diff = round(abs(100.0 - float(total_weight)), 2)
        ket = f"Kurang {diff}" if float(total_weight) < 100.0 else f"Lebih {diff}"
        raise ApiError(
            400,
            "01",
            f"Nilai bobot parameter harus 100, tidak boleh kurang atau lebih! Saat ini total bobot adalah {total_weight} ({ket}).",
        )

    mapping.product_id = body.product_id
    mapping.version_name = body.version_name.strip()
    if body.passing_score is not None:
        mapping.passing_score = body.passing_score
    if body.attachment_name is not None:
        mapping.attachment_name = body.attachment_name
    if body.attachment_path is not None:
        mapping.attachment_path = body.attachment_path

    # Replace existing items with updated items
    db.execute(delete(ProductParameterMappingItem).where(ProductParameterMappingItem.mapping_id == mapping.id))
    db.flush()

    for idx, item in enumerate(body.items, start=1):
        db.add(
            ProductParameterMappingItem(
                mapping_id=mapping.id,
                parameter_id=item.parameter_id,
                parameter_name=item.parameter_name.strip(),
                code=item.code.strip(),
                description=item.description.strip(),
                weight=item.weight,
                value=item.value,
                total=item.total,
                display_order=item.display_order or idx,
            )
        )

    write_audit(
        db,
        actor,
        "scoring.mapping_updated",
        object_type="product_parameter_mapping",
        object_id=str(mapping.id),
        after_data={
            "product_id": mapping.product_id,
            "version_name": mapping.version_name,
            "items_count": len(body.items),
        },
    )
    db.commit()

    return {"rcode": "00", "message": "Mapping Parameter & Produk berhasil diperbarui", "result": {"id": mapping.id}}


# ==========================================
# 3. Scoring Kredit
# ==========================================

@router.get("/credit/debtors")
def list_scoring_debtors(
    search: str | None = None,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    query = select(Debtor).where(Debtor.is_active.is_(True))
    if search:
        s = f"%{search.strip()}%"
        query = query.where(
            (Debtor.nik.ilike(s))
            | (Debtor.full_name.ilike(s))
            | (Debtor.cis_id.ilike(s))
            | (Debtor.cif_id.ilike(s))
        )
    debtors = db.scalars(query.order_by(Debtor.full_name).limit(50)).all()

    items = []
    for d in debtors:
        branch = db.get(Branch, d.branch_id)
        items.append({
            "id": d.id,
            "cis_id": d.cis_id or "-",
            "cif_id": d.cif_id or "-",
            "nik": d.nik,
            "full_name": d.full_name,
            "phone": d.phone or "",
            "address": d.address or "",
            "gender": d.gender or "",
            "religion": d.religion or "",
            "birth_date": d.birth_date.isoformat() if d.birth_date else "",
            "birth_place": d.birth_place or "",
            "npwp": d.npwp or "",
            "branch_id": d.branch_id,
            "branch_name": branch.name if branch else "-",
        })

    return {"rcode": "00", "message": "Data debitur berhasil ditampilkan", "result": {"items": items}}


@router.get("/credit/products")
def list_mapped_products(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    # Query products that have at least one active mapping
    mappings = db.scalars(
        select(ProductParameterMapping)
        .where(ProductParameterMapping.is_active.is_(True))
        .order_by(ProductParameterMapping.id.desc())
    ).all()

    seen_products = set()
    result = []
    for m in mappings:
        if m.product_id in seen_products:
            continue
        seen_products.add(m.product_id)
        p = db.get(Product, m.product_id)
        if p and p.is_active:
            result.append({
                "mapping_id": m.id,
                "product_id": p.id,
                "product_code": p.code,
                "product_name": p.name,
                "version_name": m.version_name,
                "label": f"{p.code} - {p.name} ({m.version_name})",
            })

    return {"rcode": "00", "message": "Produk dengan mapping parameter berhasil ditampilkan", "result": {"items": result}}


@router.get("/credit/mapping-items/{product_id}")
def get_product_mapping_items(
    product_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    mapping = db.scalar(
        select(ProductParameterMapping)
        .where(ProductParameterMapping.product_id == product_id, ProductParameterMapping.is_active.is_(True))
        .order_by(ProductParameterMapping.id.desc())
        .limit(1)
    )
    if mapping is None:
        raise ApiError(404, "01", "Produk belum memiliki mapping parameter")

    items = db.scalars(
        select(ProductParameterMappingItem)
        .where(ProductParameterMappingItem.mapping_id == mapping.id)
        .order_by(ProductParameterMappingItem.display_order, ProductParameterMappingItem.id)
    ).all()

    # Group sub parameters by parameter_name
    can_view = can_view_score_details(db, actor)
    grouped: dict[str, list] = {}
    for item in items:
        if item.parameter_name not in grouped:
            grouped[item.parameter_name] = []
        row = {
            "id": item.id,
            "code": item.code,
            "description": item.description,
        }
        if can_view:
            row["weight"] = _number(item.weight)
            row["value"] = _number(item.value)
            row["total"] = _number(item.total)
        grouped[item.parameter_name].append(row)

    parameters = [
        {"name": name, "sub_parameters": subs}
        for name, subs in grouped.items()
    ]

    result_body: dict[str, Any] = {
        "mapping_id": mapping.id,
        "version_name": mapping.version_name,
        "parameters": parameters,
        "can_view_score_details": can_view,
    }
    if can_view:
        result_body["passing_score"] = _number(_resolve_mapping_passing_score(db, mapping))

    return {
        "rcode": "00",
        "message": "Parameter mapping berhasil ditampilkan",
        "result": result_body,
    }


@router.get("/credit/supervisors")
def list_supervisors(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    # Approver candidates only (permission scoring.approve)
    users = db.scalars(
        select(User)
        .where(User.is_active.is_(True))
        .order_by(User.full_name)
    ).all()

    items = []
    for u in users:
        # Only users who can approve (Approver) — not assign-only Administrator
        if has_permission(db, u, "scoring.approve"):
            r = db.get(Role, u.role_id)
            b = db.get(Branch, u.branch_id)
            role_name = (r.name if r else "Approver") or "Approver"
            branch_name = b.name if b else "-"
            items.append({
                "id": u.id,
                "full_name": u.full_name,
                "username": u.username,
                "role_name": role_name,
                "branch_name": branch_name,
                "label": f"{u.full_name} — {role_name} · {branch_name}",
            })

    return {"rcode": "00", "message": "Daftar Approver berhasil ditampilkan", "result": {"items": items}}


@router.get("/credit/debtor-history/{debtor_id}")
def debtor_scoring_history(
    debtor_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    statement = (
        select(CreditScoring, Product)
        .join(Product, Product.id == CreditScoring.product_id)
        .where(CreditScoring.debtor_id == debtor_id)
        .order_by(CreditScoring.id.desc())
    )
    rows = db.execute(statement).all()
    items = []
    seen_nos = set()
    for cs, prod in rows:
        formatted_score = str(int(round(float(cs.total_score)))) if cs.total_score is not None else "-"
        seen_nos.add(cs.scoring_no)
        items.append({
            "id": cs.id,
            "scoring_no": cs.scoring_no,
            "product_id": prod.id,
            "product_code": prod.code,
            "product_name": prod.name,
            "total_score": formatted_score,
            "eligibility_status": cs.eligibility_status,
            "status": cs.status,
            "created_at": cs.created_at.strftime("%d-%m-%Y %H:%M") if cs.created_at else "-",
        })

    # Include ScoringTransaction rows for this debtor
    tx_rows = db.execute(
        select(ScoringTransaction, Product, ScoringSnapshot)
        .join(Product, Product.id == ScoringTransaction.product_id)
        .outerjoin(ScoringSnapshot, ScoringSnapshot.transaction_id == ScoringTransaction.id)
        .where(ScoringTransaction.debtor_id == debtor_id)
        .order_by(ScoringTransaction.id.desc())
    ).all()
    for tx, prod, sn in tx_rows:
        if tx.transaction_no not in seen_nos:
            score_val = "-"
            if sn and sn.total_score is not None:
                score_val = str(int(round(float(sn.total_score))))
            items.append({
                "id": tx.id,
                "scoring_no": tx.transaction_no,
                "product_id": prod.id,
                "product_code": prod.code,
                "product_name": prod.name,
                "total_score": score_val,
                "eligibility_status": sn.result_label if sn and sn.result_label else ("LAYAK" if score_val != "-" and int(score_val) >= 350 else "-"),
                "status": tx.status,
                "created_at": tx.created_at.strftime("%d-%m-%Y %H:%M") if tx.created_at else "-",
            })

    return {"rcode": "00", "message": "Riwayat scoring debitur berhasil ditampilkan", "result": items}


@router.get("/credit/check-duplicate")
def check_duplicate(
    debtor_id: int,
    product_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    priors_cs = db.scalars(
        select(CreditScoring)
        .where(CreditScoring.debtor_id == debtor_id, CreditScoring.product_id == product_id)
        .order_by(CreditScoring.id.desc())
    ).all()

    prior_items = []
    for cs in priors_cs:
        formatted_score = str(int(round(float(cs.total_score)))) if cs.total_score is not None else "-"
        prior_items.append({
            "id": cs.id,
            "scoring_no": cs.scoring_no,
            "total_score": formatted_score,
            "eligibility_status": cs.eligibility_status,
            "status": cs.status,
            "created_at": cs.created_at.strftime("%d-%m-%Y %H:%M") if cs.created_at else "-",
        })

    if not prior_items:
        priors_tx = db.scalars(
            select(ScoringTransaction)
            .where(ScoringTransaction.debtor_id == debtor_id, ScoringTransaction.product_id == product_id)
            .order_by(ScoringTransaction.id.desc())
        ).all()
        for tx in priors_tx:
            prior_items.append({
                "id": tx.id,
                "scoring_no": tx.transaction_no,
                "total_score": "-",
                "eligibility_status": "-",
                "status": tx.status,
                "created_at": tx.created_at.strftime("%d-%m-%Y %H:%M") if tx.created_at else "-",
            })

    # Also fetch all other products the debtor has ever applied for
    other_scorings = db.execute(
        select(CreditScoring, Product)
        .join(Product, Product.id == CreditScoring.product_id)
        .where(CreditScoring.debtor_id == debtor_id)
        .order_by(CreditScoring.id.desc())
    ).all()
    all_history = []
    for cs, prod in other_scorings:
        all_history.append({
            "scoring_no": cs.scoring_no,
            "product_name": prod.name,
            "product_code": prod.code,
            "total_score": str(int(round(float(cs.total_score)))) if cs.total_score is not None else "-",
            "eligibility_status": cs.eligibility_status,
            "status": cs.status,
            "created_at": cs.created_at.strftime("%d-%m-%Y") if cs.created_at else "-",
        })

    is_duplicate = len(prior_items) > 0
    return {
        "rcode": "00",
        "message": "Cek duplikasi berhasil",
        "result": {
            "is_duplicate": is_duplicate,
            "count": len(prior_items),
            "prior_scoring": prior_items[0] if prior_items else None,
            "prior_scorings": prior_items,
            "debtor_all_history": all_history,
        },
    }


@router.post("/credit/save")
def save_credit_scoring(
    body: SaveCreditScoringBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    debtor = db.get(Debtor, body.debtor_id)
    if debtor is None:
        raise ApiError(404, "01", "Debitur tidak ditemukan")

    # 1. Update debtor data if provided
    if body.debtor_data:
        if body.debtor_data.full_name:
            debtor.full_name = body.debtor_data.full_name.strip()
        if body.debtor_data.phone:
            debtor.phone = body.debtor_data.phone.strip()
        if body.debtor_data.address:
            debtor.address = body.debtor_data.address.strip()
        if body.debtor_data.gender:
            debtor.gender = body.debtor_data.gender.strip()
        if body.debtor_data.religion:
            debtor.religion = body.debtor_data.religion.strip()
        if body.debtor_data.birth_place:
            debtor.birth_place = body.debtor_data.birth_place.strip()
        if body.debtor_data.npwp:
            debtor.npwp = body.debtor_data.npwp.strip()
        debtor.updated_at = datetime.now()

    # 2. Determine passing_score from product mapping / global setting (ignore client override)
    mapping = db.get(ProductParameterMapping, body.mapping_id) if body.mapping_id else None
    if mapping is None:
        mapping = db.scalars(
            select(ProductParameterMapping)
            .where(
                ProductParameterMapping.product_id == body.product_id,
                ProductParameterMapping.is_active.is_(True),
            )
            .order_by(ProductParameterMapping.id.desc())
        ).first()
    if mapping is None:
        raise ApiError(400, "01", "Produk belum memiliki mapping parameter aktif")
    cutoff = _resolve_mapping_passing_score(db, mapping)

    # Resolve trusted weight/value/total from mapping items (ignore client score fields)
    mapping_items = db.scalars(
        select(ProductParameterMappingItem).where(ProductParameterMappingItem.mapping_id == mapping.id)
    ).all()
    item_by_code = {(row.parameter_name, row.code): row for row in mapping_items}

    resolved_details: list[dict[str, Any]] = []
    total_score = Decimal("0")
    for choice in body.details:
        key = (choice.parameter_name, choice.sub_parameter_code)
        mapped = item_by_code.get(key)
        if mapped is None:
            raise ApiError(
                400,
                "01",
                f"Pilihan parameter tidak valid: {choice.parameter_name} / {choice.sub_parameter_code}",
            )
        line_total = Decimal(str(mapped.total))
        total_score += line_total
        resolved_details.append(
            {
                "parameter_name": mapped.parameter_name,
                "sub_parameter_code": mapped.code,
                "sub_parameter_desc": mapped.description or choice.sub_parameter_desc,
                "weight": Decimal(str(mapped.weight)),
                "value": Decimal(str(mapped.value)),
                "total": line_total,
            }
        )

    eligibility_status = "LAYAK" if total_score >= cutoff else "TIDAK LAYAK"

    # 3. Generate scoring transaction number
    today_str = datetime.now().strftime("%Y%m%d")
    count_today = db.scalar(
        select(func.count(CreditScoring.id))
        .where(func.to_char(CreditScoring.created_at, "YYYYMMDD") == today_str)
    ) or 0
    scoring_no = f"SCR-{today_str}-{(count_today + 1):04d}"

    # Check for duplicate scoring history on same debtor & product
    prior_count = db.scalar(
        select(func.count(CreditScoring.id)).where(
            CreditScoring.debtor_id == debtor.id,
            CreditScoring.product_id == body.product_id,
        )
    ) or 0
    if prior_count == 0:
        prior_count = db.scalar(
            select(func.count(ScoringTransaction.id)).where(
                ScoringTransaction.debtor_id == debtor.id,
                ScoringTransaction.product_id == body.product_id,
            )
        ) or 0

    is_duplicate = prior_count > 0
    dup_reason = (body.duplicate_reason or "").strip() or None

    # If duplicate detected, transaction enters waiting_duplicate_approval
    if is_duplicate:
        status = "waiting_duplicate_approval"
    elif body.send_to_supervisor:
        status = "submitted_to_supervisor"
    else:
        status = "draft"

    actor_branch = db.get(Branch, actor.branch_id) if actor.branch_id else None
    resolved_branchid = str(body.branchid or (actor_branch.code if actor_branch else "")).strip() or None

    scoring = CreditScoring(
        scoring_no=scoring_no,
        debtor_id=debtor.id,
        product_id=body.product_id,
        mapping_id=mapping.id,
        total_score=total_score,
        passing_score=cutoff,
        eligibility_status=eligibility_status,
        status=status,
        duplicate_reason=dup_reason,
        supervisor_id=body.supervisor_id if body.send_to_supervisor else None,
        notes=body.notes,
        created_by=actor.id,
        branch_id=debtor.branch_id or actor.branch_id,
        branchid=resolved_branchid,
    )
    db.add(scoring)
    db.flush()

    for item in resolved_details:
        db.add(
            CreditScoringDetail(
                scoring_id=scoring.id,
                parameter_name=item["parameter_name"],
                sub_parameter_code=item["sub_parameter_code"],
                sub_parameter_desc=item["sub_parameter_desc"],
                weight=item["weight"],
                value=item["value"],
                total=item["total"],
            )
        )

    # Bridge into scoring_transactions so Daftar Scoring / Approval / Laporan remain connected
    bridged = _bridge_credit_to_transaction(
        db,
        actor=actor,
        scoring=scoring,
        details=resolved_details,
        send_to_supervisor=bool(body.send_to_supervisor),
    )

    # 4. If sent to supervisor, create notification
    if body.send_to_supervisor and body.supervisor_id:
        db.add(
            Notification(
                recipient_user_id=body.supervisor_id,
                event_type="sent_to_approver",
                channel="in_app",
                title="Pengajuan Scoring Kredit Baru",
                body=f"Pengajuan scoring kredit {scoring_no} menunggu persetujuan Anda.",
                object_type="scoring_transaction",
                object_id=bridged.id,
            )
        )

    write_audit(
        db,
        actor,
        "scoring.credit_scoring_saved",
        object_type="credit_scoring",
        object_id=str(scoring.id),
        after_data={
            "scoring_no": scoring_no,
            "status": status,
            "total_score": str(total_score),
            "passing_score": str(cutoff),
            "eligibility_status": eligibility_status,
            "supervisor_id": body.supervisor_id,
            "bridged_transaction_id": bridged.id,
        },
    )

    if is_duplicate:
        write_audit(
            db,
            actor,
            "scoring.duplicate_permission_requested",
            object_type="credit_scoring",
            object_id=str(scoring.id),
            reason=dup_reason,
            after_data={
                "scoring_no": scoring_no,
                "status": status,
                "duplicate_reason": dup_reason,
                "supervisor_id": body.supervisor_id,
                "bridged_transaction_id": bridged.id,
            },
        )
    db.commit()

    can_view = can_view_score_details(db, actor)
    result_body: dict[str, Any] = {
        "id": scoring.id,
        "scoring_no": scoring_no,
        "status": status,
        "transaction_id": bridged.id,
        "can_view_score_details": can_view,
    }
    if can_view:
        result_body["total_score"] = str(total_score)
        result_body["passing_score"] = str(cutoff)
        result_body["eligibility_status"] = eligibility_status

    message = "Scoring kredit berhasil disimpan"
    if can_view:
        message = f"Scoring kredit berhasil disimpan ({eligibility_status})"
    if body.send_to_supervisor:
        message += " dan dikirim ke Approver"
    else:
        message += " sebagai draft"

    return {
        "rcode": "00",
        "message": message,
        "result": result_body,
    }
