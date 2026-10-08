from sqlalchemy import create_engine, text
from app.core.config import get_settings
from app.core.security import hash_password

e = create_engine(get_settings().database_url)
pwd = "ScoringUAT2026!"
with e.begin() as c:
    cols = c.execute(
        text(
            "SELECT column_name FROM information_schema.columns "
            "WHERE table_name='users' AND column_name ILIKE '%mfa%'"
        )
    ).fetchall()
    print("mfa cols", [x[0] for x in cols])
    for r in c.execute(
        text(
            "SELECT id, username, is_active, mfa_enabled, "
            "mfa_secret_encrypted IS NOT NULL AS has_secret "
            "FROM users WHERE username IN "
            "('reviewer.scoring','approval.scoring','admin.ti2')"
        )
    ).fetchall():
        print(r)
    # Disable MFA for UAT users
    n = c.execute(
        text(
            """
      UPDATE users
      SET mfa_enabled = FALSE,
          mfa_secret_encrypted = NULL
      WHERE username IN ('reviewer.scoring','approval.scoring','admin.ti2')
    """
        )
    ).rowcount
    print("disabled mfa rows", n)
    for uname in ("reviewer.scoring", "approval.scoring", "admin.ti2"):
        c.execute(
            text("UPDATE users SET password_hash=:h, is_active=TRUE WHERE username=:u"),
            {"h": hash_password(pwd), "u": uname},
        )
    print("passwords reset")
