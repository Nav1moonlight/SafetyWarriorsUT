# Auto-start Impact Warrior
$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
$silentVbs = "$projectRoot\scripts\silent_startup.vbs"

Write-Host "Mengonfigurasi Auto-Start Impact Warrior..." -ForegroundColor Cyan

try {
    $startupDir = [Environment]::GetFolderPath('Startup')
    $shortcutPath = Join-Path $startupDir "ImpactWarrior_Server.lnk"
    
    $wshShell = New-Object -ComObject WScript.Shell
    $shortcut = $wshShell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = "wscript.exe"
    $shortcut.Arguments = '"' + $silentVbs + '"'
    $shortcut.WorkingDirectory = $projectRoot
    $shortcut.Description = "Auto-start Impact Warrior Laravel Server and Scheduler Silently"
    $shortcut.Save()
    
    Write-Host "Berhasil memasang shortcut di Startup Folder Windows:" -ForegroundColor Green
    Write-Host "  -> $shortcutPath" -ForegroundColor Gray
    Write-Host "`nPengaturan Selesai! Impact Warrior akan otomatis berjalan di background setiap kali komputer menyala." -ForegroundColor Green
} catch {
    Write-Warning "Gagal membuat shortcut Startup: $_"
}
