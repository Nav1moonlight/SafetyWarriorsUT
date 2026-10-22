# Cloudflare Tunnel Runner for Impact Warrior UT BJM
param(
    [switch]$Background
)

$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
Set-Location $projectRoot

# 1. Pastikan web server lokal sudah aktif
& "$projectRoot\scripts\app_runner.ps1" -Quiet

$cloudflaredBin = "cloudflared"
$cfPaths = @(
    "C:\Program Files (x86)\cloudflared\cloudflared.exe",
    "C:\Program Files\cloudflared\cloudflared.exe"
)
foreach ($p in $cfPaths) {
    if (Test-Path $p) {
        $cloudflaredBin = $p
        break
    }
}

$logsDir = Join-Path $projectRoot "storage\logs"
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}
$cfLog = Join-Path $logsDir "cloudflared.log"

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "   IMPACT WARRIOR - CLOUDFLARE SECURE TUNNEL" -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "Membuka jalur tunnel HTTPS publik gratis via Cloudflare..." -ForegroundColor Yellow

# Hentikan proses cloudflared lama jika ada
Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

# Jalankan cloudflared tunnel mengarah ke http://127.0.0.1:8000
if (Test-Path $cfLog) { Remove-Item $cfLog -Force -ErrorAction SilentlyContinue }

$proc = Start-Process -FilePath $cloudflaredBin `
    -ArgumentList "tunnel", "--url", "http://127.0.0.1:8000" `
    -RedirectStandardError $cfLog `
    -WindowStyle Hidden `
    -PassThru

Write-Host "Menghubungkan ke jaringan Cloudflare Edge..." -ForegroundColor Gray

# Cari URL trycloudflare.com dari log
$tunnelUrl = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $cfLog) {
        $logContent = Get-Content $cfLog -Raw -ErrorAction SilentlyContinue
        if ($logContent -match "https://[a-zA-Z0-9\-]+\.trycloudflare\.com") {
            $tunnelUrl = $matches[0]
            break
        }
    }
}

if ($tunnelUrl) {
    Write-Host "`n✓ TUNNEL HTTPS BERHASIL AKTIF!" -ForegroundColor Green
    Write-Host "---------------------------------------------------" -ForegroundColor DarkGray
    Write-Host "URL PUBLIK ANDA (HTTPS AMAN):" -ForegroundColor Cyan
    Write-Host ">> $tunnelUrl" -ForegroundColor Yellow -BackgroundColor Black
    Write-Host "---------------------------------------------------" -ForegroundColor DarkGray
    Write-Host "📱 Cara Install di HP Android (PWA):" -ForegroundColor White
    Write-Host "   1. Buka URL di atas menggunakan Google Chrome di HP Anda." -ForegroundColor Gray
    Write-Host "   2. Klik menu titik tiga (⋮) di Chrome > pilih 'Tambahkan ke Layar Utama' / 'Instal Aplikasi'." -ForegroundColor Gray
    Write-Host "   3. Aplikasi Impact Warrior siap dipakai di HP seperti aplikasi native!`n" -ForegroundColor Gray
    
    # Simpan URL terakhir ke file agar mudah dibaca script lain
    $urlFile = Join-Path $projectRoot "storage\logs\tunnel_url.txt"
    Set-Content -Path $urlFile -Value $tunnelUrl
    
    if (-not $Background) {
        Write-Host "Tekan Ctrl+C untuk menutup tunnel, atau biarkan jendela ini tetap terbuka." -ForegroundColor DarkYellow
        # Tunggu proses
        $proc.WaitForExit()
    }
} else {
    Write-Error "Gagal mendeteksi URL Cloudflare Tunnel. Silakan periksa log di storage\logs\cloudflared.log"
}
