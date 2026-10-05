const puppeteer = require('puppeteer');
const path = require('path');

(async () => {
  const out = __dirname;
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto('http://127.0.0.1:8770/_phase6_scoring_preview.html#credit', {
    waitUntil: 'networkidle0',
    timeout: 60000,
  });
  await page.waitForSelector('#creditWizardPreview [data-wizard-pane="1"]');
  await new Promise((r) => setTimeout(r, 600));
  await page.screenshot({ path: path.join(out, 'phase6-credit-step1-desktop.png'), fullPage: true });

  await page.evaluate(() => {
    const go = window.jQuery('#creditWizardPreview').data('wizardGo');
    if (typeof go === 'function') go(4);
  });
  await new Promise((r) => setTimeout(r, 500));
  await page.screenshot({ path: path.join(out, 'phase6-credit-summary-desktop.png'), fullPage: true });
  await page.screenshot({ path: path.join(out, 'phase6-credit-desktop.png'), fullPage: true });
  console.log('done');
  await browser.close();
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
