# Create Desktop Shortcut for Impact Warrior UT BJM
$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
$desktopDir = [Environment]::GetFolderPath('Desktop')
$shortcutPath = Join-Path $desktopDir "Impact Warrior UT BJM.lnk"
$iconPath = Join-Path $projectRoot "public\images\app.ico"
$launcherVbs = Join-Path $projectRoot "START_APP_MODE.vbs"

Write-Host "Membuat Shortcut Desktop Impact Warrior UT BJM..." -ForegroundColor Cyan

try {
    $wshShell = New-Object -ComObject WScript.Shell
    $shortcut = $wshShell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = "wscript.exe"
    $shortcut.Arguments = '"' + $launcherVbs + '"'
    $shortcut.WorkingDirectory = $projectRoot
    $shortcut.Description = "Buka Impact Warrior UT BJM (App Mode Window)"
    
    if (Test-Path $iconPath) {
        $shortcut.IconLocation = "$iconPath, 0"
    }
    
    $shortcut.Save()
    
    Write-Host "Berhasil membuat shortcut di Desktop:" -ForegroundColor Green
    Write-Host "  -> $shortcutPath" -ForegroundColor Yellow
} catch {
    Write-Error "Gagal membuat shortcut di Desktop: $_"
}
