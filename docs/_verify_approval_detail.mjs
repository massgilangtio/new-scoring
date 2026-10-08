import { chromium } from 'playwright';
import { createRequire } from 'module';
import path from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const SECRET = 'JBSWY3DPEHPK3PXP';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('#username', 'approval.scoring');
await page.fill('#password', 'ScoringUAT2026!');
await page.click('button[type="submit"], .login-submit');
await page.waitForSelector('#mfaVerify:not([hidden]) .otp-digit, body.mfa-open', { timeout: 15000 });
if (await page.locator('#mfaSetup:not([hidden])').isVisible().catch(() => false)) {
  await page.click('#mfaContinue');
}
await page.waitForSelector('#mfaVerify:not([hidden]) .otp-digit', { timeout: 10000 });
const code = authenticator.generate(SECRET);
const digits = page.locator('.otp-digit');
for (let i = 0; i < 6; i++) {
  await digits.nth(i).fill(code[i]);
}
await page.evaluate((c) => { document.getElementById('otpCode').value = c; }, code);
await page.click('#mfaForm button[type="submit"]');
await page.waitForSelector('#sidebar', { timeout: 30000 });

await page.goto(`${BASE}/approvals/3`, { waitUntil: 'networkidle' });
await page.waitForTimeout(800);
const out = path.join(__dirname, 'uat-flow-screenshots', 'approval-parameter-detail.png');
await page.screenshot({ path: out, fullPage: true });
const hasTable = await page.locator('text=Rincian Parameter Terpilih').count();
const rows = await page.locator('table tbody tr').count();
console.log({ out, hasTable, rows, title: await page.title() });
await browser.close();
