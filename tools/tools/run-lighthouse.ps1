<#
Quick Lighthouse runner (PowerShell)
Usage:
  1) Start a static server at http://localhost:8000
  2) From project root run:
     powershell -ExecutionPolicy Bypass -File .\tools\run-lighthouse.ps1
#>
param(
    [string]$url = "http://localhost:8000",
    [string]$outputDir = "lighthouse-reports"
)

if (-not (Test-Path $outputDir)) { New-Item -ItemType Directory -Path $outputDir | Out-Null }

# Run Lighthouse via npx (will download if needed)
$time = Get-Date -Format "yyyyMMdd-HHmmss"
$outHtml = Join-Path $outputDir "lighthouse-$($time).report.html"
$outJson = Join-Path $outputDir "lighthouse-$($time).report.json"

Write-Host "Running Lighthouse for $url" -ForegroundColor Cyan

# Performance focused audit with categories
$npx = "npx -y lighthouse $url --output html --output=json --output-path $outHtml --quiet --chrome-flags=\"--no-sandbox --headless\" --emulated-form-factor=desktop --only-categories=performance,accessibility,best-practices,seo"

Write-Host "Command: $npx" -ForegroundColor DarkGray
Invoke-Expression $npx

if ($LASTEXITCODE -eq 0) {
    Write-Host "Lighthouse finished. Reports saved to: $outputDir" -ForegroundColor Green
} else {
    Write-Host "Lighthouse returned non-zero exit code ($LASTEXITCODE)." -ForegroundColor Yellow
}

