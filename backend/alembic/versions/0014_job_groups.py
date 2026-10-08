from pathlib import Path

import psycopg
from alembic import op

revision = "0014_job_groups"
down_revision = "0013_menu_permissions"
branch_labels = None
depends_on = None


def _exec(filename: str) -> None:
    sql = (Path(__file__).resolve().parents[1] / "sql" / filename).read_text(encoding="utf-8")
    connection = op.get_bind()
    raw = connection.connection.driver_connection
    with psycopg.ClientCursor(raw) as cursor:
        cursor.execute(sql)


def upgrade() -> None:
    _exec("014_job_groups.sql")


def downgrade() -> None:
    _exec("014_job_groups_downgrade.sql")
