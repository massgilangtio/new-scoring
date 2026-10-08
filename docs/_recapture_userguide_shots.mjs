import { chromium } from 'playwright';
import { createRequire } from 'module';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://127.0.0.1:8080';
const SECRET = 'JBSWY3DPEHPK3PXP';
const shotDir = 'D:/DEV/04-DOCUMENTATION/SKORING/setup-userguide/result-setup/shots';
const uatDir = path.join(__dirname, 'uat-flow-screenshots');

async function login(page, user, pass) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('#username', user);
  await page.fill('#password', pass);
  await page.click('button[type="submit"], .login-submit');
  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open', { timeout: 15000 });
  if (await page.locator('#mfaSetup:not([hidden])').isVisible().catch(() => false)) {
    await page.click('#mfaContinue');
  }
  await page.waitForSelector('#mfaVerify:not([hidden]) .otp-digit', { timeout: 10000 });
  const code = authenticator.generate(SECRET);
  const digits = page.locator('.otp-digit');
  for (let i = 0; i < 6; i++) await digits.nth(i).fill(code[i]);
  await page.evaluate((c) => { const el = document.getElementById('otpCode'); if (el) el.value = c; }, code);
  await page.click('#mfaForm button[type="submit"]');
  await page.waitForSelector('#sidebar', { timeout: 30000 });
}

async function logout(page) {
  const form = page.locator('form.header-logout-form').first();
  if (await form.count()) await form.evaluate((f) => f.submit());
  await page.waitForTimeout(600);
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
}

function saveBoth(name, buf) {
  fs.writeFileSync(path.join(shotDir, name), buf);
  fs.writeFileSync(path.join(uatDir, name), buf);
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

// Approver modal as reviewer — open credit, pick debtor/product/params quickly, open modal
await login(page, 'reviewer.scoring', 'ScoringUAT2026!');
await page.goto(`${BASE}/scoring/credit`, { waitUntil: 'networkidle' });
await page.evaluate(() => {
  const d = document.querySelector('#debtorSelect');
  const opt = [...d.options].find((o) => o.value);
  if (opt) { d.value = opt.value; if (window.jQuery) jQuery(d).val(opt.value).trigger('change'); }
});
await page.click('#btnCreditNext1');
await page.waitForTimeout(400);
await page.evaluate(() => {
  const d = document.querySelector('#productSelect');
  const opt = [...d.options].find((o) => o.value);
  if (opt) { d.value = opt.value; if (window.jQuery) jQuery(d).val(opt.value).trigger('change'); }
});
await page.waitForTimeout(1000);
await page.click('#btnCreditNext2');
await page.waitForSelector('.param-radio', { timeout: 20000 });
await page.evaluate(() => {
  const groups = {};
  document.querySelectorAll('.param-radio').forEach((r) => {
    const name = r.getAttribute('name');
    if (!groups[name]) groups[name] = r;
  });
  Object.values(groups).forEach((r) => { r.checked = true; if (window.jQuery) jQuery(r).trigger('change'); });
});
await page.click('#btnCreditNext3');
await page.waitForTimeout(400);
await page.click('#btnSaveScoring');
await page.waitForSelector('.swal2-confirm');
await page.click('.swal2-confirm');
await page.waitForSelector('#supervisorModal.show', { timeout: 10000 });
await page.waitForTimeout(500);
// select first approver for filled shot
await page.evaluate(() => {
  const el = document.querySelector('#supervisorSelect');
  const opt = [...el.options].find((o) => o.value);
  if (opt) { el.value = opt.value; if (window.jQuery) jQuery(el).val(opt.value).trigger('change'); }
});
let buf = await page.screenshot({ type: 'png', fullPage: false });
saveBoth('08-pick-approver-modal.png', buf);
await page.waitForTimeout(300);
buf = await page.screenshot({ type: 'png', fullPage: false });
saveBoth('09-pick-approver-selected.png', buf);
// close modal without saving
await page.click('#supervisorModal .btn-default, #supervisorModal [data-bs-dismiss="modal"]');
await page.waitForTimeout(400);
await logout(page);

// Admin users page
await login(page, 'admin.ti2', 'ScoringUAT2026!');
await page.goto(`${BASE}/access/users`, { waitUntil: 'networkidle' });
await page.waitForTimeout(800);
buf = await page.screenshot({ type: 'png', fullPage: true });
saveBoth('39-menu-admin-user.png', buf);

console.log('Updated shots: 08, 09, 39');
await browser.close();
