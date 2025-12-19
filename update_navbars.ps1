# PowerShell script to remove logo from navbar in all HTML files

$files = @(
    'services.html',
    'online-classes.html', 
    'contact.html',
    'apply.html',
    'achievements.html',
    'gallery.html'
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Processing $file..."
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Pattern to match the navbar-brand with logo
        $pattern = '(<a class="navbar-brand" href="index\.html" aria-label="VAREEN Academy homepage">)\s*<img src="images/main-logo\.png"[^>]*>\s*(VAREEN Academy)'
        $replacement = '$1`r`n                $2'
        
        $newContent = $content -replace $pattern, $replacement
        
        if ($content -ne $newContent) {
            Set-Content $file $newContent -NoNewline -Encoding UTF8
            Write-Host "✓ Updated $file"
        } else {
            Write-Host "- No changes needed for $file"
        }
    } else {
        Write-Host "✗ File not found: $file"
    }
}

Write-Host "`nAll files processed!"

