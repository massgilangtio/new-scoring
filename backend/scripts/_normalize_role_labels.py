"""Normalize role/job-group labels + employee full names for UAT clarity."""
from sqlalchemy import create_engine, text
from app.core.config import get_settings

ROLE_NAMES = {
    "admin_it": "Administrator",
    "operator": "Administrator",
    "approver": "Approver",
    "approval_scoring": "Approver",
    "reviewer": "Reviewer",
    "reviewer_scoring": "Reviewer",
}

JOB_GROUP_NAMES = {
    "administrator": "Administrator",
    "operator": "Administrator",
    "approver": "Approver",
    "approver_scoring": "Approver",
    "reviewer": "Reviewer",
    "reviewer_skoring": "Reviewer",
}

# Distinct employee names (bukan nama role)
USER_NAMES = {
    "admin.ti": "Andi Pratama",
    "admin.ti2": "Dian Kusuma",
    "operator.pst": "Rina Marlina",
    "supervisor.pst": "Hendra Wijaya",
    "reviewer.scoring": "Siti Rahayu",
    "reviewer.scoring2": "Agus Setiawan",
    "approval.scoring": "Bambang Sutrisno",
    "approval.scoring2": "Maya Putri",
}

e = create_engine(get_settings().database_url)
with e.begin() as c:
    for code, name in ROLE_NAMES.items():
        n = c.execute(
            text("UPDATE roles SET name = :n WHERE code = :c"),
            {"n": name, "c": code},
        ).rowcount
        print(f"role {code} -> {name}: {n}")

    for code, name in JOB_GROUP_NAMES.items():
        n = c.execute(
            text("UPDATE job_groups SET name = :n WHERE code = :c"),
            {"n": name, "c": code},
        ).rowcount
        print(f"job_group {code} -> {name}: {n}")

    for username, full_name in USER_NAMES.items():
        n = c.execute(
            text("UPDATE users SET full_name = :n WHERE username = :u"),
            {"n": full_name, "u": username},
        ).rowcount
        print(f"user {username} -> {full_name}: {n}")

    print("--- verify ---")
    for r in c.execute(
        text(
            "SELECT u.username, u.full_name, r.code, r.name, j.name "
            "FROM users u JOIN roles r ON r.id=u.role_id "
            "LEFT JOIN job_groups j ON j.id=u.job_group_id ORDER BY u.id"
        )
    ).fetchall():
        print(r)
