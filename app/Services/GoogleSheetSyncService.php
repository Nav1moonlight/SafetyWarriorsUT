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
     * Dapatkan daftar username / nama terdaftar yang diizinkan (whitelist).
     * Jika kosong, semua nama diterima.
     */
    public function getAllowedUsers(): array
    {
        $raw = AppSetting::get('allowed_users', '');
        if (empty(trim($raw))) {
            return [];
        }

        $list = array_map('trim', preg_split('/[\r\n,]+/', $raw));
        return array_values(array_filter($list));
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

            $allowedUsers = $this->getAllowedUsers();
            $allowedLower = array_map('strtolower', $allowedUsers);

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $activeSheetRowIds = [];

            foreach ($rows as $row) {
                if (empty($row['reporter_name'])) {
                    continue;
                }

                // Cek whitelist nama terdaftar / username
                if (!empty($allowedLower) && !in_array(strtolower($row['reporter_name']), $allowedLower)) {
                    $skipped++;
                    continue;
                }

                $sheetRowId = md5($row['reported_at']->format('Y-m-d H:i:s') . '_' . $row['reporter_name'] . '_' . ($row['description'] ?? ''));
                $activeSheetRowIds[] = $sheetRowId;

                $points = SafetyReport::calculatePoints(
                    $row['has_unsafe_condition'],
                    $row['has_unsafe_act'],
                    $row['has_suggestion'],
                    $row['has_direct_action'],
                    $row['has_action'],
                    $row['has_ehs_activity'],
                    $row['has_csr_activity'],
                    $row['has_speaker_activity']
                );

                $report = SafetyReport::updateOrCreate(
                    ['sheet_row_id' => $sheetRowId],
                    [
                        'reported_at' => $row['reported_at'],
                        'nrp' => $row['nrp'] ?? null,
                        'reporter_name' => trim($row['reporter_name']),
                        'department' => trim($row['department'] ?: 'Umum'),
                        'activity_indicator' => $row['activity_indicator'] ?? null,
                        'indicator_category' => $row['indicator_category'] ?? 'EHS',
                        'has_unsafe_act' => $row['has_unsafe_act'],
                        'has_unsafe_condition' => $row['has_unsafe_condition'],
                        'has_suggestion' => $row['has_suggestion'],
                        'has_action' => $row['has_action'],
                        'has_direct_action' => $row['has_direct_action'],
                        'has_ehs_activity' => $row['has_ehs_activity'],
                        'has_csr_activity' => $row['has_csr_activity'],
                        'has_speaker_activity' => $row['has_speaker_activity'],
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

            // Hapus data lokal yang sudah dihapus di Google Sheet (Mirroring sinkronisasi penuh)
            $deleted = 0;
            if (!empty($activeSheetRowIds)) {
                $deleted = SafetyReport::whereNotNull('sheet_row_id')
                    ->whereNotIn('sheet_row_id', $activeSheetRowIds)
                    ->delete();
            } else {
                $deleted = SafetyReport::whereNotNull('sheet_row_id')->delete();
            }

            AppSetting::set('last_sync_at', Carbon::now()->toIso8601String());
            $syncMsg = "Berhasil: {$imported} baru, {$updated} diperbarui";
            if ($deleted > 0) {
                $syncMsg .= ", {$deleted} data lama dibersihkan";
            }
            if ($skipped > 0) {
                $syncMsg .= ", {$skipped} dilewati (bukan nama resmi)";
            }
            AppSetting::set('last_sync_status', $syncMsg);

            return [
                'success' => true,
                'message' => "Sinkronisasi selesai. {$imported} data baru ditambahkan, {$updated} diperbarui" . ($deleted > 0 ? ", {$deleted} data yang dihapus dari Sheet telah dibersihkan." : "."),
                'imported' => $imported,
                'updated' => $updated,
                'deleted' => $deleted,
                'skipped' => $skipped,
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
        $data = [];
        $headers = [];

        // Gunakan php://temp stream dengan fgetcsv untuk parsing CSV RFC-compliant
        // Ini memastikan baris dengan deskripsi multi-line / enter di dalam tanda kutip tidak terpotong menjadi baris rusak
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csvString);
        rewind($stream);

        $rowIdx = 0;
        while (($cols = fgetcsv($stream)) !== false) {
            // Lewati baris kosong
            if (empty($cols) || (count($cols) === 1 && $cols[0] === null)) {
                continue;
            }

            if ($rowIdx === 0) {
                foreach ($cols as $idx => $col) {
                    $headers[$idx] = strtolower(trim((string)$col));
                }
                $rowIdx++;
                continue;
            }
            $rowIdx++;

            if (empty($headers)) {
                continue;
            }

            $mapped = $this->mapColumns($headers, $cols);
            if ($mapped) {
                $data[] = $mapped;
            }
        }

        fclose($stream);
        return $data;
    }

    /**
     * Mapping kolom respon Google Sheet sesuai sistem indikator IMPACT WARRIOR
     */
    protected function mapColumns(array $headers, array $cols): ?array
    {
        $row = [
            'reported_at' => Carbon::now(),
            'reporter_name' => '',
            'department' => 'Umum',
            'activity_indicator' => '',
            'indicator_category' => 'EHS',
            'has_unsafe_act' => false,
            'has_unsafe_condition' => false,
            'has_suggestion' => false,
            'has_action' => false,
            'has_direct_action' => false,
            'has_ehs_activity' => false,
            'has_csr_activity' => false,
            'has_speaker_activity' => false,
            'description' => '',
            'photo_url' => '',
            'location' => '',
            'raw_payload' => [],
        ];

        $rawIndicatorText = '';

        // 1. Dapatkan teks indikator kegiatan terlebih dahulu dengan prioritas kolom khusus
        foreach ($cols as $idx => $val) {
            $val = trim($val);
            $header = $headers[$idx] ?? '';
            $row['raw_payload'][$header ?: "col_{$idx}"] = $val;

            if (empty($val)) continue;

            // Foto Bukti
            if (str_contains($header, 'foto') || str_contains($header, 'photo') || str_contains($header, 'gambar') || str_contains($val, 'drive.google.com') || (str_starts_with($val, 'http') && str_contains($header, 'bukti'))) {
                $row['photo_url'] = $val;
                continue;
            }

            // Keterangan / Deskripsi
            if (str_contains($header, 'keterangan') || str_contains($header, 'penjelasan') || str_contains($header, 'deskripsi') || str_contains($header, 'detail')) {
                $row['description'] = $val;
                continue;
            }

            // Timestamp
            if (str_contains($header, 'timestamp') || str_contains($header, 'waktu') || str_contains($header, 'tanggal') || $idx === 0) {
                try {
                    $row['reported_at'] = Carbon::parse($val);
                } catch (\Exception $e) {}
                continue;
            }

            // NRP Karyawan (jika ada kolom khusus nrp / nik / id)
            if (str_contains($header, 'nrp') || str_contains($header, 'nik') || str_contains($header, 'id karyawan') || str_contains($header, 'nomor registrasi')) {
                $row['nrp'] = $val;
                continue;
            }

            // Nama Pelapor (Bisa juga format "123456 - Nama Karyawan")
            if (str_contains($header, 'nama') || str_contains($header, 'name') || str_contains($header, 'pelapor')) {
                // Ekstrak NRP jika nama digabung format "123456 - Taufan" atau "Taufan (123456)"
                if (preg_match('/^(\d{5,12})\s*[-_|\/]\s*(.*)$/', $val, $m)) {
                    $row['nrp'] = $m[1];
                    $row['reporter_name'] = trim($m[2]);
                } elseif (preg_match('/^(.*?)\s*[\(\[](\d{5,12})[\)\]]$/', $val, $m)) {
                    $row['reporter_name'] = trim($m[1]);
                    $row['nrp'] = $m[2];
                } else {
                    $row['reporter_name'] = $val;
                }
                continue;
            }

            // Departemen
            if (str_contains($header, 'departemen') || str_contains($header, 'department') || str_contains($header, 'dept') || str_contains($header, 'divisi') || str_contains($header, 'section')) {
                if (!empty($val)) {
                    $row['department'] = $val;
                }
                continue;
            }

            // Jenis Temuan / Indikator Kegiatan
            if (str_contains($header, 'jenis') || str_contains($header, 'indikator') || str_contains($header, 'kategori') || $header === 'temuan' || str_contains($header, 'kegiatan')) {
                $rawIndicatorText = $val;
                continue;
            }

            // Lokasi
            if (str_contains($header, 'lokasi') || str_contains($header, 'area') || str_contains($header, 'tempat')) {
                $row['location'] = $val;
                continue;
            }
        }

        if (empty($row['reporter_name']) && isset($cols[1])) {
            $row['reporter_name'] = $cols[1];
        }
        if (($row['department'] === 'Umum' || empty($row['department'])) && isset($cols[2]) && !empty($cols[2])) {
            $row['department'] = $cols[2];
        }

        // Tentukan indikator kegiatan berdasarkan teks indikator kegiatan yang dipilih (bukan dari keterangan)
        $this->assignActivityIndicator($row, $rawIndicatorText);

        return !empty($row['reporter_name']) ? $row : null;
    }

    /**
     * Parsing dan pemetaan indikator kegiatan IMPACT WARRIOR
     */
    protected function assignActivityIndicator(array &$row, string $text): void
    {
        $t = strtolower(trim($text));
        $row['activity_indicator'] = $text ?: 'Kondisi Tidak Aman';

        // 1. Menjadi Narasumber / Pemateri EHS & CSR (+4 Poin)
        if (str_contains($t, 'narasumber') || str_contains($t, 'pemateri')) {
            $row['has_speaker_activity'] = true;
            $row['indicator_category'] = 'EHS / CSR';
            $row['activity_indicator'] = 'Menjadi Narasumber / Pemateri';
            return;
        }

        // 2. Mengikuti Kegiatan CSR (+2 Poin)
        if (str_contains($t, 'kegiatan csr') || (str_contains($t, 'csr') && str_contains($t, 'kegiatan'))) {
            $row['has_csr_activity'] = true;
            $row['indicator_category'] = 'CSR';
            $row['activity_indicator'] = 'Mengikuti Kegiatan CSR';
            return;
        }

        // 3. Mengikuti Kegiatan EHS (+2 Poin)
        if (str_contains($t, 'kegiatan ehs') || (str_contains($t, 'ehs') && str_contains($t, 'kegiatan'))) {
            $row['has_ehs_activity'] = true;
            $row['indicator_category'] = 'EHS';
            $row['activity_indicator'] = 'Mengikuti Kegiatan EHS';
            return;
        }

        // 4. Penanganan Langsung (+3 Poin)
        if (str_contains($t, 'penanganan langsung') || str_contains($t, 'tindakan langsung') || (str_contains($t, 'action') && !str_contains($t, 'masukan') && !str_contains($t, 'rekomendasi'))) {
            $row['has_direct_action'] = true;
            $row['has_action'] = true;
            $row['indicator_category'] = 'EHS';
            $row['activity_indicator'] = 'Penanganan Langsung';
            return;
        }

        // 5. Rekomendasi Perbaikan / Pencegahan (Masukan / Solusi) (+3 Poin)
        if (str_contains($t, 'masukan') || str_contains($t, 'rekomendasi') || str_contains($t, 'pencegahan') || str_contains($t, 'saran') || str_contains($t, 'solusi')) {
            $row['has_suggestion'] = true;
            $row['indicator_category'] = 'EHS';
            $row['activity_indicator'] = 'Rekomendasi Perbaikan / Pencegahan';
            return;
        }

        // 6. Tindakan / Pelaporan Near Miss (+2 Poin)
        if (str_contains($t, 'tindakan tidak aman') || str_contains($t, 'tindakan bahaya') || str_contains($t, 'near miss') || str_contains($t, 'unsafe act')) {
            $row['has_unsafe_act'] = true;
            $row['indicator_category'] = 'EHS';
            $row['activity_indicator'] = 'Tindakan / Pelaporan Near Miss';
            return;
        }

        // 7. Kondisi Tidak Aman (+1 Poin) - Default jika kondisi bahaya atau fallback
        $row['has_unsafe_condition'] = true;
        $row['indicator_category'] = 'EHS';
        $row['activity_indicator'] = 'Kondisi Tidak Aman';
    }
}
