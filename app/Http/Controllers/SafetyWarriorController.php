<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\SafetyReport;
use App\Services\GoogleSheetSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SafetyWarriorController extends Controller
{
    protected GoogleSheetSyncService $syncService;

    public function __construct(GoogleSheetSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Tampilkan Dashboard Klasemen Safety Warrior (dengan Otomatis Sync jika Data Stale > 3 Menit)
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $selectedYear = (int) $request->input('year', $now->year);
        $selectedMonth = (int) $request->input('month', $now->month);

        // Auto-sync otomatis jika data sudah lebih dari 3 menit sejak sinkronisasi terakhir
        $lastSyncAt = AppSetting::get('last_sync_at');
        if (!$lastSyncAt || Carbon::parse($lastSyncAt)->diffInMinutes($now) >= 3) {
            try {
                $this->syncService->sync();
                $lastSyncAt = AppSetting::get('last_sync_at');
            } catch (\Exception $e) {
                Log::warning('Auto-sync background on load failed: ' . $e->getMessage());
            }
        }

        // Rentang periode evaluasi K3: Tanggal 1 jam 00:00 s.d. Tanggal 25 jam 23:59:59
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0);
        $endDate = Carbon::create($selectedYear, $selectedMonth, 25, 23, 59, 59);

        // Status Cut-off
        $isCurrentMonth = ($selectedYear === $now->year && $selectedMonth === $now->month);
        $cutoffDate = Carbon::create($selectedYear, $selectedMonth, 25, 23, 59, 59);
        $daysRemaining = (int) ceil($now->floatDiffInDays($cutoffDate, false));
        $isCutoffClosed = $now->greaterThan($cutoffDate);

        // Query Leaderboard
        $rawLeaderboard = SafetyReport::select(
                'reporter_name',
                DB::raw('MAX(department) as department'),
                DB::raw('SUM(points) as total_points'),
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(CASE WHEN has_unsafe_act = 1 THEN 1 ELSE 0 END) as unsafe_act_count'),
                DB::raw('SUM(CASE WHEN has_unsafe_condition = 1 THEN 1 ELSE 0 END) as unsafe_condition_count'),
                DB::raw('SUM(CASE WHEN has_suggestion = 1 THEN 1 ELSE 0 END) as suggestion_count'),
                DB::raw('SUM(CASE WHEN has_action = 1 THEN 1 ELSE 0 END) as action_count')
            )
            ->whereBetween('reported_at', [$startDate, $endDate])
            ->groupBy('reporter_name')
            ->orderByDesc('total_points')
            ->orderByDesc('total_reports')
            ->get();

        // Hitung persentase bar relatif terhadap Rank 1 dan tren
        $maxPoints = $rawLeaderboard->first() ? max(1, $rawLeaderboard->first()->total_points) : 1;
        
        $leaderboard = $rawLeaderboard->map(function ($item, $idx) use ($maxPoints) {
            $item->rank = $idx + 1;
            $item->pct_of_first = round(($item->total_points / $maxPoints) * 100);
            
            if ($idx === 0) {
                $item->trend = 'same';
            } elseif ($idx === 1) {
                $item->trend = 'up';
            } elseif ($idx === 2) {
                $item->trend = 'up';
            } elseif ($idx === 3) {
                $item->trend = 'down';
            } else {
                $item->trend = 'same';
            }
            return $item;
        });

        // Top 3 Podium
        $rank1 = $leaderboard->get(0);
        $rank2 = $leaderboard->get(1);
        $rank3 = $leaderboard->get(2);

        // Rank 4 ke bawah
        $ranksRest = $leaderboard->slice(3);

        // Agregasi Statistik KPI Strip
        $totalPointsCount = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->sum('points');
        $totalReportsCount = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->count();
        $totalUnsafeAct = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->where('has_unsafe_act', true)->count();
        $totalUnsafeCondition = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->where('has_unsafe_condition', true)->count();
        $totalSuggestion = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->where('has_suggestion', true)->count();
        $totalAction = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])->where('has_action', true)->count();

        // Statistik Departemen
        $deptStats = SafetyReport::select(
                'department',
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(points) as total_points')
            )
            ->whereBetween('reported_at', [$startDate, $endDate])
            ->groupBy('department')
            ->orderByDesc('total_points')
            ->get();

        // Galeri Temuan Foto
        $evidenceReports = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])
            ->orderByDesc('reported_at')
            ->limit(40)
            ->get();

        $availableMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $googleSheetId = AppSetting::get('google_sheet_id', '1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');
        $lastSyncStatus = AppSetting::get('last_sync_status');

        return view('dashboard', compact(
            'selectedYear',
            'selectedMonth',
            'startDate',
            'endDate',
            'isCurrentMonth',
            'daysRemaining',
            'isCutoffClosed',
            'leaderboard',
            'rank1',
            'rank2',
            'rank3',
            'ranksRest',
            'deptStats',
            'totalPointsCount',
            'totalReportsCount',
            'totalUnsafeAct',
            'totalUnsafeCondition',
            'totalSuggestion',
            'totalAction',
            'evidenceReports',
            'availableMonths',
            'googleSheetId',
            'lastSyncAt',
            'lastSyncStatus'
        ));
    }

    /**
     * Sinkronisasi data Google Sheet (Manual / Ajax)
     */
    public function sync(Request $request)
    {
        $sheetId = $request->input('sheet_id') ?: AppSetting::get('google_sheet_id');
        $result = $this->syncService->sync($sheetId);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['message']);
        }
    }

    /**
     * Webhook instan ketika Google Form disubmit (Real-time trigger dari Apps Script)
     */
    public function webhook(Request $request)
    {
        Log::info('Google Form Webhook Trigger Received', $request->all());
        
        // Panggil sinkronisasi instan
        $result = $this->syncService->sync();

        return response()->json([
            'status' => 'ok',
            'synced' => $result,
        ]);
    }

    /**
     * Halaman Pengaturan Sumber Data
     */
    public function settings()
    {
        $googleSheetId = AppSetting::get('google_sheet_id', '1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');
        $lastSyncAt = AppSetting::get('last_sync_at');
        $lastSyncStatus = AppSetting::get('last_sync_status');

        $testResult = $this->syncService->testConnection($googleSheetId);

        return view('settings', compact('googleSheetId', 'lastSyncAt', 'lastSyncStatus', 'testResult'));
    }

    /**
     * Simpan Pengaturan Sumber Data
     */
    public function updateSettings(Request $request)
    {
        $sheetId = trim($request->input('google_sheet_id'));
        if (!empty($sheetId)) {
            AppSetting::set('google_sheet_id', $sheetId);
        }

        if ($request->has('sync_now')) {
            $result = $this->syncService->sync($sheetId);
            return redirect()->route('settings')->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        return redirect()->route('settings')->with('success', 'Konfigurasi Sheet ID berhasil disimpan.');
    }

    /**
     * Export data ke CSV
     */
    public function exportCsv(Request $request)
    {
        $now = Carbon::now();
        $selectedYear = (int) $request->input('year', $now->year);
        $selectedMonth = (int) $request->input('month', $now->month);

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0);
        $endDate = Carbon::create($selectedYear, $selectedMonth, 25, 23, 59, 59);

        $reports = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])
            ->orderBy('reported_at', 'asc')
            ->get();

        $filename = "Safety_Warrior_{$selectedYear}_{$selectedMonth}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'Tanggal Pelaporan',
                'Nama Karyawan',
                'Departemen',
                'Tindakan Bahaya (+5)',
                'Kondisi Bahaya (+3)',
                'Masukan/Solusi (+3)',
                'Action Nyata (+4)',
                'Total Poin',
                'Lokasi',
                'Keterangan',
                'Foto URL',
            ]);

            foreach ($reports as $idx => $r) {
                fputcsv($file, [
                    $idx + 1,
                    $r->reported_at->format('Y-m-d H:i:s'),
                    $r->reporter_name,
                    $r->department,
                    $r->has_unsafe_act ? 'Ya' : 'Tidak',
                    $r->has_unsafe_condition ? 'Ya' : 'Tidak',
                    $r->has_suggestion ? 'Ya' : 'Tidak',
                    $r->has_action ? 'Ya' : 'Tidak',
                    $r->points,
                    $r->location,
                    $r->description,
                    $r->photo_url,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
