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
            <span>DIUJI: {{ now()->format('d/m/Y H:i:s') }} WITA</span>
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

    <!-- Configuration Form -->
    <div class="box-industrial p-5 sm:p-6 space-y-4">
        <div class="font-mono text-xs uppercase tracking-wider text-[var(--text-secondary)]">
            PENGATURAN SUMBER DATA & GOOGLE FORM
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-4">
            @csrf
            
            <!-- Google Sheet ID -->
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
                <p class="font-mono text-[11px] text-[var(--text-secondary)] mt-1.5">
                    ID adalah kode acak di URL spreadsheet antara <code class="text-[var(--accent)]">/d/</code> dan <code class="text-[var(--accent)]">/edit</code>. Anda juga dapat langsung menempelkan seluruh URL Google Sheet.
                </p>
            </div>

            <!-- Google Form URL (Quick Navigation) -->
            <div class="pt-2 border-t border-[var(--border)]">
                <div class="flex items-center justify-between mb-1.5 font-mono text-xs">
                    <label for="google_form_url" class="text-[var(--text-primary)] uppercase font-semibold flex items-center space-x-1.5">
                        <i class="fa-solid fa-file-pen text-[var(--accent)]"></i>
                        <span>LINK GOOGLE FORM LAPORAN K3 & CSR (TOMBOL NAVIGASI CEPAT)</span>
                    </label>
                    @if(!empty($googleFormUrl))
                        <a href="{{ $googleFormUrl }}" target="_blank" class="text-[var(--accent)] hover:underline uppercase inline-flex items-center">
                            TEST BUKA FORM ↗
                        </a>
                    @endif
                </div>

                <input type="url" name="google_form_url" id="google_form_url" 
                       value="{{ old('google_form_url', $googleFormUrl) }}" 
                       class="w-full bg-[var(--bg)] border border-[var(--border)] px-3.5 py-2.5 font-mono text-xs text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)]" 
                       placeholder="https://docs.google.com/forms/d/e/.../viewform">
                <p class="font-mono text-[11px] text-[var(--text-secondary)] mt-1.5">
                    Masukkan URL Google Form input laporan karyawan agar muncul tombol pintasan <strong>"ISI LAPORAN K3"</strong> di navbar & floating action button.
                </p>
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

    <!-- PANDUAN LENGKAP KONEKSI & MAINTENANCE UNTUK CLIENT (MANDIRI & MUDAH) -->
    <div class="box-industrial p-5 sm:p-6 space-y-6">
        <div class="border-b border-[var(--border)] pb-3">
            <span class="font-mono text-[11px] text-[var(--accent)] uppercase font-bold tracking-wider block">PANDUAN OPERASIONAL</span>
            <h2 class="font-heading font-black text-2xl uppercase tracking-tight text-[var(--text-primary)] mt-0.5">
                PANDUAN KONEKSI & MAINTENANCE SISTEM (UNTUK CLIENT)
            </h2>
            <p class="font-mono text-xs text-[var(--text-secondary)] mt-1">
                Ikuti langkah-langkah mudah di bawah ini jika ingin mengganti Google Form/Sheet baru atau memperbaiki kendala sinkronisasi secara mandiri.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 font-mono text-xs">
            
            <!-- Step 1: Izin Akses Spreadsheet -->
            <div class="p-4 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between space-y-3" style="border-radius: var(--radius);">
                <div>
                    <div class="flex items-center space-x-2 text-[var(--accent)] font-bold text-sm mb-2">
                        <span class="w-5 h-5 rounded-full bg-[var(--accent)] text-black flex items-center justify-center text-xs">1</span>
                        <span>IZIN AKSES SHEET</span>
                    </div>
                    <ol class="space-y-1.5 text-[var(--text-secondary)] text-[11px] leading-relaxed list-decimal pl-4">
                        <li>Buka Google Spreadsheet form Anda.</li>
                        <li>Klik tombol biru <strong>"Bagikan" (Share)</strong> di kanan atas.</li>
                        <li>Di bagian <strong>Akses umum</strong>, pilih <strong>"Siapa saja yang memiliki link"</strong>.</li>
                        <li>Pastikan perannya diset ke <strong>"Pelihat" (Viewer)</strong>.</li>
                        <li>Klik <strong>Selesai</strong>.</li>
                    </ol>
                </div>
                <div class="pt-2 border-t border-[var(--border)] text-[10px] text-[#2ECC71] font-bold">
                    ✓ SYARAT UTAMA SINKRONISASI
                </div>
            </div>

            <!-- Step 2: Izin Akses Folder Foto Drive -->
            <div class="p-4 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between space-y-3" style="border-radius: var(--radius);">
                <div>
                    <div class="flex items-center space-x-2 text-[var(--accent)] font-bold text-sm mb-2">
                        <span class="w-5 h-5 rounded-full bg-[var(--accent)] text-black flex items-center justify-center text-xs">2</span>
                        <span>IZIN FOTO BUKTI (DRIVE)</span>
                    </div>
                    <ol class="space-y-1.5 text-[var(--text-secondary)] text-[11px] leading-relaxed list-decimal pl-4">
                        <li>Buka <strong>Google Drive</strong> akun pemilik form.</li>
                        <li>Cari folder respon tempat form menyimpan foto upload (biasanya dinamai otomatis sesuai pertanyaan upload form).</li>
                        <li>Klik kanan folder tersebut &rarr; <strong>Bagikan (Share)</strong>.</li>
                        <li>Ubah Akses Umum menjadi <strong>"Siapa saja yang memiliki link"</strong> (Pelihat).</li>
                    </ol>
                </div>
                <div class="pt-2 border-t border-[var(--border)] text-[10px] text-[#2ECC71] font-bold">
                    ✓ AGAR FOTO DAPAT TAMPIL DI WEB
                </div>
            </div>

            <!-- Step 3: Format Pertanyaan GForm -->
            <div class="p-4 bg-[var(--bg)] border border-[var(--border)] flex flex-col justify-between space-y-3" style="border-radius: var(--radius);">
                <div>
                    <div class="flex items-center space-x-2 text-[var(--accent)] font-bold text-sm mb-2">
                        <span class="w-5 h-5 rounded-full bg-[var(--accent)] text-black flex items-center justify-center text-xs">3</span>
                        <span>FORMAT GFORM OTOMATIS</span>
                    </div>
                    <ul class="space-y-1.5 text-[var(--text-secondary)] text-[11px] leading-relaxed list-disc pl-4">
                        <li><strong class="text-[var(--text-primary)]">Timestamp:</strong> Dibuat otomatis oleh Google Form saat submit.</li>
                        <li><strong class="text-[var(--text-primary)]">NRP:</strong> Pertanyaan nomor registrasi (Contoh: <code class="text-[var(--accent)]">001</code>).</li>
                        <li><strong class="text-[var(--text-primary)]">Nama Karyawan:</strong> Teks singkat nama pelapor.</li>
                        <li><strong class="text-[var(--text-primary)]">Departemen:</strong> Dropdown atau teks singkat divisi.</li>
                        <li><strong class="text-[var(--text-primary)]">Jenis Temuan:</strong> Pilihan indikator kegiatan (+1, +2, +3, +4).</li>
                    </ul>
                </div>
                <div class="pt-2 border-t border-[var(--border)] text-[10px] text-[var(--accent)] font-bold">
                    ✓ TERDETEKSI OTOMATIS OLEH SISTEM
                </div>
            </div>

        </div>

        <!-- Troubleshooting Cepat Jika Ada Masalah -->
        <div class="p-4 bg-[var(--bg)] border border-[var(--border)] space-y-2 font-mono text-xs">
            <span class="text-[var(--accent)] font-bold uppercase flex items-center space-x-1.5 text-xs">
                <i class="fa-solid fa-wrench"></i>
                <span>TROUBLESHOOTING / JIKA TERJADI KENDALA:</span>
            </span>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px] text-[var(--text-secondary)] leading-relaxed pt-1">
                <div>
                    <strong class="text-[var(--text-primary)] block">• Data di Web Belum Muncul Setelah Submit?</strong>
                    Tekan tombol <strong>"SINKRON"</strong> di pojok kanan atas navbar website, atau pastikan Sheet ID di atas sudah benar.
                </div>
                <div>
                    <strong class="text-[var(--text-primary)] block">• Baris di Sheet Sudah Dihapus Tapi Masih Ada di Web?</strong>
                    Cukup klik tombol <strong>"SIMPAN & SINKRONKAN SEKARANG"</strong> di halaman ini, sistem akan otomatis membersihkan data yang terhapus di sheet.
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
