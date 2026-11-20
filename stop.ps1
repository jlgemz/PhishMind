Write-Host "🛑 Stopping PhishGuard..." -ForegroundColor Cyan

Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -like "*app.py*" -or $_.CommandLine -like "*artisan serve*"
} | ForEach-Object {
    Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
}

Write-Host "✅ All PhishGuard services stopped!" -ForegroundColor Green
