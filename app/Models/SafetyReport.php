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
        'reporter_name',
        'department',
        'has_unsafe_act',
        'has_unsafe_condition',
        'has_suggestion',
        'has_action',
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
        'points' => 'integer',
        'raw_payload' => 'array',
    ];

    /**
     * Hitung total poin berdasarkan aturan K3 Safety Warrior:
     * - Tindakan Tidak Aman (Unsafe Act) = +5
     * - Kondisi Tidak Aman (Unsafe Condition) = +3
     * - Masukan Pengendalian/Perbaikan = +3
     * - Action Perbaikan = +4
     */
    public static function calculatePoints(bool $unsafeAct, bool $unsafeCondition, bool $suggestion, bool $action): int
    {
        $points = 0;
        if ($unsafeAct) {
            $points += 5;
        }
        if ($unsafeCondition) {
            $points += 3;
        }
        if ($suggestion) {
            $points += 3;
        }
        if ($action) {
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
