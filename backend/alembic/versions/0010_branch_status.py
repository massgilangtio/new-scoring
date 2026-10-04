from pathlib import Path

import psycopg
from alembic import op

revision = "0010_branch_status"
down_revision = "0009_product_type"
branch_labels = None
depends_on = None


def _exec(filename: str) -> None:
    sql = (Path(__file__).resolve().parents[1] / "sql" / filename).read_text(encoding="utf-8")
    connection = op.get_bind()
    raw = connection.connection.driver_connection
    with psycopg.ClientCursor(raw) as cursor:
        cursor.execute(sql)


def upgrade() -> None:
    _exec("010_branch_status.sql")


def downgrade() -> None:
    _exec("010_branch_status_downgrade.sql")
