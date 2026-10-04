from sqlalchemy import create_engine, text
from sqlalchemy.exc import DBAPIError
import pytest
from fastapi.testclient import TestClient

from app.core.config import get_settings
from app.main import app


@pytest.fixture
def db():
    engine = create_engine(get_settings().database_url)
    connection = engine.connect()
    transaction = connection.begin()
    try:
        yield connection
    finally:
        transaction.rollback()
        connection.close()
        engine.dispose()


def expect_denied(db, statement, params=None):
    with pytest.raises(DBAPIError):
        with db.begin_nested():
            db.execute(text(statement), params or {})


def seed(db):
    branch_id = db.execute(
        text("INSERT INTO branches (code, name) VALUES ('MDN', 'Medan') RETURNING id")
    ).scalar_one()
    other_branch_id = db.execute(
        text("INSERT INTO branches (code, name) VALUES ('BTG', 'Binjai') RETURNING id")
    ).scalar_one()
    role_id = db.execute(
        text("INSERT INTO roles (code, name) VALUES ('schema_reviewer', 'Schema Reviewer') RETURNING id")
    ).scalar_one()
    user_id = db.execute(
        text(
            """
            INSERT INTO users (username, password_hash, full_name, role_id, branch_id)
            VALUES ('reviewer.mdn', 'hash', 'Reviewer Medan', :role_id, :branch_id)
            RETURNING id
            """
        ),
        {"role_id": role_id, "branch_id": branch_id},
    ).scalar_one()
    other_user_id = db.execute(
        text(
            """
            INSERT INTO users (username, password_hash, full_name, role_id, branch_id)
            VALUES ('reviewer.btg', 'hash', 'Reviewer Binjai', :role_id, :branch_id)
            RETURNING id
            """
        ),
        {"role_id": role_id, "branch_id": other_branch_id},
    ).scalar_one()
    product_id = db.execute(
        text("INSERT INTO products (code, name) VALUES ('KMG', 'Kredit Umum') RETURNING id")
    ).scalar_one()
    debtor_id = db.execute(
        text(
            """
            INSERT INTO debtors (nik, full_name, branch_id, created_by)
            VALUES ('1271060908900001', 'Budi Santoso', :branch_id, :created_by)
            RETURNING id
            """
        ),
        {"branch_id": branch_id, "created_by": user_id},
    ).scalar_one()
    return {
        "branch_id": branch_id,
        "other_branch_id": other_branch_id,
        "role_id": role_id,
        "user_id": user_id,
        "other_user_id": other_user_id,
        "product_id": product_id,
        "debtor_id": debtor_id,
    }


def insert_version(db, ids, weight_a=60, weight_b=40):
    version_id = db.execute(
        text(
            """
            INSERT INTO scoring_versions (product_id, version_no, created_by)
            VALUES (:product_id, 1, :created_by)
            RETURNING id
            """
        ),
        {"product_id": ids["product_id"], "created_by": ids["user_id"]},
    ).scalar_one()
    for name, weight in (("Pendapatan", weight_a), ("Jaminan", weight_b)):
        parameter_id = db.execute(
            text(
                """
                INSERT INTO scoring_parameters (scoring_version_id, name, weight)
                VALUES (:version_id, :name, :weight)
                RETURNING id
                """
            ),
            {"version_id": version_id, "name": name, "weight": weight},
        ).scalar_one()
        db.execute(
            text(
                """
                INSERT INTO scoring_parameter_options (scoring_parameter_id, label, value)
                VALUES (:parameter_id, 'Ya', 5)
                """
            ),
            {"parameter_id": parameter_id},
        )
    return version_id


def test_initial_setting_and_table_count(db):
    setting = db.execute(
        text("SELECT setting_value FROM system_settings WHERE setting_key = 'duplicate_enabled'")
    ).scalar_one()
    table_count = db.execute(
        text(
            """
            SELECT count(*)
            FROM information_schema.tables
            WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
            """
        )
    ).scalar_one()
    assert setting in {"true", "false"}
    assert table_count == 27


def test_nik_must_be_16_digits(db):
    ids = seed(db)
    expect_denied(
        db,
        """
        INSERT INTO debtors (nik, full_name, branch_id, created_by)
        VALUES ('123', 'Pendek', :branch_id, :created_by)
        """,
        {"branch_id": ids["branch_id"], "created_by": ids["user_id"]},
    )


def test_user_requires_one_branch_and_one_role(db):
    expect_denied(
        db,
        """
        INSERT INTO users (username, password_hash, full_name)
        VALUES ('tanpa-akses', 'hash', 'Tanpa Akses')
        """,
    )


