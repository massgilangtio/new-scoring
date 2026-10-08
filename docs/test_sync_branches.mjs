import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'branch-sync-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const MFA_SECRET = 'E52SGKFV4PQZ2UUBE4NJPLNW62IROWJ6';

async function main() {
  console.log('[test] Launching browser...');
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });
  const page = await context.newPage();

  // 1. Login
  console.log('[test] Navigating to login...');
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.fill('#username', '4259');
  await page.fill('#password', '123456');
  await page.click('button[type="submit"], .login-submit');

  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open, #mfaVerify:not([hidden])', { timeout: 15000 });
  const code = authenticator.generate(MFA_SECRET);
  console.log(`[test] Generated TOTP: ${code}`);

  const digits = page.locator('.otp-digit');
  const count = await digits.count();
  if (count >= 6) {
    for (let i = 0; i < 6; i++) {
      await digits.nth(i).click();
      await digits.nth(i).fill(code[i]);
    }
  }
  await page.evaluate((c) => {
    const el = document.getElementById('otpCode');
    if (el) el.value = c;
  }, code);

  await page.locator('#mfaForm button[type="submit"]').click();
  await page.waitForSelector('#sidebar, .app-sidebar, #app', { timeout: 20000 });
  console.log('[test] Logged in successfully!');

  // 2. Navigate to Master Cabang
  console.log('[test] Navigating to /master/branches...');
  await page.goto(`${BASE}/master/branches`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);

  const shot1 = path.join(outDir, '01-master-branches-table.png');
  await page.screenshot({ path: shot1, fullPage: true });
  console.log(`[test] Screenshot 1 saved: ${shot1}`);

  // 3. Click "Sinkronkan dari Core Gateway" button
  console.log('[test] Clicking Sinkronkan dari Core Gateway button...');
  await page.click('#btnSyncBranch');

  // Wait for page reload after sync
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);

  const shot2 = path.join(outDir, '02-master-branches-after-sync.png');
  await page.screenshot({ path: shot2, fullPage: true });
  console.log(`[test] Screenshot 2 saved: ${shot2}`);

  await browser.close();
  console.log('[test] Done all steps!');
}

main().catch(err => {
  console.error('[test] Error:', err);
  process.exit(1);
});
