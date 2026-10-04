from datetime import UTC, datetime, timedelta

import jwt
import pyotp
import segno
from cryptography.fernet import Fernet, InvalidToken
from pwdlib import PasswordHash

from app.core.config import get_settings

password_hash = PasswordHash.recommended()
PURPOSE_ACCESS = "access"
PURPOSE_MFA_SETUP = "mfa_setup"
PURPOSE_MFA_VERIFY = "mfa_verify"


def hash_password(password: str) -> str:
    return password_hash.hash(password)


def verify_password(password: str, hashed: str) -> bool:
    return password_hash.verify(password, hashed)


def _fernet() -> Fernet:
    return Fernet(get_settings().mfa_encryption_key.encode())


def encrypt_mfa_secret(secret: str) -> str:
    return _fernet().encrypt(secret.encode()).decode()


def decrypt_mfa_secret(encrypted: str) -> str:
    try:
        return _fernet().decrypt(encrypted.encode()).decode()
    except InvalidToken as exc:
        raise ValueError("mfa secret cannot be decrypted") from exc


def new_totp_secret() -> str:
    return pyotp.random_base32()


def provisioning_uri(secret: str, username: str) -> str:
    return pyotp.TOTP(secret).provisioning_uri(
        name=username,
        issuer_name="New Scoring Credit System",
    )


def qr_svg(uri: str) -> str:
    symbol = segno.make(uri, error="m")
    rows = list(symbol.matrix_iter(border=4))
    size = len(rows)
    module = 4
    pixel = size * module
    rects = []
    for y, row in enumerate(rows):
        x = 0
        while x < size:
            if not row[x]:
                x += 1
                continue
            start = x
            x += 1
            while x < size and row[x]:
                x += 1
            rects.append(
                f'<rect x="{start * module}" y="{y * module}" width="{(x - start) * module}" height="{module}"/>'
            )
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {pixel} {pixel}" '
        f'width="{pixel}" height="{pixel}" shape-rendering="crispEdges" role="img">'
        f'<rect width="100%" height="100%" fill="#fff"/>'
        f'<g fill="#000">{"".join(rects)}</g></svg>'
    )


def matching_totp_step(secret: str, code: str) -> int | None:
    totp = pyotp.TOTP(secret)
    now = datetime.now(UTC)
    for offset in (0, -30, 30):
        moment = now + timedelta(seconds=offset)
        if totp.verify(code, for_time=moment, valid_window=0):
            return int(moment.timestamp()) // 30
    return None


def create_token(user_id: int, purpose: str, minutes: int) -> str:
    settings = get_settings()
    expires = datetime.now(UTC) + timedelta(minutes=minutes)
    payload = {"sub": str(user_id), "purpose": purpose, "exp": expires}
    return jwt.encode(payload, settings.jwt_secret, algorithm="HS256")


def decode_token(token: str, purpose: str) -> int:
    settings = get_settings()
    try:
        payload = jwt.decode(token, settings.jwt_secret, algorithms=["HS256"])
    except jwt.PyJWTError as exc:
        raise ValueError("invalid token") from exc
    if payload.get("purpose") != purpose:
        raise ValueError("invalid token")
    subject = payload.get("sub")
    if not isinstance(subject, str) or not subject.isdigit():
        raise ValueError("invalid token")
    return int(subject)
