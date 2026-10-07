import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'userhris-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const MFA_SECRET = 'JBSWY3DPEHPK3PXP';

async function main() {
  console.log('[capture userhris] Starting browser...');
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });
  const page = await context.newPage();

  page.on('console', msg => console.log('[browser]', msg.type(), msg.text()));

  // 1. Login
  console.log('[capture userhris] Logging in as 1776...');
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('#username', '1776');
  await page.fill('#password', 'P@ssw0rd');
  await page.click('button[type="submit"], .login-submit');

  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open, #mfaVerify:not([hidden])', { timeout: 15000 });
  const code = authenticator.generate(MFA_SECRET);
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

  // Wait for dashboard to finish loading
  await page.waitForSelector('#sidebar, .app-sidebar, #app', { timeout: 20000 });
  await page.waitForTimeout(1500);

  // 2. Navigate to /access/users
  console.log('[capture userhris] Navigating to /access/users...');
  await page.goto(`${BASE}/access/users`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#usersTable', { timeout: 15000 });
  await page.waitForTimeout(1000);

  const shot1 = path.join(outDir, '01-menu-pengguna-tbl_userhris.png');
  await page.screenshot({ path: shot1, fullPage: true });
  console.log(`[capture userhris] Captured step 1: ${shot1}`);

  // 3. Click detail button on first row (user 1776)
  console.log('[capture userhris] Opening detail modal...');
  await page.evaluate(() => {
    const btn = document.querySelector('.btn-detail-hris');
    if (btn) btn.click();
  });
  await page.waitForSelector('#detailHrisModal.show', { timeout: 8000 });
  await page.waitForTimeout(600);

  const shot2 = path.join(outDir, '02-modal-detail-tbl_userhris.png');
  await page.screenshot({ path: shot2, fullPage: true });
  console.log(`[capture userhris] Captured step 2: ${shot2}`);

  await browser.close();
  console.log('[capture userhris] All steps completed successfully!');
}

main().catch((err) => {
  console.error('[capture error]', err);
  process.exit(1);
});
