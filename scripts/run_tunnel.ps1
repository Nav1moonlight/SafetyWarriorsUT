# Cloudflare Tunnel Runner for Impact Warrior UT BJM
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

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "   IMPACT WARRIOR - CLOUDFLARE SECURE TUNNEL" -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "Membuka jalur tunnel HTTPS resmi Cloudflare..." -ForegroundColor Yellow
Write-Host "Koneksi mengarah ke: http://127.0.0.1:8000" -ForegroundColor Gray
Write-Host "---------------------------------------------------" -ForegroundColor DarkGray
Write-Host "Petunjuk:" -ForegroundColor White
Write-Host "- Salin URL 'https://...trycloudflare.com' yang muncul di bawah." -ForegroundColor Gray
Write-Host "- Buka di Google Chrome pada HP Android Anda." -ForegroundColor Gray
Write-Host "- Klik menu (titik tiga) > 'Tambahkan ke Layar Utama' / 'Instal Aplikasi'." -ForegroundColor Gray
Write-Host "---------------------------------------------------" -ForegroundColor DarkGray
Write-Host "Menghubungkan ke Cloudflare Edge... (Tekan Ctrl+C untuk berhenti)`n" -ForegroundColor Cyan

# Jalankan cloudflared langsung di foreground terminal agar output dan URL trycloudflare terlihat interaktif
& $cloudflaredBin tunnel --url http://127.0.0.1:8000 --edge-ip-version 4
