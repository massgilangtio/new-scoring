import uuid
from decimal import Decimal

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Product, Role, RolePermission, User

client = TestClient(app)


def test_version_activates_only_when_weight_is_100_and_then_locks():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        product = Product(code=f"SC{suffix[:6]}", name="Produk Skor")
        role = Role(code=f"scfg{suffix[:6]}", name="Konfigurator")
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'scoring.configure'")).scalar_one()
        branch_id = db.execute(text("SELECT id FROM branches WHERE code = 'PST'")).scalar_one()
        db.add_all([product, role])
        db.flush()
        db.add(RolePermission(role_id=role.id, permission_id=permission_id))
        user = User(
            username=f"cfg-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Konfigurator",
            role_id=role.id,
            branch_id=branch_id,
        )
        db.add(user)
        db.commit()
        product_id = product.id
        user_id = user.id

    headers = {"Authorization": f"Bearer {create_token(user_id, PURPOSE_ACCESS, 30)}"}
    denied = client.post(f"/api/v1/scoring/products/{product_id}/versions")
    assert denied.status_code == 401

    created = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=headers)
    assert created.status_code == 200
    version_id = created.json()["result"]["id"]
    first = client.post(
        f"/api/v1/scoring/versions/{version_id}/parameters",
        headers=headers,
        json={"name": "Pendapatan", "weight": "60"},
    )
    parameter_id = first.json()["result"]["id"]
    client.post(
        f"/api/v1/scoring/parameters/{parameter_id}/options",
        headers=headers,
        json={"label": "Tinggi", "value": "5"},
    )
    early = client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=headers)
    assert early.status_code == 400
    assert early.json()["message"] == "Total weight harus 100 sebelum aktivasi"

    second = client.post(
        f"/api/v1/scoring/versions/{version_id}/parameters",
        headers=headers,
        json={"name": "Jaminan", "weight": "40"},
    )
    second_id = second.json()["result"]["id"]
    client.post(
        f"/api/v1/scoring/parameters/{second_id}/options",
        headers=headers,
        json={"label": "Ada", "value": "3"},
    )
    client.post(
        f"/api/v1/scoring/versions/{version_id}/fields",
        headers=headers,
        json={"field_key": "catatan", "label": "Catatan", "field_type": "textarea"},
    )
    activated = client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=headers)
    assert activated.status_code == 200

    locked = client.post(
        f"/api/v1/scoring/versions/{version_id}/parameters",
        headers=headers,
        json={"name": "Tambahan", "weight": "0"},
    )
    assert locked.status_code == 400

    again = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=headers)
    second_version = again.json()["result"]["id"]
    only = client.post(
        f"/api/v1/scoring/versions/{second_version}/parameters",
        headers=headers,
        json={"name": "Tunggal", "weight": "100"},
    )
    only_id = only.json()["result"]["id"]
    client.post(
        f"/api/v1/scoring/parameters/{only_id}/options",
        headers=headers,
        json={"label": "Ya", "value": "1"},
    )
    switched = client.post(f"/api/v1/scoring/versions/{second_version}/activate", headers=headers)
    assert switched.status_code == 200
    listing = client.get(f"/api/v1/scoring/products/{product_id}/versions", headers=headers)
    statuses = {item["id"]: item["status"] for item in listing.json()["result"]["items"]}
    assert statuses[version_id] == "inactive"
    assert statuses[second_version] == "active"
    assert Decimal("100") == Decimal("60") + Decimal("40")
    engine.dispose()
