from datetime import datetime
import httpx

from app.core.config import Settings


def _encrypt_with_gateway(host: str, text: str, headers: dict) -> str | None:
    """
    Panggil endpoint Gateway /hashing (reqid: 00000) untuk mengenkripsi text.
    """
    url = f"{host}/hashing"
    payload = {
        "reqid": "00000",
        "encrypted": text,
    }
    try:
        resp = httpx.post(url, json=payload, headers=headers, timeout=10)
        data = resp.json()
        if data.get("rcode") == "00":
            return data.get("result", {}).get("encrypted")
    except Exception:
        pass
    return None


def call_hris_auth_login(username: str, password: str, settings: Settings) -> dict | bool | None:
    """
    Panggil endpoint Gateway /hris/authLogin (reqid: HR001).
    Mengembalikan:
      - dict (profile pegawai) jika autentikasi berhasil (rcode == '00')
      - False jika kredensial ditolak oleh Gateway (rcode == '99')
      - None jika Gateway tidak aktif / offline / belum dikonfigurasi
    """
    host = settings.gateway_host_url.rstrip("/")
    if not host or host == "http://change-me-gateway-host":
        return None

    # Jika mock lokal, gunakan mock router in-process
    if host.endswith("/mock-gateway") or host == "mock":
        from app.api.routes.gateway_mock import HrisAuthRequest, mock_hris_auth_login

        data = mock_hris_auth_login(HrisAuthRequest(reqid="HR001", userId=username, password=password))
        if data.get("rcode") == "00":
            return data.get("result") or {}
        return False

    # Standar header Gateway
    headers = {
        "Content-Type": "application/json",
        "X-Api-Key": settings.gateway_api_key or "default-key",
        "X-Client-Id": settings.gateway_client_id or "99",
    }
    if settings.gateway_client_secret and settings.gateway_client_secret != "-":
        headers["X-Client-Secret"] = settings.gateway_client_secret
    if settings.gateway_signature and settings.gateway_signature != "-":
        headers["X-Signature"] = settings.gateway_signature

    # 1. Enkripsi username dan password via /hashing jika belum terenkripsi
    enc_user = username
    if len(username) < 30:  # bukan base64 ciphertext
        enc_user = _encrypt_with_gateway(host, username, headers) or username

    enc_pass = password
    if len(password) < 30:  # bukan base64 ciphertext
        enc_pass = _encrypt_with_gateway(host, password, headers) or password

    # 2. Panggil /hris/authLogin
    url = f"{host}/hris/authLogin"
    payload = {
        "reqid": "HR001",
        "userId": enc_user,
        "password": enc_pass,
    }

    try:
        resp = httpx.post(url, json=payload, headers=headers, timeout=10)
        data = resp.json()
    except Exception:
        return None

    if not isinstance(data, dict):
        return None

    rcode = data.get("rcode") or data.get("statusId") or data.get("responseCode")
    if rcode == "00":
        # Response Gateway nyata mengembalikan list pada field 'data'
        items = data.get("data")
        profile = items[0] if isinstance(items, list) and items else data.get("result") or {}

        # Petakan profil pegawai HRIS ke format sistem
        nama = profile.get("nama") or profile.get("full_name") or f"Pegawai {username}"
        unit_kerja = profile.get("nm_unit_kerja") or profile.get("unitKerjaName") or ""
        jabatan = profile.get("nm_jabatan") or profile.get("jabdef") or ""
        email = profile.get("user_email") or profile.get("email") or ""
        kd_penempatan = profile.get("kd_unit_penempatan") or "001"

        return {
            "userId": profile.get("nama_login") or username,
            "full_name": nama,
            "email": email,
            "unitKerjaId": profile.get("id_unit_kerja") or profile.get("unitKerjaId"),
            "unitKerjaName": unit_kerja,
            "jabatan": jabatan,
            "nrik": profile.get("nrik"),
            "branchCode": kd_penempatan,
            "role": "admin_it",  # Divisi Teknologi Informasi dipetakan ke Admin IT
            "status": "AKTIF",
            "_raw": profile,
        }

    return False


def call_hris_inq_master_pegawai_by_kondisi(
    settings: Settings,
    userid: str = "",
    kondisi: str = "",
    id_unit_kerja: str = "",
) -> list[dict] | None:
    """
    Panggil endpoint Gateway /hris/inqMasterPegawaiByKondisi (reqid: HR006).
    """
    host = settings.gateway_host_url.rstrip("/")
    if not host or host == "http://change-me-gateway-host":
        return None

    headers = {
        "Content-Type": "application/json",
        "X-Api-Key": settings.gateway_api_key or "default-key",
        "X-Client-Id": settings.gateway_client_id or "99",
    }
    if settings.gateway_client_secret and settings.gateway_client_secret != "-":
        headers["X-Client-Secret"] = settings.gateway_client_secret
    if settings.gateway_signature and settings.gateway_signature != "-":
        headers["X-Signature"] = settings.gateway_signature

    url = f"{host}/hris/inqMasterPegawaiByKondisi"
    payload = {
        "reqid": "HR006",
        "userid": userid,
        "kondisi": kondisi,
    }
    if id_unit_kerja:
        payload["id_unit_kerja"] = id_unit_kerja

    try:
        resp = httpx.post(url, json=payload, headers=headers, timeout=15)
        data = resp.json()
        if data.get("rcode") == "00":
            return data.get("data") or []
    except Exception:
        pass
    return None

