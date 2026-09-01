#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer');

const argv = require('minimist')(process.argv.slice(2));
const baseUrl = argv.base || 'http://localhost:8000';
const onlyScheme = argv.scheme || null; // 'light' or 'dark' to restrict

const pages = [
  'index.html',
  'about.html',
  'programs.html',
  'services.html',
  'online-classes.html',
  'apply.html',
  'contact.html',
  'gallery.html',
  'achievements.html',
  '404.html',
  'offline.html'
];

const outDir = path.join(process.cwd(), 'visual-screenshots');
if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });

(async () => {
  console.log(`Starting visual-check against ${baseUrl}`);
  const browser = await puppeteer.launch({ args: ['--no-sandbox', '--disable-setuid-sandbox'] });
  try {
    for (const p of pages) {
      const name = p.replace('.html', '');
      for (const scheme of ['light', 'dark']) {
        if (onlyScheme && onlyScheme !== scheme) continue;
        const page = await browser.newPage();
        await page.setViewport({ width: 1366, height: 900 });
        await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: scheme }]);
        const url = `${baseUrl}/${p}`;
        console.log(`Loading ${url} (${scheme})`);
        try {
          const resp = await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
          const status = resp && resp.status ? resp.status() : 'unknown';
          if (status !== 200 && status !== 0) {
            console.warn(`Warning: ${url} returned HTTP ${status}`);
          }
          const outPath = path.join(outDir, `${name}-${scheme}.png`);
          await page.screenshot({ path: outPath, fullPage: true });
          console.log(`Saved ${outPath}`);
        } catch (err) {
          console.error(`Error loading ${url}: ${err.message}`);
        } finally {
          await page.close();
        }
      }
    }
  } finally {
    await browser.close();
    console.log('Visual-check complete. Screenshots saved to', outDir);
  }
})();

