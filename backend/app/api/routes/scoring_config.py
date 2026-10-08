from decimal import Decimal

from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy import delete, func, select
from sqlalchemy.exc import DBAPIError
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import (
    DynamicField,
    DynamicFieldOption,
    Product,
    ScoringParameter,
    ScoringParameterOption,
    ScoringThreshold,
    ScoringVersion,
    User,
)
from app.services.audit import write_audit
from app.services.authorization import SCORING_CONFIGURE, has_permission

router = APIRouter(prefix="/api/v1/scoring", tags=["scoring-config"])
CHOICE_FIELDS = {"dropdown", "radio", "checkbox"}
FIELD_TYPES = {"text", "number", "date", "dropdown", "textarea", "radio", "checkbox"}


class ParameterBody(BaseModel):
    name: str = Field(min_length=1, max_length=150)
    weight: Decimal = Field(ge=0)
    display_order: int = 0


class OptionBody(BaseModel):
    label: str = Field(min_length=1, max_length=150)
    value: Decimal
    display_order: int = 0


class ThresholdBody(BaseModel):
    min_score: Decimal
    max_score: Decimal | None = None
    result_label: str = Field(min_length=1, max_length=150)
    display_order: int = 0


class DynamicFieldBody(BaseModel):
    field_key: str = Field(pattern=r"^[a-z0-9_]{1,40}$")
    label: str = Field(min_length=1, max_length=150)
    field_type: str
    is_required: bool = False
    is_active: bool = True
    display_order: int = 0


class DynamicOptionBody(BaseModel):
    label: str = Field(min_length=1, max_length=150)
    value: str = Field(min_length=1, max_length=150)
    display_order: int = 0


def require_configurer(user: User = Depends(current_user), db: Session = Depends(get_db)) -> User:
    if not has_permission(db, user, SCORING_CONFIGURE):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    return user


def _draft(db: Session, version_id: int) -> ScoringVersion:
    version = db.get(ScoringVersion, version_id)
    if version is None:
        raise ApiError(404, "01", "Versi scoring tidak ditemukan")
    if version.status != "draft":
        raise ApiError(400, "01", "Konfigurasi hanya dapat diubah saat versi masih draft")
    return version


def _number(value: Decimal | None) -> str | None:
    if value is None:
        return None
    return format(value, "f")


def _detail(db: Session, version: ScoringVersion) -> dict:
    parameters = db.scalars(
        select(ScoringParameter).where(ScoringParameter.scoring_version_id == version.id).order_by(ScoringParameter.display_order, ScoringParameter.id)
    ).all()
    parameter_items = []
    for parameter in parameters:
        options = db.scalars(
            select(ScoringParameterOption)
            .where(ScoringParameterOption.scoring_parameter_id == parameter.id)
            .order_by(ScoringParameterOption.display_order, ScoringParameterOption.id)
        ).all()
        parameter_items.append(
            {
                "id": parameter.id,
                "name": parameter.name,
                "weight": _number(parameter.weight),
                "display_order": parameter.display_order,
                "options": [
                    {"id": option.id, "label": option.label, "value": _number(option.value), "display_order": option.display_order}
                    for option in options
                ],
            }
        )
    thresholds = db.scalars(
        select(ScoringThreshold).where(ScoringThreshold.scoring_version_id == version.id).order_by(ScoringThreshold.display_order, ScoringThreshold.id)
    ).all()
    fields = db.scalars(
        select(DynamicField).where(DynamicField.scoring_version_id == version.id).order_by(DynamicField.display_order, DynamicField.id)
    ).all()
    field_items = []
    for field in fields:
        options = db.scalars(
            select(DynamicFieldOption).where(DynamicFieldOption.dynamic_field_id == field.id).order_by(DynamicFieldOption.display_order, DynamicFieldOption.id)
        ).all()
        field_items.append(
            {
                "id": field.id,
                "field_key": field.field_key,
                "label": field.label,
                "field_type": field.field_type,
                "is_required": field.is_required,
                "is_active": field.is_active,
                "display_order": field.display_order,
                "options": [{"id": option.id, "label": option.label, "value": option.value, "display_order": option.display_order} for option in options],
            }
        )
    return {
        "id": version.id,
        "product_id": version.product_id,
        "version_no": version.version_no,
        "status": version.status,
        "parameters": parameter_items,
        "thresholds": [
            {
                "id": row.id,
                "min_score": _number(row.min_score),
                "max_score": _number(row.max_score),
                "result_label": row.result_label,
                "display_order": row.display_order,
            }
            for row in thresholds
        ],
        "dynamic_fields": field_items,
    }


