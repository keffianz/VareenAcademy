# Lighthouse Audit — Quick Guide

This document describes how to run Lighthouse audits locally against the static site.

## Requirements
- Node.js (for `npx lighthouse`) OR Docker + the provided `docker-compose.yml`.
- A local static server serving the project on `http://localhost:8000` (see `SMOKE_TESTS.md`).

## PowerShell (Windows)
1. Start the static server (from project root):
```powershell
py -3 -m http.server 8000
```
2. Run the Lighthouse script:
```powershell
powershell -ExecutionPolicy Bypass -File .\tools\run-lighthouse.ps1
```
3. Output is written to `lighthouse-reports/` as HTML and JSON files.

## Bash / macOS / WSL
1. Start the static server:
```bash
python3 -m http.server 8000
```
2. Run the script:
```bash
./tools/run-lighthouse.sh http://localhost:8000
```

## Using Docker (optional, when running `docker compose up --build`)
- The `visual` container runs headless Chromium for Puppeteer tasks. You can run Lighthouse inside a custom container too; if you want I can add a Lighthouse job to `docker-compose.yml`.

## What the audit covers
- Performance, Accessibility, Best Practices, and SEO.
- The scripts run Lighthouse in desktop emulation. You can change `--emulated-form-factor=mobile` if you want mobile-focused metrics.

## Next steps after running
- Share the HTML report(s) or the score numbers and I will provide targeted recommendations (image optimizations, caching, critical CSS, etc.).
- Optionally I can add an automated CI job to run Lighthouse and fail the build when scores fall below thresholds.

