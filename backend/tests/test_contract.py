from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health_and_auth_errors_use_the_same_envelope():
    health = client.get("/health")
    assert health.status_code == 200
    assert set(health.json()) == {"rcode", "message", "result"}
    assert health.json()["rcode"] == "00"

    missing = client.get("/api/v1/auth/me")
    assert missing.status_code == 401
    assert set(missing.json()) == {"rcode", "message", "result"}
    assert missing.json()["rcode"] == "03"

    invalid = client.post("/api/v1/auth/login", json={})
    assert invalid.status_code == 400
    assert set(invalid.json()) == {"rcode", "message", "result"}
    assert invalid.json()["rcode"] == "01"
