from fastapi import APIRouter, Depends
from pydantic import BaseModel, Field
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db, mfa_setup_user, mfa_verify_user
from app.api.errors import ApiError
from app.models.tables import Branch, Role, User
from app.services.audit import write_audit
from app.services.auth import begin_login, confirm_mfa, verify_mfa
from app.services.authorization import permission_codes

router = APIRouter(prefix="/api/v1/auth", tags=["auth"])


class LoginRequest(BaseModel):
    username: str = Field(min_length=1, max_length=100)
    password: str = Field(min_length=1, max_length=200)


class MfaCodeRequest(BaseModel):
    code: str = Field(pattern=r"^\d{6}$")


def _profile(db: Session, user: User) -> dict:
    role = db.get(Role, user.role_id)
    branch = db.get(Branch, user.branch_id)
    return {
        "id": user.id,
        "username": user.username,
        "full_name": user.full_name,
        "role_id": user.role_id,
        "role_name": role.name if role is not None else "",
        "branch_id": user.branch_id,
        "branch_name": branch.name if branch is not None else "",
        "mfa_enabled": user.mfa_enabled,
        "permissions": permission_codes(db, user),
    }


@router.post("/login")
def login(body: LoginRequest, db: Session = Depends(get_db)) -> dict:
    try:
        _step, result = begin_login(db, body.username.strip(), body.password)
    except PermissionError as exc:
        raise ApiError(401, "01", "Username atau password tidak sesuai") from exc
    return {"rcode": "00", "message": "Lanjutkan verifikasi autentikator", "result": result}


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


@router.get("/me")
def me(user: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    return {"rcode": "00", "message": "Data berhasil ditampilkan", "result": _profile(db, user)}


@router.post("/logout")
def logout(user: User = Depends(current_user), db: Session = Depends(get_db)) -> dict:
    write_audit(db, user, "auth.logout")
    db.commit()
    return {"rcode": "00", "message": "Logout berhasil", "result": {}}
