<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akumulasi Klasemen IMPACT WARRIOR - {{ $monthName }} {{ $selectedYear }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --accent: #D49B00;
            --hazard-stripe: repeating-linear-gradient(45deg, #D49B00, #D49B00 8px, #111111 8px, #111111 16px);
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background-color: #F3F1EB;
            color: var(--text-primary);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-heading {
            font-family: 'Barlow Condensed', sans-serif;
        }

        .font-mono {
            font-family: 'IBM Plex Mono', monospace;
        }

        .hazard-bar {
            background: var(--hazard-stripe);
            height: 4px;
            width: 100%;
        }

        .box-sheet {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #FFFFFF !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 10.5px !important;
            }
            .page-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                background-color: #FFFFFF !important;
            }
            @page {
                size: A4 landscape;
                margin: 8mm 6mm 8mm 6mm;
            }
            table {
                width: 100% !important;
                table-layout: fixed !important;
                font-size: 10px !important;
            }
            th, td {
                padding: 4px 5px !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
            thead {
                display: table-header-group !important;
            }
            tfoot {
                display: table-footer-group !important;
            }
            .avoid-break {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="py-6 px-4">

    <!-- Top Action Bar (Sesuai Latar #F3F1EB, Hidden on Print) -->
    <div class="no-print max-w-6xl mx-auto mb-6 p-3 px-4 flex flex-wrap items-center justify-between gap-3 font-mono text-xs shadow-sm"
         style="background:#EAE6DC; border:1px solid #D4CEBF; border-radius:3px;">
        <div class="flex items-center space-x-3">
            <a href="{{ route('dashboard', ['month' => $selectedMonth, 'year' => $selectedYear, 'department' => $selectedDept, 'sort' => $sortBy]) }}" 
               class="px-3 py-1.5 border border-[#D4CEBF] hover:border-[#111111] text-[#111111] hover:bg-[#DFD9CC] font-bold uppercase transition flex items-center space-x-1.5 bg-[#EAE6DC]">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>KEMBALI KE DASHBOARD</span>
            </a>
            <span class="text-[#D4CEBF]">|</span>
            <span class="text-[#666666] uppercase tracking-widest text-[10px] font-bold">PRINTOUT REKAPITULASI RESMI</span>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Period filter mini -->
            <form method="GET" action="{{ route('print.pdf') }}" class="flex items-center gap-1.5 border border-[#D4CEBF] px-2 py-1.5 h-9 bg-[#F3F1EB]">
                <span class="text-[10px] text-[#666666] uppercase font-bold">Periode:</span>
                <select name="month" onchange="this.form.submit()" class="bg-transparent text-[#111111] text-xs px-1 py-0.5 focus:outline-none cursor-pointer uppercase font-mono font-bold">
                    @foreach($availableMonths as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }} style="background:#EAE6DC; color:#111111;">{{ strtoupper($mName) }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="bg-transparent text-[#111111] text-xs px-1 py-0.5 focus:outline-none cursor-pointer font-mono font-bold">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }} style="background:#EAE6DC; color:#111111;">{{ $y }}</option>
                    @endfor
                </select>
                @if(!empty($selectedDept))
                    <input type="hidden" name="department" value="{{ $selectedDept }}">
                @endif
                <input type="hidden" name="sort" value="{{ $sortBy }}">
            </form>

            <button onclick="window.print()" 
                    class="px-4 py-1.5 bg-[#D49B00] hover:bg-yellow-600 text-black font-bold uppercase tracking-wider transition flex items-center space-x-1.5 cursor-pointer h-9 shadow-sm">
                <i class="fa-solid fa-print text-sm"></i>
                <span>CETAK / SIMPAN PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet Container (Harmonis dengan Palet Dashboard) -->
    <div class="page-container max-w-6xl mx-auto p-7 shadow-lg bg-white border border-zinc-300">
        
        <!-- Hazard Accent Line -->
        <div class="hazard-bar mb-4" style="border-radius: 2px;"></div>

        <!-- Header Dokumen / Kop Laporan -->
        <div class="border-b border-[var(--border)] pb-4 mb-4 flex items-start justify-between">
            <div class="flex items-center space-x-3.5">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 object-contain">
                <div>
                    <div class="font-mono text-[10px] uppercase tracking-widest text-[var(--text-secondary)] font-bold">
                        LAPORAN RESMI MONITORING K3 & CSR (EHS DIVISION)
                    </div>
                    <h1 class="font-heading font-black text-3xl uppercase tracking-tight text-[var(--text-primary)] mt-0.5">
                        AKUMULASI KLASEMEN BULANAN IMPACT WARRIOR
                    </h1>
                    <div class="font-mono text-xs text-[var(--text-secondary)] mt-0.5 flex items-center space-x-2">
                        <span>PERIODE: <strong class="text-[var(--text-primary)]">{{ $startDate->format('d/m/Y') }} s.d. {{ $endDate->format('d/m/Y') }}</strong> ({{ strtoupper($monthName) }} {{ $selectedYear }})</span>
                        @if(!empty($selectedDept))
                            <span>•</span>
                            <span>DEPARTEMEN: <strong class="text-[#B45309]">{{ strtoupper($selectedDept) }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="text-right font-mono text-[11px] text-[var(--text-secondary)]">
                <div class="font-bold text-[var(--text-primary)]">STATUS: TERVALIDASI</div>
                <div>WAKTU CETAK: {{ now()->format('d/m/Y H:i') }} WITA</div>
                <div class="mt-1 inline-block px-2 py-0.5 bg-[var(--bg)] border border-[var(--border)] text-[var(--text-primary)] font-bold text-[10px]" style="border-radius: 2px;">
                    TOTAL: {{ count($leaderboard) }} KARYAWAN
                </div>
            </div>
        </div>

        <!-- Legend / Keterangan Warna Indikator -->
        <div class="mb-4 bg-slate-50 border border-slate-200 p-2.5 rounded flex flex-wrap items-center justify-between font-mono text-[11px] text-slate-600">
            <span class="font-bold uppercase text-slate-900">RINCIAN INDIKATOR TEMUAN / KEGIATAN:</span>
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#FFB020]"></span>
                    <span>Kondisi Tidak Aman (<strong>+1</strong>)</span>
                </span>
                <span class="inline-flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#FF5A36]"></span>
                    <span>Tindakan / Near Miss (<strong>+2</strong>)</span>
                </span>
                <span class="inline-flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#0284C7]"></span>
                    <span>Saran / Usulan (<strong>+3</strong>)</span>
                </span>
                <span class="inline-flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#16A34A]"></span>
                    <span>Penanganan Langsung (<strong>+3</strong>)</span>
                </span>
                <span class="inline-flex items-center space-x-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#9333EA]"></span>
                    <span>Pemateri / Edukasi (<strong>+4</strong>)</span>
                </span>
            </div>
        </div>

        <!-- Main Accumulation Table -->
        <table class="w-full border-collapse border border-[var(--border)] text-xs">
            <thead>
                <tr class="bg-zinc-800 text-white font-mono text-[11px] uppercase tracking-wider">
                    <th class="border border-zinc-700 px-2.5 py-2 text-center w-12">RANK</th>
                    <th class="border border-zinc-700 px-3 py-2 text-center w-20">NRP</th>
                    <th class="border border-zinc-700 px-3 py-2 text-left">NAMA KARYAWAN</th>
                    <th class="border border-zinc-700 px-3 py-2 text-center w-24">DEPT</th>
                    <th class="border border-zinc-700 px-2 py-2 text-center bg-amber-900/60 w-20" title="Kondisi Tidak Aman (+1)">KONDISI (+1)</th>
                    <th class="border border-zinc-700 px-2 py-2 text-center bg-orange-900/60 w-20" title="Tindakan / Near Miss (+2)">TINDAKAN (+2)</th>
                    <th class="border border-zinc-700 px-2 py-2 text-center bg-sky-900/60 w-20" title="Saran / Rekomendasi (+3)">SARAN (+3)</th>
                    <th class="border border-zinc-700 px-2 py-2 text-center bg-emerald-900/60 w-20" title="Penanganan Langsung (+3)">ACTION (+3)</th>
                    <th class="border border-zinc-700 px-2 py-2 text-center bg-purple-900/60 w-20" title="Pemateri (+4)">PEMATERI (+4)</th>
                    <th class="border border-zinc-700 px-2.5 py-2 text-center w-20">TOTAL TEMUAN / KEGIATAN</th>
                    <th class="border border-zinc-700 px-3 py-2 text-right bg-[#D49B00] text-black font-black w-24">TOTAL POIN</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)] font-body">
                @php
                    $sumCond = 0;
                    $sumAct = 0;
                    $sumSug = 0;
                    $sumAction = 0;
                    $sumSpeaker = 0;
                    $sumReports = 0;
                    $sumPoints = 0;
                @endphp

                @forelse($leaderboard as $idx => $emp)
                    @php
                        $rank = $idx + 1;
                        $sumCond += $emp->cond_count;
                        $sumAct += $emp->act_count;
                        $sumSug += $emp->suggestion_count;
                        $sumAction += $emp->action_count;
                        $sumSpeaker += $emp->speaker_count;
                        $sumReports += $emp->total_reports;
                        $sumPoints += $emp->total_points;

                        // Baris berwarna lembut di atas kertas putih
                        $rowBg = $rank === 1 ? 'bg-amber-50/70 font-semibold' : ($rank === 2 ? 'bg-slate-50 font-medium' : ($rank === 3 ? 'bg-orange-50/50' : ($idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white')));
                    @endphp
                    <tr class="{{ $rowBg }}">
                        <!-- Rank -->
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono font-bold">
                            @if($rank === 1)
                                <span class="px-1.5 py-0.5 bg-[#D49B00] text-black font-black rounded text-[11px]">01</span>
                            @elseif($rank === 2)
                                <span class="px-1.5 py-0.5 bg-zinc-300 text-black font-bold rounded text-[11px]">02</span>
                            @elseif($rank === 3)
                                <span class="px-1.5 py-0.5 bg-amber-200 text-black font-bold rounded text-[11px]">03</span>
                            @else
                                <span class="text-[var(--text-secondary)]">{{ sprintf('%02d', $rank) }}</span>
                            @endif
                        </td>

                        <!-- NRP -->
                        <td class="border border-[var(--border)] px-2.5 py-2 text-center font-mono font-bold text-[var(--text-primary)]">
                            {{ $emp->nrp ?? '-' }}
                        </td>

                        <!-- Nama Karyawan -->
                        <td class="border border-[var(--border)] px-3 py-2 text-[var(--text-primary)] font-semibold">
                            {{ $emp->reporter_name }}
                        </td>

                        <!-- Departemen -->
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono uppercase text-[var(--text-secondary)]">
                            {{ $emp->department }}
                        </td>

                        <!-- Rincian Skor per Kategori -->
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono text-amber-900 {{ $emp->cond_count > 0 ? 'bg-amber-100 font-bold' : 'text-zinc-400' }}">
                            {{ $emp->cond_count }}
                        </td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono text-orange-900 {{ $emp->act_count > 0 ? 'bg-orange-100 font-bold' : 'text-zinc-400' }}">
                            {{ $emp->act_count }}
                        </td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono text-sky-900 {{ $emp->suggestion_count > 0 ? 'bg-sky-100 font-bold' : 'text-zinc-400' }}">
                            {{ $emp->suggestion_count }}
                        </td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono text-emerald-900 {{ $emp->action_count > 0 ? 'bg-emerald-100 font-bold' : 'text-zinc-400' }}">
                            {{ $emp->action_count }}
                        </td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono text-purple-900 {{ $emp->speaker_count > 0 ? 'bg-purple-100 font-bold' : 'text-zinc-400' }}">
                            {{ $emp->speaker_count }}
                        </td>

                        <!-- Total Laporan -->
                        <td class="border border-[var(--border)] px-2 py-2 text-center font-mono font-bold text-[var(--text-primary)]">
                            {{ $emp->total_reports }}
                        </td>

                        <!-- Total Poin -->
                        <td class="border border-[var(--border)] px-3 py-2 text-right font-mono font-black text-sm text-[var(--text-primary)] bg-amber-100/50">
                            {{ $emp->total_points }} PTS
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="border border-[var(--border)] px-4 py-8 text-center font-mono text-[var(--text-secondary)] uppercase">
                            Belum ada rekapan data karyawan pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <!-- Total Akumulasi Keseluruhan -->
            @if(count($leaderboard) > 0)
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold text-slate-900 border-t-2 border-slate-300">
                        <td colspan="4" class="border border-slate-200 px-3 py-2.5 text-right uppercase tracking-wider text-xs">
                            TOTAL AKUMULASI ({{ count($leaderboard) }} KARYAWAN):
                        </td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-amber-900 font-bold">{{ $sumCond }}</td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-orange-900 font-bold">{{ $sumAct }}</td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-sky-900 font-bold">{{ $sumSug }}</td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-emerald-900 font-bold">{{ $sumAction }}</td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-purple-900 font-bold">{{ $sumSpeaker }}</td>
                        <td class="border border-[var(--border)] px-2 py-2 text-center text-[var(--text-primary)] font-bold">{{ $sumReports }}</td>
                        <td class="border border-[var(--border)] px-3 py-2 text-right bg-[#D49B00] text-black font-black text-sm">
                            {{ $sumPoints }} PTS
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <!-- Kolom Tanda Tangan / Verifikasi (Sempurna untuk arsip PDF formal) -->
        <div class="avoid-break mt-6 pt-4 border-t border-[var(--border)] flex items-start justify-between font-mono text-xs text-[var(--text-secondary)]">
            <div>
                <p class="font-bold text-[var(--text-primary)] mb-1">CATATAN EVALUASI EHS & CSR:</p>
                <p class="text-[11px] text-[var(--text-secondary)] leading-relaxed max-w-md">
                    Rekapitulasi ini bersumber dari verifikasi temuan inspeksi bahaya, tindakan keselamatan, saran perbaikan, aksi penanganan langsung, serta pemateri K3 periode {{ $startDate->format('d/m/Y') }} s.d. {{ $endDate->format('d/m/Y') }}.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-8 text-center min-w-[320px]">
                <div>
                    <span class="text-[11px] text-[var(--text-secondary)] uppercase block mb-12">Disiapkan Oleh:</span>
                    <div class="border-b border-zinc-800 w-36 mx-auto mb-1"></div>
                    <span class="font-bold text-[var(--text-primary)] uppercase block text-[11px]">Koordinator EHS</span>
                </div>
                <div>
                    <span class="text-[11px] text-[var(--text-secondary)] uppercase block mb-12">Disetujui Oleh:</span>
                    <div class="border-b border-zinc-800 w-36 mx-auto mb-1"></div>
                    <span class="font-bold text-[var(--text-primary)] uppercase block text-[11px]">Management Representative</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
