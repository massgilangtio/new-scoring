from datetime import UTC, datetime

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import (
    PURPOSE_ACCESS,
    PURPOSE_MFA_SETUP,
    PURPOSE_MFA_VERIFY,
    create_token,
    decrypt_mfa_secret,
    encrypt_mfa_secret,
    hash_password,
    matching_totp_step,
    new_totp_secret,
    provisioning_uri,
    qr_svg,
    verify_password,
)
from app.models.tables import Role, User
from app.services.audit import write_audit

_UNKNOWN_USER_HASH = hash_password("unused-timing-padding")


def find_user(db: Session, username: str) -> User | None:
    return db.scalar(select(User).where(User.username == username))


def access_token_for(user: User) -> str:
    return create_token(user.id, PURPOSE_ACCESS, get_settings().access_token_minutes)


def begin_login(db: Session, username: str, password: str) -> tuple[str, dict]:
    user = find_user(db, username)
    password_ok = user is not None and verify_password(password, user.password_hash)
    if user is None:
        verify_password(password, _UNKNOWN_USER_HASH)
    if user is None or not password_ok or not user.is_active:
        if user is not None:
            write_audit(db, user, "auth.login_failed", reason="Kredensial tidak sesuai atau user nonaktif")
            db.commit()
        raise PermissionError("credentials")
    role = db.get(Role, user.role_id)
    if role is None or not role.is_active:
        write_audit(db, user, "auth.login_failed", reason="Role tidak aktif")
        db.commit()
        raise PermissionError("credentials")

    settings = get_settings()
    if user.mfa_enabled:
        token = create_token(user.id, PURPOSE_MFA_VERIFY, settings.mfa_token_minutes)
        return "mfa_verify", {"step": "mfa_verify", "mfa_token": token}

    secret = decrypt_mfa_secret(user.mfa_secret_encrypted) if user.mfa_secret_encrypted else new_totp_secret()
    if user.mfa_secret_encrypted is None:
        user.mfa_secret_encrypted = encrypt_mfa_secret(secret)
        db.commit()
    uri = provisioning_uri(secret, user.username)
    token = create_token(user.id, PURPOSE_MFA_SETUP, settings.mfa_token_minutes)
    return "mfa_setup", {
        "step": "mfa_setup",
        "mfa_token": token,
        "otpauth_uri": uri,
        "qr_svg": qr_svg(uri),
    }


def confirm_mfa(db: Session, user: User, code: str) -> str:
    if user.mfa_enabled or not user.mfa_secret_encrypted:
        raise PermissionError("mfa")
    secret = decrypt_mfa_secret(user.mfa_secret_encrypted)
    step = matching_totp_step(secret, code)
    if step is None or (user.mfa_last_step is not None and step <= user.mfa_last_step):
        write_audit(db, user, "auth.mfa_failed", reason="Kode autentikator tidak sesuai")
        db.commit()
        raise PermissionError("mfa")
    user.mfa_enabled = True
    user.mfa_confirmed_at = datetime.now(UTC)
    user.mfa_last_step = step
    write_audit(db, user, "auth.mfa_enabled")
    write_audit(db, user, "auth.login_succeeded")
    db.commit()
    return access_token_for(user)


def verify_mfa(db: Session, user: User, code: str) -> str:
    if not user.mfa_enabled or not user.mfa_secret_encrypted:
        raise PermissionError("mfa")
    secret = decrypt_mfa_secret(user.mfa_secret_encrypted)
    step = matching_totp_step(secret, code)
    if step is None or (user.mfa_last_step is not None and step <= user.mfa_last_step):
        write_audit(db, user, "auth.mfa_failed", reason="Kode autentikator tidak sesuai")
        db.commit()
        raise PermissionError("mfa")
    user.mfa_last_step = step
    write_audit(db, user, "auth.login_succeeded")
    db.commit()
    return access_token_for(user)
