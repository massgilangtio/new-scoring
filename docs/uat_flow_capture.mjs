/**
 * E2E UAT: reviewer scoring → list → assign supervisor → report → approver decide → report
 * Screenshots → docs/uat-flow-screenshots/ + single PDF
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const require = createRequire(import.meta.url);
const { authenticator } = require('otplib');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.UAT_BASE_URL || 'http://127.0.0.1:8080';
const outDir = path.resolve(__dirname, 'uat-flow-screenshots');
fs.mkdirSync(outDir, { recursive: true });

const UAT_MFA_SECRET = process.env.UAT_MFA_SECRET || 'JBSWY3DPEHPK3PXP';
const creds = {
  reviewer: { user: 'reviewer.scoring', pass: 'ScoringUAT2026!' },
  approver: { user: 'approval.scoring', pass: 'ScoringUAT2026!' },
  admin: { user: 'admin.ti2', pass: 'ScoringUAT2026!' },
};

const steps = [];
const menuAudit = { reviewer: [], approver: [], admin: [], notes: [] };

function log(msg) {
  console.log(`[uat] ${msg}`);
}

async function shot(page, name, caption) {
  const file = `${String(steps.length + 1).padStart(2, '0')}-${name}.png`;
  const full = path.join(outDir, file);
  await page.waitForTimeout(400);
  await page.screenshot({ path: full, fullPage: true });
  steps.push({ file, caption, name });
  log(`shot ${file} — ${caption}`);
  return full;
}

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

  // MFA verify panel (mfa_enabled users)
  await page.waitForSelector('#mfaLayer:not([hidden]), body.mfa-open', { timeout: 15000 });
  const setupOnly = await page.locator('#mfaSetup:not([hidden])').isVisible().catch(() => false);
  const verifyVisible = await page.locator('#mfaVerify:not([hidden])').isVisible().catch(() => false);
  if (setupOnly && !verifyVisible) {
    // Should not happen after prepare script; try Continue if QR setup shown erroneously
    const cont = page.locator('#mfaContinue');
    if (await cont.count()) await cont.click();
  }
  await page.waitForSelector('#mfaVerify:not([hidden]) .otp-digit', { timeout: 10000 });
  const code = authenticator.generate(UAT_MFA_SECRET);
  await fillOtp(page, code);
  await page.click('#mfaForm button[type="submit"], #mfaVerify .mfa-next');
  await page.waitForSelector('#sidebar, .app-sidebar', { timeout: 30000 });
}

async function logout(page) {
  const form = page.locator('form.header-logout-form').first();
  if (await form.count()) {
    await Promise.all([
      page.waitForURL(/login/, { timeout: 20000 }).catch(() => null),
      form.evaluate((f) => f.submit()),
    ]);
  } else {
    // Fallback: clear cookies
    await page.context().clearCookies();
  }
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#username', { timeout: 15000 });
}

async function collectMenus(page, roleKey) {
  const items = await page.evaluate(() => {
    const out = [];
    document.querySelectorAll('#sidebar .menu-item .menu-link, .app-sidebar .menu-item .menu-link').forEach((a) => {
      const text = (a.querySelector('.menu-text')?.textContent || a.textContent || '').replace(/\s+/g, ' ').trim();
      const href = a.getAttribute('href') || '';
      if (text && href && href !== 'javascript:;' && !href.startsWith('#')) {
        out.push({ text, href });
      }
    });
    // unique by href
    const seen = new Set();
    return out.filter((x) => {
      if (seen.has(x.href)) return false;
      seen.add(x.href);
      return true;
    });
  });
  menuAudit[roleKey] = items;
  return items;
}

async function select2Pick(page, selectId, optionTextOrIndex) {
  // Prefer direct value set — Select2 UI is flaky in headless.
  const ok = await page.evaluate(
    ({ selectId, optionTextOrIndex }) => {
      const el = document.querySelector('#' + selectId);
      if (!el) return false;
      let value = '';
      const options = [...el.options].filter((o) => o.value);
      if (typeof optionTextOrIndex === 'number') {
        const opt = options[optionTextOrIndex] || options[0];
        if (!opt) return false;
        value = opt.value;
      } else {
        const opt = options.find((o) => (o.textContent || '').includes(optionTextOrIndex));
        if (!opt) return false;
        value = opt.value;
      }
      el.value = value;
      if (window.jQuery) {
        window.jQuery(el).val(value).trigger('change');
      } else {
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
      return !!el.value;
    },
    { selectId, optionTextOrIndex }
  );
  if (!ok) throw new Error('Failed to pick #' + selectId);
  await page.waitForTimeout(400);
}

async function run() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();
  page.setDefaultTimeout(30000);

  // Health check
  const health = await page.goto(BASE, { waitUntil: 'domcontentloaded' }).catch((e) => e);
  if (health instanceof Error) throw new Error(`Frontend not reachable at ${BASE}: ${health.message}`);

  // ========== 1. Login reviewer ==========
  await login(page, creds.reviewer);
  await collectMenus(page, 'reviewer');
  await shot(page, 'login-reviewer-dashboard', '1. Login sebagai reviewer.scoring — Dashboard');

  // ========== 2. Transaksi scoring ==========
  await page.goto(`${BASE}/scoring/credit`, { waitUntil: 'networkidle' });
  await shot(page, 'scoring-credit-step1', '2a. Pengajuan Scoring — pilih debitur');

  await select2Pick(page, 'debtorSelect', 0);
  await page.waitForTimeout(500);
  await shot(page, 'scoring-debtor-selected', '2b. Debitur dipilih');

  await page.click('#btnCreditNext1');
  await page.waitForTimeout(400);

  // Product
  await select2Pick(page, 'productSelect', 0);
  await page.waitForTimeout(1000);
  await shot(page, 'scoring-product-selected', '2c. Produk dipilih');
  await page.click('#btnCreditNext2');

  // Wait parameters load
  await page.waitForSelector('.param-radio', { timeout: 25000 });
  await page.waitForTimeout(800);

  // Select first radio per parameter group
  const picked = await page.evaluate(() => {
    const groups = {};
    document.querySelectorAll('.param-radio').forEach((r) => {
      const name = r.getAttribute('name') || r.dataset.paramName || r.name;
      if (!groups[name]) groups[name] = r;
    });
    Object.values(groups).forEach((r) => {
      r.checked = true;
      r.dispatchEvent(new Event('change', { bubbles: true }));
      if (window.jQuery) window.jQuery(r).trigger('change');
    });
    return Object.keys(groups).length;
  });
  if (!picked) throw new Error('Tidak ada parameter radio untuk dipilih');
  await page.waitForTimeout(400);
  await shot(page, 'scoring-parameters-filled', '2d. Penilaian parameter diisi');
  await page.click('#btnCreditNext3');
  await page.waitForTimeout(500);
  await shot(page, 'scoring-summary', '2e. Ringkasan / konfirmasi sebelum simpan');

  // Save → Kirim ke Supervisi
  await page.click('#btnSaveScoring');
  await page.waitForSelector('.swal2-container', { timeout: 10000 });
  await shot(page, 'scoring-confirm-swal', '2f. Konfirmasi kirim ke supervisi');
  await page.click('.swal2-confirm');

  // ========== 4. Pilih approver (supervisor modal) ==========
  await page.waitForSelector('#supervisorModal.show', { timeout: 15000 });
  await page.waitForTimeout(600);
  await shot(page, 'pick-approver-modal', '4. Pilih Approver / Supervisi Scoring');

  await select2Pick(page, 'supervisorSelect', 0);
  // Prefer Approver Scoring 1 if listed
  await page.evaluate(() => {
    const el = document.querySelector('#supervisorSelect');
    const opt = [...el.options].find((o) => /Approver Scoring 1|approval\.scoring/i.test(o.textContent || '')) || [...el.options].find((o) => o.value);
    if (opt) {
      el.value = opt.value;
      if (window.jQuery) window.jQuery(el).val(opt.value).trigger('change');
    }
  });
  await page.waitForTimeout(400);
  await shot(page, 'pick-approver-selected', '4b. Approver dipilih — siap kirim');
  await page.click('#btnConfirmSendSupervisor');

  // Wait success
  await page.waitForSelector('.swal2-success, .swal2-icon-success, .swal2-confirm', { timeout: 30000 });
  await page.waitForTimeout(600);
  await shot(page, 'scoring-saved-success', '2g. Scoring berhasil disimpan & dikirim');
  // Close success
  if (await page.locator('.swal2-confirm').count()) {
    await page.click('.swal2-confirm');
  }
  await page.waitForTimeout(1000);

  // ========== 3. Daftar scoring ==========
  await page.goto(`${BASE}/transactions`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await shot(page, 'daftar-scoring', '3. Daftar Scoring — pengajuan muncul di list');

  // ========== 5. Report transaksi scoring (reviewer) ==========
  await page.goto(`${BASE}/reports/scoring`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  await shot(page, 'report-scoring-reviewer', '5. Laporan Scoring (setelah pengajuan, sebagai reviewer)');

  // ========== Menu crawl reviewer unused check ==========
  for (const m of menuAudit.reviewer) {
    try {
      const res = await page.goto(m.href, { waitUntil: 'domcontentloaded', timeout: 20000 });
      const title = await page.title();
      const bodyText = await page.locator('body').innerText().catch(() => '');
      const broken = (res && res.status() >= 400) || /Whoops!|Halaman Tidak Ditemukan|404/.test(bodyText);
      menuAudit.notes.push({ role: 'reviewer', ...m, title, status: res?.status(), broken });
      await shot(page, `menu-reviewer-${m.text.replace(/\W+/g, '_').toLowerCase()}`, `Menu reviewer: ${m.text}`);
    } catch (e) {
      menuAudit.notes.push({ role: 'reviewer', ...m, error: String(e), broken: true });
    }
  }

  await logout(page);

  // ========== 6. Login approver ==========
  await login(page, creds.approver);
  await collectMenus(page, 'approver');
  await shot(page, 'login-approver-dashboard', '6. Login sebagai approval.scoring — Dashboard');

  // ========== 7. Approval + catatan ==========
  await page.goto(`${BASE}/approvals`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await shot(page, 'approvals-inbox', '7a. Inbox Approval — Menunggu Persetujuan');

  // Open first inbox item — action link may be inside yellow dropdown
  const detailHref = await page.evaluate(() => {
    const a = document.querySelector('a[href*="/approvals/"]');
    return a ? a.getAttribute('href') : null;
  });
  if (!detailHref) throw new Error('Tidak ada item di inbox approval');
  await page.goto(detailHref, { waitUntil: 'networkidle' });
  await page.waitForTimeout(800);
  await shot(page, 'approval-detail', '7b. Detail pengajuan untuk keputusan');

  // Decide approved + note
  const approved = page.locator('input[name="decision"][value="approved"]');
  if (!(await approved.count())) {
    throw new Error('Form keputusan tidak tampil — cek assigned_approver_id / status');
  }
  await page.locator('#lblApproved').click();
  await page.fill('#note', 'UAT E2E: disetujui. Catatan kelayakan OK, lanjut proses kredit.');
  await shot(page, 'approval-filled', '7c. Keputusan Setujui + catatan diisi');
  await page.click('#btnDecide');
  await page.waitForSelector('.swal2-container', { timeout: 10000 });
  await page.click('.swal2-confirm');
  await page.waitForTimeout(1500);
  await shot(page, 'approval-done', '7d. Keputusan tersimpan');

  // ========== 8. Report (approver) ==========
  await page.goto(`${BASE}/reports/scoring`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(1200);
  await shot(page, 'report-scoring-approver', '8. Laporan Scoring setelah approval');

  for (const m of menuAudit.approver) {
    try {
      const res = await page.goto(m.href, { waitUntil: 'domcontentloaded', timeout: 20000 });
      const title = await page.title();
      const bodyText = await page.locator('body').innerText().catch(() => '');
      const broken = (res && res.status() >= 400) || /Whoops!|Halaman Tidak Ditemukan|404/.test(bodyText);
      menuAudit.notes.push({ role: 'approver', ...m, title, status: res?.status(), broken });
    } catch (e) {
      menuAudit.notes.push({ role: 'approver', ...m, error: String(e), broken: true });
    }
  }

  await logout(page);

  // ========== Admin full menu audit ==========
  await login(page, creds.admin);
  await collectMenus(page, 'admin');
  await shot(page, 'login-admin-dashboard', 'Audit: login admin.ti2 — semua menu');
  for (const m of menuAudit.admin) {
    try {
      const res = await page.goto(m.href, { waitUntil: 'domcontentloaded', timeout: 25000 });
      await page.waitForTimeout(400);
      const title = await page.title();
      const bodyText = await page.locator('body').innerText().catch(() => '');
      const emptyTable = /Tidak ada data|Belum ada|0 data|No data/i.test(bodyText);
      const broken = (res && res.status() >= 400) || /Whoops!|Halaman Tidak Ditemukan|404/.test(bodyText);
      menuAudit.notes.push({ role: 'admin', ...m, title, status: res?.status(), broken, emptyTable });
      const safe = m.text.replace(/\W+/g, '_').toLowerCase().slice(0, 40);
      await shot(page, `menu-admin-${safe}`, `Menu admin: ${m.text}`);
    } catch (e) {
      menuAudit.notes.push({ role: 'admin', ...m, error: String(e), broken: true });
    }
  }

  await browser.close();

  // Write manifesto
  const manifest = {
    generated_at: new Date().toISOString(),
    base: BASE,
    steps,
    menuAudit,
  };
  fs.writeFileSync(path.join(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2));
  fs.writeFileSync(
    path.join(outDir, 'MENU-AUDIT.md'),
    buildMenuReport(menuAudit)
  );

  await buildPdf(outDir, steps, menuAudit);
  log(`Done. ${steps.length} screenshots → ${outDir}`);
}

function buildMenuReport(audit) {
  const lines = ['# Menu Audit UAT', ''];
  lines.push('## Temuan ringkas', '');
  lines.push(
    '- **Pengajuan Scoring** (`/scoring/credit`) menulis `credit_scorings`; sebelum bridge, **Daftar Scoring / Approval / Laporan** membaca `scoring_transactions` (kosong / putus). Bridge UAT sudah ditambahkan agar kirim-ke-supervisi membuat transaksi paralel.',
    '- Menu **Approval** hanya muncul untuk role dengan `scoring.approve` / `scoring.assign` (bukan reviewer).',
    '- Route legacy `/scoring/products`, `/scoring/versions/*`, `/transactions/new` (redirect) masih ada di Routes tapi tidak di sidebar aktif reviewer.',
    ''
  );
  for (const role of ['reviewer', 'approver', 'admin']) {
    lines.push(`## Menu terlihat — ${role}`, '');
    for (const m of audit[role] || []) {
      lines.push(`- ${m.text} → \`${m.href}\``);
    }
    lines.push('');
  }
  lines.push('## Hasil crawl', '');
  for (const n of audit.notes || []) {
    const flag = n.broken ? 'BROKEN' : n.emptyTable ? 'EMPTY?' : 'OK';
    lines.push(`- [${flag}] (${n.role}) ${n.text || ''} \`${n.href || ''}\` ${n.title || n.error || ''}`);
  }
  lines.push('');
  return lines.join('\n');
}

async function buildPdf(outDir, steps, audit) {
  // Prefer pdfkit if available; else HTML→print via playwright
  const pdfPath = path.join(outDir, 'UAT-Flow-Scoring.pdf');
  let PDFDocument;
  try {
    const require = createRequire(import.meta.url);
    PDFDocument = require('pdfkit');
  } catch {
    PDFDocument = null;
  }

  if (PDFDocument) {
    const doc = new PDFDocument({ autoFirstPage: false, margin: 36 });
    const stream = fs.createWriteStream(pdfPath);
    doc.pipe(stream);
    doc.addPage({ size: 'A4' });
    doc.fontSize(18).text('UAT Flow — Scoring Reviewer → Approver', { align: 'center' });
    doc.moveDown();
    doc.fontSize(11).text(`Generated: ${new Date().toLocaleString('id-ID')}`);
    doc.text(`Base URL: ${BASE}`);
    doc.moveDown();
    doc.fontSize(12).text('Tahapan:', { underline: true });
    steps.forEach((s, i) => doc.fontSize(10).text(`${i + 1}. ${s.caption}`));
    for (const s of steps) {
      doc.addPage({ size: 'A4', layout: 'landscape' });
      doc.fontSize(12).text(s.caption, { align: 'left' });
      doc.moveDown(0.5);
      const img = path.join(outDir, s.file);
      if (fs.existsSync(img)) {
        const maxW = 770;
        const maxH = 480;
        doc.image(img, { fit: [maxW, maxH], align: 'center', valign: 'center' });
      }
    }
    doc.addPage({ size: 'A4' });
    doc.fontSize(14).text('Menu Audit', { underline: true });
    doc.moveDown();
    doc.fontSize(9).text(buildMenuReport(audit));
    doc.end();
    await new Promise((res, rej) => {
      stream.on('finish', res);
      stream.on('error', rej);
    });
    log(`PDF (pdfkit) → ${pdfPath}`);
    return;
  }

  // Fallback: HTML print
  const htmlParts = [
    '<html><head><meta charset="utf-8"><title>UAT Flow Scoring</title>',
    '<style>body{font-family:Segoe UI,Arial,sans-serif;margin:24px} h1{font-size:20px} img{max-width:100%;border:1px solid #ccc;margin:12px 0} .cap{font-weight:600;margin-top:24px} @media print{.page{page-break-after:always}}</style>',
    '</head><body>',
    '<h1>UAT Flow — Scoring Reviewer → Approver</h1>',
    `<p>${new Date().toLocaleString('id-ID')} — ${BASE}</p><ol>`,
    ...steps.map((s) => `<li>${s.caption}</li>`),
    '</ol>',
    ...steps.map(
      (s) =>
        `<div class="page"><div class="cap">${s.caption}</div><img src="${s.file}" /></div>`
    ),
    `<h2>Menu Audit</h2><pre>${buildMenuReport(audit).replace(/</g, '&lt;')}</pre>`,
    '</body></html>',
  ];
  fs.writeFileSync(path.join(outDir, 'index.html'), htmlParts.join('\n'));
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('file://' + path.join(outDir, 'index.html').replace(/\\/g, '/'), {
    waitUntil: 'networkidle',
  });
  await page.pdf({ path: pdfPath, format: 'A4', landscape: true, printBackground: true });
  await browser.close();
  log(`PDF (playwright) → ${pdfPath}`);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
