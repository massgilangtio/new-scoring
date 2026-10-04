from pathlib import Path

import psycopg
from alembic import op

revision = "0005_scoring_configure"
down_revision = "0004_master_permission"
branch_labels = None
depends_on = None


def _exec(filename: str) -> None:
    sql = (Path(__file__).resolve().parents[1] / "sql" / filename).read_text(encoding="utf-8")
    connection = op.get_bind()
    raw = connection.connection.driver_connection
    with psycopg.ClientCursor(raw) as cursor:
        cursor.execute(sql)


def upgrade() -> None:
    _exec("005_scoring_configure.sql")


def downgrade() -> None:
    _exec("005_scoring_configure_downgrade.sql")
