from datetime import UTC, datetime

from fastapi import APIRouter, Depends
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import Notification, User

router = APIRouter(prefix="/api/v1/notifications", tags=["notifications"])


@router.get("")
def list_notifications(actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    rows = db.scalars(
        select(Notification)
        .where(Notification.recipient_user_id == actor.id)
        .order_by(Notification.id.desc())
        .limit(50)
    ).all()
    unread = db.scalar(
        select(func.count(Notification.id)).where(
            Notification.recipient_user_id == actor.id,
            Notification.read_at.is_(None),
        )
    )
    return {
        "rcode": "00",
        "message": "Data berhasil ditampilkan",
        "result": {
            "unread_count": int(unread or 0),
            "items": [
                {
                    "id": row.id,
                    "event_type": row.event_type,
                    "title": row.title,
                    "body": row.body,
                    "object_id": row.object_id,
                    "read": row.read_at is not None,
                    "created_at": row.created_at.isoformat(),
                }
                for row in rows
            ],
        },
    }


@router.post("/{notification_id}/read")
def mark_read(notification_id: int, actor: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    row = db.get(Notification, notification_id)
    if row is None or row.recipient_user_id != actor.id:
        raise ApiError(404, "01", "Notifikasi tidak ditemukan")
    if row.read_at is None:
        row.read_at = datetime.now(UTC)
        db.commit()
    return {"rcode": "00", "message": "Notifikasi ditandai sudah dibaca", "result": {"id": row.id}}
