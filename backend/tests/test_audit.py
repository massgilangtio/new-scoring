import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.exc import DBAPIError
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Role, RolePermission, User

client = TestClient(app)


def test_audit_records_before_and_after_and_cannot_be_changed():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch = Branch(code=f"AU{suffix[:4]}", name="Audit Awal")
        other = Branch(code=f"AX{suffix[:4]}", name="Audit Lain")
        role = Role(code=f"au{suffix[:6]}", name="Auditor")
        db.add_all([branch, other, role])
        db.flush()
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'master.manage'")).scalar_one()
        view_all = db.execute(text("SELECT id FROM permissions WHERE code = 'branch.view_all'")).scalar_one()
        db.add(RolePermission(role_id=role.id, permission_id=permission_id))
        wide = Role(code=f"aw{suffix[:6]}", name="Audit Semua")
        db.add(wide)
        db.flush()
        db.add(RolePermission(role_id=wide.id, permission_id=permission_id))
        db.add(RolePermission(role_id=wide.id, permission_id=view_all))
        local = User(username=f"au-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Lokal", role_id=role.id, branch_id=branch.id)
        global_user = User(username=f"aw-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Semua", role_id=wide.id, branch_id=other.id)
        db.add_all([local, global_user])
        db.commit()
        local_id, global_id, branch_id = local.id, global_user.id, branch.id

    headers = {"Authorization": f"Bearer {create_token(local_id, PURPOSE_ACCESS, 30)}"}
    updated = client.patch(
        f"/api/v1/master/branches/{branch_id}",
        headers=headers,
        json={"code": f"AU{suffix[:4]}", "name": "Audit Baru", "is_active": True},
    )
    assert updated.status_code == 200
    local_rows = client.get("/api/v1/audit", headers=headers).json()["result"]["items"]
    match = next(row for row in local_rows if row["action"] == "master.branch_updated" and row["object_id"] == str(branch_id))
    assert match["before"]["name"] == "Audit Awal"
    assert match["after"]["name"] == "Audit Baru"
    other_headers = {"Authorization": f"Bearer {create_token(global_id, PURPOSE_ACCESS, 30)}"}
    wide_ids = {row["id"] for row in client.get("/api/v1/audit", headers=other_headers).json()["result"]["items"]}
    assert match["id"] in wide_ids
    connection = engine.connect()
    transaction = connection.begin()
    failed = False
    try:
        connection.execute(text("UPDATE audit_logs SET action = 'tampered' WHERE id = :id"), {"id": match["id"]})
    except DBAPIError:
        failed = True
        transaction.rollback()
    else:
        transaction.rollback()
    connection.close()
    assert failed is True
    engine.dispose()
