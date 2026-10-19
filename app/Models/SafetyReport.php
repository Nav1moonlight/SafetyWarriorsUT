<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafetyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_row_id',
        'reported_at',
        'nrp',
        'reporter_name',
        'department',
        'activity_indicator',
        'indicator_category',
        'has_unsafe_act',
        'has_unsafe_condition',
        'has_suggestion',
        'has_action',
        'has_direct_action',
        'has_ehs_activity',
        'has_csr_activity',
        'has_speaker_activity',
        'points',
        'description',
        'photo_url',
        'location',
        'raw_payload',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'has_unsafe_act' => 'boolean',
        'has_unsafe_condition' => 'boolean',
        'has_suggestion' => 'boolean',
        'has_action' => 'boolean',
        'has_direct_action' => 'boolean',
        'has_ehs_activity' => 'boolean',
        'has_csr_activity' => 'boolean',
        'has_speaker_activity' => 'boolean',
        'points' => 'integer',
        'raw_payload' => 'array',
    ];

    /**
     * Hitung total poin berdasarkan aturan IMPACT WARRIOR:
     * - Kondisi Tidak Aman = +1
     * - Tindakan / Pelaporan Near Miss = +2
     * - Rekomendasi Perbaikan / Pencegahan (Masukan) = +3
     * - Penanganan Langsung (Action Langsung) = +3
     * - Mengikuti Kegiatan EHS = +2
     * - Mengikuti Kegiatan CSR = +2
     * - Menjadi Narasumber / Pemateri EHS & CSR = +4
     */
    public static function calculatePoints(
        bool $unsafeCondition,
        bool $unsafeAct,
        bool $suggestion,
        bool $directAction,
        bool $action,
        bool $ehsActivity = false,
        bool $csrActivity = false,
        bool $speakerActivity = false
    ): int {
        $points = 0;

        if ($unsafeCondition) {
            $points += 1;
        }
        if ($unsafeAct) {
            $points += 2;
        }
        if ($suggestion) {
            $points += 3;
        }
        if ($directAction || $action) {
            $points += 3;
        }
        if ($ehsActivity) {
            $points += 2;
        }
        if ($csrActivity) {
            $points += 2;
        }
        if ($speakerActivity) {
            $points += 4;
        }

        return $points;
    }

    /**
     * Format Google Drive URL agar bisa ditampilkan langsung sebagai gambar
     */
    public function getDirectPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_url) {
            return null;
        }

        $url = trim($this->photo_url);

        // Jika URL Google Drive, lewatkan melalui proxy controller lokal
        if (str_contains($url, 'drive.google.com')) {
            $fileId = null;
            if (preg_match('/[-\w]{25,}/', $url, $matches)) {
                $fileId = $matches[0];
            }

            if ($fileId) {
                return route('drive.image', ['id' => $fileId]);
            }
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return asset($url);
    }
}
