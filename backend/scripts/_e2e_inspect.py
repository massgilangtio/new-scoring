from sqlalchemy import create_engine, text
from app.core.config import get_settings

e = create_engine(get_settings().database_url)
with e.connect() as c:
    tables = c.execute(
        text(
            "SELECT table_name FROM information_schema.tables "
            "WHERE table_schema='public' AND table_name ILIKE '%scor%' ORDER BY 1"
        )
    ).fetchall()
    print("tables", [t[0] for t in tables])
    for tname in [r[0] for r in tables]:
        n = c.execute(text(f"SELECT count(*) FROM {tname}")).scalar()
        print(f"  {tname}: {n}")
    print(
        "versions active",
        c.execute(
            text(
                "SELECT id, product_id, version_no, status FROM scoring_versions "
                "WHERE status='active' LIMIT 15"
            )
        ).fetchall(),
    )
    print(
        "prod329 versions",
        c.execute(
            text("SELECT id, version_no, status FROM scoring_versions WHERE product_id=329")
        ).fetchall(),
    )
    # sample options for mapping items
    cols = c.execute(
        text(
            "SELECT column_name FROM information_schema.columns "
            "WHERE table_name='product_parameter_mapping_items' ORDER BY ordinal_position"
        )
    ).fetchall()
    print("map item cols", [x[0] for x in cols])
    for r in c.execute(
        text(
            "SELECT id, parameter_name, code, description, weight, value, total "
            "FROM product_parameter_mapping_items WHERE mapping_id=1 LIMIT 5"
        )
    ).fetchall():
        print("item", r)
