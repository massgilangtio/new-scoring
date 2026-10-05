const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

(async () => {
  const outDir = __dirname;
  fs.mkdirSync(outDir, { recursive: true });
  const base = 'http://127.0.0.1:8770/_phase8_approvals_preview.html';
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  async function shot(hash, name, width, height) {
    const page = await browser.newPage();
    await page.setViewport({ width, height, deviceScaleFactor: 1 });
    await page.goto(base + '#' + hash, { waitUntil: 'networkidle0', timeout: 60000 });
    await page.waitForFunction(
      (h) => {
        const el = document.querySelector('[data-preview="' + h + '"]');
        return el && el.classList.contains('is-on');
      },
      { timeout: 10000 },
      hash
    );
    await new Promise((r) => setTimeout(r, 600));
    await page.screenshot({ path: path.join(outDir, name), fullPage: true });
    await page.close();
    console.log('wrote', name);
  }

  await shot('inbox', 'phase8-inbox-desktop.png', 1440, 900);
  await shot('decide', 'phase8-decide-desktop.png', 1440, 900);
  await shot('inbox', 'phase8-inbox-mobile.png', 390, 844);
  await browser.close();
})().catch((err) => {
  console.error(err);
  process.exit(1);
});
