# Impact Warrior Background Runner
param(
    [switch]$OpenBrowser,
    [switch]$Quiet
)

$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $projectRoot

# 1. Deteksi executable PHP
$phpBin = $null
if (Get-Command php -ErrorAction SilentlyContinue) {
    $phpBin = (Get-Command php).Source
} elseif (Test-Path "$env:USERPROFILE\.config\herd-lite\bin\php.exe") {
    $phpBin = "$env:USERPROFILE\.config\herd-lite\bin\php.exe"
} elseif (Test-Path "C:\php\php.exe") {
    $phpBin = "C:\php\php.exe"
}

if (-not $phpBin) {
    Write-Error "[Impact Warrior] PHP binary tidak ditemukan. Pastikan PHP terinstall dan terdaftar di PATH."
    exit 1
}

$logsDir = Join-Path $projectRoot "storage\logs"
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}

$serverLog = Join-Path $logsDir "server.log"
$schedLog = Join-Path $logsDir "scheduler.log"

# 2. Cek apakah Web Server (Port 8000) sudah aktif
$serverConn = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if (-not $serverConn) {
    if (-not $Quiet) { Write-Host "[1/2] Menjalankan Laravel Web Server (Port 8000)..." -ForegroundColor Cyan }
    $cmdArg = "/c `"`"$phpBin`" artisan serve --host=0.0.0.0 --port=8000 >> `"$serverLog`" 2>&1`""
    Start-Process -FilePath "cmd.exe" -ArgumentList $cmdArg -WorkingDirectory $projectRoot -WindowStyle Hidden
} else {
    if (-not $Quiet) { Write-Host "[1/2] Web Server sudah aktif di port 8000." -ForegroundColor Green }
}

# 3. Cek apakah Scheduler (schedule:work untuk sinkronisasi Google Sheet) sudah aktif
$existingScheduler = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
    Where-Object { $_.CommandLine -like "*artisan*schedule:work*" }

if (-not $existingScheduler) {
    if (-not $Quiet) { Write-Host "[2/2] Menjalankan Background Scheduler Sinkronisasi Google Sheet..." -ForegroundColor Cyan }
    $schedCmdArg = "/c `"`"$phpBin`" artisan schedule:work >> `"$schedLog`" 2>&1`""
    Start-Process -FilePath "cmd.exe" -ArgumentList $schedCmdArg -WorkingDirectory $projectRoot -WindowStyle Hidden
} else {
    if (-not $Quiet) { Write-Host "[2/2] Background Scheduler sudah berjalan." -ForegroundColor Green }
}

# Tunggu sebentar hingga server mengikat port
Start-Sleep -Seconds 2

if (-not $Quiet) {
    Write-Host ""
    Write-Host "Impact Warrior siap digunakan di: http://127.0.0.1:8000" -ForegroundColor Green
}

if ($OpenBrowser) {
    Start-Process "http://127.0.0.1:8000"
}
