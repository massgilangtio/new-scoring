import pytest
from decimal import Decimal
from fastapi.testclient import TestClient
from sqlalchemy import select

from app.core.security import PURPOSE_ACCESS, create_token
from app.main import app
from app.models.tables import SystemLog, User
from app.db.session import engine


def test_system_logs_records_success_and_errors():
    client = TestClient(app, raise_server_exceptions=False)

    # 1. Test 404
    r_404 = client.get("/api/v1/unknown-test-path")
    assert r_404.status_code == 404

    # 2. Test 401
    r_401 = client.get("/api/v1/auth/me")
    assert r_401.status_code == 401

    # 3. Test API Datatables
    with engine.connect() as conn:
        uid = conn.execute(select(User.id).limit(1)).scalar()

    token = create_token(uid, PURPOSE_ACCESS, 60)
    res = client.get("/api/v1/system-logs/datatables", headers={"Authorization": f"Bearer {token}"})
    assert res.status_code == 200
    data = res.json()["result"]
    assert "items" in data
    assert "stats" in data
    assert data["stats"]["total"] >= 2
    assert data["stats"]["client_error"] >= 2

    # 4. Test API Detail
    latest_id = data["items"][0]["id"]
    detail_res = client.get(f"/api/v1/system-logs/{latest_id}", headers={"Authorization": f"Bearer {token}"})
    assert detail_res.status_code == 200
    detail = detail_res.json()["result"]
    assert "path" in detail
    assert "status_code" in detail
    assert "execution_time_ms" in detail
