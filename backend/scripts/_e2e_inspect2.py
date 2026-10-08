from sqlalchemy import create_engine, text
from app.core.config import get_settings

e = create_engine(get_settings().database_url)
with e.connect() as c:
    for r in c.execute(
        text(
            "SELECT id, scoring_no, status, supervisor_id, created_by, debtor_id, product_id, "
            "total_score, eligibility_status FROM credit_scorings ORDER BY id"
        )
    ).fetchall():
        print("cs", r)
    # distinct parameter names in mapping 1
    names = c.execute(
        text(
            "SELECT parameter_name, count(*) FROM product_parameter_mapping_items "
            "WHERE mapping_id=1 GROUP BY parameter_name ORDER BY min(display_order)"
        )
    ).fetchall()
    print("params", names)
    # reviewer/approval ids and branches vs debtor branch 239
    for r in c.execute(
        text(
            "SELECT u.id,u.username,u.branch_id,b.name,r.code FROM users u "
            "JOIN roles r ON r.id=u.role_id LEFT JOIN branches b ON b.id=u.branch_id "
            "WHERE u.username IN ('reviewer.scoring','approval.scoring','admin.ti2','approval.scoring2')"
        )
    ).fetchall():
        print("user", r)
    print(
        "branch 239",
        c.execute(text("SELECT id,name,code FROM branches WHERE id=239")).fetchone(),
    )
