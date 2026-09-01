# VAREEN Academy — Quick Smoke Test Checklist

This document explains quick manual and automated smoke checks you can run locally to verify the recent UI fixes (navbar spacing, contrast, install banner removal, forms wiring).

## 1) Run a local static server
From the project root (`c:\Users\user\OneDrive\Desktop\vareen academy`), start a simple static server.

PowerShell - recommended (if Python 3 is installed):
```powershell
py -3 -m http.server 8000
# or
python -m http.server 8000
```

Node (if you have npm/node):
```powershell
npx serve . -p 8000
```

Open http://localhost:8000 in your browser.

## 2) Automated smoke script (PowerShell)
A helper script is included at `scripts\smoke-test.ps1`.

Run it from the project root (in PowerShell):
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\smoke-test.ps1
```

The script checks a list of key pages for HTTP 200 and verifies presence of a few important tokens:
- `class="navbar"` — page includes the navigation
- `text-contrast` — our explicit white-text utility on dark backgrounds
- `data-api` — forms wired to backend (where applicable)

Exit code `0` means all checks passed. Non-zero indicates one or more failures.

## 3) Manual visual checklist
Open these pages and verify the listed items visually in light and dark OS/browser themes:

- index.html
  - Navbar is not overlapping the hero (top content visible)
  - Buttons that should be white use visible white (on dark backgrounds) or readable text on light background
  - Install banner text removed (no visible "Install VAREEN Academy for a better experience")

- about.html / achievements.html / programs.html
  - Hero and CTA sections with `bg-primary` show white text (check `text-contrast` classes)
  - Milestone badges and stats are readable

- apply.html / contact.html
  - Forms submit behavior: when backend absent they fall back to simulated submission; when `data-api` is present they will POST via JS
  - Check that forms have `data-api` attribute if you want real API POSTs

- gallery.html
  - Lightbox and gallery images load; no console errors

- offline.html and 404.html
  - Serve correctly as static pages

- Footer (any page)
  - Social links visible and readable (use `text-contrast` in dark footer)

## 4) Service worker and PWA / SW checks
- In Chrome/Edge, open DevTools > Application > Service Workers
  - Confirm `sw.js` is registered (if you served over `http://localhost:8000` — note: some browsers require `localhost` or `https` for SW)
  - For an installed PWA flow, check that the install banner text has been removed

## 5) Light / Dark theme verification
- Toggle OS theme or use DevTools > Rendering to emulate `prefers-color-scheme: dark`/`light`.
- Confirm no white text on white backgrounds and that white text appears correctly on dark backgrounds.

## 6) If you see issues
- Copy a screenshot and the page URL (e.g. `http://localhost:8000/about.html`) and paste here.
- I can patch specific pages or adjust CSS further.

---

If you'd like, I can also add a small Node-based visual test harness (Puppeteer) to take screenshots in both light and dark modes and report diffs — tell me and I'll scaffold it (adds a small `package.json` and script).

