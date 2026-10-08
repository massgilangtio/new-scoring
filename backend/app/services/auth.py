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
from app.models.tables import Branch, JobGroup, Role, User, UserHris
from app.services.audit import write_audit
from app.services.authorization import permission_codes
from app.services.gateway import call_hris_auth_login

_UNKNOWN_USER_HASH = hash_password("unused-timing-padding")
_DEFAULT_MFA_SECRET = "JBSWY3DPEHPK3PXP"


def find_user(db: Session, username: str) -> User | None:
    return db.scalar(select(User).where(User.username == username))


def access_token_for(user: User) -> str:
    return create_token(user.id, PURPOSE_ACCESS, get_settings().access_token_minutes)


def build_user_profile(db: Session, user: User) -> dict:
    role = db.get(Role, user.role_id)
    branch = db.get(Branch, user.branch_id)
    hris = db.scalar(
        select(UserHris).where(
            (UserHris.npp == user.username)
            | (UserHris.userid == user.username)
            | (UserHris.userid == f"u{user.username}")
        )
    )
    job_group = db.get(JobGroup, user.job_group_id) if user.job_group_id else None

    # Tentukan data profil riil sesuai kebutuhan session pengguna
    npp = (hris.npp if hris and hris.npp else user.username).strip()
    nama = (hris.nama if hris and hris.nama else user.full_name).strip()
    jabatan = (hris.nm_jabatan if hris and hris.nm_jabatan else (job_group.nama_kel_jabatan if job_group else "")).strip()
    rolenm = role.name if role is not None else ""
    roleid = role.code if role is not None else ""
    branchid = (hris.branchid if hris and hris.branchid else (branch.code if branch else "001")).strip()

    # Ambil branch_name yang akurat dari tabel branches
    branch_obj = db.scalar(select(Branch).where(Branch.code == branchid)) if branchid else None
    if branch_obj is None and user.branch_id:
        branch_obj = db.get(Branch, user.branch_id)
    if branch_obj and user.branch_id != branch_obj.id:
        user.branch_id = branch_obj.id
        db.flush()
    branch_name = branch_obj.name if branch_obj is not None else (branch.name if branch is not None else "KANTOR PUSAT")

    id_unit_kerja = (hris.id_unit_kerja if hris and hris.id_unit_kerja else "").strip()
    nm_unit_kerja = (hris.nm_unit_kerja if hris and hris.nm_unit_kerja else "").strip()
    id_kel_jabatan = (job_group.id_kel_jabatan if job_group else (hris.id_kel_jabatan if hris else "")).strip()
    nama_kel_jabatan = (job_group.nama_kel_jabatan if job_group else (hris.nama_kel_jabatan if hris else "")).strip()

    return {
        # Atribut utama yang diminta user untuk disimpan di session
        "npp": npp,
        "nama": nama,
        "jabatan": jabatan,
        "rolenm": rolenm,
        "roleid": roleid,
        "branchid": branchid,
        "branch_name": branch_name,
        "branchnm": branch_name,
        "branch_code": branchid,
        "id_unit_kerja": id_unit_kerja,
        "nm_unit_kerja": nm_unit_kerja,
        "id_kel_jabatan": id_kel_jabatan,
        "nama_kel_jabatan": nama_kel_jabatan,
        # Field kompatibilitas
        "id": user.id,
        "username": user.username,
        "full_name": nama,
        "role_id": user.role_id,
        "role_name": rolenm,
        "job_group_id": user.job_group_id,
        "job_group_code": id_kel_jabatan,
        "job_group_name": nama_kel_jabatan,
        "branch_id": branch_obj.id if branch_obj else user.branch_id,
        "mfa_enabled": user.mfa_enabled,
        "permissions": permission_codes(db, user),
    }


