<#
.SYNOPSIS
    Memeriksa status aktifitas Impact Warrior dan Auto-Start.
#>
$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "   IMPACT WARRIOR - STATUS MONITOR SISTEM" -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan

# 1. Cek Web Server
$conn = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($conn) {
    $procId = $conn.OwningProcess[0]
    Write-Host "[WEB SERVER]        : " -NoNewline
    Write-Host "AKTIF (RUNNING)" -ForegroundColor Green
    Write-Host "  - Alamat URL      : http://127.0.0.1:8000"
    Write-Host "  - Port            : 8000"
    Write-Host "  - Process ID (PID): $procId"
} else {
    Write-Host "[WEB SERVER]        : " -NoNewline
    Write-Host "NONAKTIF (STOPPED)" -ForegroundColor Red
}

# 2. Cek Scheduler
$scheduler = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
    Where-Object { $_.CommandLine -like "*artisan*schedule:work*" }
if ($scheduler) {
    Write-Host "[AUTO-SYNC SCHED]   : " -NoNewline
    Write-Host "AKTIF (RUNNING)" -ForegroundColor Green
    Write-Host "  - Process ID (PID): $($scheduler.ProcessId[0])"
    Write-Host "  - Frekuensi       : Setiap 5 menit (Google Sheet Auto-Sync)"
} else {
    Write-Host "[AUTO-SYNC SCHED]   : " -NoNewline
    Write-Host "NONAKTIF (STOPPED)" -ForegroundColor Yellow
}

# 3. Cek Status Autostart di Windows
$startupLnk = "$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Startup\ImpactWarrior_Server.lnk"
$task = Get-ScheduledTask -TaskName "ImpactWarrior_AutoStart" -ErrorAction SilentlyContinue

Write-Host "[AUTOSTART WINDOWS] : " -NoNewline
if ((Test-Path $startupLnk) -or ($task -ne $null)) {
    Write-Host "TERPASANG (ENABLED)" -ForegroundColor Green
    if (Test-Path $startupLnk) { Write-Host "  - Metode Startup  : Folder Startup Windows" }
    if ($task) { Write-Host "  - Metode Task     : Task Scheduler (Saat Log On)" }
} else {
    Write-Host "TIDAK TERPASANG (DISABLED)" -ForegroundColor Gray
}

Write-Host "===================================================" -ForegroundColor Cyan
