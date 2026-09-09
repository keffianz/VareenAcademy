# Font Awesome 7.3.1 — Self-Hosted Font Files

This directory holds the self-hosted Font Awesome 7.3.1 font files.

## ⚠️ IMPORTANT: Upload These Files to Your Server

**The font files MUST exist on your server at `/public/icons/` for icons to render.**

The CSS (`../css/font-awesome-local.css`) references these fonts via relative paths:
```css
src: url(../fonts/fa-solid-900.woff2);
src: url(../fonts/fa-regular-400.woff2);
src: url(../fonts/fa-brands-400.woff2);
src: url(../fonts/fa-v4compatibility.woff2);
```

## Required Files

| File | Size | Usage |
|------|------|-------|
| `fa-solid-900.woff2` | ~117KB | `fas`, `fa-solid` icons (most common) |
| `fa-regular-400.woff2` | ~20KB | `far`, `fa-regular` icons |
| `fa-brands-400.woff2` | ~113KB | `fab`, `fa-brands` icons |
| `fa-v4compatibility.woff2` | ~4KB | Legacy v4 icon shims |

## Content Security Policy (CSP)

Your server's CSP allows fonts from `'self'` only:
```
font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net
```

Since these font files are on your own server (`'self'`), they are allowed. The CDN fallback was **removed** because `cdnjs.cloudflare.com` is NOT in the CSP `font-src` directive.

**Do NOT re-add the CDN fallback** — it will be blocked by CSP.

## Troubleshooting

If icons still show as empty boxes:

1. **Verify files exist on server** — Check that `/public/fonts/fa-solid-900.woff2` returns HTTP 200
2. **Check MIME type** — Server must serve `.woff2` as `font/woff2`
3. **Clear browser cache** — Hard refresh with Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
4. **Check DevTools Console** — Look for 404 errors or CSP violations

## License

Font Awesome Free 7.3.1 — CC BY 4.0 (Icons), SIL OFL 1.1 (Fonts), MIT (Code)
Copyright 2026 Fonticons, Inc.
