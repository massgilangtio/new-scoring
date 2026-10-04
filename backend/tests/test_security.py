import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Role, RolePermission, User

client = TestClient(app)


def token_for(user_id: int) -> str:
    return create_token(user_id, PURPOSE_ACCESS, 30)


def test_inactive_role_cannot_keep_a_session_or_log_in():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    password = "Rahasia-Uji-123"
    with Session(engine) as db:
        branch = Branch(code=f"s{suffix}", name="Cabang Keamanan")
        role = Role(code=f"sr{suffix}", name="Role Keamanan", is_active=True)
        db.add_all([branch, role])
        db.flush()
        user = User(
            username=f"sec-{suffix}",
            password_hash=hash_password(password),
            full_name="Keamanan",
            role_id=role.id,
            branch_id=branch.id,
        )
        db.add(user)
        db.commit()
        user_id = user.id
        role_id = role.id

    headers = {"Authorization": f"Bearer {token_for(user_id)}"}
    with Session(engine) as db:
        role = db.get(Role, role_id)
        role.is_active = False
        db.commit()

    blocked = client.get("/api/v1/auth/me", headers=headers)
    assert blocked.status_code == 401
    assert blocked.json()["rcode"] == "03"

    login = client.post("/api/v1/auth/login", json={"username": f"sec-{suffix}", "password": password})
    assert login.status_code == 401
    assert login.json()["rcode"] == "01"
    engine.dispose()


def test_master_manager_cannot_change_another_branch():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch_a = Branch(code=f"ma{suffix}", name="Cabang A")
        branch_b = Branch(code=f"mb{suffix}", name="Cabang B")
        role = Role(code=f"mm{suffix}", name="Master Lokal")
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'master.manage'")).scalar_one()
        db.add_all([branch_a, branch_b, role])
        db.flush()
        db.add(RolePermission(role_id=role.id, permission_id=permission_id))
        user = User(
            username=f"mm-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Master",
            role_id=role.id,
            branch_id=branch_a.id,
        )
        db.add(user)
        db.commit()
        user_id = user.id
        own_id = branch_a.id
        other_id = branch_b.id

    headers = {"Authorization": f"Bearer {token_for(user_id)}"}
    denied = client.patch(
        f"/api/v1/master/branches/{other_id}",
        headers=headers,
        json={"code": f"mb{suffix}", "name": "Diubah", "is_active": True},
    )
    assert denied.status_code == 403
    assert denied.json()["rcode"] == "04"

    allowed = client.patch(
        f"/api/v1/master/branches/{own_id}",
        headers=headers,
        json={"code": f"ma{suffix}", "name": "Cabang A diperbarui", "is_active": True},
    )
    assert allowed.status_code == 200
    assert allowed.json()["rcode"] == "00"
    engine.dispose()


def test_api_schema_is_not_published():
    missing = client.get("/docs")
    assert missing.status_code == 404
    assert missing.json()["rcode"] == "01"
    assert client.get("/openapi.json").status_code == 404
