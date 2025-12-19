# Quick smoke-test script for VAREEN Academy static site
# Usage: Run a static server in project root (see SMOKE_TESTS.md), then:
#   powershell -ExecutionPolicy Bypass -File .\scripts\smoke-test.ps1

param(
    [string]$BaseUrl = "http://localhost:8000"
)

$pages = @(
    "index.html",
    "about.html",
    "programs.html",
    "services.html",
    "online-classes.html",
    "apply.html",
    "contact.html",
    "gallery.html",
    "achievements.html",
    "404.html",
    "offline.html"
)

$failures = @()

Write-Host "Starting smoke tests against: $BaseUrl" -ForegroundColor Cyan

foreach ($p in $pages) {
    $url = "$BaseUrl/$p"
    try {
        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -Method GET -TimeoutSec 15
        if ($resp.StatusCode -ne 200) {
            Write-Host "FAIL: $p -> HTTP $($resp.StatusCode)" -ForegroundColor Red
            $failures += "$p -> HTTP $($resp.StatusCode)"
            continue
        }

        $body = $resp.Content

        # Basic content checks
        $checks = @()
        if ($body -match "class=\"navbar"" ) { $checks += 'nav' }
        if ($body -match "text-contrast") { $checks += 'text-contrast' }
        if ($body -match "data-api") { $checks += 'data-api' }

        Write-Host "OK: $p (checks: $($checks -join ', '))" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR: $p -> $($_.Exception.Message)" -ForegroundColor Yellow
        $failures += "$p -> $($_.Exception.Message)"
    }
}

if ($failures.Count -gt 0) {
    Write-Host "\nSmoke tests completed with failures:" -ForegroundColor Red
    $failures | ForEach-Object { Write-Host " - $_" }
    exit 1
} else {
    Write-Host "\nSmoke tests completed: all pages returned HTTP 200 and passed basic presence checks." -ForegroundColor Green
    exit 0
}

