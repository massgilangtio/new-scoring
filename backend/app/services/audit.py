from sqlalchemy.orm import Session

from app.models.tables import AuditLog, Role, User


def write_audit(
    db: Session,
    user: User,
    action: str,
    *,
    object_type: str = "user",
    object_id: str | None = None,
    reason: str | None = None,
    before_data: dict | None = None,
    after_data: dict | None = None,
) -> None:
    role = db.get(Role, user.role_id)
    db.add(
        AuditLog(
            actor_user_id=user.id,
            actor_role_id=user.role_id,
            actor_role_name=role.name if role is not None else "",
            actor_branch_id=user.branch_id,
            action=action,
            object_type=object_type,
            object_id=object_id if object_id is not None else str(user.id),
            reason=reason,
            before_data=before_data,
            after_data=after_data,
        )
    )
