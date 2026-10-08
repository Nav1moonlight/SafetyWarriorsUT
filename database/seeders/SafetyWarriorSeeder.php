<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\SafetyReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SafetyWarriorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppSetting::set('google_sheet_id', '1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');
        AppSetting::set('company_name', 'Safety Warrior K3');
        AppSetting::set('last_sync_status', 'Terkoneksi ke Google Sheet ID: 1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');

        SafetyReport::truncate();

        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        // Data sampel bulan berjalan dengan variasi skor realistis (37, 21, 18, 16, 9, 4, 0)
        $samples = [
            // Peringkat 1: Rizki Ramadhan (Dept Service) -> Total: 37 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 3, 9, 15),
                'reporter_name' => 'Rizki Ramadhan',
                'department' => 'Service',
                'has_unsafe_act' => true, // +5
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => true, // +4  => 15
                'description' => 'Mekanik tidak memakai helm & sarung tangan saat servis forklift. Langsung ditegur dan APD lengkap dipasang.',
                'photo_url' => '/sample_photos/IMG_20261002_225314_653.jpg',
                'location' => 'Workshop Bay 02',
            ],
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 7, 14, 20),
                'reporter_name' => 'Rizki Ramadhan',
                'department' => 'Service',
                'has_unsafe_act' => true, // +5
                'has_unsafe_condition' => false,
                'has_suggestion' => true, // +3
                'has_action' => true, // +4  => 12
                'description' => 'Kabel las terkelupas di dekat lintasan pejalan kaki. Dilakukan isolasi ganda dan dipasang pengait gantung.',
                'photo_url' => '/sample_photos/IMG_20261004_180750_443.jpg',
                'location' => 'Fabrikasi Service',
            ],
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 12, 10, 45),
                'reporter_name' => 'Rizki Ramadhan',
                'department' => 'Service',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => true, // +4  => 10 (Total: 37)
                'description' => 'Tumpahan oli hidrolik di lantai kerja. Segera diserap pasir absorbent dan dipasang rambu licin.',
                'photo_url' => '/sample_photos/IMG_20261004_181121_311.jpg',
                'location' => 'Area Washing Unit',
            ],

            // Peringkat 2: Rizal Pratama (Dept Service) -> Total: 21 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 5, 8, 30),
                'reporter_name' => 'Rizal Pratama',
                'department' => 'Service',
                'has_unsafe_act' => true, // +5
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => false, // => 11
                'description' => 'Dongkrak hidrolik bergeser miring saat penopang unit loader. Pekerjaan dihentikan dan dipasang safety stand.',
                'photo_url' => '/sample_photos/IMG_20261002_225314_653.jpg',
                'location' => 'Heavy Equipment Bay',
            ],
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 10, 11, 0),
                'reporter_name' => 'Rizal Pratama',
                'department' => 'Service',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => true, // +4 => 10 (Total: 21)
                'description' => 'Lampu darurat mati di ruang kompresor. Penggantian fitting lampu dan pengetesan baterai cadangan.',
                'photo_url' => '/sample_photos/IMG_20261004_180750_443.jpg',
                'location' => 'Ruang Kompresor',
            ],

            // Peringkat 3: Adi Sucipto (Dept Maintenance) -> Total: 18 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 4, 13, 10),
                'reporter_name' => 'Adi Sucipto',
                'department' => 'Maintenance',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => true, // +4 => 10
                'description' => 'Cover pelindung sproket conveyor terbuka. Dipasang penutup pelat pengaman dan dikunci baut.',
                'photo_url' => '/sample_photos/IMG_20261004_181121_311.jpg',
                'location' => 'Conveyor Line 1',
            ],
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 9, 15, 40),
                'reporter_name' => 'Adi Sucipto',
                'department' => 'Maintenance',
                'has_unsafe_act' => true, // +5
                'has_unsafe_condition' => false,
                'has_suggestion' => true, // +3
                'has_action' => false, // => 8 (Total: 18)
                'description' => 'Pekerjaan kelistrikan panel tanpa gembok LOTO. Diberikan instruksi kepatuhan SOP isolasi energi.',
                'photo_url' => '/sample_photos/IMG_20261002_225314_653.jpg',
                'location' => 'Panel MCC Utility',
            ],

            // Peringkat 4: Didi Wahyudi (Dept Produksi) -> Total: 16 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 6, 10, 15),
                'reporter_name' => 'Didi Wahyudi',
                'department' => 'Produksi',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => true, // +4 => 10
                'description' => 'Palet kayu menghalangi akses pintu keluar darurat. Segera dipindahkan ke staging rak buffer.',
                'photo_url' => '/sample_photos/IMG_20261004_180750_443.jpg',
                'location' => 'Pintu Evakuasi Sektor B',
            ],
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 14, 16, 20),
                'reporter_name' => 'Didi Wahyudi',
                'department' => 'Produksi',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => true, // +3
                'has_suggestion' => true, // +3
                'has_action' => false, // => 6 (Total: 16)
                'description' => 'Pin segel APAR rusak dan tekanan jarum di area merah. Dilaporkan untuk pengisian ulang.',
                'photo_url' => '/sample_photos/IMG_20261004_181121_311.jpg',
                'location' => 'Area Staging Material',
            ],

            // Peringkat 5: Budi Santoso (Dept Logistik) -> Total: 9 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 8, 11, 30),
                'reporter_name' => 'Budi Santoso',
                'department' => 'Logistik',
                'has_unsafe_act' => true, // +5
                'has_unsafe_condition' => false,
                'has_suggestion' => false,
                'has_action' => true, // +4 => 9
                'description' => 'Forklift beroperasi tanpa menyalakan lampu sirine rotator. Lampu diperbaiki dan diuji fungsi.',
                'photo_url' => '/sample_photos/IMG_20261002_225314_653.jpg',
                'location' => 'Gudang Finished Goods',
            ],

            // Peringkat 6: Hendra Wijaya (Dept HSE) -> Total: 4 poin
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 11, 9, 0),
                'reporter_name' => 'Hendra Wijaya',
                'department' => 'HSE',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => false,
                'has_suggestion' => false,
                'has_action' => true, // +4 => 4
                'description' => 'Pemasangan penutup pipa drainase darurat setelah inspeksi rutin.',
                'photo_url' => '/sample_photos/IMG_20261004_180750_443.jpg',
                'location' => 'Drainase Workshop',
            ],

            // Peringkat 7: Siti Rahmawati (Dept Logistik) -> Total: 0 poin (Belum ada poin temuan)
            [
                'reported_at' => Carbon::create($currentYear, $currentMonth, 2, 8, 0),
                'reporter_name' => 'Siti Rahmawati',
                'department' => 'Logistik',
                'has_unsafe_act' => false,
                'has_unsafe_condition' => false,
                'has_suggestion' => false,
                'has_action' => false, // 0 poin
                'description' => 'Pendaftaran data safety induction anggota tim baru.',
                'photo_url' => null,
                'location' => 'Kantor Logistik',
            ],
        ];

        foreach ($samples as $s) {
            $points = SafetyReport::calculatePoints(
                $s['has_unsafe_act'],
                $s['has_unsafe_condition'],
                $s['has_suggestion'],
                $s['has_action']
            );

            SafetyReport::create([
                'reported_at' => $s['reported_at'],
                'reporter_name' => $s['reporter_name'],
                'department' => $s['department'],
                'has_unsafe_act' => $s['has_unsafe_act'],
                'has_unsafe_condition' => $s['has_unsafe_condition'],
                'has_suggestion' => $s['has_suggestion'],
                'has_action' => $s['has_action'],
                'points' => $points,
                'description' => $s['description'],
                'photo_url' => $s['photo_url'],
                'location' => $s['location'],
            ]);
        }
    }
}
