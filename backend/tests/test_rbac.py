import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, select, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import create_token, hash_password
from app.core.security import PURPOSE_ACCESS
from app.main import app
from app.models.tables import Branch, Role, RolePermission, User
from app.services.authorization import ACCESS_MANAGE, BRANCH_VIEW_ALL

client = TestClient(app)


def token_for(user_id: int) -> str:
    return create_token(user_id, PURPOSE_ACCESS, 30)


def test_branch_scope_follows_permission_not_role_name():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch_a = Branch(code=f"a{suffix}", name="Cabang A")
        branch_b = Branch(code=f"b{suffix}", name="Cabang B")
        permission_manage = db.execute(text("SELECT id FROM permissions WHERE code = :code"), {"code": ACCESS_MANAGE}).scalar_one()
        permission_all = db.execute(text("SELECT id FROM permissions WHERE code = :code"), {"code": BRANCH_VIEW_ALL}).scalar_one()
        local_role = Role(code=f"local{suffix}", name="Pengelola Cabang")
        wide_role = Role(code=f"wide{suffix}", name="Pengelola Pusat")
        db.add_all([branch_a, branch_b, local_role, wide_role])
        db.flush()
        db.add_all([
            RolePermission(role_id=local_role.id, permission_id=permission_manage),
            RolePermission(role_id=wide_role.id, permission_id=permission_manage),
            RolePermission(role_id=wide_role.id, permission_id=permission_all),
        ])
        local_user = User(
            username=f"local-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Lokal",
            role_id=local_role.id,
            branch_id=branch_a.id,
        )
        other_user = User(
            username=f"other-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Lain",
            role_id=local_role.id,
            branch_id=branch_b.id,
        )
        wide_user = User(
            username=f"wide-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Luas",
            role_id=wide_role.id,
            branch_id=branch_a.id,
        )
        db.add_all([local_user, other_user, wide_user])
        db.commit()
        local_id, other_id, wide_id = local_user.id, other_user.id, wide_user.id
        role_ids = [local_role.id, wide_role.id]
        branch_ids = [branch_a.id, branch_b.id]

    try:
        headers = {"Authorization": f"Bearer {token_for(local_id)}"}
        listed = client.get("/api/v1/access/users", headers=headers)
        assert listed.status_code == 200
        usernames = [item["username"] for item in listed.json()["result"]["items"]]
        assert f"local-{suffix}" in usernames
        assert f"other-{suffix}" not in usernames

        denied = client.post(
            "/api/v1/access/users",
            headers=headers,
            json={
                "username": f"new-{suffix}",
                "password": "Rahasia-Uji-123",
                "full_name": "Baru",
                "role_id": role_ids[0],
                "branch_id": branch_ids[1],
            },
        )
        assert denied.status_code == 403
        assert denied.json()["rcode"] == "04"

        created = client.post(
            "/api/v1/access/users",
            headers={"Authorization": f"Bearer {token_for(wide_id)}"},
            json={
                "username": f"new-{suffix}",
                "password": "Rahasia-Uji-123",
                "full_name": "Baru",
                "role_id": role_ids[0],
                "branch_id": branch_ids[1],
            },
        )
        assert created.status_code == 200
        assert created.json()["rcode"] == "00"
    finally:
        engine.dispose()


def test_me_reports_permissions_and_reviewer_cannot_manage_access():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with engine.begin() as connection:
        branch_id = connection.execute(
            text("INSERT INTO branches (code, name) VALUES (:code, 'Cabang') RETURNING id"),
            {"code": f"r{suffix}"},
        ).scalar_one()
        role_id = connection.execute(text("SELECT id FROM roles WHERE code = 'reviewer'")).scalar_one()
        user_id = connection.execute(
            text(
                """
                INSERT INTO users (username, password_hash, full_name, role_id, branch_id)
                VALUES (:username, :password_hash, 'Reviewer Uji', :role_id, :branch_id)
                RETURNING id
                """
            ),
            {
                "username": f"rev-{suffix}",
                "password_hash": hash_password("Rahasia-Uji-123"),
                "role_id": role_id,
                "branch_id": branch_id,
            },
        ).scalar_one()
    headers = {"Authorization": f"Bearer {token_for(user_id)}"}
    me = client.get("/api/v1/auth/me", headers=headers)
    assert me.status_code == 200
    assert me.json()["result"]["permissions"] == ["scoring.submit"]
    forbidden = client.get("/api/v1/access/users", headers=headers)
    assert forbidden.status_code == 403
    assert forbidden.json()["rcode"] == "04"
    with engine.begin() as connection:
        connection.execute(text("DELETE FROM users WHERE id = :id"), {"id": user_id})
        connection.execute(text("DELETE FROM branches WHERE id = :id"), {"id": branch_id})
