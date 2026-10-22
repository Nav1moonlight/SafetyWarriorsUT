# Launch Impact Warrior in App Mode (Chrome or Edge without URL bar / tabs)
param(
    [string]$Url = "http://127.0.0.1:8000"
)

$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path

# 1. Pastikan server lokal dan background scheduler sudah aktif
& "$PSScriptRoot\app_runner.ps1" -Quiet

# 2. Cari executable Chrome atau Microsoft Edge
$chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$chromePath86 = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
$edgePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$edgePath64 = "C:\Program Files\Microsoft\Edge\Application\msedge.exe"

$browserExe = $null

if (Test-Path $chromePath) {
    $browserExe = $chromePath
} elseif (Test-Path $chromePath86) {
    $browserExe = $chromePath86
} elseif (Test-Path $edgePath) {
    $browserExe = $edgePath
} elseif (Test-Path $edgePath64) {
    $browserExe = $edgePath64
}

# 3. Jalankan dalam --app mode
if ($browserExe) {
    $appArgs = "--app=$Url"
    Start-Process -FilePath $browserExe -ArgumentList $appArgs
} else {
    # Fallback ke default browser jika tidak ditemukan
    Start-Process $Url
}
