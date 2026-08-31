VAREEN Academy — Static Build Instructions

This repository contains a static-friendly version of the VAREEN Academy website (HTML, CSS, JS).

What changed for the static build
- Forms (`contact.html`, `apply.html`) now use client-side simulated submissions so the site works without a PHP backend.
- `sw.js` no longer attempts to fetch or cache `/api/` endpoints.
- `api/` folder remains in the repo for reference but is not required for static hosting.

How to host
- Use any static host (GitHub Pages, Netlify, Vercel, or a simple static server).

Local testing (PowerShell)
```powershell
# Serve the current directory on port 8000 (requires Python installed)
python -m http.server 8000
# Then open http://localhost:8000 in your browser
```

Notes & recommendations
- If you want real form submissions, integrate a server endpoint or use a third-party service (Formspree, Netlify Forms, etc.).
- For production PWA features (push, background sync), a backend is needed — the current static setup keeps the PWA shell but avoids server API calls.

If you want, I can convert forms to use a chosen third-party service or produce HTML-only templates for exporting to a CMS.