def begin_login(db: Session, username: str, password: str) -> tuple[str, dict]:
    settings = get_settings()
    hris_result = call_hris_auth_login(username, password, settings)

    hris_row = db.scalar(
        select(UserHris).where(
            (UserHris.npp == username) | (UserHris.userid == username) | (UserHris.userid == f"u{username}")
        )
    )

    if isinstance(hris_result, dict):
        user = find_user(db, username)
        # Sesuai aturan: stsauth di tbl_userhris
        # 0 = tidak menggunakan MFA
        # 1 = pakai MFA
        uses_mfa = bool(hris_row and hris_row.stsauth == 1)
        has_hris_secret = bool(uses_mfa and hris_row.secret_key)

        # Identifikasi branchid & unit kerja dari data HRIS
        branch_id_str = str(
            (hris_row and hris_row.branchid)
            or hris_result.get("branchCode")
            or hris_result.get("_raw", {}).get("kd_unit_penempatan")
            or ""
        ).strip()
        unit_kerja_str = str(
            (hris_row and hris_row.id_unit_kerja)
            or hris_result.get("unitKerjaId")
            or hris_result.get("_raw", {}).get("id_unit_kerja")
            or ""
        ).strip()

        # Aturan Divisi TI: branchid == '001' (atau 'PST') dan unit kerja == '665' -> role SUPERADMIN & kelompok jabatan 999
        is_divisi_ti = (branch_id_str in ("001", "PST") and unit_kerja_str == "665")

        if is_divisi_ti:
            target_role = db.scalar(select(Role).where(Role.code == "SUPERADMIN"))
            target_jg = db.scalar(select(JobGroup).where(JobGroup.id_kel_jabatan == "999"))
            if target_jg is None and target_role:
                target_jg = JobGroup(
                    id_kel_jabatan="999",
                    nama_kel_jabatan="Divisi Teknologi Informasi",
                    role_id=target_role.id,
                    total_pegawai=1,
                    is_active=True,
                )
                db.add(target_jg)
                db.flush()
            if hris_row:
                hris_row.id_kel_jabatan = "999"
                hris_row.nama_kel_jabatan = "Divisi Teknologi Informasi"
        else:
            kel_jab_id = str((hris_row and hris_row.id_kel_jabatan) or "").strip()
            target_jg = db.scalar(select(JobGroup).where(JobGroup.id_kel_jabatan == kel_jab_id)) if kel_jab_id else None
            if target_jg and target_jg.role_id:
                target_role = db.get(Role, target_jg.role_id)
            else:
                target_role = db.scalar(select(Role).where(Role.code == "SUPERADMIN")) or db.scalar(select(Role))

        # Resolve branch dari branch_id_str data HRIS
        target_branch = None
        if branch_id_str:
            target_branch = db.scalar(select(Branch).where(Branch.code == branch_id_str))
        if target_branch is None:
            target_branch = db.scalar(select(Branch).where(Branch.code.in_(("001", "PST")))) or db.scalar(select(Branch))

        if user is None:
            user = User(
                username=username,
                full_name=hris_result.get("full_name") or (hris_row.nama if hris_row else f"Pegawai HRIS ({username})"),
                password_hash=hash_password(password),
                role_id=target_role.id if target_role else 1,
                job_group_id=target_jg.id if target_jg else None,
                branch_id=target_branch.id if target_branch else 1,
                is_active=True,
                mfa_enabled=uses_mfa,
                mfa_secret_encrypted=encrypt_mfa_secret(hris_row.secret_key) if has_hris_secret else None,
                mfa_confirmed_at=datetime.now(UTC) if has_hris_secret else None,
            )
            db.add(user)
            db.commit()
            db.refresh(user)
        else:
            user.password_hash = hash_password(password)
            user.is_active = True
            if target_role:
                user.role_id = target_role.id
            if target_jg:
                user.job_group_id = target_jg.id
            if target_branch:
                user.branch_id = target_branch.id
            user.mfa_enabled = uses_mfa
            if has_hris_secret:
                user.mfa_secret_encrypted = encrypt_mfa_secret(hris_row.secret_key)
                if not user.mfa_confirmed_at:
                    user.mfa_confirmed_at = datetime.now(UTC)
            elif not uses_mfa:
                user.mfa_secret_encrypted = None
                user.mfa_last_step = None
                user.mfa_confirmed_at = None
            db.commit()
    else:
        user = find_user(db, username)
        password_ok = user is not None and verify_password(password, user.password_hash)
        if user is None:
            verify_password(password, _UNKNOWN_USER_HASH)
        if user is None or not password_ok or not user.is_active:
            if user is not None:
                write_audit(db, user, "auth.login_failed", reason="Kredensial tidak sesuai atau user nonaktif")
                db.commit()
            raise PermissionError("credentials")

        if hris_row is not None:
            uses_mfa = (hris_row.stsauth == 1)
            user.mfa_enabled = uses_mfa
            if hris_row.branchid:
                matched_b = db.scalar(select(Branch).where(Branch.code == str(hris_row.branchid).strip()))
                if matched_b and user.branch_id != matched_b.id:
                    user.branch_id = matched_b.id
            if not uses_mfa:
                user.mfa_secret_encrypted = None
                user.mfa_last_step = None
                user.mfa_confirmed_at = None
            elif hris_row.secret_key and not user.mfa_secret_encrypted:
                user.mfa_secret_encrypted = encrypt_mfa_secret(hris_row.secret_key)
                user.mfa_confirmed_at = datetime.now(UTC)
            db.commit()

    role = db.get(Role, user.role_id)
    if role is None or not role.is_active:
        write_audit(db, user, "auth.login_failed", reason="Role tidak aktif")
        db.commit()
        raise PermissionError("credentials")

    settings = get_settings()

    # 1. stsauth == 0: TIDAK MENGGUNAKAN MFA (Langsung masuk)
    if not user.mfa_enabled:
        write_audit(db, user, "auth.login_succeeded", reason="Login langsung tanpa MFA (stsauth=0)")
        db.commit()
        return "direct", {
            "step": "direct",
            "access_token": access_token_for(user),
            "user": build_user_profile(db, user),
        }

    # 2. stsauth == 1: PAKAI MFA
    # Jika sudah punya secret key dan sudah terkonfirmasi
    if user.mfa_secret_encrypted and user.mfa_confirmed_at:
        token = create_token(user.id, PURPOSE_MFA_VERIFY, settings.mfa_token_minutes)
        return "mfa_verify", {"step": "mfa_verify", "mfa_token": token}

    # Belum setup secret key / baru diaktifkan: buka MFA Setup (QR Code)
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

    # Simpan secret_key ke tbl_userhris
    hris_row = db.scalar(
        select(UserHris).where(
            (UserHris.npp == user.username) | (UserHris.userid == user.username) | (UserHris.userid == f"u{user.username}")
        )
    )
    if hris_row is not None:
        hris_row.secret_key = secret
        hris_row.stsauth = 1
        hris_row.updated_at = datetime.now()

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

    # Pastikan secret_key sinkron ke tbl_userhris
    hris_row = db.scalar(
        select(UserHris).where(
            (UserHris.npp == user.username) | (UserHris.userid == user.username) | (UserHris.userid == f"u{user.username}")
        )
    )
    if hris_row is not None:
        if not hris_row.secret_key:
            hris_row.secret_key = secret
        hris_row.stsauth = 1
        hris_row.updated_at = datetime.now()

    write_audit(db, user, "auth.login_succeeded")
    db.commit()
    return access_token_for(user)


def reset_user_mfa(db: Session, user: User) -> tuple[str, str, str]:
    """
    Reset MFA untuk user:
    - mfa_enabled = False
    - tbl_userhris.secret_key = None, stsauth = 0
    - generate secret baru, encrypt dan simpan di user.mfa_secret_encrypted
    - return (secret, uri, qr_svg)
    """
    secret = new_totp_secret()
    user.mfa_enabled = False
    user.mfa_secret_encrypted = encrypt_mfa_secret(secret)
    user.mfa_last_step = None
    user.mfa_confirmed_at = None

    hris_row = db.scalar(
        select(UserHris).where(
            (UserHris.npp == user.username) | (UserHris.userid == user.username) | (UserHris.userid == f"u{user.username}")
        )
    )
    if hris_row is not None:
        hris_row.secret_key = None
        hris_row.stsauth = 0
        hris_row.updated_at = datetime.now()

    db.commit()
    uri = provisioning_uri(secret, user.username)
    svg = qr_svg(uri)
    return secret, uri, svg
