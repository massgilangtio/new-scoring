"""Prepare UAT users with known password + MFA secret (pyotp)."""
from datetime import UTC, datetime

from sqlalchemy import create_engine, text

from app.core.config import get_settings
from app.core.security import encrypt_mfa_secret, hash_password

UAT_PASSWORD = "ScoringUAT2026!"
UAT_MFA_SECRET = "JBSWY3DPEHPK3PXP"  # fixed base32 for UAT

e = create_engine(get_settings().database_url)
with e.begin() as c:
    enc = encrypt_mfa_secret(UAT_MFA_SECRET)
    for uname in ("reviewer.scoring", "approval.scoring", "admin.ti2"):
        n = c.execute(
            text(
                """
          UPDATE users
          SET password_hash = :h,
              is_active = TRUE,
              mfa_enabled = TRUE,
              mfa_secret_encrypted = :s,
              mfa_confirmed_at = :t,
              mfa_last_step = NULL
          WHERE username = :u
        """
            ),
            {"h": hash_password(UAT_PASSWORD), "s": enc, "t": datetime.now(UTC), "u": uname},
        ).rowcount
        print(f"uat-ready {uname}: {n}")

    # ensure active mapping exists
    print(
        "active_maps",
        c.execute(
            text("SELECT count(*) FROM product_parameter_mappings WHERE is_active IS TRUE")
        ).scalar(),
    )

    c.execute(
        text(
            """
      INSERT INTO role_permissions (role_id, permission_id)
      SELECT roles.id, permissions.id
      FROM roles
      JOIN permissions ON permissions.code = 'report.scoring'
      WHERE roles.code IN ('reviewer_scoring', 'approval_scoring')
      ON CONFLICT DO NOTHING
    """
        )
    )
    print("report.scoring granted")
    print("MFA_SECRET", UAT_MFA_SECRET)
    print("PASSWORD", UAT_PASSWORD)