def test_activation_requires_weight_100_and_then_locks_configuration(db):
    ids = seed(db)
    version_id = insert_version(db, ids, weight_a=50, weight_b=40)
    expect_denied(
        db,
        "UPDATE scoring_versions SET status = 'active' WHERE id = :id",
        {"id": version_id},
    )

    balanced_id = insert_version(db, ids | {"product_id": _second_product(db)}, weight_a=60, weight_b=40)
    db.execute(text("UPDATE scoring_versions SET status = 'active' WHERE id = :id"), {"id": balanced_id})
    status = db.execute(
        text("SELECT status FROM scoring_versions WHERE id = :id"),
        {"id": balanced_id},
    ).scalar_one()
    assert status == "active"
    expect_denied(
        db,
        "UPDATE scoring_parameters SET weight = 10 WHERE scoring_version_id = :id",
        {"id": balanced_id},
    )


def test_only_one_active_version_per_product(db):
    ids = seed(db)
    first_id = insert_version(db, ids)
    db.execute(text("UPDATE scoring_versions SET status = 'active' WHERE id = :id"), {"id": first_id})
    second_id = db.execute(
        text(
            """
            INSERT INTO scoring_versions (product_id, version_no, created_by)
            VALUES (:product_id, 2, :created_by)
            RETURNING id
            """
        ),
        {"product_id": ids["product_id"], "created_by": ids["user_id"]},
    ).scalar_one()
    db.execute(
        text(
            """
            INSERT INTO scoring_parameters (scoring_version_id, name, weight)
            VALUES (:version_id, 'Tunggal', 100)
            """
        ),
        {"version_id": second_id},
    )
    parameter_id = db.execute(
        text("SELECT id FROM scoring_parameters WHERE scoring_version_id = :id"),
        {"id": second_id},
    ).scalar_one()
    db.execute(
        text(
            """
            INSERT INTO scoring_parameter_options (scoring_parameter_id, label, value)
            VALUES (:parameter_id, 'Ya', 1)
            """
        ),
        {"parameter_id": parameter_id},
    )
    expect_denied(
        db,
        "UPDATE scoring_versions SET status = 'active' WHERE id = :id",
        {"id": second_id},
    )
    db.execute(text("UPDATE scoring_versions SET status = 'inactive' WHERE id = :id"), {"id": first_id})
    db.execute(text("UPDATE scoring_versions SET status = 'active' WHERE id = :id"), {"id": second_id})
    active_count = db.execute(
        text(
            """
            SELECT count(*) FROM scoring_versions
            WHERE product_id = :product_id AND status = 'active'
            """
        ),
        ids,
    ).scalar_one()
    assert active_count == 1


def test_transaction_branch_matches_reviewer_and_inputs_lock_after_submit(db):
    ids = seed(db)
    version_id = insert_version(db, ids)
    expect_denied(
        db,
        """
        INSERT INTO scoring_transactions (
            transaction_no, debtor_id, product_id, scoring_version_id, branch_id, created_by
        ) VALUES (
            'TX-1', :debtor_id, :product_id, :version_id, :other_branch_id, :user_id
        )
        """,
        ids | {"version_id": version_id},
    )
    transaction_id = db.execute(
        text(
            """
            INSERT INTO scoring_transactions (
                transaction_no, debtor_id, product_id, scoring_version_id, branch_id, created_by
            ) VALUES (
                'TX-1', :debtor_id, :product_id, :version_id, :branch_id, :user_id
            )
            RETURNING id
            """
        ),
        ids | {"version_id": version_id},
    ).scalar_one()
    parameter_id = db.execute(
        text("SELECT id FROM scoring_parameters WHERE scoring_version_id = :id LIMIT 1"),
        {"id": version_id},
    ).scalar_one()
    option_id = db.execute(
        text("SELECT id FROM scoring_parameter_options WHERE scoring_parameter_id = :id LIMIT 1"),
        {"id": parameter_id},
    ).scalar_one()
    db.execute(
        text(
            """
            INSERT INTO scoring_inputs (transaction_id, scoring_parameter_id, scoring_parameter_option_id)
            VALUES (:transaction_id, :parameter_id, :option_id)
            """
        ),
        {"transaction_id": transaction_id, "parameter_id": parameter_id, "option_id": option_id},
    )
    db.execute(
        text("UPDATE scoring_transactions SET status = 'submitted' WHERE id = :id"),
        {"id": transaction_id},
    )
    expect_denied(
        db,
        "DELETE FROM scoring_inputs WHERE transaction_id = :id",
        {"id": transaction_id},
    )