@router.get("/products/{product_id}/versions")
def list_versions(
    product_id: int,
    _actor: User = Depends(require_configurer),
    db: Session = Depends(get_db),
) -> dict:
    product = db.get(Product, product_id)
    if product is None:
        raise ApiError(404, "01", "Produk tidak ditemukan")
    rows = db.scalars(select(ScoringVersion).where(ScoringVersion.product_id == product_id).order_by(ScoringVersion.version_no)).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "product": {"id": product.id, "code": product.code, "name": product.name},
            "items": [
                {
                    "id": row.id,
                    "version_no": row.version_no,
                    "status": row.status,
                    "activated_at": row.activated_at.isoformat() if row.activated_at else None,
                }
                for row in rows
            ],
        },
    }


@router.post("/products/{product_id}/versions")
def create_version(
    product_id: int,
    actor: User = Depends(require_configurer),
    db: Session = Depends(get_db),
) -> dict:
    product = db.get(Product, product_id)
    if product is None or not product.is_active:
        raise ApiError(400, "01", "Produk tidak tersedia")
    current = db.scalar(select(func.max(ScoringVersion.version_no)).where(ScoringVersion.product_id == product_id))
    version = ScoringVersion(product_id=product_id, version_no=(current or 0) + 1, created_by=actor.id, status="draft")
    db.add(version)
    db.flush()
    write_audit(db, actor, "scoring.version_created", object_type="scoring_version", object_id=str(version.id), after_data={"version_no": version.version_no})
    db.commit()
    return {"rcode": "00", "message": "Versi draft berhasil dibuat", "result": {"id": version.id, "version_no": version.version_no}}


@router.post("/versions/{version_id}/copy")
def copy_version(
    version_id: int,
    actor: User = Depends(require_configurer),
    db: Session = Depends(get_db),
) -> dict:
    source = db.get(ScoringVersion, version_id)
    if source is None:
        raise ApiError(404, "01", "Versi scoring tidak ditemukan")
    current = db.scalar(select(func.max(ScoringVersion.version_no)).where(ScoringVersion.product_id == source.product_id))
    clone = ScoringVersion(
        product_id=source.product_id,
        version_no=(current or 0) + 1,
        created_by=actor.id,
        status="draft",
    )
    db.add(clone)
    db.flush()
    parameters = db.scalars(
        select(ScoringParameter).where(ScoringParameter.scoring_version_id == source.id).order_by(ScoringParameter.id)
    ).all()
    for parameter in parameters:
        copied = ScoringParameter(
            scoring_version_id=clone.id,
            name=parameter.name,
            weight=parameter.weight,
            display_order=parameter.display_order,
        )
        db.add(copied)
        db.flush()
        options = db.scalars(
            select(ScoringParameterOption).where(ScoringParameterOption.scoring_parameter_id == parameter.id)
        ).all()
        for option in options:
            db.add(
                ScoringParameterOption(
                    scoring_parameter_id=copied.id,
                    label=option.label,
                    value=option.value,
                    display_order=option.display_order,
                )
            )
    thresholds = db.scalars(select(ScoringThreshold).where(ScoringThreshold.scoring_version_id == source.id)).all()
    for threshold in thresholds:
        db.add(
            ScoringThreshold(
                scoring_version_id=clone.id,
                min_score=threshold.min_score,
                max_score=threshold.max_score,
                result_label=threshold.result_label,
                display_order=threshold.display_order,
            )
        )
    fields = db.scalars(select(DynamicField).where(DynamicField.scoring_version_id == source.id)).all()
    for field in fields:
        copied_field = DynamicField(
            scoring_version_id=clone.id,
            field_key=field.field_key,
            label=field.label,
            field_type=field.field_type,
            is_required=field.is_required,
            is_active=field.is_active,
            display_order=field.display_order,
        )
        db.add(copied_field)
        db.flush()
        field_options = db.scalars(select(DynamicFieldOption).where(DynamicFieldOption.dynamic_field_id == field.id)).all()
        for option in field_options:
            db.add(
                DynamicFieldOption(
                    dynamic_field_id=copied_field.id,
                    label=option.label,
                    value=option.value,
                    display_order=option.display_order,
                )
            )
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.version_copied",
        object_type="scoring_version",
        object_id=str(clone.id),
        after_data={"source_version_id": source.id, "version_no": clone.version_no},
    )
    db.commit()
    db.refresh(source)
    return {
        "rcode": "00",
        "message": "Versi lama disalin menjadi draft baru",
        "result": {"id": clone.id, "version_no": clone.version_no, "source_status": source.status},
    }


