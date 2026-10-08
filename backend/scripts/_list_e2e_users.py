from sqlalchemy import create_engine, text
from app.core.config import get_settings

e = create_engine(get_settings().database_url)
with e.connect() as c:
    rows = c.execute(text("""
      SELECT u.username, u.full_name, r.code, u.is_active, u.mfa_enabled
      FROM users u JOIN roles r ON r.id = u.role_id
      WHERE r.code IN ('reviewer_scoring','reviewer','approval_scoring','approver','admin_it','operator')
      ORDER BY r.code, u.username
    """)).fetchall()
    for r in rows:
        print(f"{r.username}|{r.full_name}|{r.code}|active={r.is_active}|mfa={r.mfa_enabled}")

    print("---perms reviewer_scoring---")
    for r in c.execute(text("""
      SELECT p.code FROM permissions p
      JOIN role_permissions rp ON rp.permission_id=p.id
      JOIN roles r ON r.id=rp.role_id WHERE r.code='reviewer_scoring' ORDER BY 1
    """)).fetchall():
        print(r.code)

    print("---perms approval_scoring---")
    for r in c.execute(text("""
      SELECT p.code FROM permissions p
      JOIN role_permissions rp ON rp.permission_id=p.id
      JOIN roles r ON r.id=rp.role_id WHERE r.code='approval_scoring' ORDER BY 1
    """)).fetchall():
        print(r.code)

    print("---product mappings---")
    for r in c.execute(text("""
      SELECT p.id, p.code, p.name, m.id AS mapping_id, m.is_active
      FROM products p
      LEFT JOIN product_parameter_mappings m ON m.product_id=p.id AND m.is_active IS TRUE
      WHERE p.is_active IS TRUE
      ORDER BY p.code LIMIT 10
    """)).fetchall():
        print(f"product={r.id}:{r.code} mapping={r.mapping_id} active={r.is_active}")

    print("---debtors sample---")
    for r in c.execute(text("""
      SELECT id, cis_id, nik, full_name FROM debtors WHERE is_active IS TRUE ORDER BY id LIMIT 5
    """)).fetchall():
        print(f"{r.id}|{r.cis_id}|{r.nik}|{r.full_name}")
