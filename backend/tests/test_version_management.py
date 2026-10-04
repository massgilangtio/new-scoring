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


def test_copy_keeps_old_version_and_score():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        product = Product(code=f"VR{suffix[:6]}", name="Produk Versi")
        role = Role(code=f"ver{suffix[:6]}", name="Versi")
        permission_id = db.execute(text("SELECT id FROM permissions WHERE code = 'scoring.configure'")).scalar_one()
        branch_id = db.execute(text("SELECT id FROM branches WHERE code = 'PST'")).scalar_one()
        db.add_all([product, role])
        db.flush()
        db.add(RolePermission(role_id=role.id, permission_id=permission_id))
        user = User(username=f"ver-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Versi", role_id=role.id, branch_id=branch_id)
        db.add(user)
        db.commit()
        product_id, user_id = product.id, user.id

    headers = {"Authorization": f"Bearer {create_token(user_id, PURPOSE_ACCESS, 30)}"}
    version_id = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=headers).json()["result"]["id"]
    parameter_id = client.post(
        f"/api/v1/scoring/versions/{version_id}/parameters",
        headers=headers,
        json={"name": "Pendapatan", "weight": "100"},
    ).json()["result"]["id"]
    option_id = client.post(
        f"/api/v1/scoring/parameters/{parameter_id}/options",
        headers=headers,
        json={"label": "Tinggi", "value": "4"},
    ).json()["result"]["id"]
    client.post(
        f"/api/v1/scoring/versions/{version_id}/thresholds",
        headers=headers,
        json={"min_score": "0", "max_score": "1000", "result_label": "Lama"},
    )
    assert client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=headers).status_code == 200

    copied = client.post(f"/api/v1/scoring/versions/{version_id}/copy", headers=headers)
    assert copied.status_code == 200
    assert copied.json()["result"]["source_status"] == "active"
    clone_id = copied.json()["result"]["id"]
    assert clone_id != version_id

    detail = client.get(f"/api/v1/scoring/versions/{clone_id}", headers=headers).json()["result"]
    clone_parameter = detail["parameters"][0]["id"]
    clone_option = detail["parameters"][0]["options"][0]["id"]
    assert clone_parameter != parameter_id
    changed = client.post(
        f"/api/v1/scoring/parameters/{clone_parameter}/options",
        headers=headers,
        json={"label": "Baru", "value": "9"},
    )
    assert changed.status_code == 200

    original = client.post(
        f"/api/v1/scoring/versions/{version_id}/calculate",
        headers=headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}]},
    )
    assert original.status_code == 200
    assert Decimal(original.json()["result"]["total_score"]) == Decimal("400")
    assert original.json()["result"]["result_label"] == "Lama"

    removed = client.delete(f"/api/v1/scoring/versions/{version_id}", headers=headers)
    assert removed.status_code == 405
    engine.dispose()