@router.get("/versions/{version_id}")
def get_version(
    version_id: int,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    version = db.get(ScoringVersion, version_id)
    if version is None:
        raise ApiError(404, "01", "Versi scoring tidak ditemukan")
    if version.status != "active" and not has_permission(db, actor, SCORING_CONFIGURE):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": _detail(db, version)}


@router.post("/versions/{version_id}/parameters")
def add_parameter(version_id: int, body: ParameterBody, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    _draft(db, version_id)
    row = ScoringParameter(scoring_version_id=version_id, name=body.name.strip(), weight=body.weight, display_order=body.display_order)
    db.add(row)
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.parameter_created",
        object_type="scoring_parameter",
        object_id=str(row.id),
        after_data={"name": row.name, "weight": str(row.weight)},
    )
    db.commit()
    return {"rcode": "00", "message": "Parameter berhasil disimpan", "result": {"id": row.id}}


@router.post("/parameters/{parameter_id}/options")
def add_option(parameter_id: int, body: OptionBody, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    parameter = db.get(ScoringParameter, parameter_id)
    if parameter is None:
        raise ApiError(404, "01", "Parameter tidak ditemukan")
    _draft(db, parameter.scoring_version_id)
    row = ScoringParameterOption(scoring_parameter_id=parameter.id, label=body.label.strip(), value=body.value, display_order=body.display_order)
    db.add(row)
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.option_created",
        object_type="scoring_parameter_option",
        object_id=str(row.id),
        after_data={"label": row.label, "value": str(row.value)},
    )
    db.commit()
    return {"rcode": "00", "message": "Nilai berhasil disimpan", "result": {"id": row.id}}


@router.post("/versions/{version_id}/thresholds")
def add_threshold(version_id: int, body: ThresholdBody, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    _draft(db, version_id)
    if body.max_score is not None and body.min_score > body.max_score:
        raise ApiError(400, "01", "Batas bawah tidak boleh lebih besar dari batas atas")
    row = ScoringThreshold(
        scoring_version_id=version_id,
        min_score=body.min_score,
        max_score=body.max_score,
        result_label=body.result_label.strip(),
        display_order=body.display_order,
    )
    db.add(row)
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.threshold_created",
        object_type="scoring_threshold",
        object_id=str(row.id),
        after_data={"min_score": str(row.min_score), "result_label": row.result_label},
    )
    db.commit()
    return {"rcode": "00", "message": "Threshold berhasil disimpan", "result": {"id": row.id}}


@router.post("/versions/{version_id}/fields")
def add_field(version_id: int, body: DynamicFieldBody, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    _draft(db, version_id)
    if body.field_type not in FIELD_TYPES:
        raise ApiError(400, "01", "Tipe field tidak dikenali")
    exists = db.scalar(select(DynamicField.id).where(DynamicField.scoring_version_id == version_id, DynamicField.field_key == body.field_key))
    if exists is not None:
        raise ApiError(400, "01", "Kunci field sudah digunakan pada versi ini")
    row = DynamicField(
        scoring_version_id=version_id,
        field_key=body.field_key,
        label=body.label.strip(),
        field_type=body.field_type,
        is_required=body.is_required,
        is_active=body.is_active,
        display_order=body.display_order,
    )
    db.add(row)
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.field_created",
        object_type="dynamic_field",
        object_id=str(row.id),
        after_data={"field_key": row.field_key, "label": row.label, "field_type": row.field_type},
    )
    db.commit()
    return {"rcode": "00", "message": "Dynamic field berhasil disimpan", "result": {"id": row.id}}


@router.post("/fields/{field_id}/options")
def add_field_option(field_id: int, body: DynamicOptionBody, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    field = db.get(DynamicField, field_id)
    if field is None:
        raise ApiError(404, "01", "Dynamic field tidak ditemukan")
    if field.field_type not in CHOICE_FIELDS:
        raise ApiError(400, "01", "Opsi hanya untuk dropdown, radio, atau checkbox")
    _draft(db, field.scoring_version_id)
    row = DynamicFieldOption(dynamic_field_id=field.id, label=body.label.strip(), value=body.value.strip(), display_order=body.display_order)
    db.add(row)
    db.flush()
    write_audit(
        db,
        actor,
        "scoring.field_option_created",
        object_type="dynamic_field_option",
        object_id=str(row.id),
        after_data={"label": row.label, "value": row.value},
    )
    db.commit()
    return {"rcode": "00", "message": "Opsi field berhasil disimpan", "result": {"id": row.id}}


def _ready_to_activate(db: Session, version: ScoringVersion) -> None:
    parameters = db.scalars(select(ScoringParameter).where(ScoringParameter.scoring_version_id == version.id)).all()
    total = sum((row.weight for row in parameters), Decimal("0"))
    if total != Decimal("100"):
        raise ApiError(400, "01", "Total weight harus 100 sebelum aktivasi")
    for parameter in parameters:
        count = db.scalar(select(func.count(ScoringParameterOption.id)).where(ScoringParameterOption.scoring_parameter_id == parameter.id))
        if not count:
            raise ApiError(400, "01", "Setiap parameter wajib memiliki nilai")
    fields = db.scalars(select(DynamicField).where(DynamicField.scoring_version_id == version.id, DynamicField.is_active.is_(True))).all()
    for field in fields:
        if field.field_type not in CHOICE_FIELDS:
            continue
        count = db.scalar(select(func.count(DynamicFieldOption.id)).where(DynamicFieldOption.dynamic_field_id == field.id))
        if not count:
            raise ApiError(400, "01", "Field pilihan wajib memiliki opsi")


@router.post("/versions/{version_id}/activate")
def activate_version(version_id: int, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    version = db.get(ScoringVersion, version_id)
    if version is None:
        raise ApiError(404, "01", "Versi scoring tidak ditemukan")
    if version.status == "active":
        raise ApiError(400, "01", "Versi ini sudah aktif")
    _ready_to_activate(db, version)
    try:
        current = db.scalars(
            select(ScoringVersion).where(
                ScoringVersion.product_id == version.product_id,
                ScoringVersion.status == "active",
                ScoringVersion.id != version.id,
            )
        ).all()
        for old in current:
            old.status = "inactive"
        db.flush()
        version.status = "active"
        write_audit(db, actor, "scoring.version_activated", object_type="scoring_version", object_id=str(version.id), after_data={"version_no": version.version_no})
        db.commit()
    except DBAPIError as exc:
        db.rollback()
        raise ApiError(400, "01", "Versi scoring tidak dapat diaktifkan") from exc
    return {"rcode": "00", "message": "Versi scoring berhasil diaktifkan", "result": {"id": version.id}}


@router.delete("/parameters/{parameter_id}")
def delete_parameter(parameter_id: int, actor: User = Depends(require_configurer), db: Session = Depends(get_db)) -> dict:
    parameter = db.get(ScoringParameter, parameter_id)
    if parameter is None:
        raise ApiError(404, "01", "Parameter tidak ditemukan")
    _draft(db, parameter.scoring_version_id)
    before_name = parameter.name
    db.execute(delete(ScoringParameterOption).where(ScoringParameterOption.scoring_parameter_id == parameter.id))
    db.delete(parameter)
    write_audit(
        db,
        actor,
        "scoring.parameter_deleted",
        object_type="scoring_parameter",
        object_id=str(parameter_id),
        before_data={"name": before_name},
    )
    db.commit()
    return {"rcode": "00", "message": "Parameter berhasil dihapus", "result": {}}
