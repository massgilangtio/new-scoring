import httpx
import pyotp
import re
from sqlalchemy import select
from sqlalchemy.orm import Session
from app.db.session import engine
from app.models.tables import User
from app.core.security import decrypt_mfa_secret

def get_totp():
    with Session(engine) as db:
        u = db.scalar(select(User).where(User.username == 'admin.ti'))
        secret = decrypt_mfa_secret(u.mfa_secret_encrypted)
        return pyotp.TOTP(secret).now()

client = httpx.Client(base_url='http://127.0.0.1:8080', follow_redirects=True)
client.get('/login')
csrf = client.cookies.get('csrf_cookie_name')
r2 = client.post('/login', data={'csrf_test_name': csrf, 'username': 'admin.ti', 'password': 'Admin123'}, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'})
code = get_totp()
csrf = client.cookies.get('csrf_cookie_name') or r2.json().get('csrf')
client.post('/mfa', data={'csrf_test_name': csrf, 'code': code}, headers={'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'})

urls = [
    ('/scoring/credit', '1. Scoring Kredit'),
    ('/transactions/new', '2. Pengajuan Scoring'),
    ('/transactions', '3. Daftar Scoring'),
    ('/rescore', '4. Request Scoring Ulang'),
]

print("=== TESTING THE 4 MENUS ===")
for path, label in urls:
    res = client.get(path)
    has_error = "Whoops!" in res.text or "An uncaught Exception" in res.text
    has_404 = "404 - File Not Found" in res.text or "Halaman Tidak Ditemukan" in res.text
    t_match = re.search(r"<title>(.*?)</title>", res.text)
    page_title = t_match.group(1).strip() if t_match else "-"
    h_match = re.search(r"<h[1-4][^>]*>(.*?)</h[1-4]>", res.text)
    header_text = re.sub(r"<[^>]+>", "", h_match.group(1)).strip() if h_match else "-"

    print(f"[{label}]")
    print(f"  URL: {res.url}")
    print(f"  HTTP Status: {res.status_code}")
    print(f"  Page Title: {page_title}")
    print(f"  Header Text: {header_text}")
    print(f"  Has Error/Exception: {has_error}")
    print(f"  Has 404: {has_404}")
    print(f"  Response Length: {len(res.text)} bytes")
    print()
