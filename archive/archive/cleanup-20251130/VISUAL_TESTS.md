# Visual Tests (Puppeteer)

This file explains how to run the Puppeteer visual-check scaffolding added to the project.

## Setup
1. From the project root, install dependencies (requires Node.js >= 16):

```powershell
npm install
```

2. Start a static server at the project root (one of the following):

```powershell
py -3 -m http.server 8000
# or
python -m http.server 8000
# or (Node)
npx serve . -p 8000
```

Make sure the site is available at `http://localhost:8000`.

## Run visual-check
- Full (both light and dark):

```powershell
npm run visual-check
```

- Only light mode:

```powershell
npm run visual-check:light
```

- Only dark mode:

```powershell
npm run visual-check:dark
```

Screenshots are written to the `visual-screenshots/` directory in the project root. Filenames follow the pattern `<page>-<scheme>.png` (for example `about-dark.png`).

## Notes
- The script launches Puppeteer in headless mode and captures full-page screenshots at 1366x900 viewport.
- If you want pixel diffs, I can add a comparator using `pixelmatch` and a baseline directory; tell me if you want that.
- If your system blocks `puppeteer` downloads, you can install `puppeteer-core` and point to a local Chrome by setting the `PUPPETEER_EXECUTABLE_PATH` environment variable.

## Next steps
- After running, upload any screenshots that look wrong, or tell me which page(s) need further tweaks and I'll patch them.

