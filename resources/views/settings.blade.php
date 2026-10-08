@extends('layouts.app')

@section('title', 'Sumber Data Google Sheets')

@section('content')
<div class="space-y-6">

    <!-- Page Title -->
    <div class="pb-4 border-b border-[var(--border)]">
        <div class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">
            KONFIGURASI INTEGRASI / GOOGLE FORMS & SHEETS
        </div>
        <h1 class="font-heading text-4xl sm:text-5xl font-black uppercase tracking-tight text-[var(--text-primary)] mt-1">
            SUMBER DATA & SINKRONISASI
        </h1>
    </div>

    <!-- 3-Condition Connection Status Banner -->
    <div class="box-industrial p-5 sm:p-6 space-y-4">
        <div class="font-mono text-xs uppercase tracking-wider text-[var(--text-secondary)] flex items-center justify-between">
            <span>STATUS KONEKSI SHEET</span>
            <span>DIUJI: {{ date('d/m/Y H:i:s') }} WIB</span>
        </div>

        @php
            $status = $testResult['status'] ?? 'yellow';
            $statusColor = $status === 'green' ? '#2ECC71' : ($status === 'yellow' ? '#FFB020' : '#FF5A36');
            $statusTitle = $status === 'green' ? 'TERHUBUNG (DATA TERSEDIA)' : ($status === 'yellow' ? 'TERHUBUNG (DATA BELUM TERSEDIA / HEADER ONLY)' : 'GAGAL TERHUBUNG (AKSES DIBATASI ATAU ID TIDAK VALID)');
        @endphp

        <div class="p-4 border bg-[var(--bg)] space-y-2" style="border-color: {{ $statusColor }};">
            <div class="flex items-center space-x-2 font-mono text-xs font-bold" style="color: {{ $statusColor }};">
                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $statusColor }};"></span>
                <span>{{ $statusTitle }}</span>
            </div>
            <p class="font-mono text-xs text-[var(--text-secondary)] leading-relaxed">
                {{ $testResult['message'] ?? 'Memeriksa status koneksi...' }}
            </p>
        </div>

        <!-- Sync Log Meta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 font-mono text-xs">
            <div class="p-3 bg-[var(--bg)] border border-[var(--border)]">
                <span class="text-[var(--text-secondary)] uppercase text-[10px] block">GOOGLE SHEET ID AKTIF</span>
                <span class="text-[var(--text-primary)] font-bold text-xs truncate block mt-0.5">{{ $googleSheetId }}</span>
            </div>
            <div class="p-3 bg-[var(--bg)] border border-[var(--border)]">
                <span class="text-[var(--text-secondary)] uppercase text-[10px] block">TERAKHIR KALI DISINKRONKAN</span>
                <span class="text-[var(--text-primary)] font-bold text-xs block mt-0.5">
                    {{ $lastSyncAt ? \Carbon\Carbon::parse($lastSyncAt)->format('d/m/Y H:i:s') : 'BELUM PERNAH' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Sheet ID Configuration Form -->
    <div class="box-industrial p-5 sm:p-6 space-y-4">
        <div class="font-mono text-xs uppercase tracking-wider text-[var(--text-secondary)]">
            PENGATURAN ID SPREADSHEET
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <div class="flex items-center justify-between mb-1.5 font-mono text-xs">
                    <label for="google_sheet_id" class="text-[var(--text-primary)] uppercase font-semibold">
                        GOOGLE SHEET ID ATAU URL SPREADSHEET
                    </label>
                    <a href="https://docs.google.com/spreadsheets/d/{{ $googleSheetId }}/edit" 
                       target="_blank" 
                       class="text-[var(--accent)] hover:underline uppercase inline-flex items-center">
                        BUKA SHEET ↗
                    </a>
                </div>

                <input type="text" name="google_sheet_id" id="google_sheet_id" 
                       value="{{ old('google_sheet_id', $googleSheetId) }}" 
                       class="w-full bg-[var(--bg)] border border-[var(--border)] px-3.5 py-2.5 font-mono text-xs text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)]" 
                       placeholder="1q_RvGbrrhVMseMBb-I6fPDVnpX7ng-MZv9FJ1FR-UPQ" 
                       required>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-2 font-mono text-xs">
                <button type="submit" name="sync_now" value="1" 
                        class="px-4 py-2 bg-[var(--accent)] text-black font-bold uppercase hover:bg-yellow-400 transition active:scale-95 cursor-pointer">
                    SIMPAN & SINKRONKAN SEKARANG
                </button>
                
                <button type="submit" name="save_only" value="1" 
                        class="px-4 py-2 border border-[var(--border)] hover:border-[var(--text-secondary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] uppercase transition cursor-pointer">
                    SIMPAN ID SAJA
                </button>
            </div>
        </form>
    </div>

    <!-- 3 Opsi Metode Sinkronisasi Otomatis -->
    <div class="box-industrial p-5 sm:p-6 space-y-4">
        <div class="font-mono text-xs uppercase tracking-wider text-[var(--accent)] font-bold flex items-center space-x-2">
            <i class="fa-solid fa-robot"></i>
            <span>3 METODE SINKRONISASI OTOMATIS (TANPA KLIK MANUAL)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 font-mono text-xs">
            
            <!-- Metode 1 -->
            <div class="p-3.5 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between">
                <div>
                    <span class="text-[var(--accent)] font-bold block mb-1">1. AUTO-SYNC ON LOAD (AKTIF)</span>
                    <p class="text-[var(--text-secondary)] text-[11px] leading-relaxed">
                        Setiap kali ada yang membuka dashboard, jika data terakhir sudah lebih dari 3 menit, sistem otomatis menarik data baru di latar belakang.
                    </p>
                </div>
                <span class="text-[#2ECC71] text-[10px] mt-2 block font-bold">✓ SUDAH OTOMATIS AKTIF</span>
            </div>

            <!-- Metode 2 -->
            <div class="p-3.5 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between">
                <div>
                    <span class="text-[var(--accent)] font-bold block mb-1">2. CRON JOB SERVER</span>
                    <p class="text-[var(--text-secondary)] text-[11px] leading-relaxed">
                        Jalankan scheduler Laravel setiap 5 menit di background server:
                    </p>
                    <code class="block bg-[var(--surface)] text-[10px] p-1.5 mt-1.5 border border-[var(--border)] text-[var(--text-primary)]">
                        * * * * * php artisan schedule:run
                    </code>
                </div>
                <span class="text-[var(--text-secondary)] text-[10px] mt-2 block">JADWAL TIAP 5 MENIT</span>
            </div>

            <!-- Metode 3 -->
            <div class="p-3.5 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between">
                <div>
                    <span class="text-[var(--accent)] font-bold block mb-1">3. REAL-TIME GOOGLE FORM TRIGGER</span>
                    <p class="text-[var(--text-secondary)] text-[11px] leading-relaxed">
                        Karyawan submit Google Form &rarr; data langsung masuk ke dashboard detik itu juga via Webhook Apps Script.
                    </p>
                    <div class="text-[10px] text-[var(--text-primary)] mt-1.5 bg-[var(--surface)] p-1.5 border border-[var(--border)] truncate">
                        POST: /api/webhook/google-form
                    </div>
                </div>
                <span class="text-[var(--text-secondary)] text-[10px] mt-2 block">REAL-TIME ON SUBMIT</span>
            </div>

        </div>
    </div>

    <!-- Petunjuk Singkat Izin Akses & Format Form -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Akses Berbagi -->
        <div class="box-industrial p-5 space-y-2.5 font-mono text-xs">
            <div class="text-[var(--text-primary)] font-bold uppercase flex items-center space-x-2">
                <i class="fa-solid fa-lock-open text-[var(--accent)]"></i>
                <span>CARA BUKA HAK AKSES SHEET</span>
            </div>
            <p class="text-[var(--text-secondary)] leading-relaxed">
                1. Buka spreadsheet Google Form Anda di browser.<br>
                2. Klik tombol <strong>"Bagikan" (Share)</strong> di kanan atas.<br>
                3. Pada bagian Akses Umum, ubah menjadi <strong>"Siapa saja yang memiliki link" (Anyone with the link)</strong> dengan hak <strong>Pelihat (Viewer)</strong>.<br>
                4. Data akan otomatis terbaca oleh dashboard!
            </p>
        </div>

        <!-- Kolom Form yang Dipetakan -->
        <div class="box-industrial p-5 space-y-2.5 font-mono text-xs">
            <div class="text-[var(--text-primary)] font-bold uppercase flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-[var(--accent)]"></i>
                <span>KOLOM GFORM YANG TERDETEKSI</span>
            </div>
            <div class="space-y-1 text-[var(--text-secondary)]">
                <div>• <strong class="text-[var(--text-primary)]">Nama:</strong> Nama Pelapor / Karyawan</div>
                <div>• <strong class="text-[var(--text-primary)]">Departemen:</strong> Service, Produksi, Maintenance, dll.</div>
                <div>• <strong class="text-[var(--text-primary)]">Kategori Temuan:</strong> Tindakan (+5), Kondisi (+3), Masukan (+3), Action (+4)</div>
                <div>• <strong class="text-[var(--text-primary)]">Foto & Keterangan:</strong> Link Google Drive & Deskripsi</div>
            </div>
        </div>

    </div>

</div>
@endsection
