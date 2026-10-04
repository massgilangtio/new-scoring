import uuid
from datetime import UTC, datetime, timedelta
from urllib.parse import parse_qs, urlparse

import pyotp
from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text

from app.core.config import get_settings
from app.core.security import hash_password
from app.main import app

client = TestClient(app)


def _secret(uri: str) -> str:
    return parse_qs(urlparse(uri).query)["secret"][0]


def _next_code(uri: str) -> str:
    totp = pyotp.TOTP(_secret(uri))
    moment = datetime.now(UTC) + timedelta(seconds=30)
    return totp.at(moment)


def test_login_requires_authenticator_then_issues_access_token():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    username = f"auth-{suffix}"
    password = "Rahasia-Uji-123"
    with engine.begin() as connection:
        branch_id = connection.execute(
            text("INSERT INTO branches (code, name) VALUES (:code, 'Cabang Uji') RETURNING id"),
            {"code": f"AU{suffix}"},
        ).scalar_one()
        role_id = connection.execute(
            text("INSERT INTO roles (code, name) VALUES (:code, 'Role Uji') RETURNING id"),
            {"code": f"RU{suffix}"},
        ).scalar_one()
        user_id = connection.execute(
            text(
                """
                INSERT INTO users (username, password_hash, full_name, role_id, branch_id)
                VALUES (:username, :password_hash, 'Pengguna Uji', :role_id, :branch_id)
                RETURNING id
                """
            ),
            {
                "username": username,
                "password_hash": hash_password(password),
                "role_id": role_id,
                "branch_id": branch_id,
            },
        ).scalar_one()

    try:
        bad = client.post("/api/v1/auth/login", json={"username": username, "password": "salah-sekali"})
        assert bad.status_code == 401
        assert bad.json()["rcode"] == "01"

        setup = client.post("/api/v1/auth/login", json={"username": username, "password": password})
        assert setup.status_code == 200
        setup_body = setup.json()
        assert setup_body["rcode"] == "00"
        assert setup_body["result"]["step"] == "mfa_setup"
        assert setup_body["result"]["qr_svg"].startswith("<svg")
        totp = pyotp.TOTP(_secret(setup_body["result"]["otpauth_uri"]))
        current = totp.now()
        wrong = "000000" if current != "000000" else "111111"
        setup_token = setup_body["result"]["mfa_token"]

        rejected = client.post(
            "/api/v1/auth/mfa/confirm",
            json={"code": wrong},
            headers={"Authorization": f"Bearer {setup_token}"},
        )
        assert rejected.status_code == 401
        assert rejected.json()["rcode"] == "02"

        confirmed = client.post(
            "/api/v1/auth/mfa/confirm",
            json={"code": current},
            headers={"Authorization": f"Bearer {setup_token}"},
        )
        assert confirmed.status_code == 200
        access = confirmed.json()["result"]["access_token"]
        me = client.get("/api/v1/auth/me", headers={"Authorization": f"Bearer {access}"})
        assert me.status_code == 200
        assert me.json()["result"]["username"] == username
        assert me.json()["result"]["mfa_enabled"] is True

        second = client.post("/api/v1/auth/login", json={"username": username, "password": password})
        assert second.json()["result"]["step"] == "mfa_verify"
        verify_token = second.json()["result"]["mfa_token"]
        replay = client.post(
            "/api/v1/auth/mfa/verify",
            json={"code": current},
            headers={"Authorization": f"Bearer {verify_token}"},
        )
        assert replay.status_code == 401
        verified = client.post(
            "/api/v1/auth/mfa/verify",
            json={"code": _next_code(setup_body["result"]["otpauth_uri"])},
            headers={"Authorization": f"Bearer {verify_token}"},
        )
        assert verified.status_code == 200
        second_access = verified.json()["result"]["access_token"]
        logged_out = client.post("/api/v1/auth/logout", headers={"Authorization": f"Bearer {second_access}"})
        assert logged_out.json()["rcode"] == "00"

        with engine.connect() as connection:
            actions = connection.execute(
                text("SELECT action FROM audit_logs WHERE actor_user_id = :id ORDER BY id"),
                {"id": user_id},
            ).scalars().all()
        assert "auth.login_failed" in actions
        assert "auth.mfa_failed" in actions
        assert "auth.mfa_enabled" in actions
        assert "auth.login_succeeded" in actions
        assert "auth.logout" in actions
    finally:
        engine.dispose()
