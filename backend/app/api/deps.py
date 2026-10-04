from collections.abc import Generator

from fastapi import Depends
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy.orm import Session

from app.api.errors import ApiError
from app.core.security import PURPOSE_ACCESS, PURPOSE_MFA_SETUP, PURPOSE_MFA_VERIFY, decode_token
from app.db.session import SessionLocal
from app.models.tables import Role, User

bearer = HTTPBearer(auto_error=False)


def get_db() -> Generator[Session, None, None]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


def _user_from_token(db: Session, credentials: HTTPAuthorizationCredentials | None, purpose: str) -> User:
    if credentials is None or credentials.scheme.lower() != "bearer":
        raise ApiError(401, "03", "Sesi tidak berlaku")
    try:
        user_id = decode_token(credentials.credentials, purpose)
    except ValueError as exc:
        raise ApiError(401, "03", "Sesi tidak berlaku") from exc
    user = db.get(User, user_id)
    role = db.get(Role, user.role_id) if user is not None else None
    if user is None or not user.is_active or role is None or not role.is_active:
        raise ApiError(401, "03", "Sesi tidak berlaku")
    return user


def current_user(
    credentials: HTTPAuthorizationCredentials | None = Depends(bearer),
    db: Session = Depends(get_db),
) -> User:
    return _user_from_token(db, credentials, PURPOSE_ACCESS)


def mfa_setup_user(
    credentials: HTTPAuthorizationCredentials | None = Depends(bearer),
    db: Session = Depends(get_db),
) -> User:
    return _user_from_token(db, credentials, PURPOSE_MFA_SETUP)


def mfa_verify_user(
    credentials: HTTPAuthorizationCredentials | None = Depends(bearer),
    db: Session = Depends(get_db),
) -> User:
    return _user_from_token(db, credentials, PURPOSE_MFA_VERIFY)
