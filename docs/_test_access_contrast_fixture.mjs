import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const outDir = path.resolve('access-screenshots');
fs.mkdirSync(outDir, { recursive: true });

function parseRgb(c) {
  const m = c && c.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?/i);
  if (!m) return null;
  return [Number(m[1]), Number(m[2]), Number(m[3]), m[4] !== undefined ? Number(m[4]) : 1];
}
function relLuma([r, g, b]) {
  const f = (v) => {
    v /= 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  };
  return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
}
function contrastRatio(fg, bg) {
  const a = relLuma(fg);
  const b = relLuma(bg);
  const [hi, lo] = a > b ? [a, b] : [b, a];
  return (hi + 0.05) / (lo + 0.05);
}
function blendOnWhite([r, g, b, a = 1]) {
  const alpha = a;
  return [
    Math.round(r * alpha + 255 * (1 - alpha)),
    Math.round(g * alpha + 255 * (1 - alpha)),
    Math.round(b * alpha + 255 * (1 - alpha)),
  ];
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1200, height: 720 } });
await page.goto('http://127.0.0.1:8080/_access_contrast_fixture.html', { waitUntil: 'networkidle' });
await page.waitForSelector('#usersTable .badge.access-badge-role');

const shot = path.join(outDir, 'access-users-contrast.png');
await page.screenshot({ path: shot, fullPage: false });

const styles = await page.evaluate(() => {
  const pick = (sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const cs = getComputedStyle(el);
    return {
      text: (el.textContent || '').trim(),
      color: cs.color,
      backgroundColor: cs.backgroundColor,
    };
  };
  return {
    role: pick('#usersTable .badge.access-badge-role'),
    status: pick('#usersTable .badge.access-badge-active'),
    inactive: pick('#usersTable .badge.access-badge-inactive'),
    username: pick('#usersTable .user-meta-user'),
    name: pick('#usersTable .user-meta-name'),
  };
});

const report = { screenshot: shot, styles, checks: {} };
for (const key of ['role', 'status', 'inactive', 'username', 'name']) {
  const item = styles[key];
  if (!item) {
    report.checks[key] = { ok: false, reason: 'missing' };
    continue;
  }
  const fg = parseRgb(item.color);
  const bgRaw = parseRgb(item.backgroundColor) || [255, 255, 255, 1];
  const bg = blendOnWhite(bgRaw);
  const ratio = contrastRatio(fg, bg);
  const min = key === 'username' || key === 'name' ? 4.5 : 4.0;
  report.checks[key] = {
    ok: ratio >= min,
    ratio: Number(ratio.toFixed(2)),
    min,
    color: item.color,
    backgroundColor: item.backgroundColor,
    text: item.text,
  };
}

console.log(JSON.stringify(report, null, 2));
await browser.close();

const failed = Object.entries(report.checks).filter(([, v]) => !v.ok).map(([k]) => k);
if (failed.length) {
  console.error('CONTRAST_FAIL=' + failed.join(','));
  process.exit(2);
}
console.log('CONTRAST_PASS');
