import sys
import os
import subprocess
import time
import json
import httpx
import pyotp
import urllib.request
import asyncio

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

def login_and_get_cookies():
    client = httpx.Client(base_url="http://127.0.0.1:8080", follow_redirects=True)
    r1 = client.get("/login")
    csrf = client.cookies.get("csrf_cookie_name")
    
    r2 = client.post("/login", data={
        "csrf_test_name": csrf,
        "username": "admin.ti",
        "password": "Admin123"
    }, headers={"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"})
    
    code = get_totp()
    csrf = client.cookies.get("csrf_cookie_name") or r2.json().get("csrf")
    r3 = client.post("/mfa", data={
        "csrf_test_name": csrf,
        "code": code
    }, headers={"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"})
    
    return client.cookies

def test_with_cdp():
    cookies = login_and_get_cookies()
    ci_session = cookies.get("ci_session")
    csrf_token = cookies.get("csrf_cookie_name")
    print("Logged in. ci_session:", ci_session)
    
    chrome_path = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
    user_data = os.path.abspath("temp_chrome_profile")
    
    # Start Chrome with remote debugging
    chrome_proc = subprocess.Popen([
        chrome_path,
        "--headless=new",
        "--remote-debugging-port=9222",
        f"--user-data-dir={user_data}",
        "--window-size=1400,1200",
        "--disable-gpu",
        "about:blank"
    ])
    
    try:
        time.sleep(2)
        # Query CDP list and select actual browser page
        pages = httpx.get("http://127.0.0.1:9222/json").json()
        page_target = next(p for p in pages if p.get("type") == "page")
        ws_url = page_target["webSocketDebuggerUrl"]
        print("CDP WS URL:", ws_url)
        
        # Use python standard websocket or asyncio
        import websockets
        async def run_cdp():
            async with websockets.connect(ws_url) as ws:
                msg_id = 0
                async def send(method, params=None):
                    nonlocal msg_id
                    msg_id += 1
                    payload = {"id": msg_id, "method": method, "params": params or {}}
                    await ws.send(json.dumps(payload))
                    while True:
                        res = json.loads(await ws.recv())
                        if res.get("id") == msg_id:
                            return res.get("result", {})
                
                await send("Page.enable")
                await send("Network.enable")
                
                # Set cookies
                await send("Network.setCookie", {
                    "name": "ci_session",
                    "value": ci_session,
                    "domain": "127.0.0.1",
                    "path": "/"
                })
                await send("Network.setCookie", {
                    "name": "csrf_cookie_name",
                    "value": csrf_token,
                    "domain": "127.0.0.1",
                    "path": "/"
                })
                
                # Navigate to /scoring/mapping
                print("Navigating to http://127.0.0.1:8080/scoring/mapping...")
                await send("Page.navigate", {"url": "http://127.0.0.1:8080/scoring/mapping"})
                await asyncio.sleep(3.5)
                
                # Click #btnTambahMappingTabel
                print("Clicking #btnTambahMappingTabel...")
                eval_click = await send("Runtime.evaluate", {
                    "expression": """
                        (function() {
                            var btn = document.getElementById('btnTambahMappingTabel');
                            if (btn) {
                                btn.click();
                                return 'CLICKED_BUTTON';
                            }
                            var m = new bootstrap.Modal(document.getElementById('mappingModal'));
                            m.show();
                            return 'OPENED_MODAL';
                        })()
                    """
                })
                print("Click result:", eval_click)
                
                # Wait for modal animation & Select2
                await asyncio.sleep(2.0)
                
                # Take top screenshot
                screenshot = await send("Page.captureScreenshot", {"format": "png"})
                import base64
                img_data = base64.b64decode(screenshot["data"])
                screenshot_path = os.path.abspath("mapping_modal_top_verified.png")
                with open(screenshot_path, "wb") as f:
                    f.write(img_data)
                print(f"Top screenshot saved to {screenshot_path} ({len(img_data)} bytes)")
                
                # Scroll modal body to bottom
                await send("Runtime.evaluate", {
                    "expression": """
                        (function() {
                            var b = document.querySelector('.mapping-modal-body');
                            if (b) b.scrollTop = b.scrollHeight;
                        })()
                    """
                })
                await asyncio.sleep(1.2)
                
                # Take bottom screenshot
                screenshot_bottom = await send("Page.captureScreenshot", {"format": "png"})
                img_data_bottom = base64.b64decode(screenshot_bottom["data"])
                bottom_path = os.path.abspath("mapping_modal_bottom_verified.png")
                with open(bottom_path, "wb") as f:
                    f.write(img_data_bottom)
                print(f"Bottom screenshot saved to {bottom_path} ({len(img_data_bottom)} bytes)")
                
                # Expand modal body to fit entirely and capture full modal screenshot
                await send("Runtime.evaluate", {
                    "expression": """
                        (function() {
                            var b = document.querySelector('.mapping-modal-body');
                            if (b) {
                                b.style.maxHeight = 'none';
                                b.style.overflow = 'visible';
                            }
                        })()
                    """
                })
                await asyncio.sleep(0.8)
                
                # Evaluate bounding rect of mapping modal dialog
                rect_res = await send("Runtime.evaluate", {
                    "expression": """
                        (function() {
                            var el = document.querySelector('.mapping-modal-content');
                            if (!el) return null;
                            var r = el.getBoundingClientRect();
                            return {
                                x: r.left + window.scrollX,
                                y: r.top + window.scrollY,
                                width: r.width,
                                height: r.height,
                                scale: 1
                            };
                        })()
                    """,
                    "returnByValue": True
                })
                clip_rect = rect_res.get("result", {}).get("value")
                
                # Set device metrics for high-res full capture
                if clip_rect:
                    await send("Emulation.setDeviceMetricsOverride", {
                        "width": 1400,
                        "height": int(clip_rect["y"] + clip_rect["height"] + 100),
                        "deviceScaleFactor": 1,
                        "mobile": False
                    })
                    await asyncio.sleep(0.5)
                    full_shot = await send("Page.captureScreenshot", {
                        "format": "png",
                        "clip": clip_rect
                    })
                    full_path = os.path.abspath("mapping_modal_full_verified.png")
                    with open(full_path, "wb") as f:
                        f.write(base64.b64decode(full_shot["data"]))
                    print(f"Full modal screenshot saved to {full_path}")
        asyncio.run(run_cdp())
        
    finally:
        chrome_proc.terminate()
        try:
            chrome_proc.wait(timeout=3)
        except Exception:
            chrome_proc.kill()

if __name__ == "__main__":
    test_with_cdp()
