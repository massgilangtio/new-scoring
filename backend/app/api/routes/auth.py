from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db, mfa_any_user, mfa_setup_user, mfa_verify_user
from app.api.errors import ApiError
from app.core.config import get_settings
from app.core.security import PURPOSE_MFA_SETUP, create_token
from app.models.tables import Branch, JobGroup, Role, User, UserHris
from app.services.audit import write_audit
from app.services.auth import (
    begin_login,
    build_user_profile,
    confirm_mfa,
    reset_user_mfa,
    verify_mfa,
)
from app.services.authorization import permission_codes

router = APIRouter(prefix="/api/v1/auth", tags=["auth"])


class LoginRequest(BaseModel):
    username: str = Field(min_length=1, max_length=100)
    password: str = Field(min_length=1, max_length=200)


class MfaCodeRequest(BaseModel):
    code: str = Field(pattern=r"^\d{6}$")


_profile = build_user_profile


@router.post("/login")
def login(body: LoginRequest, db: Session = Depends(get_db)) -> dict:
    try:
        step, result = begin_login(db, body.username.strip(), body.password)
    except PermissionError as exc:
        raise ApiError(401, "01", "Username atau password tidak sesuai") from exc
    msg = "Login berhasil" if step == "direct" else "Lanjutkan verifikasi autentikator"
    return {"rcode": "00", "message": msg, "result": result}


@router.post("/mfa/confirm")
def mfa_confirm(
    body: MfaCodeRequest,
    user: User = Depends(mfa_setup_user),
    db: Session = Depends(get_db),
) -> dict:
    try:
        token = confirm_mfa(db, user, body.code)
    except PermissionError as exc:
        raise ApiError(401, "02", "Kode autentikator tidak sesuai") from exc
    return {
        "rcode": "00",
        "message": "Autentikator berhasil diaktifkan",
        "result": {"step": "authenticated", "access_token": token, "user": _profile(db, user)},
    }


@router.post("/mfa/verify")
def mfa_verify(
    body: MfaCodeRequest,
    user: User = Depends(mfa_verify_user),
    db: Session = Depends(get_db),
) -> dict:
    try:
        token = verify_mfa(db, user, body.code)
    except PermissionError as exc:
        raise ApiError(401, "02", "Kode autentikator tidak sesuai") from exc
    return {
        "rcode": "00",
        "message": "Login berhasil",
        "result": {"step": "authenticated", "access_token": token, "user": _profile(db, user)},
    }


@router.post("/mfa/reset")
def mfa_reset(
    user: User = Depends(mfa_any_user),
    db: Session = Depends(get_db),
) -> dict:
    _secret, uri, svg = reset_user_mfa(db, user)
    token = create_token(user.id, PURPOSE_MFA_SETUP, get_settings().mfa_token_minutes)
    write_audit(db, user, "auth.mfa_reset", reason="Pengguna meminta reset barcode QR")
    db.commit()
    return {
        "rcode": "00",
        "message": "Secret key berhasil direset. Silakan scan barcode baru.",
        "result": {
            "step": "mfa_setup",
            "mfa_token": token,
            "otpauth_uri": uri,
            "qr_svg": svg,
        },
    }


@router.get("/me")
def me(user: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": _profile(db, user)}


@router.post("/logout")
def logout(user: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    write_audit(db, user, "auth.logout")
    db.commit()
    return {"rcode": "00", "message": "Logout berhasil", "result": {}}
