# Font Awesome — Self-Hosted Font Files

This directory holds the self-hosted Font Awesome 6.5.2 font files.
Download them from https://fontawesome.com/download (Free version)
or from the CDN and place the `.woff2` files here.

## Required Files

| File | Weight | Usage |
|------|--------|-------|
| `fa-solid-900.woff2` | 900 | `fas`, `fa-solid` icons (most common) |
| `fa-regular-400.woff2` | 400 | `far`, `fa-regular` icons |
| `fa-brands-400.woff2` | 400 | `fab`, `fa-brands` icons |
| `fa-v4compatibility.woff2` | 400 | Legacy v4 icon shims |

## Quick Download (PowerShell)

```powershell
$baseUrl = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts"
$files = @("fa-solid-900.woff2", "fa-regular-400.woff2", "fa-brands-400.woff2", "fa-v4compatibility.woff2")
foreach ($f in $files) {
    Invoke-WebRequest -Uri "$baseUrl/$f" -OutFile "$f"
}
```

## Fallback

If these font files are missing, `layout.php` automatically detects this
and falls back to the Font Awesome CDN. This ensures icons always render,
even before the local font files are in place.

## License

Font Awesome Free — CC BY 4.0 (Icons), SIL OFL 1.1 (Fonts), MIT (Code)
Copyright 2024 Fonticons, Inc.
