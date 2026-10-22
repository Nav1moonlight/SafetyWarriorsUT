Add-Type -AssemblyName System.Drawing

$projectRoot = (Resolve-Path "$PSScriptRoot\..").Path
$srcPath = Join-Path $projectRoot "public\images\logo.png"
$destDir = Join-Path $projectRoot "public\icons"

if (-not (Test-Path $destDir)) { 
    New-Item -ItemType Directory -Path $destDir | Out-Null 
}

$srcImg = [System.Drawing.Image]::FromFile($srcPath)
$sizes = @(72, 96, 128, 144, 152, 192, 384, 512)

foreach ($s in $sizes) {
    $bmp = New-Object System.Drawing.Bitmap $s, $s
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.Clear([System.Drawing.Color]::Transparent)
    $g.DrawImage($srcImg, 0, 0, $s, $s)
    $g.Dispose()
    
    $outPath = Join-Path $destDir ("icon-${s}x${s}.png")
    $bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Host "Created: $outPath"
}

# Generate 128x128 icon
$icoBmp = New-Object System.Drawing.Bitmap 128, 128
$gIco = [System.Drawing.Graphics]::FromImage($icoBmp)
$gIco.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$gIco.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
$gIco.Clear([System.Drawing.Color]::Transparent)
$gIco.DrawImage($srcImg, 0, 0, 128, 128)
$gIco.Dispose()

$hIcon = $icoBmp.GetHicon()
$icon = [System.Drawing.Icon]::FromHandle($hIcon)

$icoFile = [System.IO.File]::OpenWrite((Join-Path $projectRoot "public\favicon.ico"))
$icon.Save($icoFile)
$icoFile.Close()

$appIcoPath = Join-Path $projectRoot "public\images\app.ico"
$icoFile2 = [System.IO.File]::OpenWrite($appIcoPath)
$icon.Save($icoFile2)
$icoFile2.Close()

$icon.Dispose()
$icoBmp.Dispose()
$srcImg.Dispose()

Write-Host "PWA icons and app.ico generated successfully!"
