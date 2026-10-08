<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PhotoProxyController extends Controller
{
    /**
     * Proxy dan cache foto dari Google Drive
     */
    public function show(string $id)
    {
        $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        if (empty($cleanId)) {
            return $this->fallbackSvg('ID Foto Tidak Valid');
        }

        $cacheDir = 'public/drive_cache';
        $cacheFile = "{$cacheDir}/{$cleanId}.jpg";

        // 1. Cek cache lokal
        if (Storage::exists($cacheFile) && Storage::size($cacheFile) > 1000) {
            $content = Storage::get($cacheFile);
            return response($content, 200, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // 2. Fetch URL langsung Google Drive Thumbnail
        $url = "https://drive.google.com/thumbnail?id={$cleanId}&sz=w1000";

        try {
            $response = Http::timeout(4)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SafetyWarrior/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                $body = $response->body();
                // Jika bukan halaman HTML login Google
                if (!str_contains($body, '<!doctype') && !str_contains($body, '<html') && !str_contains($body, 'accounts.google.com')) {
                    Storage::put($cacheFile, $body);
                    return response($body, 200, [
                        'Content-Type' => 'image/jpeg',
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            }
        } catch (\Exception $e) {
            // timeout / network issue
        }

        // 3. Fallback SVG jika folder Drive masih privat
        return $this->fallbackSvg('Akses Drive Privat');
    }

    /**
     * Tampilkan SVG Placeholder informatif
     */
    protected function fallbackSvg(string $reason)
    {
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400" fill="none">
    <rect width="600" height="400" fill="#0C0C0C"/>
    <rect x="1" y="1" width="598" height="398" stroke="#232323" stroke-width="2"/>
    <g transform="translate(300, 160)" text-anchor="middle">
        <circle cx="0" cy="0" r="30" fill="#1C1917"/>
        <path d="M-8 -4 L-8 -10 A 8 8 0 0 1 8 -10 L 8 -4 M-12 -4 L12 -4 L12 14 L-12 14 Z" fill="none" stroke="#FFC400" stroke-width="2.5" stroke-linecap="round"/>
        <circle cx="0" cy="5" r="2" fill="#FFC400"/>
        
        <text y="54" fill="#F5F5F5" font-family="'IBM Plex Mono', monospace" font-size="12" font-weight="bold" letter-spacing="1">FOTO DRIVE MASIH PRIVAT</text>
        <text y="74" fill="#8A8A8A" font-family="'IBM Plex Sans', sans-serif" font-size="11">Buka folder upload respon form di Google Drive</text>
        <text y="92" fill="#FFC400" font-family="'IBM Plex Sans', sans-serif" font-size="11">Ubah akses folder: "Siapa saja yang memiliki link (Pelihat)"</text>
    </g>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
