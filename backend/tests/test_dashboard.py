import uuid

from fastapi.testclient import TestClient
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, create_token, hash_password
from app.main import app
from app.models.tables import Branch, Debtor, Product, Role, RolePermission, ScoringTransaction, ScoringVersion, User

client = TestClient(app)


def test_dashboard_is_scoped_to_the_user_branch():
    engine = create_engine(get_settings().database_url)
    suffix = uuid.uuid4().hex[:8]
    with Session(engine) as db:
        branch_a = Branch(code=f"D{suffix[:6]}", name="Dasbor A")
        branch_b = Branch(code=f"E{suffix[:6]}", name="Dasbor B")
        role = Role(code=f"ds{suffix[:6]}", name="Pembaca Dasbor")
        wide = Role(code=f"dw{suffix[:6]}", name="Semua Cabang")
        db.add_all([branch_a, branch_b, role, wide])
        db.flush()
        view_all = db.execute(text("SELECT id FROM permissions WHERE code = 'branch.view_all'")).scalar_one()
        db.add(RolePermission(role_id=wide.id, permission_id=view_all))
        local = User(username=f"dl-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Lokal", role_id=role.id, branch_id=branch_a.id)
        other = User(username=f"do-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Lain", role_id=role.id, branch_id=branch_b.id)
        global_user = User(username=f"dg-{suffix}", password_hash=hash_password("Rahasia-Uji-123"), full_name="Global", role_id=wide.id, branch_id=branch_a.id)
        db.add_all([local, other, global_user])
        db.flush()
        debtor_a = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}7", full_name="A", branch_id=branch_a.id, created_by=local.id)
        debtor_b = Debtor(nik=f"{int(suffix, 16) % 10**15:015d}8", full_name="B", branch_id=branch_b.id, created_by=other.id)
        product = Product(code=f"DB{suffix[:6]}", name="Produk Dasbor")
        db.add_all([debtor_a, debtor_b, product])
        db.flush()
        version = ScoringVersion(product_id=product.id, version_no=1, status="draft", created_by=local.id)
        db.add(version)
        db.flush()
        db.add_all([
            ScoringTransaction(transaction_no=f"TMP-{suffix}-a", debtor_id=debtor_a.id, product_id=product.id, scoring_version_id=version.id, branch_id=branch_a.id, created_by=local.id, status="approved"),
            ScoringTransaction(transaction_no=f"TMP-{suffix}-b", debtor_id=debtor_b.id, product_id=product.id, scoring_version_id=version.id, branch_id=branch_b.id, created_by=other.id, status="draft"),
        ])
        db.commit()
        local_id, global_id = local.id, global_user.id

    local_body = client.get("/api/v1/dashboard", headers={"Authorization": f"Bearer {create_token(local_id, PURPOSE_ACCESS, 30)}"}).json()["result"]
    global_body = client.get("/api/v1/dashboard", headers={"Authorization": f"Bearer {create_token(global_id, PURPOSE_ACCESS, 30)}"}).json()["result"]
    local_numbers = {row["transaction_no"] for row in local_body["recent"]}
    global_numbers = {row["transaction_no"] for row in global_body["recent"]}
    assert local_body["branch_scope"] == "own"
    assert global_body["branch_scope"] == "all"
    assert f"TMP-{suffix}-a" in local_numbers
    assert f"TMP-{suffix}-b" not in local_numbers
    assert f"TMP-{suffix}-a" in global_numbers and f"TMP-{suffix}-b" in global_numbers
    engine.dispose()
