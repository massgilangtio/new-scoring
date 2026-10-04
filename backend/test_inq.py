import urllib.request
import json
from app.api.deps import get_db
from app.models.tables import User
from app.services.auth import access_token_for
from sqlalchemy import select

def test():
    db = next(get_db())
    user = db.scalar(select(User).where(User.username == "operator.pst"))
    token = access_token_for(user)
    print("TOKEN generated successfully for user:", user.username)

    # 1. Test CIF yang belum ada (fallback ke gateway -> not found / not configured)
    req = urllib.request.Request(
        "http://127.0.0.1:8000/api/v1/master/debtors/inquiry-cif/0101727664",
        headers={"Authorization": f"Bearer {token}"}
    )
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode())
        print("INQUIRY RESULT (0101727664):")
        print(json.dumps(data, indent=2))

if __name__ == "__main__":
    test()
