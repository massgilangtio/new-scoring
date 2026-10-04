from datetime import UTC, datetime

from fastapi import APIRouter, Depends
from pydantic import BaseModel, ConfigDict, Field
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import Debtor, Product, RescoreRequest, SystemSetting, User
from app.services.audit import write_audit
from app.services.authorization import has_permission, visible_branch_id

router = APIRouter(prefix="/api/v1/rescore", tags=["rescore"])


class RescoreBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    debtor_id: int
    product_id: int
    reason: str = Field(min_length=1, max_length=1000)


class DuplicateSettingBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    enabled: bool


def _request_item(row: RescoreRequest, debtor: Debtor, product: Product) -> dict:
    return {
        "id": row.id,
        "status": row.status,
        "reason": row.reason,
        "debtor_name": debtor.full_name,
        "nik": debtor.nik,
        "product_name": product.name,
        "debtor_id": row.debtor_id,
        "product_id": row.product_id,
    }


@router.get("")
def list_requests(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not (has_permission(db, actor, "scoring.submit") or has_permission(db, actor, "scoring.approve")):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    statement = (
        select(RescoreRequest, Debtor, Product)
        .join(Debtor, Debtor.id == RescoreRequest.debtor_id)
        .join(Product, Product.id == RescoreRequest.product_id)
        .order_by(RescoreRequest.id.desc())
    )
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(RescoreRequest.branch_id == scope)
    items = [_request_item(row, debtor, product) for row, debtor, product in db.execute(statement).all()]
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": {"items": items}}


@router.post("")
def create_request(body: RescoreBody, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.submit"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    debtor = db.get(Debtor, body.debtor_id)
    product = db.get(Product, body.product_id)
    if debtor is None or product is None or debtor.branch_id != actor.branch_id:
        raise ApiError(400, "01", "Debitur atau produk tidak tersedia untuk cabang Anda")
    reason = body.reason.strip()
    if not reason:
        raise ApiError(400, "01", "Alasan wajib diisi")
    existing = db.scalar(
        select(RescoreRequest.id).where(
            RescoreRequest.debtor_id == debtor.id,
            RescoreRequest.product_id == product.id,
            RescoreRequest.status.in_(("waiting_approval", "approved")),
        )
    )
    if existing is not None:
        raise ApiError(400, "01", "Permintaan scoring ulang untuk NIK dan produk ini masih berjalan")
    row = RescoreRequest(
        debtor_id=debtor.id,
        product_id=product.id,
        branch_id=actor.branch_id,
        reason=reason,
        requested_by=actor.id,
    )
    db.add(row)
    db.flush()
    write_audit(db, actor, "scoring.rescore_requested", object_type="rescore_request", object_id=str(row.id), reason=reason)
    db.commit()
    return {"rcode": "00", "message": "Permintaan scoring ulang berhasil diajukan", "result": {"id": row.id}}


@router.post("/{request_id}/approve")
def approve_request(request_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.approve"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    row = db.get(RescoreRequest, request_id)
    if row is None or row.status != "waiting_approval":
        raise ApiError(400, "01", "Permintaan tidak menunggu persetujuan")
    scope = visible_branch_id(db, actor)
    if scope is not None and row.branch_id != scope:
        raise ApiError(403, "04", "Anda tidak memiliki hak akses untuk cabang ini")
    if row.requested_by == actor.id:
        raise ApiError(403, "04", "Permintaan tidak dapat disetujui oleh pemohon")
    row.status = "approved"
    row.approved_by = actor.id
    row.approved_at = datetime.now(UTC)
    write_audit(db, actor, "scoring.rescore_approved", object_type="rescore_request", object_id=str(row.id))
    db.commit()
    return {"rcode": "00", "message": "Permintaan scoring ulang disetujui", "result": {"id": row.id}}


@router.get("/duplicate-setting")
def get_duplicate_setting(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    if not has_permission(db, actor, "scoring.configure"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    setting = db.get(SystemSetting, "duplicate_enabled")
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {"enabled": setting is not None and setting.setting_value == "true"},
    }


@router.put("/duplicate-setting")
def update_duplicate_setting(
    body: DuplicateSettingBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not has_permission(db, actor, "scoring.configure"):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    setting = db.get(SystemSetting, "duplicate_enabled")
    if setting is None:
        setting = SystemSetting(setting_key="duplicate_enabled", setting_value="false")
        db.add(setting)
    setting.setting_value = "true" if body.enabled else "false"
    setting.updated_by = actor.id
    write_audit(
        db,
        actor,
        "scoring.duplicate_setting_updated",
        object_type="system_setting",
        object_id="duplicate_enabled",
        after_data={"enabled": body.enabled},
    )
    db.commit()
    return {"rcode": "00", "message": "Pengaturan duplikasi berhasil disimpan", "result": {"enabled": body.enabled}}
