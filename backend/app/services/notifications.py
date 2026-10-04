from sqlalchemy.orm import Session

from app.models.tables import Notification

TITLES = {
    "sent_to_approver": "Pengajuan menunggu keputusan Anda",
    "approved": "Pengajuan disetujui",
    "returned": "Pengajuan dikembalikan",
    "rejected": "Pengajuan ditolak",
}


def notify(db: Session, recipient_id: int, event_type: str, body: str, transaction_id: int) -> None:
    db.add(
        Notification(
            recipient_user_id=recipient_id,
            event_type=event_type,
            title=TITLES[event_type],
            body=body,
            object_type="scoring_transaction",
            object_id=transaction_id,
        )
    )
