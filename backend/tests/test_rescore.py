import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Debtor, Product, Role, RolePermission, User

client = TestClient(app)


def test_rescore_request_is_consumed_and_duplicate_keeps_source():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch = Branch(code=f"R{suffix[:6]}", name="Cabang Ulang")
        maker_role = Role(code=f"rm{suffix[:6]}", name="Pengaju Ulang")
        approver_role = Role(code=f"ra{suffix[:6]}", name="Penyetuju Ulang")
        db.add_all([branch, maker_role, approver_role])
        db.flush()
        codes = {
            row[0]: row[1]
            for row in db.execute(
                text(
                    "SELECT code, id FROM permissions WHERE code IN "
                    "('scoring.submit','scoring.configure','scoring.approve','scoring.assign')"
                )
            ).all()
        }
        for code in ("scoring.submit", "scoring.configure", "scoring.assign"):
            db.add(RolePermission(role_id=maker_role.id, permission_id=codes[code]))
        db.add(RolePermission(role_id=approver_role.id, permission_id=codes["scoring.approve"]))
        maker = User(
            username=f"rm-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Pengaju",
            role_id=maker_role.id,
            branch_id=branch.id,
        )
        approver = User(
            username=f"ra-{suffix}",
            password_hash=hash_password("Rahasia-Uji-123"),
            full_name="Penyetuju",
            role_id=approver_role.id,
            branch_id=branch.id,
        )
        db.add_all([maker, approver])
        db.flush()
        debtor = Debtor(
            nik=f"{int(suffix, 16) % 10**15:015d}6",
            full_name="Ulang",
            branch_id=branch.id,
            created_by=maker.id,
        )
        product = Product(code=f"RU{suffix[:6]}", name="Produk Ulang")
        db.add_all([debtor, product])
        db.commit()
        maker_id, approver_id = maker.id, approver.id
        debtor_id, product_id = debtor.id, product.id

    maker_headers = {"Authorization": f"Bearer {create_token(maker_id, PURPOSE_ACCESS, 30)}"}
    approver_headers = {"Authorization": f"Bearer {create_token(approver_id, PURPOSE_ACCESS, 30)}"}
    version_id = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=maker_headers).json()["result"]["id"]
    parameter_id = client.post(
        f"/api/v1/scoring/versions/{version_id}/parameters",
        headers=maker_headers,
        json={"name": "Utama", "weight": "100"},
    ).json()["result"]["id"]
    option_id = client.post(
        f"/api/v1/scoring/parameters/{parameter_id}/options",
        headers=maker_headers,
        json={"label": "Ya", "value": "2"},
    ).json()["result"]["id"]
    client.post(
        f"/api/v1/scoring/versions/{version_id}/thresholds",
        headers=maker_headers,
        json={"min_score": "0", "max_score": "1000", "result_label": "Layak"},
    )
    client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=maker_headers)
    first_id = client.post(
        "/api/v1/transactions",
        headers=maker_headers,
        json={"product_id": product_id, "debtor_id": debtor_id},
    ).json()["result"]["id"]
    client.put(
        f"/api/v1/transactions/{first_id}/answers",
        headers=maker_headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}], "fields": []},
    )
    client.post(f"/api/v1/transactions/{first_id}/submit", headers=maker_headers)
    client.post(f"/api/v1/approvals/{first_id}/assign", headers=maker_headers, json={"approver_id": approver_id})
    client.post(
        f"/api/v1/approvals/{first_id}/decide",
        headers=approver_headers,
        json={"decision": "approved", "note": "Disetujui"},
    )

    blocked = client.post(
        "/api/v1/transactions",
        headers=maker_headers,
        json={"product_id": product_id, "debtor_id": debtor_id},
    )
    assert blocked.status_code == 400
    denied_self = client.post(
        "/api/v1/rescore",
        headers=maker_headers,
        json={"debtor_id": debtor_id, "product_id": product_id, "reason": "Perlu penilaian baru"},
    )
    request_id = denied_self.json()["result"]["id"]
    assert client.post(f"/api/v1/rescore/{request_id}/approve", headers=maker_headers).status_code == 403
    assert client.post(f"/api/v1/rescore/{request_id}/approve", headers=approver_headers).status_code == 200

    second = client.post(
        "/api/v1/transactions",
        headers=maker_headers,
        json={"product_id": product_id, "debtor_id": debtor_id},
    )
    assert second.status_code == 200
    consumed = client.get("/api/v1/rescore", headers=maker_headers).json()["result"]["items"][0]
    assert consumed["status"] == "consumed"

    assert client.put(
        "/api/v1/rescore/duplicate-setting",
        headers=maker_headers,
        json={"enabled": False},
    ).status_code == 200
    assert client.post(f"/api/v1/transactions/{first_id}/duplicate", headers=maker_headers).status_code == 400
    assert client.put(
        "/api/v1/rescore/duplicate-setting",
        headers=maker_headers,
        json={"enabled": True},
    ).status_code == 200
    newer = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=maker_headers).json()["result"]["id"]
    new_parameter = client.post(
        f"/api/v1/scoring/versions/{newer}/parameters",
        headers=maker_headers,
        json={"name": "Baru", "weight": "100"},
    ).json()["result"]["id"]
    client.post(f"/api/v1/scoring/parameters/{new_parameter}/options", headers=maker_headers, json={"label": "Ya", "value": "9"})
    client.post(
        f"/api/v1/scoring/versions/{newer}/thresholds",
        headers=maker_headers,
        json={"min_score": "0", "max_score": "2000", "result_label": "Baru"},
    )
    client.post(f"/api/v1/scoring/versions/{newer}/activate", headers=maker_headers)
    duplicated = client.post(f"/api/v1/transactions/{first_id}/duplicate", headers=maker_headers)
    assert duplicated.status_code == 200
    assert duplicated.json()["result"]["duplicated_from_id"] == first_id
    source = client.get(f"/api/v1/transactions/{first_id}", headers=maker_headers).json()["result"]
    clone = client.get(f"/api/v1/transactions/{duplicated.json()['result']['id']}", headers=maker_headers).json()["result"]
    assert source["status"] == "approved"
    assert source["snapshot"]["result_label"] == "Layak"
    assert clone["version"]["id"] == newer
    assert clone["version"]["status"] == "active"
    client.put("/api/v1/rescore/duplicate-setting", headers=maker_headers, json={"enabled": False})
    engine.dispose()
