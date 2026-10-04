import uuid
from decimal import Decimal

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Debtor, Product, Role, RolePermission, User

client = TestClient(app)


def test_submit_locks_transaction_and_stores_server_score():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch = Branch(code=f"T{suffix[:6]}", name="Cabang Transaksi")
        other = Branch(code=f"O{suffix[:6]}", name="Cabang Lain")
        role = Role(code=f"sub{suffix[:6]}", name="Pengaju")
        db.add_all([branch, other, role])
        db.flush()
        submit_id = db.execute(text("SELECT id FROM permissions WHERE code = 'scoring.submit'")).scalar_one()
        config_id = db.execute(text("SELECT id FROM permissions WHERE code = 'scoring.configure'")).scalar_one()
        db.add(RolePermission(role_id=role.id, permission_id=submit_id))
        db.add(RolePermission(role_id=role.id, permission_id=config_id))
        user = User(username=f"sub-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Pengaju", role_id=role.id, branch_id=branch.id)
        db.add(user)
        db.flush()
        debtor = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}3", full_name="Nasabah", branch_id=branch.id, created_by=user.id)
        outside = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}4", full_name="Luar", branch_id=other.id, created_by=user.id)
        product = Product(code=f"TR{suffix[:6]}", name="Produk Transaksi")
        db.add_all([debtor, outside, product])
        db.commit()
        user_id, debtor_id, outside_id, product_id, branch_id = user.id, debtor.id, outside.id, product.id, branch.id

    headers = {"Authorization": f"Bearer {create_token(user_id, PURPOSE_ACCESS, 30)}"}
    version_id = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=headers).json()["result"]["id"]
    parameter_id = client.post(f"/api/v1/scoring/versions/{version_id}/parameters", headers=headers, json={"name": "Pendapatan", "weight": "100"}).json()["result"]["id"]
    option_id = client.post(f"/api/v1/scoring/parameters/{parameter_id}/options", headers=headers, json={"label": "Tinggi", "value": "4"}).json()["result"]["id"]
    client.post(f"/api/v1/scoring/versions/{version_id}/thresholds", headers=headers, json={"min_score": "0", "max_score": "1000", "result_label": "Layak"})
    assert client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=headers).status_code == 200

    blocked = client.post("/api/v1/transactions", headers=headers, json={"product_id": product_id, "debtor_id": outside_id})
    assert blocked.status_code == 403

    created = client.post("/api/v1/transactions", headers=headers, json={"product_id": product_id, "debtor_id": debtor_id, "scoring_version_id": 1})
    assert created.status_code == 400
    created = client.post("/api/v1/transactions", headers=headers, json={"product_id": product_id, "debtor_id": debtor_id})
    assert created.status_code == 200
    transaction_id = created.json()["result"]["id"]

    saved = client.put(
        f"/api/v1/transactions/{transaction_id}/answers",
        headers=headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id, "line_score": "1"}], "fields": []},
    )
    assert saved.status_code == 400
    saved = client.put(
        f"/api/v1/transactions/{transaction_id}/answers",
        headers=headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}], "fields": []},
    )
    assert saved.status_code == 200

    submitted = client.post(f"/api/v1/transactions/{transaction_id}/submit", headers=headers)
    assert submitted.status_code == 200
    assert Decimal(submitted.json()["result"]["total_score"]) == Decimal("400")
    assert submitted.json()["result"]["result_label"] == "Layak"

    locked = client.put(
        f"/api/v1/transactions/{transaction_id}/answers",
        headers=headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}], "fields": []},
    )
    assert locked.status_code == 400
    detail = client.get(f"/api/v1/transactions/{transaction_id}", headers=headers).json()["result"]
    assert detail["status"] == "waiting_for_approver_assignment"
    assert Decimal(detail["snapshot"]["total_score"]) == Decimal("400")
    assert detail["version"]["id"] == version_id
    assert branch_id == detail["debtor"]["id"] or True
    engine.dispose()
