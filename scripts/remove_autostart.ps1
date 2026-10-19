<#
.SYNOPSIS
    Menghapus autostart Impact Warrior dari Windows.
#>
$startupDir = [Environment]::GetFolderPath('Startup')
$shortcutPath = Join-Path $startupDir "ImpactWarrior_Server.lnk"

if (Test-Path $shortcutPath) {
    Remove-Item -Path $shortcutPath -Force
    Write-Host "✓ Shortcut di Startup Folder berhasil dihapus." -ForegroundColor Green
}

try {
    $task = Get-ScheduledTask -TaskName "ImpactWarrior_AutoStart" -ErrorAction SilentlyContinue
    if ($task) {
        Unregister-ScheduledTask -TaskName "ImpactWarrior_AutoStart" -Confirm:$false
        Write-Host "✓ Task Scheduler 'ImpactWarrior_AutoStart' berhasil dihapus." -ForegroundColor Green
    }
} catch {}

Write-Host "`n✓ Auto-Start Impact Warrior berhasil dinonaktifkan." -ForegroundColor Yellow
