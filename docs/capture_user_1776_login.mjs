import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'hris-auth-1776-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const MFA_SECRET = 'JBSWY3DPEHPK3PXP';

async function main() {
  console.log('[capture 1776] Starting browser...');
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });
  const page = await context.newPage();

  page.on('console', msg => console.log('[browser]', msg.type(), msg.text()));
  page.on('response', resp => {
    if (resp.status() >= 400) {
      console.log('[response error]', resp.status(), resp.url());
    }
  });

  // 1. Visit Login Page
  console.log('[capture 1776] Navigating to login page...');
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);

  // 2. Fill User 1776 and Password P@ssw0rd
  console.log('[capture 1776] Filling user 1776 and password...');
  await page.fill('#username', '1776');
  await page.fill('#password', 'P@ssw0rd');
  await page.waitForTimeout(400);

  const shot1 = path.join(outDir, '01-login-form-1776-filled.png');
  await page.screenshot({ path: shot1, fullPage: true });
  console.log(`[capture 1776] Captured step 1: ${shot1}`);

  // 3. Submit Login Form
  console.log('[capture 1776] Submitting login form...');
  await page.click('button[type="submit"], .login-submit');

  // Wait for MFA modal/layer to appear
  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open, #mfaVerify:not([hidden])', { timeout: 15000 });
  await page.waitForTimeout(600);

  const shot2 = path.join(outDir, '02-mfa-challenge-1776.png');
  await page.screenshot({ path: shot2, fullPage: true });
  console.log(`[capture 1776] Captured step 2: ${shot2}`);

  // 4. Fill MFA 6-digit TOTP Code
  console.log('[capture 1776] Generating and filling MFA TOTP code...');
  const code = authenticator.generate(MFA_SECRET);
  console.log(`[capture 1776] Generated TOTP: ${code}`);

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

  // 5. Submit MFA and navigate to Dashboard
  console.log('[capture 1776] Submitting MFA code...');
  await page.locator('#mfaForm button[type="submit"]').click();

  // Wait for dashboard to load
  console.log('[capture 1776] Waiting for dashboard...');
  await page.waitForSelector('#sidebar, .app-sidebar, #app', { timeout: 20000 });
  await page.waitForTimeout(1000);

  const shot3 = path.join(outDir, '03-dashboard-1776.png');
  await page.screenshot({ path: shot3, fullPage: true });
  console.log(`[capture 1776] Captured step 3: ${shot3}`);

  await browser.close();
  console.log('[capture 1776] All steps completed successfully!');
}

main().catch((err) => {
  console.error('[capture error]', err);
  process.exit(1);
});
