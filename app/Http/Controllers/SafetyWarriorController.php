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
     * Tampilkan Dashboard IMPACT WARRIOR
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $defaultDate = $now->day >= 25 ? $now->copy()->addMonth() : $now;
        $selectedYear = (int) $request->input('year', $defaultDate->year);
        $selectedMonth = (int) $request->input('month', $defaultDate->month);
        $selectedDept = trim($request->input('department', ''));
        $sortBy = trim($request->input('sort', 'points')); // 'points' (default skor), 'name_asc', 'name_desc'
        $sortGallery = trim($request->input('sort_gallery', 'latest')); // 'latest', 'name_asc', 'dept_asc'

        // Auto-sync jika data stale > 3 menit
        $lastSyncAt = AppSetting::get('last_sync_at');
        if (!$lastSyncAt || Carbon::parse($lastSyncAt)->diffInMinutes($now) >= 3) {
            try {
                $this->syncService->sync();
                $lastSyncAt = AppSetting::get('last_sync_at');
            } catch (\Exception $e) {
                Log::warning('Auto-sync background on load failed: ' . $e->getMessage());
            }
        }

        // Rentang periode evaluasi bulanan: Tanggal 25 bulan sebelumnya s.d. Tanggal 24 bulan berjalan (Tanpa Cut-off)
        $endDate = Carbon::create($selectedYear, $selectedMonth, 24, 23, 59, 59);
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0)->subMonth()->day(25)->setTime(0, 0, 0);

        $isCurrentPeriod = $now->between($startDate, $endDate);
        $isCurrentMonth = $isCurrentPeriod;
        $daysRemaining = 0;
        $isCutoffClosed = false;

        // Daftar semua departemen unik untuk filter
        $availableDepartments = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department', 'asc')
            ->pluck('department')
            ->toArray();

        // Base query untuk leaderboard (NRP sebagai Primary Key identitas)
        $lbQuery = SafetyReport::select(
                DB::raw('COALESCE(nrp, reporter_name) as employee_key'),
                DB::raw('MAX(nrp) as nrp'),
                DB::raw('MAX(reporter_name) as reporter_name'),
                DB::raw('MAX(department) as department'),
                DB::raw('SUM(points) as total_points'),
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(CASE WHEN has_unsafe_condition = 1 THEN 1 ELSE 0 END) as cond_count'),
                DB::raw('SUM(CASE WHEN has_unsafe_act = 1 THEN 1 ELSE 0 END) as act_count'),
                DB::raw('SUM(CASE WHEN has_suggestion = 1 THEN 1 ELSE 0 END) as suggestion_count'),
                DB::raw('SUM(CASE WHEN (has_action = 1 OR has_direct_action = 1) THEN 1 ELSE 0 END) as action_count'),
                DB::raw('SUM(CASE WHEN has_ehs_activity = 1 THEN 1 ELSE 0 END) as ehs_count'),
                DB::raw('SUM(CASE WHEN has_csr_activity = 1 THEN 1 ELSE 0 END) as csr_count'),
                DB::raw('SUM(CASE WHEN has_speaker_activity = 1 THEN 1 ELSE 0 END) as speaker_count')
            )
            ->whereBetween('reported_at', [$startDate, $endDate]);

        if (!empty($selectedDept)) {
            $lbQuery->where('department', $selectedDept);
        }

        $lbQuery->groupBy(DB::raw('COALESCE(nrp, reporter_name)'));

        // Pengurutan klasemen
        if ($sortBy === 'name_asc') {
            $lbQuery->orderBy('reporter_name', 'asc');
        } elseif ($sortBy === 'name_desc') {
            $lbQuery->orderBy('reporter_name', 'desc');
        } else {
            $lbQuery->orderByDesc('total_points')->orderByDesc('total_reports')->orderBy('reporter_name', 'asc');
        }

        $rawLeaderboard = $lbQuery->get();

        // Hitung persentase relatif dan nomor peringkat
        $maxPoints = $rawLeaderboard->first() ? max(1, $rawLeaderboard->max('total_points')) : 1;
        
        $leaderboard = $rawLeaderboard->map(function ($item, $idx) use ($maxPoints) {
            $item->rank = $idx + 1;
            $item->pct_of_first = round(($item->total_points / $maxPoints) * 100);
            $item->trend = 'same';
            return $item;
        });

        // Ambil semua laporan individual dalam periode ini untuk detail pop-up modal
        $allPeriodReports = SafetyReport::whereBetween('reported_at', [$startDate, $endDate])
            ->orderByDesc('reported_at')
            ->get();

        $reportsByEmployee = $allPeriodReports->groupBy(function ($item) {
            return $item->nrp ?: $item->reporter_name;
        });

        // Top 3 Podium
        $rank1 = $leaderboard->get(0);
        $rank2 = $leaderboard->get(1);
        $rank3 = $leaderboard->get(2);

        // Seluruh daftar klasemen lengkap dengan lampiran laporannya
        $allLeaderboard = $leaderboard->map(function ($emp) use ($reportsByEmployee) {
            $key = $emp->nrp ?: $emp->reporter_name;
            $items = $reportsByEmployee->get($key, collect());
            $emp->reports_list = $items->map(function ($r) {
                return [
                    'id' => $r->id,
                    'reported_at' => $r->reported_at->format('d/m/Y H:i'),
                    'activity_indicator' => $r->activity_indicator ?: 'Kondisi Tidak Aman',
                    'indicator_category' => $r->indicator_category ?: 'EHS',
                    'points' => $r->points,
                    'description' => $r->description ?: 'Tidak ada keterangan.',
                    'photo_url' => $r->photo_url ? $r->direct_photo_url : null,
                ];
            })->values()->toArray();
            return $emp;
        });

        // KPI agregasi
        $kpiQuery = SafetyReport::whereBetween('reported_at', [$startDate, $endDate]);
        if (!empty($selectedDept)) {
            $kpiQuery->where('department', $selectedDept);
        }

        $totalPointsCount = (clone $kpiQuery)->sum('points');
        $totalReportsCount = (clone $kpiQuery)->count();
        $totalCondCount = (clone $kpiQuery)->where('has_unsafe_condition', true)->count();
        $totalActCount = (clone $kpiQuery)->where('has_unsafe_act', true)->count();
        $totalSugCount = (clone $kpiQuery)->where('has_suggestion', true)->count();
        $totalActionCount = (clone $kpiQuery)->where(function ($q) {
            $q->where('has_action', true)->orWhere('has_direct_action', true);
        })->count();
        $totalSpeakerCount = (clone $kpiQuery)->where('has_speaker_activity', true)->count();
        $totalActivityCount = (clone $kpiQuery)->where(function ($q) {
            $q->where('has_ehs_activity', true)->orWhere('has_csr_activity', true);
        })->count();

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

        // Galeri Bukti Temuan
        $galleryQuery = SafetyReport::whereBetween('reported_at', [$startDate, $endDate]);
        if (!empty($selectedDept)) {
            $galleryQuery->where('department', $selectedDept);
        }

        if ($sortGallery === 'name_asc') {
            $galleryQuery->orderBy('reporter_name', 'asc')->orderByDesc('reported_at');
        } elseif ($sortGallery === 'dept_asc') {
            $galleryQuery->orderBy('department', 'asc')->orderBy('reporter_name', 'asc');
        } else {
            $galleryQuery->orderByDesc('reported_at');
        }

        $evidenceReports = $galleryQuery->limit(60)->get();

        $availableMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $googleSheetId = AppSetting::get('google_sheet_id', '1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ');
        $lastSyncStatus = AppSetting::get('last_sync_status');
        $allowedUsers = AppSetting::get('allowed_users', '');

        return view('dashboard', compact(
            'selectedYear',
            'selectedMonth',
            'selectedDept',
            'sortBy',
            'sortGallery',
            'availableDepartments',
            'startDate',
            'endDate',
            'isCurrentPeriod',
            'isCurrentMonth',
            'daysRemaining',
            'isCutoffClosed',
            'leaderboard',
            'allLeaderboard',
            'rank1',
            'rank2',
            'rank3',
            'deptStats',
            'totalPointsCount',
            'totalReportsCount',
            'totalCondCount',
            'totalActCount',
            'totalSugCount',
            'totalActionCount',
            'totalSpeakerCount',
            'totalActivityCount',
            'evidenceReports',
            'availableMonths',
            'googleSheetId',
            'lastSyncAt',
            'lastSyncStatus',
            'allowedUsers'
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
     * Webhook instan ketika Google Form disubmit
     */
    public function webhook(Request $request)
    {
        Log::info('Google Form Webhook Trigger Received', $request->all());
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
        $googleFormUrl = AppSetting::get('google_form_url', '');
        $lastSyncAt = AppSetting::get('last_sync_at');
        $lastSyncStatus = AppSetting::get('last_sync_status');
        $allowedUsers = AppSetting::get('allowed_users', '');

        $testResult = $this->syncService->testConnection($googleSheetId);

        return view('settings', compact('googleSheetId', 'googleFormUrl', 'lastSyncAt', 'lastSyncStatus', 'allowedUsers', 'testResult'));
    }

    /**
     * Simpan Pengaturan Sumber Data & Whitelist Username
     */
    public function updateSettings(Request $request)
    {
        $sheetId = trim($request->input('google_sheet_id'));
        if (!empty($sheetId)) {
            AppSetting::set('google_sheet_id', $sheetId);
        }

        // Simpan Google Form URL
        $formUrl = trim($request->input('google_form_url', ''));
        AppSetting::set('google_form_url', $formUrl);

        // Simpan whitelist username
        $allowedUsers = trim($request->input('allowed_users', ''));
        AppSetting::set('allowed_users', $allowedUsers);

        if ($request->has('sync_now')) {
            $result = $this->syncService->sync($sheetId);
            return redirect()->route('settings')->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        return redirect()->route('settings')->with('success', 'Konfigurasi Pengaturan & Link Google Form berhasil disimpan.');
    }



    /**
     * Tampilan Cetak / Print ke PDF Akumulasi Klasemen Bulanan
     */
    public function printPdf(Request $request)
    {
        $now = Carbon::now();
        $defaultDate = $now->day >= 25 ? $now->copy()->addMonth() : $now;
        $selectedYear = (int) $request->input('year', $defaultDate->year);
        $selectedMonth = (int) $request->input('month', $defaultDate->month);
        $selectedDept = trim($request->input('department', ''));
        $sortBy = $request->input('sort', 'points');

        $endDate = Carbon::create($selectedYear, $selectedMonth, 24, 23, 59, 59);
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0)->subMonth()->day(25)->setTime(0, 0, 0);

        // Query Akumulasi Karyawan (NRP sebagai Primary Key)
        $lbQuery = SafetyReport::select(
                DB::raw('COALESCE(nrp, reporter_name) as employee_key'),
                DB::raw('MAX(nrp) as nrp'),
                DB::raw('MAX(reporter_name) as reporter_name'),
                DB::raw('MAX(department) as department'),
                DB::raw('SUM(points) as total_points'),
                DB::raw('COUNT(*) as total_reports'),
                DB::raw('SUM(CASE WHEN has_unsafe_condition = 1 THEN 1 ELSE 0 END) as cond_count'),
                DB::raw('SUM(CASE WHEN has_unsafe_act = 1 THEN 1 ELSE 0 END) as act_count'),
                DB::raw('SUM(CASE WHEN has_suggestion = 1 THEN 1 ELSE 0 END) as suggestion_count'),
                DB::raw('SUM(CASE WHEN (has_action = 1 OR has_direct_action = 1) THEN 1 ELSE 0 END) as action_count'),
                DB::raw('SUM(CASE WHEN has_ehs_activity = 1 THEN 1 ELSE 0 END) as ehs_count'),
                DB::raw('SUM(CASE WHEN has_csr_activity = 1 THEN 1 ELSE 0 END) as csr_count'),
                DB::raw('SUM(CASE WHEN has_speaker_activity = 1 THEN 1 ELSE 0 END) as speaker_count')
            )
            ->whereBetween('reported_at', [$startDate, $endDate]);

        if (!empty($selectedDept)) {
            $lbQuery->where('department', $selectedDept);
        }

        $lbQuery->groupBy(DB::raw('COALESCE(nrp, reporter_name)'));

        // Pengurutan
        if ($sortBy === 'name_asc') {
            $lbQuery->orderBy('reporter_name', 'asc');
        } elseif ($sortBy === 'name_desc') {
            $lbQuery->orderBy('reporter_name', 'desc');
        } else {
            $lbQuery->orderByDesc('total_points')->orderByDesc('total_reports')->orderBy('reporter_name', 'asc');
        }

        $leaderboard = $lbQuery->get();

        $availableMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = $availableMonths[$selectedMonth] ?? 'Bulan ' . $selectedMonth;

        return view('print_pdf', compact('leaderboard', 'selectedMonth', 'selectedYear', 'selectedDept', 'monthName', 'availableMonths', 'sortBy', 'startDate', 'endDate'));
    }
}
