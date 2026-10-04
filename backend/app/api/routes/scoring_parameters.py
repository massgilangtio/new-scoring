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
    User,
)
from app.services.audit import write_audit
from app.services.authorization import has_permission

router = APIRouter(prefix="/api/v1/scoring/parameters", tags=["scoring-parameters"])


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
    passing_score: Decimal = Decimal("350.00")
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
    sub_parameter_desc: str
    weight: Decimal
    value: Decimal
    total: Decimal


class SaveCreditScoringBody(BaseModel):
    debtor_id: int
    debtor_data: DebtorUpdateData | None = None
    product_id: int
    mapping_id: int | None = None
    total_score: Decimal
    passing_score: Decimal | None = None
    details: list[ScoringChoiceItem] = Field(default_factory=list)
    send_to_supervisor: bool = False
    supervisor_id: int | None = None
    notes: str | None = None


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

    write_audit(db, actor, "scoring.master_parameter_created", object_type="master_parameter", object_id=str(param.id))
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

    write_audit(db, actor, "scoring.master_parameter_updated", object_type="master_parameter", object_id=str(param.id))
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
    write_audit(db, actor, "scoring.master_parameter_deleted", object_type="master_parameter", object_id=str(param.id))
    db.commit()

    return {"rcode": "00", "message": "Parameter berhasil dihapus", "result": {}}


# ==========================================
# 2. Mapping Parameter & Produk
# ==========================================

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

    mapping = ProductParameterMapping(
        product_id=body.product_id,
        version_name=body.version_name.strip(),
        passing_score=body.passing_score if body.passing_score is not None else Decimal("350.00"),
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

    write_audit(db, actor, "scoring.mapping_created", object_type="product_parameter_mapping", object_id=str(mapping.id))
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

    db.execute(delete(ProductParameterMappingItem).where(ProductParameterMappingItem.mapping_id == mapping.id))
    db.delete(mapping)
    write_audit(db, actor, "scoring.mapping_deleted", object_type="product_parameter_mapping", object_id=str(mapping.id))
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

    write_audit(db, actor, "scoring.mapping_updated", object_type="product_parameter_mapping", object_id=str(mapping.id))
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
    grouped: dict[str, list] = {}
    for item in items:
        if item.parameter_name not in grouped:
            grouped[item.parameter_name] = []
        grouped[item.parameter_name].append({
            "id": item.id,
            "code": item.code,
            "description": item.description,
            "weight": _number(item.weight),
            "value": _number(item.value),
            "total": _number(item.total),
        })

    parameters = [
        {"name": name, "sub_parameters": subs}
        for name, subs in grouped.items()
    ]

    return {
        "rcode": "00",
        "message": "Parameter mapping berhasil ditampilkan",
        "result": {
            "mapping_id": mapping.id,
            "version_name": mapping.version_name,
            "passing_score": _number(getattr(mapping, "passing_score", None) or Decimal("350.00")),
            "parameters": parameters,
        },
    }


@router.get("/credit/supervisors")
def list_supervisors(
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    # Query users that have role approver or permissions for approval
    roles = db.scalars(select(Role).where(Role.code.in_(["approver", "supervisor", "pimunit"]))).all()
    role_ids = [r.id for r in roles]

    users = db.scalars(
        select(User)
        .where(User.is_active.is_(True))
        .order_by(User.full_name)
    ).all()

    items = []
    for u in users:
        # Include users that have approver role or permission
        r = db.get(Role, u.role_id)
        has_appr = (u.role_id in role_ids) or has_permission(db, u, "scoring.approve") or has_permission(db, u, "scoring.assign")
        if has_appr:
            b = db.get(Branch, u.branch_id)
            items.append({
                "id": u.id,
                "full_name": u.full_name,
                "username": u.username,
                "role_name": r.name if r else "Supervisi",
                "branch_name": b.name if b else "-",
            })

    return {"rcode": "00", "message": "Daftar supervisi berhasil ditampilkan", "result": {"items": items}}


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

    # 2. Determine passing_score and eligibility_status
    mapping = db.get(ProductParameterMapping, body.mapping_id) if body.mapping_id else None
    if body.passing_score is not None:
        cutoff = body.passing_score
    elif mapping and getattr(mapping, "passing_score", None) is not None:
        cutoff = mapping.passing_score
    else:
        cutoff = Decimal("350.00")

    eligibility_status = "LAYAK" if Decimal(str(body.total_score)) >= cutoff else "TIDAK LAYAK"

    # 3. Generate scoring transaction number
    today_str = datetime.now().strftime("%Y%m%d")
    count_today = db.scalar(
        select(func.count(CreditScoring.id))
        .where(func.to_char(CreditScoring.created_at, "YYYYMMDD") == today_str)
    ) or 0
    scoring_no = f"SCR-{today_str}-{(count_today + 1):04d}"

    status = "submitted_to_supervisor" if body.send_to_supervisor else "draft"

    scoring = CreditScoring(
        scoring_no=scoring_no,
        debtor_id=debtor.id,
        product_id=body.product_id,
        mapping_id=body.mapping_id,
        total_score=body.total_score,
        passing_score=cutoff,
        eligibility_status=eligibility_status,
        status=status,
        supervisor_id=body.supervisor_id if body.send_to_supervisor else None,
        notes=body.notes,
        created_by=actor.id,
        branch_id=debtor.branch_id or actor.branch_id,
    )
    db.add(scoring)
    db.flush()

    for item in body.details:
        db.add(
            CreditScoringDetail(
                scoring_id=scoring.id,
                parameter_name=item.parameter_name,
                sub_parameter_code=item.sub_parameter_code,
                sub_parameter_desc=item.sub_parameter_desc,
                weight=item.weight,
                value=item.value,
                total=item.total,
            )
        )

    # 4. If sent to supervisor, create notification
    if body.send_to_supervisor and body.supervisor_id:
        db.add(
            Notification(
                recipient_user_id=body.supervisor_id,
                event_type="sent_to_approver",
                channel="in_app",
                title="Pengajuan Scoring Kredit Baru",
                body=f"Pengajuan scoring kredit {scoring_no} ({eligibility_status}) untuk debitur {debtor.full_name} menunggu persetujuan Anda.",
                object_type="credit_scoring",
                object_id=scoring.id,
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
            "total_score": str(body.total_score),
            "passing_score": str(cutoff),
            "eligibility_status": eligibility_status,
            "supervisor_id": body.supervisor_id,
        },
    )
    db.commit()

    return {
        "rcode": "00",
        "message": f"Scoring kredit berhasil disimpan ({eligibility_status})" + (" dan dikirim ke Supervisi" if body.send_to_supervisor else " sebagai draft"),
        "result": {
            "id": scoring.id,
            "scoring_no": scoring_no,
            "status": status,
            "total_score": str(body.total_score),
            "passing_score": str(cutoff),
            "eligibility_status": eligibility_status,
        },
    }
