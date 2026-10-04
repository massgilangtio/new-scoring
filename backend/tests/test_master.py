import uuid
from io import BytesIO

from fastapi.testclient import TestClient
from openpyxl import Workbook
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Role, RolePermission, User

client = TestClient(app)


def token_for(user_id: int) -> str:
    return create_token(user_id, PURPOSE_ACCESS, 30)


def workbook_bytes(rows: list[list[str]]) -> bytes:
    book = Workbook()
    sheet = book.active
    for row in rows:
        sheet.append(row)
    buffer = BytesIO()
    book.save(buffer)
    return buffer.getvalue()


def test_master_data_branch_scope_and_import():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch_a = Branch(code=f"MA{suffix[:6]}", name="Master A")
        branch_b = Branch(code=f"MB{suffix[:6]}", name="Master B")
        local_role = Role(code=f"mlocal{suffix[:6]}", name="Master Lokal")
        plain_role = Role(code=f"mread{suffix[:6]}", name="Hanya Baca")
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'master.manage'")).scalar_one()
        db.add_all([branch_a, branch_b, local_role, plain_role])
        db.flush()
        db.add(RolePermission(role_id=local_role.id, permission_id=permission_id))
        manager = User(
            username=f"mm-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Pengelola Master",
            role_id=local_role.id,
            branch_id=branch_a.id,
        )
        reader = User(
            username=f"mr-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Pembaca",
            role_id=plain_role.id,
            branch_id=branch_a.id,
        )
        db.add_all([manager, reader])
        db.commit()
        manager_id = manager.id
        reader_id = reader.id
        branch_a_id = branch_a.id
        branch_b_id = branch_b.id

    headers = {"Authorization": f"Bearer {token_for(manager_id)}"}
    existing_nik = f"{int(suffix, 16) % 10**15:015d}1"
    new_nik = f"{int(suffix, 16) % 10**15:015d}2"
    created = client.post(
        "/api/v1/master/debtors",
        headers=headers,
        json={"nik": existing_nik, "full_name": "Budi Santoso", "branch_id": branch_a_id},
    )
    assert created.status_code == 200
    short = client.post(
        "/api/v1/master/debtors",
        headers=headers,
        json={"nik": "123", "full_name": "Pendek", "branch_id": branch_a_id},
    )
    assert short.status_code == 400
    other_branch = client.post(
        "/api/v1/master/debtors",
        headers=headers,
        json={"nik": "1271060908900002", "full_name": "Lain Cabang", "branch_id": branch_b_id},
    )
    assert other_branch.status_code == 403
    forbidden = client.post(
        "/api/v1/master/products",
        headers={"Authorization": f"Bearer {token_for(reader_id)}"},
        json={"code": f"P{suffix[:6]}", "name": "Produk Uji"},
    )
    assert forbidden.status_code == 403

    first = workbook_bytes([["NIK", "Nama"], [new_nik, "Ani"], [existing_nik, "Budi Diperbarui"]])
    imported = client.post(
        "/api/v1/master/debtors/import",
        headers=headers,
        files={"file": ("debtors.xlsx", first, "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")},
    )
    assert imported.status_code == 200
    assert imported.json()["result"]["created"] == 1
    assert imported.json()["result"]["updated"] == 1
    listed = client.get("/api/v1/master/debtors", headers=headers)
    names = {item["nik"]: item for item in listed.json()["result"]["items"]}
    assert names[existing_nik]["full_name"] == "Budi Diperbarui"
    assert names[existing_nik]["branch_id"] == branch_a_id
    assert names[new_nik]["branch_id"] == branch_a_id

    reader_list = client.get("/api/v1/master/debtors", headers={"Authorization": f"Bearer {token_for(reader_id)}"})
    reader_niks = [item["nik"] for item in reader_list.json()["result"]["items"]]
    assert new_nik in reader_niks
    engine.dispose()
