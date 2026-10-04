from fastapi import APIRouter, Depends
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.models.tables import AuditLog, User
from app.services.authorization import visible_branch_id

router = APIRouter(prefix="/api/v1/audit", tags=["audit"])


@router.get("")
def list_audit(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    statement = select(AuditLog).order_by(AuditLog.id.desc()).limit(100)
    scope = visible_branch_id(db, actor)
    if scope is not None:
        statement = statement.where(AuditLog.actor_branch_id == scope)
    rows = db.scalars(statement).all()
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "items": [
                {
                    "id": row.id,
                    "occurred_at": row.occurred_at.isoformat(),
                    "actor_role_name": row.actor_role_name,
                    "action": row.action,
                    "object_type": row.object_type,
                    "object_id": row.object_id,
                    "reason": row.reason,
                    "before": row.before_data,
                    "after": row.after_data,
                }
                for row in rows
            ]
        },
    }
