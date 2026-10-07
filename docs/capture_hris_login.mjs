import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'hris-auth-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const MFA_SECRET = 'JBSWY3DPEHPK3PXP';

async function main() {
  console.log('[capture] Starting browser...');
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });
  const page = await context.newPage();

  page.on('console', msg => console.log('[browser]', msg.type(), msg.text()));
  page.on('requestfailed', req => {
    console.log('[requestfailed]', req.url(), req.failure()?.errorText);
  });
  page.on('response', resp => {
    console.log('[response]', resp.status(), resp.url());
  });

  // 1. Visit Login Page
  console.log('[capture] Navigating to login page...');
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);

  const shot1 = path.join(outDir, '01-login-form-initial.png');
  await page.screenshot({ path: shot1, fullPage: true });
  console.log(`[capture] Captured step 1: ${shot1}`);

  // 2. Fill User 4259 and Password P@ssw0rd
  console.log('[capture] Filling user 4259 and password...');
  await page.fill('#username', '4259');
  await page.fill('#password', 'P@ssw0rd');
  await page.waitForTimeout(400);

  const shot2 = path.join(outDir, '02-login-form-filled.png');
  await page.screenshot({ path: shot2, fullPage: true });
  console.log(`[capture] Captured step 2: ${shot2}`);

  // 3. Submit Login Form
  console.log('[capture] Submitting login form to trigger HRIS authLogin & MFA challenge...');
  await page.click('button[type="submit"], .login-submit');

  // Wait for MFA modal/layer to appear
  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open, #mfaVerify:not([hidden])', { timeout: 15000 });
  await page.waitForTimeout(600);

  const shot3 = path.join(outDir, '03-mfa-challenge.png');
  await page.screenshot({ path: shot3, fullPage: true });
  console.log(`[capture] Captured step 3: ${shot3}`);

  // 4. Fill MFA 6-digit TOTP Code
  console.log('[capture] Generating and filling MFA TOTP code...');
  const code = authenticator.generate(MFA_SECRET);
  console.log(`[capture] Generated TOTP: ${code}`);

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
  await page.waitForTimeout(400);

  const shot4 = path.join(outDir, '04-mfa-code-filled.png');
  await page.screenshot({ path: shot4, fullPage: true });
  console.log(`[capture] Captured step 4: ${shot4}`);

  // 5. Submit MFA and navigate to Dashboard
  console.log('[capture] Submitting MFA code...');
  await page.locator('#mfaForm button[type="submit"]').click();

  // Wait for dashboard to load
  console.log('[capture] Waiting for dashboard...');
  await page.waitForSelector('#sidebar, .app-sidebar, #app', { timeout: 20000 });
  await page.waitForTimeout(1000);

  const shot5 = path.join(outDir, '05-dashboard-admin-it.png');
  await page.screenshot({ path: shot5, fullPage: true });
  console.log(`[capture] Captured step 5: ${shot5}`);

  await browser.close();
  console.log('[capture] All steps completed successfully!');
}

main().catch((err) => {
  console.error('[capture] Error:', err);
  process.exit(1);
});
