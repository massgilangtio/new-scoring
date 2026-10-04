import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Debtor, Product, Role, RolePermission, User

client = TestClient(app)


def test_return_reopens_and_approval_is_final():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch = Branch(code=f"A{suffix[:6]}", name="Cabang Approval")
        other = Branch(code=f"B{suffix[:6]}", name="Cabang Beda")
        maker_role = Role(code=f"mk{suffix[:6]}", name="Maker")
        approver_role = Role(code=f"ap{suffix[:6]}", name="Approver Uji")
        db.add_all([branch, other, maker_role, approver_role])
        db.flush()
        codes = {
            row[0]: row[1]
            for row in db.execute(text("SELECT code, id FROM permissions WHERE code IN ('scoring.submit','scoring.configure','scoring.assign','scoring.approve')")).all()
        }
        for code in ("scoring.submit", "scoring.configure", "scoring.assign"):
            db.add(RolePermission(role_id=maker_role.id, permission_id=codes[code]))
        db.add(RolePermission(role_id=approver_role.id, permission_id=codes["scoring.approve"]))
        maker = User(username=f"mk-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Maker", role_id=maker_role.id, branch_id=branch.id)
        approver = User(username=f"ap-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Approver", role_id=approver_role.id, branch_id=branch.id)
        outsider = User(username=f"ou-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Luar", role_id=approver_role.id, branch_id=other.id)
        db.add_all([maker, approver, outsider])
        db.flush()
        debtor = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}5", full_name="Debitur Approval", branch_id=branch.id, created_by=maker.id)
        product = Product(code=f"AP{suffix[:6]}", name="Produk Approval")
        db.add_all([debtor, product])
        db.commit()
        maker_id, approver_id, outsider_id = maker.id, approver.id, outsider.id
        debtor_id, product_id = debtor.id, product.id

    maker_headers = {"Authorization": f"Bearer {create_token(maker_id, PURPOSE_ACCESS, 30)}"}
    approver_headers = {"Authorization": f"Bearer {create_token(approver_id, PURPOSE_ACCESS, 30)}"}
    version_id = client.post(f"/api/v1/scoring/products/{product_id}/versions", headers=maker_headers).json()["result"]["id"]
    parameter_id = client.post(f"/api/v1/scoring/versions/{version_id}/parameters", headers=maker_headers, json={"name": "Utama", "weight": "100"}).json()["result"]["id"]
    option_id = client.post(f"/api/v1/scoring/parameters/{parameter_id}/options", headers=maker_headers, json={"label": "Ya", "value": "2"}).json()["result"]["id"]
    client.post(f"/api/v1/scoring/versions/{version_id}/thresholds", headers=maker_headers, json={"min_score": "0", "max_score": "1000", "result_label": "Layak"})
    client.post(f"/api/v1/scoring/versions/{version_id}/activate", headers=maker_headers)
    transaction_id = client.post("/api/v1/transactions", headers=maker_headers, json={"product_id": product_id, "debtor_id": debtor_id}).json()["result"]["id"]
    client.put(
        f"/api/v1/transactions/{transaction_id}/answers",
        headers=maker_headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}], "fields": []},
    )
    client.post(f"/api/v1/transactions/{transaction_id}/submit", headers=maker_headers)

    wrong_branch = client.post(
        f"/api/v1/approvals/{transaction_id}/assign",
        headers=maker_headers,
        json={"approver_id": outsider_id},
    )
    assert wrong_branch.status_code == 403
    assigned = client.post(
        f"/api/v1/approvals/{transaction_id}/assign",
        headers=maker_headers,
        json={"approver_id": approver_id},
    )
    assert assigned.status_code == 200
    missing_reason = client.post(
        f"/api/v1/approvals/{transaction_id}/assign",
        headers=maker_headers,
        json={"approver_id": approver_id},
    )
    assert missing_reason.status_code == 400
    returned = client.post(
        f"/api/v1/approvals/{transaction_id}/decide",
        headers=approver_headers,
        json={"decision": "returned", "note": "Lengkapi data"},
    )
    assert returned.status_code == 200
    reopened = client.put(
        f"/api/v1/transactions/{transaction_id}/answers",
        headers=maker_headers,
        json={"answers": [{"parameter_id": parameter_id, "option_id": option_id}], "fields": []},
    )
    assert reopened.status_code == 200
    client.post(f"/api/v1/transactions/{transaction_id}/submit", headers=maker_headers)
    client.post(
        f"/api/v1/approvals/{transaction_id}/assign",
        headers=maker_headers,
        json={"approver_id": approver_id, "reason": "Tugas ulang"},
    )
    approved = client.post(
        f"/api/v1/approvals/{transaction_id}/decide",
        headers=approver_headers,
        json={"decision": "approved", "note": "Disetujui"},
    )
    assert approved.status_code == 200
    final_change = client.post(
        f"/api/v1/approvals/{transaction_id}/decide",
        headers=approver_headers,
        json={"decision": "rejected", "note": "Diubah"},
    )
    assert final_change.status_code == 403
    engine.dispose()
