from sqlalchemy import select

from app.core.config import get_settings
from app.core.security import hash_password
from app.db.session import SessionLocal
from app.models.tables import Branch, Role, User


def bootstrap() -> None:
    settings = get_settings()
    required = {
        "BOOTSTRAP_USERNAME": settings.bootstrap_username,
        "BOOTSTRAP_PASSWORD": settings.bootstrap_password,
        "BOOTSTRAP_FULL_NAME": settings.bootstrap_full_name,
        "BOOTSTRAP_ROLE_CODE": settings.bootstrap_role_code,
        "BOOTSTRAP_ROLE_NAME": settings.bootstrap_role_name,
        "BOOTSTRAP_BRANCH_CODE": settings.bootstrap_branch_code,
        "BOOTSTRAP_BRANCH_NAME": settings.bootstrap_branch_name,
    }
    missing = [name for name, value in required.items() if not value]
    if missing:
        raise SystemExit("Variabel bootstrap belum diisi")
    if len(settings.bootstrap_password) < 12:
        raise SystemExit("Password bootstrap minimal 12 karakter")

    db = SessionLocal()
    try:
        existing = db.scalar(select(User).where(User.username == settings.bootstrap_username))
        if existing is not None:
            print("Bootstrap dilewati: user sudah ada")
            return
        branch = db.scalar(select(Branch).where(Branch.code == settings.bootstrap_branch_code))
        if branch is None:
            branch = Branch(code=settings.bootstrap_branch_code, name=settings.bootstrap_branch_name)
            db.add(branch)
            db.flush()
        role = db.scalar(select(Role).where(Role.code == settings.bootstrap_role_code))
        if role is None:
            role = Role(code=settings.bootstrap_role_code, name=settings.bootstrap_role_name)
            db.add(role)
            db.flush()
        db.add(
            User(
                username=settings.bootstrap_username,
                password_hash=hash_password(settings.bootstrap_password),
                full_name=settings.bootstrap_full_name,
                role_id=role.id,
                branch_id=branch.id,
            )
        )
        db.commit()
        print("Bootstrap user dibuat")
    finally:
        db.close()


if __name__ == "__main__":
    bootstrap()
