import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Debtor, Product, Role, RolePermission, ScoringTransaction, ScoringVersion, User

client = TestClient(app)


def test_scoring_report_hides_other_branches():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch_a = Branch(code=f"L{suffix[:6]}", name="Laporan A")
        branch_b = Branch(code=f"M{suffix[:6]}", name="Laporan B")
        role = Role(code=f"lr{suffix[:6]}", name="Laporan")
        db.add_all([branch_a, branch_b, role])
        db.flush()
        user_a = User(username=f"la-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="A", role_id=role.id, branch_id=branch_a.id)
        user_b = User(username=f"lb-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="B", role_id=role.id, branch_id=branch_b.id)
        db.add_all([user_a, user_b])
        db.flush()
        debtor_a = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}1", full_name="Nasabah A", branch_id=branch_a.id, created_by=user_a.id)
        debtor_b = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}2", full_name="Nasabah B", branch_id=branch_b.id, created_by=user_b.id)
        product = Product(code=f"LP{suffix[:6]}", name="Produk Laporan")
        db.add_all([debtor_a, debtor_b, product])
        db.flush()
        version = ScoringVersion(product_id=product.id, version_no=1, status="draft", created_by=user_a.id)
        db.add(version)
        db.flush()
        db.add_all([
            ScoringTransaction(transaction_no=f"RPT-{suffix}-a", debtor_id=debtor_a.id, product_id=product.id, scoring_version_id=version.id, branch_id=branch_a.id, created_by=user_a.id, status="draft"),
            ScoringTransaction(transaction_no=f"RPT-{suffix}-b", debtor_id=debtor_b.id, product_id=product.id, scoring_version_id=version.id, branch_id=branch_b.id, created_by=user_b.id, status="draft"),
        ])
        db.commit()
        user_a_id, debtor_b_id = user_a.id, debtor_b.id

    headers = {"Authorization": f"Bearer {create_token(user_a_id, PURPOSE_ACCESS, 30)}"}
    rows = client.get("/api/v1/reports/scoring", headers=headers).json()["result"]["items"]
    numbers = {row["transaction_no"] for row in rows}
    assert f"RPT-{suffix}-a" in numbers
    assert f"RPT-{suffix}-b" not in numbers
    hidden = client.get(f"/api/v1/reports/debtors/{debtor_b_id}", headers=headers)
    assert hidden.status_code == 403
    engine.dispose()
