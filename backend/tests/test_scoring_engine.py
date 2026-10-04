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


def test_engine_uses_configuration_and_rejects_manual_score():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        product = Product(code=f"EN{suffix[:6]}", name="Produk Mesin")
        role = Role(code=f"eng{suffix[:6]}", name="Mesin")
        reader = Role(code=f"erd{suffix[:6]}", name="Tanpa Skor")
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'scoring.configure'")).scalar_one()
        branch_id = db.execute(text("SELECT id FROM branches WHERE code = 'PST'")).scalar_one()
        db.add_all([product, role, reader])
        db.flush()
        db.add(RolePermission(role_id=role.id, permission_id=permission_id))
        user = User(username=f"eng-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Mesin", role_id=role.id, branch_id=branch_id)
        other = User(username=f"erd-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Tanpa", role_id=reader.id, branch_id=branch_id)
        db.add_all([user, other])
        db.commit()
        product_id, user_id, other_id = product.id, user.id, other.id

    headers = {"Authorization": f"Bearer {create_token(user_id, PURPOSE_ACCESS, 30)}"}
    version_id = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=headers).json()["result"]["id"]
    income = client.post(f"/api/v1/scoring/versions/{version_id}/parameters", headers=headers, json={"name": "Pendapatan", "weight": "60"}).json()["result"]["id"]
    high = client.post(f"/api/v1/scoring/parameters/{income}/options", headers=headers, json={"label": "Tinggi", "value": "5"}).json()["result"]["id"]
    client.post(f"/api/v1/scoring/parameters/{income}/options", headers=headers, json={"label": "Rendah", "value": "1"})
    collateral = client.post(f"/api/v1/scoring/versions/{version_id}/parameters", headers=headers, json={"name": "Jaminan", "weight": "40"}).json()["result"]["id"]
    present = client.post(f"/api/v1/scoring/parameters/{collateral}/options", headers=headers, json={"label": "Ada", "value": "3"}).json()["result"]["id"]
    client.post(f"/api/v1/scoring/versions/{version_id}/thresholds", headers=headers, json={"min_score": "0", "max_score": "1000", "result_label": "Layak"})

    manual = client.post(
        f"/api/v1/scoring/versions/{version_id}/calculate",
        headers=headers,
        json={"answers": [{"parameter_id": income, "option_id": high}], "total_score": "999"},
    )
    assert manual.status_code == 400

    missing = client.post(
        f"/api/v1/scoring/versions/{version_id}/calculate",
        headers=headers,
        json={"answers": [{"parameter_id": income, "option_id": high}]},
    )
    assert missing.status_code == 400

    calculated = client.post(
        f"/api/v1/scoring/versions/{version_id}/calculate",
        headers=headers,
        json={"answers": [{"parameter_id": income, "option_id": high}, {"parameter_id": collateral, "option_id": present}]},
    )
    assert calculated.status_code == 200
    body = calculated.json()["result"]
    assert Decimal(body["total_score"]) == Decimal("420")
    assert body["result_label"] == "Layak"
    assert Decimal(body["lines"][0]["line_score"]) == Decimal("300")

    denied = client.post(
        f"/api/v1/scoring/versions/{version_id}/calculate",
        headers={"Authorization": f"Bearer {create_token(other_id, PURPOSE_ACCESS, 30)}"},
        json={"answers": [{"parameter_id": income, "option_id": high}, {"parameter_id": collateral, "option_id": present}]},
    )
    assert denied.status_code == 403
    engine.dispose()
