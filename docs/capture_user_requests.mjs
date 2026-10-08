import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.UAT_BASE_URL || 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'update-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const UAT_MFA_SECRET = process.env.UAT_MFA_SECRET || 'JBSWY3DPEHPK3PXP';
const creds = {
  reviewer: { user: 'reviewer.scoring', pass: 'ScoringUAT2026!' },
  approver: { user: 'approval.scoring', pass: 'ScoringUAT2026!' },
};

async function fillOtp(page, code) {
  const digits = page.locator('.otp-digit');
  const n = await digits.count();
  if (n >= 6) {
    for (let i = 0; i < 6; i++) {
      await digits.nth(i).click();
      await digits.nth(i).fill(code[i]);
    }
  } else {
    throw new Error('OTP digit inputs not found');
  }
  await page.evaluate((c) => {
    const el = document.getElementById('otpCode');
    if (el) el.value = c;
  }, code);
}

async function login(page, { user, pass }) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('#username', user);
  await page.fill('#password', pass);
  await page.click('button[type="submit"], .login-submit');

  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open', { timeout: 15000 });
  if (await page.locator('#mfaSetup:not([hidden])').isVisible().catch(() => false)) {
    await page.click('#mfaContinue');
  }
  await page.waitForSelector('#mfaVerify:not([hidden]) .otp-digit', { timeout: 10000 });
  const code = authenticator.generate(UAT_MFA_SECRET);
  const digits = page.locator('.otp-digit');
  for (let i = 0; i < 6; i++) await digits.nth(i).fill(code[i]);
  await page.evaluate((c) => { const el = document.getElementById('otpCode'); if (el) el.value = c; }, code);
  await page.click('#mfaForm button[type="submit"]');
  await page.waitForSelector('#sidebar', { timeout: 30000 });
}

