<#
.SYNOPSIS
    Menghentikan seluruh proses Impact Warrior (Web Server & Background Scheduler).
#>
$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path

Write-Host "Menghentikan layanan Impact Warrior..." -ForegroundColor Yellow

$stoppedCount = 0

# 1. Hentikan proses yang mendengarkan di Port 8000
$connections = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($connections) {
    foreach ($c in $connections) {
        $pidToKill = $c.OwningProcess
        if ($pidToKill -gt 0) {
            Stop-Process -Id $pidToKill -Force -ErrorAction SilentlyContinue
            Write-Host "✓ Menghentikan Web Server (PID: $pidToKill)" -ForegroundColor Green
            $stoppedCount++
        }
    }
}

# 2. Hentikan seluruh proses PHP artisan yang berjalan di direktori proyek ini
$phpProcesses = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue |
    Where-Object { 
        ($_.CommandLine -like "*artisan*serve*" -or $_.CommandLine -like "*artisan*schedule:work*") -and
        ($_.CommandLine -like "*SafetyWarrior*" -or $_.ExecutablePath -like "*SafetyWarrior*")
    }

foreach ($p in $phpProcesses) {
    Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
    Write-Host "✓ Menghentikan proses background (PID: $($p.ProcessId))" -ForegroundColor Green
    $stoppedCount++
}

if ($stoppedCount -eq 0) {
    Write-Host "Tidak ada proses Impact Warrior yang sedang aktif." -ForegroundColor Gray
} else {
    Write-Host "`n✓ Layanan Impact Warrior berhasil dihentikan." -ForegroundColor Green
}
