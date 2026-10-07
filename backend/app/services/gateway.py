from datetime import datetime
import httpx

from app.core.config import Settings


def call_hris_auth_login(username: str, password: str, settings: Settings) -> dict | bool | None:
    """
    Panggil endpoint Gateway /hris/authLogin (reqid: HR001).
    Mengembalikan:
      - dict (result data) jika autentikasi berhasil (rcode == '00')
      - False jika kredensial ditolak oleh Gateway
      - None jika Gateway tidak aktif / offline / belum dikonfigurasi
    """
    host = settings.gateway_host_url.rstrip("/")
    if not host or host == "http://change-me-gateway-host":
        return None

    url = f"{host}/hris/authLogin"
    payload = {
        "reqid": "HR001",
        "userId": username,
        "password": password,
    }

    if host.endswith("/mock-gateway") or host == "mock":
        from app.api.routes.gateway_mock import HrisAuthRequest, mock_hris_auth_login

        data = mock_hris_auth_login(HrisAuthRequest(**payload))
        if data.get("rcode") == "00":
            return data.get("result") or {}
        return False
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    headers = {
        "Content-Type": "application/json",
        "X-Api-Key": settings.gateway_api_key or "default-key",
        "X-Client-Id": settings.gateway_client_id or "default-client",
        "X-Client-Secret": settings.gateway_client_secret or "default-secret",
        "X-Timestamp": timestamp,
        "X-Signature": settings.gateway_signature or "CABUinapmSdD1j8lIqo8Qvvc+ksRK2g2qDEUlwUfzCM=",
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
        result = data.get("result")
        return result if isinstance(result, dict) else {}
    return False
