<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\SafetyReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetSyncService
{
    /**
     * Dapatkan Sheet ID dari settings atau default
     */
    public function getSheetId(): string
    {
        return AppSetting::get('google_sheet_id', '1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');
    }

    /**
     * Dapatkan format URL export CSV
     */
    public function getCsvUrl(?string $sheetId = null): string
    {
        $id = $sheetId ?: $this->getSheetId();

        if (str_starts_with($id, 'http://') || str_starts_with($id, 'https://')) {
            if (str_contains($id, 'pub?output=csv') || str_contains($id, 'export?format=csv') || str_contains($id, 'tqx=out:csv')) {
                return $id;
            }

            if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $id, $matches)) {
                $id = $matches[1];
            }
        }

        return "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv";
    }

    /**
     * Tes status koneksi ke Google Sheet:
     * - 'green': Terhubung dan ada data
     * - 'yellow': Terhubung tapi hanya header / data masih kosong
     * - 'red': Gagal (akses dibatasi atau ID salah)
     */
    public function testConnection(?string $sheetId = null): array
    {
        $url = $this->getCsvUrl($sheetId);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SafetyWarrior/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                $content = $response->body();
                if (!str_contains($content, '<!DOCTYPE html') && !str_contains($content, '<html')) {
                    $lines = array_filter(explode("\n", trim($content)));
                    $count = count($lines);

                    if ($count > 1) {
                        return [
                            'status' => 'green',
                            'success' => true,
                            'message' => 'Terhubung normal. Ditemukan ' . ($count - 1) . ' baris respon data laporan.',
                            'row_count' => $count - 1,
                        ];
                    } else {
                        return [
                            'status' => 'yellow',
                            'success' => true,
                            'message' => 'Terhubung ke Google Sheet, namun lembar respon masih kosong (hanya baris header).',
                            'row_count' => 0,
                        ];
                    }
                }
            }

            if ($response->status() === 401 || $response->status() === 403 || str_contains($response->body(), 'ServiceLogin')) {
                return [
                    'status' => 'red',
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Akses Google Sheet masih DIBATASI (Privat). Ubah akses menjadi "Siapa saja yang memiliki link: Pelihat" pada menu Bagikan.',
                ];
            }

            return [
                'status' => 'red',
                'success' => false,
                'status_code' => $response->status(),
                'message' => 'Gagal terhubung (Status: ' . $response->status() . '). Pastikan Sheet ID valid dan dapat diakses publik.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'red',
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sinkronisasi data dari Google Sheet ke database lokal
     */
    public function sync(?string $sheetId = null): array
    {
        $url = $this->getCsvUrl($sheetId);

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SafetyWarrior/1.0',
                ])
                ->get($url);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil data dari Google Sheet (HTTP ' . $response->status() . '). ' .
                        ($response->status() === 401 ? 'Harap atur hak akses menjadi "Siapa saja yang memiliki link: Pelihat".' : ''),
                ];
            }

            $csvContent = $response->body();
            if (str_contains($csvContent, '<!DOCTYPE html') && str_contains($csvContent, 'ServiceLogin')) {
                return [
                    'success' => false,
                    'message' => 'Google Sheet memerlukan login. Ubah hak akses menjadi Publik (Pelihat).',
                ];
            }

            $rows = $this->parseCsv($csvContent);
            if (empty($rows)) {
                return [
                    'success' => false,
                    'message' => 'Tidak ditemukan baris data respon pada Google Sheet.',
                ];
            }

            $imported = 0;
            $updated = 0;

            foreach ($rows as $row) {
                if (empty($row['reporter_name'])) {
                    continue;
                }

                $sheetRowId = md5($row['reported_at']->format('Y-m-d H:i:s') . '_' . $row['reporter_name'] . '_' . ($row['description'] ?? ''));

                $points = SafetyReport::calculatePoints(
                    $row['has_unsafe_act'],
                    $row['has_unsafe_condition'],
                    $row['has_suggestion'],
                    $row['has_action']
                );

                $report = SafetyReport::updateOrCreate(
                    ['sheet_row_id' => $sheetRowId],
                    [
                        'reported_at' => $row['reported_at'],
                        'reporter_name' => trim($row['reporter_name']),
                        'department' => trim($row['department'] ?: 'Umum'),
                        'has_unsafe_act' => $row['has_unsafe_act'],
                        'has_unsafe_condition' => $row['has_unsafe_condition'],
                        'has_suggestion' => $row['has_suggestion'],
                        'has_action' => $row['has_action'],
                        'points' => $points,
                        'description' => $row['description'] ?? null,
                        'photo_url' => $row['photo_url'] ?? null,
                        'location' => $row['location'] ?? null,
                        'raw_payload' => $row['raw_payload'] ?? null,
                    ]
                );

                if ($report->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            AppSetting::set('last_sync_at', Carbon::now()->toIso8601String());
            AppSetting::set('last_sync_status', "Berhasil: {$imported} baru, {$updated} diperbarui");

            return [
                'success' => true,
                'message' => "Sinkronisasi selesai. {$imported} data baru ditambahkan, {$updated} data diperbarui.",
                'imported' => $imported,
                'updated' => $updated,
                'total_rows' => count($rows),
            ];
        } catch (\Exception $e) {
            Log::error('Error sync Google Sheet: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal sinkronisasi data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse CSV string
     */
    protected function parseCsv(string $csvString): array
    {
        $lines = explode("\n", $csvString);
        $data = [];
        $headers = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $cols = str_getcsv($line);

            if ($i === 0) {
                foreach ($cols as $idx => $col) {
                    $headers[$idx] = strtolower(trim($col));
                }
                continue;
            }

            if (empty($headers) || count($cols) === 0) {
                continue;
            }

            $mapped = $this->mapColumns($headers, $cols);
            if ($mapped) {
                $data[] = $mapped;
            }
        }

        return $data;
    }

    /**
     * Mapping kolom
     */
    protected function mapColumns(array $headers, array $cols): ?array
    {
        $row = [
            'reported_at' => Carbon::now(),
            'reporter_name' => '',
            'department' => 'Umum',
            'has_unsafe_act' => false,
            'has_unsafe_condition' => false,
            'has_suggestion' => false,
            'has_action' => false,
            'description' => '',
            'photo_url' => '',
            'location' => '',
            'raw_payload' => [],
        ];

        foreach ($cols as $idx => $val) {
            $val = trim($val);
            $header = $headers[$idx] ?? '';
            $row['raw_payload'][$header ?: "col_{$idx}"] = $val;

            if (empty($val)) continue;

            if (str_contains($header, 'timestamp') || str_contains($header, 'waktu') || str_contains($header, 'tanggal') || $idx === 0) {
                try {
                    $row['reported_at'] = Carbon::parse($val);
                } catch (\Exception $e) {}
            }

            if (str_contains($header, 'nama') || str_contains($header, 'name') || str_contains($header, 'pelapor')) {
                $row['reporter_name'] = $val;
            }

            if (str_contains($header, 'departemen') || str_contains($header, 'department') || str_contains($header, 'dept') || str_contains($header, 'divisi') || str_contains($header, 'section')) {
                $row['department'] = $val;
            }

            $lowerVal = strtolower($val);
            if (str_contains($header, 'tindakan') || str_contains($header, 'kondisi') || str_contains($header, 'masukan') || str_contains($header, 'pengendalian') || str_contains($header, 'action') || str_contains($header, 'jenis') || str_contains($header, 'kategori') || str_contains($header, 'temuan')) {
                if (str_contains($lowerVal, 'tindakan bahaya') || str_contains($lowerVal, 'tindakan tidak aman') || str_contains($lowerVal, 'unsafe act')) {
                    $row['has_unsafe_act'] = true;
                }
                if (str_contains($lowerVal, 'kondisi bahaya') || str_contains($lowerVal, 'kondisi tidak aman') || str_contains($lowerVal, 'unsafe condition')) {
                    $row['has_unsafe_condition'] = true;
                }
                if (str_contains($lowerVal, 'masukan') || str_contains($lowerVal, 'solusi') || str_contains($lowerVal, 'pengendalian') || str_contains($lowerVal, 'saran')) {
                    $row['has_suggestion'] = true;
                }
                if (str_contains($lowerVal, 'action nyata') || str_contains($lowerVal, 'action') || str_contains($lowerVal, 'perbaikan')) {
                    $row['has_action'] = true;
                }
            }

            if (str_contains($header, 'foto') || str_contains($header, 'photo') || str_contains($header, 'gambar') || str_contains($header, 'bukti') || str_contains($val, 'drive.google.com') || str_contains($val, 'http')) {
                if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_contains($val, 'drive.google.com')) {
                    $row['photo_url'] = $val;
                }
            }

            if (str_contains($header, 'keterangan') || str_contains($header, 'deskripsi') || str_contains($header, 'penjelasan') || str_contains($header, 'detail')) {
                $row['description'] = $val;
            }

            if (str_contains($header, 'lokasi') || str_contains($header, 'area') || str_contains($header, 'tempat')) {
                $row['location'] = $val;
            }
        }

        if (empty($row['reporter_name']) && isset($cols[1])) {
            $row['reporter_name'] = $cols[1];
        }
        if ($row['department'] === 'Umum' && isset($cols[2]) && !empty($cols[2])) {
            $row['department'] = $cols[2];
        }

        if (!$row['has_unsafe_act'] && !$row['has_unsafe_condition'] && !$row['has_suggestion'] && !$row['has_action']) {
            $combinedText = strtolower(json_encode($cols));
            if (str_contains($combinedText, 'tindakan bahaya') || str_contains($combinedText, 'tindakan tidak aman')) $row['has_unsafe_act'] = true;
            if (str_contains($combinedText, 'kondisi bahaya') || str_contains($combinedText, 'kondisi tidak aman')) $row['has_unsafe_condition'] = true;
            if (str_contains($combinedText, 'masukan') || str_contains($combinedText, 'solusi')) $row['has_suggestion'] = true;
            if (str_contains($combinedText, 'action nyata') || str_contains($combinedText, 'action')) $row['has_action'] = true;

            if (!$row['has_unsafe_act'] && !$row['has_unsafe_condition'] && !$row['has_suggestion'] && !$row['has_action']) {
                $row['has_unsafe_condition'] = true;
            }
        }

        return !empty($row['reporter_name']) ? $row : null;
    }
}