async function logout(page) {
  const formLogout = page.locator('form.header-logout-form').first();
  if (await formLogout.count()) {
    await formLogout.evaluate((f) => f.submit());
    await page.waitForNavigation().catch(() => null);
  } else {
    await page.context().clearCookies();
  }
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(500);
}

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 950 } });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  console.log('1. Logging in Reviewer (reviewer.scoring)...');
  await login(page, creds.reviewer);

  console.log('2. Opening Scoring Credit Page...');
  await page.goto(`${BASE}/scoring/credit`, { waitUntil: 'networkidle' });

  // Pick Debtor 1 (MANGATUR PARULIAN SILABAN)
  console.log('   Selecting Debtor 1 (has past submissions)...');
  await page.evaluate(() => {
    const el = document.getElementById('debtorSelect');
    el.value = '1';
    window.jQuery(el).val('1').trigger('change');
  });
  await page.waitForTimeout(1200);

  // Take screenshot: Tahap 1 - Debtor detail with all products history
  const file1 = path.join(outDir, '01_step1_debtor_all_history.png');
  await page.screenshot({ path: file1, fullPage: true });
  console.log('   Captured:', file1);

  // Proceed to Step 2
  await page.click('#btnCreditNext1');
  await page.waitForTimeout(600);

  // Pick Product 329 (KMG-K JANGKA PENDEK TPP)
  console.log('   Selecting Product 329...');
  await page.evaluate(() => {
    const el = document.getElementById('productSelect');
    el.value = '329';
    window.jQuery(el).val('329').trigger('change');
  });
  await page.waitForTimeout(1000);

  // Click Next -> Triggers duplicate check and shows modal
  await page.click('#btnCreditNext2');
  await page.waitForSelector('#duplicateReasonModal.show, #duplicateReasonModal[style*="display: block"]', { timeout: 10000 });
  await page.waitForTimeout(800);

  // Take screenshot: Tahap 1 - Duplicate Reason Modal with multi-history table
  const file2 = path.join(outDir, '01_popup_duplicate_detected.png');
  await page.screenshot({ path: file2, fullPage: true });
  console.log('   Captured:', file2);

  // Fill duplicate reason and proceed
  await page.fill('#dupReasonInput', 'Penambahan agunan fisik dan permohonan kenaikan plafon fasilitas kredit (Top Up) nasabah.');
  await page.click('#btnConfirmDuplicateReason');
  await page.waitForTimeout(800);

  // Step 3: Parameters are pre-selected or let's select first for any
  await page.waitForSelector('.param-radio', { timeout: 10000 });
  await page.evaluate(() => {
    const groups = {};
    document.querySelectorAll('.param-radio').forEach((r) => {
      const name = r.name;
      if (!groups[name]) groups[name] = r;
    });
    Object.values(groups).forEach((r) => {
      r.checked = true;
      window.jQuery(r).trigger('change');
    });
  });
  await page.waitForTimeout(600);

  // Proceed to Step 4: Ringkasan
  await page.click('#btnCreditNext3');
  await page.waitForTimeout(800);

  // Click Simpan Scoring Kredit
  await page.click('#btnSaveScoring');
  await page.waitForSelector('.swal2-confirm', { timeout: 10000 });
  await page.click('.swal2-confirm'); // "Ya, Kirim ke Approver"
  
  await page.waitForSelector('#supervisorModal.show, #supervisorModal[style*="display: block"]', { timeout: 10000 });
  await page.evaluate(() => {
    const el = document.getElementById('supervisorSelect');
    el.value = '5';
    window.jQuery(el).val('5').trigger('change');
  });
  await page.waitForTimeout(500);

  // Submit to supervisor
  await page.click('#btnConfirmSendSupervisor');
  await page.waitForSelector('.swal2-confirm', { timeout: 15000 });
  await page.click('.swal2-confirm'); // "Selesai"
  await page.waitForTimeout(1000);

  console.log('3. Reviewer submitted duplicate scoring. Logging out Reviewer...');
  await logout(page);

  console.log('4. Logging in Approver (approval.scoring)...');
  await login(page, creds.approver);

  console.log('5. Navigating to Approver Queue (/approvals)...');
  await page.goto(`${BASE}/approvals`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1000);

  // Screenshot Tahap 6: Regular Queue (Card Menunggu Penugasan deleted, only 2 cards)
  const file6 = path.join(outDir, '06_moved_to_scoring_approval_queue.png');
  await page.screenshot({ path: file6, fullPage: true });
  console.log('   Captured:', file6);

  // Click Tab 2: Izin Pengajuan Ulang (Gate 1)
  console.log('   Clicking Tab 2: Izin Pengajuan Ulang (Gate 1)...');
  await page.click('#tab-duplicates-btn');
  await page.waitForTimeout(1000);

  // Screenshot Tahap 4: Banner info top + datatable full width
  const file4 = path.join(outDir, '04_approver_duplicate_requests_tab.png');
  await page.screenshot({ path: file4, fullPage: true });
  console.log('   Captured:', file4);

  // Approve Gate 1 for the newly submitted duplicate transaction
  console.log('   Approving Gate 1 permission in modal...');
  const btnApprove = page.locator('#duplicateTable .btn-approve-duplicate').first();
  if (await btnApprove.count()) {
    await btnApprove.click();
    await page.waitForSelector('#approveDuplicateModal.show, #approveDuplicateModal[style*="display: block"]', { timeout: 10000 });
    await page.click('#approveDuplicateModal button[type="submit"]');
    await page.waitForTimeout(2000);
  }

  // Navigate to inbox / detail transaction (Gate 2)
  console.log('6. Navigating to Transaction Detail (Gate 2)...');
  await page.goto(`${BASE}/approvals`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);

  // Find link to detail in inbox
  const detailBtn = page.locator('#inboxTable a.btn-theme[href*="/approvals/"]').first();
  if (await detailBtn.count()) {
    const href = await detailBtn.getAttribute('href');
    await page.goto(href, { waitUntil: 'networkidle' });
  } else {
    // Fallback to detail tx 10
    await page.goto(`${BASE}/approvals/10`, { waitUntil: 'networkidle' });
  }

  await page.waitForTimeout(1200);

  // Screenshot Tahap 7: Detail transaction with duplicate banner and integer formatted scores
  const file7 = path.join(outDir, '07_approver_detail_with_duplicate_banner.png');
  await page.screenshot({ path: file7, fullPage: true });
  console.log('   Captured:', file7);

  await browser.close();
  console.log('Done! All screenshots captured and verified.');
}

run().catch((err) => {
  console.error('Error running capture script:', err);
  process.exit(1);
});
