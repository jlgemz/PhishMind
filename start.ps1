Write-Host "🚀 Starting PhishGuard..." -ForegroundColor Cyan

# Stop existing processes if any
Get-Process -Name "python", "php" -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -like "*app.py*" -or $_.CommandLine -like "*artisan serve*"
} | Stop-Process -Force -ErrorAction SilentlyContinue

# Ensure MySQL is running
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    if (Test-Path "C:\xampp\mysql\bin\mysqld.exe") {
        Write-Host "Starting XAMPP MySQL..." -ForegroundColor Yellow
        Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini --standalone" -WorkingDirectory "C:\xampp\mysql" -WindowStyle Hidden
        Start-Sleep -Seconds 2
    }
}

$ScriptDir = $PSScriptRoot

# Start ML Service
Write-Host "Starting ML Service on port 8001..." -ForegroundColor Yellow
$mlVenvPython = Join-Path $ScriptDir "ml_service\venv\Scripts\python.exe"
if (-not (Test-Path $mlVenvPython)) {
    $mlVenvPython = "python"
}
Start-Process -FilePath $mlVenvPython -ArgumentList "app.py" -WorkingDirectory (Join-Path $ScriptDir "ml_service") -WindowStyle Hidden

# Wait for ML Service
Start-Sleep -Seconds 3

# Start Laravel
Write-Host "Starting Laravel on port 8000..." -ForegroundColor Yellow
Start-Process -FilePath "php" -ArgumentList "artisan serve --host=127.0.0.1 --port=8000" -WorkingDirectory $ScriptDir -WindowStyle Hidden

Write-Host ""
Write-Host "✅ PhishGuard is running!" -ForegroundColor Green
Write-Host "📊 Web Interface: http://localhost:8000" -ForegroundColor Cyan
Write-Host "🤖 ML Service:    http://localhost:8001" -ForegroundColor Cyan
Write-Host ""
Write-Host "To stop all services: .\stop.ps1" -ForegroundColor Gray