def test_reassignment_requires_reason_and_same_branch(db):
    ids = seed(db)
    version_id = insert_version(db, ids)
    transaction_id = db.execute(
        text(
            """
            INSERT INTO scoring_transactions (
                transaction_no, debtor_id, product_id, scoring_version_id, branch_id, created_by
            ) VALUES (
                'TX-2', :debtor_id, :product_id, :version_id, :branch_id, :user_id
            )
            RETURNING id
            """
        ),
        ids | {"version_id": version_id},
    ).scalar_one()
    db.execute(
        text(
            """
            INSERT INTO approver_assignments (
                transaction_id, revision_no, approver_id, assigned_by
            ) VALUES (:transaction_id, 1, :user_id, :user_id)
            """
        ),
        {"transaction_id": transaction_id, "user_id": ids["user_id"]},
    )
    expect_denied(
        db,
        """
        INSERT INTO approver_assignments (
            transaction_id, revision_no, approver_id, assigned_by
        ) VALUES (:transaction_id, 1, :user_id, :user_id)
        """,
        {"transaction_id": transaction_id, "user_id": ids["user_id"]},
    )
    expect_denied(
        db,
        """
        INSERT INTO approver_assignments (
            transaction_id, revision_no, approver_id, reason, assigned_by
        ) VALUES (:transaction_id, 1, :other_user_id, 'Pindah cabang', :user_id)
        """,
        {
            "transaction_id": transaction_id,
            "other_user_id": ids["other_user_id"],
            "user_id": ids["user_id"],
        },
    )
    db.execute(
        text(
            """
            INSERT INTO approver_assignments (
                transaction_id, revision_no, approver_id, reason, assigned_by
            ) VALUES (:transaction_id, 1, :user_id, 'Alih tugas', :user_id)
            """
        ),
        {"transaction_id": transaction_id, "user_id": ids["user_id"]},
    )


def test_snapshot_and_audit_are_immutable(db):
    ids = seed(db)
    version_id = insert_version(db, ids)
    transaction_id = db.execute(
        text(
            """
            INSERT INTO scoring_transactions (
                transaction_no, debtor_id, product_id, scoring_version_id, branch_id, created_by
            ) VALUES (
                'TX-3', :debtor_id, :product_id, :version_id, :branch_id, :user_id
            )
            RETURNING id
            """
        ),
        ids | {"version_id": version_id},
    ).scalar_one()
    snapshot_id = db.execute(
        text(
            """
            INSERT INTO scoring_snapshots (
                transaction_id, revision_no, scoring_version_id, total_score,
                result_label, threshold_min, calculated_by
            ) VALUES (
                :transaction_id, 1, :version_id, 300, 'Hasil konfigurasi', 0, :user_id
            )
            RETURNING id
            """
        ),
        {"transaction_id": transaction_id, "version_id": version_id, "user_id": ids["user_id"]},
    ).scalar_one()
    expect_denied(
        db,
        "UPDATE scoring_snapshots SET total_score = 1 WHERE id = :id",
        {"id": snapshot_id},
    )
    db.execute(
        text(
            """
            INSERT INTO audit_logs (
                actor_user_id, actor_role_id, actor_role_name, actor_branch_id,
                action, object_type, object_id
            ) VALUES (
                :user_id, :role_id, 'Reviewer', :branch_id, 'create', 'scoring_transaction', :object_id
            )
            """
        ),
        ids | {"object_id": str(transaction_id)},
    )
    expect_denied(db, "DELETE FROM audit_logs")


def test_approved_transaction_cannot_change(db):
    ids = seed(db)
    version_id = insert_version(db, ids)
    transaction_id = db.execute(
        text(
            """
            INSERT INTO scoring_transactions (
                transaction_no, debtor_id, product_id, scoring_version_id, branch_id, created_by, status
            ) VALUES (
                'TX-4', :debtor_id, :product_id, :version_id, :branch_id, :user_id, 'approved'
            )
            RETURNING id
            """
        ),
        ids | {"version_id": version_id},
    ).scalar_one()
    expect_denied(
        db,
        "UPDATE scoring_transactions SET status = 'draft' WHERE id = :id",
        {"id": transaction_id},
    )


def test_health_reports_database():
    response = TestClient(app).get("/health")
    assert response.status_code == 200
    body = response.json()
    assert body["rcode"] == "00"
    assert body["result"]["database"] == "ok"


def _second_product(db):
    return db.execute(
        text("INSERT INTO products (code, name) VALUES ('KUR', 'Kredit Usaha') RETURNING id")
    ).scalar_one()
