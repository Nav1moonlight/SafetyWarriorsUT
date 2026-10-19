@extends('layouts.app')

@section('title', 'Klasemen IMPACT WARRIOR')

@section('content')
<div class="space-y-8">

    <!-- 1. Header Periode & Controls -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[var(--border)]">
        <div>
            <div class="flex flex-wrap items-center gap-2 font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">
                <span class="inline-flex items-center px-2 py-0.5 border border-[var(--border)] bg-[var(--surface)] text-[var(--text-primary)] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isCurrentPeriod ? 'bg-[#2ECC71]' : 'bg-zinc-500' }} mr-1.5"></span>
                    {{ $isCurrentPeriod ? 'PERIODE AKTIF' : 'ARSIP PERIODE' }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 border border-[var(--border)] bg-[var(--surface)] text-[var(--text-primary)]">
                    <i class="fa-regular fa-calendar mr-1.5 text-[var(--accent)] text-[10px]"></i>
                    {{ $startDate->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}
                </span>
                <span class="text-zinc-500">|</span>
                <span class="text-[var(--text-secondary)]">SISTEM MONITORING EHS & CSR</span>
            </div>

            <h1 class="font-heading text-4xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tight text-[var(--text-primary)] mt-1.5">
                KLASEMEN {{ $availableMonths[$selectedMonth] ?? 'BULAN' }} {{ $selectedYear }}
            </h1>
            
            <p class="font-mono text-[11px] text-[var(--text-secondary)] mt-1 tracking-widest uppercase">
                Sistem Monitoring K3 &amp; CSR &nbsp;·&nbsp; EHS Division
            </p>
        </div>

        <div class="flex flex-nowrap items-center gap-2 font-mono text-xs shrink-0 overflow-x-auto">
            <!-- Filter Bulan & Tahun Form -->
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1.5 bg-[var(--surface)] border border-[var(--border)] px-2 py-1.5 h-9 shrink-0">
                <input type="hidden" name="department" value="{{ $selectedDept }}">
                <input type="hidden" name="sort" value="{{ $sortBy }}">
                
                <span class="text-[10px] text-[var(--text-secondary)]">PERIODE:</span>
                <select name="month" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-1 py-0.5 focus:outline-none cursor-pointer uppercase font-mono">
                    @foreach($availableMonths as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">{{ strtoupper($mName) }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-1 py-0.5 focus:outline-none cursor-pointer font-mono">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">{{ $y }}</option>
                    @endfor
                </select>
            </form>


            <!-- Print / Cetak PDF Button -->
            <a href="{{ route('print.pdf', ['month' => $selectedMonth, 'year' => $selectedYear, 'department' => $selectedDept, 'sort' => $sortBy]) }}" 
               class="inline-flex items-center px-3 h-9 border border-[var(--accent)] text-[var(--accent)] hover:bg-[var(--accent)] hover:text-black transition uppercase tracking-wider font-bold bg-[var(--surface)] whitespace-nowrap shrink-0"
               title="Buka laporan format cetak PDF bulanan">
                <i class="fa-solid fa-print mr-1.5 text-xs"></i> CETAK / PDF
            </a>
        </div>
    </div>

    <!-- 2. KPI Horizontal Strip (Single Strip with Vertical Dividers) -->
    <div class="box-industrial grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 divide-y md:divide-y-0 divide-x-0 md:divide-x divide-[var(--border)]">
        
        <!-- Total Poin -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between">
            <span class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">TOTAL POIN</span>
            <div class="font-heading font-black text-3xl sm:text-4xl lg:text-5xl text-[var(--accent)] tabular-nums tracking-tight mt-1">
                <span data-countup="{{ $totalPointsCount }}">{{ $totalPointsCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase mt-1">AKUMULASI POIN</span>
        </div>

        <!-- Total Laporan -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between border-t md:border-t-0">
            <span class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">TOTAL LAPORAN</span>
            <div class="font-heading font-black text-3xl sm:text-4xl lg:text-5xl text-[var(--text-primary)] tabular-nums tracking-tight mt-1">
                <span data-countup="{{ $totalReportsCount }}">{{ $totalReportsCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase mt-1">TEMUAN / KEGIATAN</span>
        </div>

        <!-- Kondisi Tidak Aman (+1) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t md:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--unsafe-cond);"></span>
                <span class="truncate">KONDISI</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalCondCount }}">{{ $totalCondCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+1 POIN</span>
        </div>

        <!-- Tindakan / Near Miss (+2) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t md:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--unsafe-act);"></span>
                <span class="truncate">TINDAKAN / NM</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalActCount }}">{{ $totalActCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+2 POIN</span>
        </div>

        <!-- Rekomendasi / Saran (+3) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t lg:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--suggestion);"></span>
                <span class="truncate">SARAN</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalSugCount }}">{{ $totalSugCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+3 POIN</span>
        </div>

        <!-- Penanganan Langsung / Action (+3) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t lg:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--action);"></span>
                <span class="truncate">ACTION</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-[#2ECC71] tabular-nums mt-1">
                <span data-countup="{{ $totalActionCount }}">{{ $totalActionCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+3 POIN</span>
        </div>

        <!-- Pemateri (+4) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t lg:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-[#9333EA]"></span>
                <span class="truncate">PEMATERI</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-[#9333EA] tabular-nums mt-1">
                <span data-countup="{{ $totalSpeakerCount }}">{{ $totalSpeakerCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+4 POIN</span>
        </div>

        <!-- Kegiatan EHS & CSR (+2) -->
        <div class="p-3 sm:p-3.5 flex flex-col justify-between border-t lg:border-t-0">
            <div class="flex items-center space-x-1 font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-teal-400"></span>
                <span class="truncate">KEGIATAN</span>
            </div>
            <div class="font-heading font-bold text-2xl sm:text-3xl text-teal-400 tabular-nums mt-1">
                <span data-countup="{{ $totalActivityCount }}">{{ $totalActivityCount }}</span>
            </div>
            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase">+2 POIN</span>
        </div>

    </div>

    <!-- 3. Hero Juara: Podium 3 Besar -->
    @if($rank1 && $sortBy === 'points')
        <div>
            <div class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)] mb-3 flex items-center space-x-2">
                <i class="fa-solid fa-trophy text-[var(--accent)] text-xs"></i>
                <span>PODIUM TOP 3 IMPACT WARRIOR</span>
                @if(!empty($selectedDept))
                    <span class="text-[var(--accent)]">• DEPARTEMEN {{ strtoupper($selectedDept) }}</span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                
                <!-- Rank 2 Podium -->
                @if($rank2)
                    <div class="md:col-span-4 order-2 md:order-1 box-industrial p-5 flex flex-col justify-between relative border-t-2 border-t-zinc-400">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-[11px] uppercase text-[var(--text-secondary)]">PERINGKAT 02</span>
                                <div class="flex items-center space-x-2 mt-1">
                                    <h3 class="font-heading font-bold text-xl sm:text-2xl uppercase tracking-tight text-[var(--text-primary)]">
                                        {{ $rank2->reporter_name }}
                                    </h3>
                                    @if(!empty($rank2->nrp))
                                        <span class="px-1.5 py-0.2 bg-[var(--bg)] text-[var(--text-primary)] border border-[var(--border)] font-mono text-[10px] font-bold">
                                            NRP: {{ $rank2->nrp }}
                                        </span>
                                    @endif
                                </div>
                                <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                    DEPT. {{ $rank2->department }} • {{ $rank2->total_reports }} LAPORAN
                                </div>
                            </div>
                            <div class="font-heading font-black text-4xl sm:text-5xl stroke-num select-none">
                                02
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-[var(--border)] flex items-end justify-between">
                            <div>
                                <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mb-1">RINCIAN TEMUAN / KEGIATAN</div>
                                <div class="font-mono text-xs text-[var(--text-primary)] flex items-center flex-wrap gap-1">
                                    <span style="color: var(--unsafe-cond)">●</span> {{ $rank2->cond_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--unsafe-act)">●</span> {{ $rank2->act_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--suggestion)">●</span> {{ $rank2->suggestion_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--action)">●</span> {{ $rank2->action_count }}
                                    @if(($rank2->speaker_count ?? 0) > 0)
                                        <span class="text-[var(--text-secondary)]">·</span>
                                        <span class="text-[#9333EA]">●</span> {{ $rank2->speaker_count }}
                                    @endif
                                    @if((($rank2->ehs_count ?? 0) + ($rank2->csr_count ?? 0)) > 0)
                                        <span class="text-[var(--text-secondary)]">·</span>
                                        <span class="text-teal-400">●</span> {{ ($rank2->ehs_count ?? 0) + ($rank2->csr_count ?? 0) }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right font-heading font-black text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums">
                                <span data-countup="{{ $rank2->total_points }}">{{ $rank2->total_points }}</span>
                                <span class="text-xs font-mono font-normal text-[var(--text-secondary)] block">POIN</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Rank 1 Podium -->
                <div class="md:col-span-4 order-1 md:order-2 box-industrial p-6 flex flex-col justify-between relative border-2 border-[var(--accent)] bg-[var(--surface)]">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-[var(--accent)] bg-[var(--bg)] px-2 py-0.5 border border-[var(--accent)] inline-block">
                                JUARA 1 • IMPACT WARRIOR
                            </span>
                            <div class="flex items-center space-x-2 mt-2">
                                <h2 class="font-heading font-black text-2xl sm:text-3xl uppercase tracking-tight text-[var(--text-primary)]">
                                    {{ $rank1->reporter_name }}
                                </h2>
                                @if(!empty($rank1->nrp))
                                    <span class="px-1.5 py-0.5 bg-[var(--bg)] text-[var(--accent)] border border-[var(--accent)] font-mono text-[10px] font-bold">
                                        NRP: {{ $rank1->nrp }}
                                    </span>
                                @endif
                            </div>
                            <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                DEPT. {{ $rank1->department }} • {{ $rank1->total_reports }} LAPORAN
                            </div>
                        </div>
                        <div class="font-heading font-black text-6xl stroke-num-gold select-none">
                            01
                        </div>
                    </div>

                    <div class="my-4 py-3 border-y border-[var(--border)] flex items-center justify-between">
                        <div>
                            <div class="font-mono text-[11px] text-[var(--text-secondary)] uppercase mb-1">KONTRIBUSI INDIKATOR</div>
                            <div class="font-mono text-sm text-[var(--text-primary)] flex items-center flex-wrap gap-1.5">
                                <span style="color: var(--unsafe-cond)">●</span> {{ $rank1->cond_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--unsafe-act)">●</span> {{ $rank1->act_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--suggestion)">●</span> {{ $rank1->suggestion_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--action)">●</span> {{ $rank1->action_count }}
                                @if(($rank1->speaker_count ?? 0) > 0)
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span class="text-[#9333EA]">●</span> {{ $rank1->speaker_count }}
                                @endif
                                @if((($rank1->ehs_count ?? 0) + ($rank1->csr_count ?? 0)) > 0)
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span class="text-teal-400">●</span> {{ ($rank1->ehs_count ?? 0) + ($rank1->csr_count ?? 0) }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right font-heading font-black text-5xl sm:text-6xl text-[var(--accent)] tabular-nums">
                            <span data-countup="{{ $rank1->total_points }}">{{ $rank1->total_points }}</span>
                            <span class="text-xs font-mono font-bold text-[var(--accent)] block tracking-widest">POIN</span>
                        </div>
                    </div>

                    <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase flex flex-wrap gap-x-2 gap-y-0.5">
                        <span><span style="color: var(--unsafe-cond)">●</span> KONDISI</span>
                        <span><span style="color: var(--unsafe-act)">●</span> TINDAKAN</span>
                        <span><span style="color: var(--suggestion)">●</span> SARAN</span>
                        <span><span style="color: var(--action)">●</span> ACTION</span>
                        <span><span class="text-[#9333EA]">●</span> PEMATERI</span>
                        <span><span class="text-teal-400">●</span> KEGIATAN</span>
                    </div>
                </div>

                <!-- Rank 3 Podium -->
                @if($rank3)
                    <div class="md:col-span-4 order-3 box-industrial p-5 flex flex-col justify-between relative border-t-2 border-t-[#B45309]">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-[11px] uppercase text-[var(--text-secondary)]">PERINGKAT 03</span>
                                <div class="flex items-center space-x-2 mt-1">
                                    <h3 class="font-heading font-bold text-xl sm:text-2xl uppercase tracking-tight text-[var(--text-primary)]">
                                        {{ $rank3->reporter_name }}
                                    </h3>
                                    @if(!empty($rank3->nrp))
                                        <span class="px-1.5 py-0.2 bg-[var(--bg)] text-[var(--text-primary)] border border-[var(--border)] font-mono text-[10px] font-bold">
                                            NRP: {{ $rank3->nrp }}
                                        </span>
                                    @endif
                                </div>
                                <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                    DEPT. {{ $rank3->department }} • {{ $rank3->total_reports }} LAPORAN
                                </div>
                            </div>
                            <div class="font-heading font-black text-4xl sm:text-5xl stroke-num select-none">
                                03
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-[var(--border)] flex items-end justify-between">
                            <div>
                                <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mb-1">RINCIAN TEMUAN / KEGIATAN</div>
                                <div class="font-mono text-xs text-[var(--text-primary)] flex items-center flex-wrap gap-1">
                                    <span style="color: var(--unsafe-cond)">●</span> {{ $rank3->cond_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--unsafe-act)">●</span> {{ $rank3->act_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--suggestion)">●</span> {{ $rank3->suggestion_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--action)">●</span> {{ $rank3->action_count }}
                                    @if(($rank3->speaker_count ?? 0) > 0)
                                        <span class="text-[var(--text-secondary)]">·</span>
                                        <span class="text-[#9333EA]">●</span> {{ $rank3->speaker_count }}
                                    @endif
                                    @if((($rank3->ehs_count ?? 0) + ($rank3->csr_count ?? 0)) > 0)
                                        <span class="text-[var(--text-secondary)]">·</span>
                                        <span class="text-teal-400">●</span> {{ ($rank3->ehs_count ?? 0) + ($rank3->csr_count ?? 0) }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right font-heading font-black text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums">
                                <span data-countup="{{ $rank3->total_points }}">{{ $rank3->total_points }}</span>
                                <span class="text-xs font-mono font-normal text-[var(--text-secondary)] block">POIN</span>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    <!-- 4. Daftar Seluruh Klasemen Karyawan (Lengkap, Detail, & Bisa Diurutkan) -->
    <div class="space-y-3">
        <!-- Controls & Header directly above leaderboard -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 font-mono text-xs uppercase text-[var(--text-secondary)]">
            <div>
                <span class="font-bold text-[var(--text-primary)] text-sm flex items-center space-x-2">
                    <i class="fa-solid fa-ranking-star text-[var(--accent)]"></i>
                    <span>DAFTAR KLASEMEN LENGKAP KARYAWAN</span>
                </span>
                <span class="text-[11px] text-[var(--text-secondary)] block mt-0.5">
                    KLIK BARIS KARYAWAN UNTUK MELIHAT RINCIAN DOKUMENTASI LAPORAN LENGKAP
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter Departemen & Urutkan Klasemen Form -->
                <form method="GET" action="{{ route('dashboard') }}" class="inline-flex items-center h-9 bg-[var(--surface)] border border-[var(--border)] px-2">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    
                    <!-- Departemen Filter -->
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-[var(--text-secondary)]">DEPT:</span>
                        <select name="department" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-1 focus:outline-none cursor-pointer uppercase font-mono">
                            <option value="" class="bg-[var(--surface)] text-[var(--text-primary)]">SEMUA DEPT</option>
                            @foreach($availableDepartments as $dept)
                                <option value="{{ $dept }}" {{ $selectedDept == $dept ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">{{ strtoupper($dept) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Urutkan Berdasarkan -->
                    <div class="flex items-center space-x-1 border-l border-[var(--border)] pl-2 ml-1">
                        <span class="text-[10px] text-[var(--text-secondary)]">URUT:</span>
                        <select name="sort" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-1 focus:outline-none cursor-pointer uppercase font-mono">
                            <option value="points" {{ $sortBy == 'points' ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">SKOR TERTINGGI</option>
                            <option value="name_asc" {{ $sortBy == 'name_asc' ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">ABJAD (A - Z)</option>
                            <option value="name_desc" {{ $sortBy == 'name_desc' ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">ABJAD (Z - A)</option>
                        </select>
                    </div>
                </form>

                <span class="inline-flex items-center h-9 px-3 border border-[var(--border)] bg-[var(--surface)] text-[var(--text-primary)] font-mono text-xs font-bold whitespace-nowrap">
                    {{ count($allLeaderboard) }} KARYAWAN
                </span>

                <a href="{{ route('print.pdf', ['month' => $selectedMonth, 'year' => $selectedYear, 'department' => $selectedDept, 'sort' => $sortBy]) }}" 
                   title="Cetak akumulasi klasemen ke PDF"
                   class="inline-flex items-center h-9 px-3 border border-[var(--accent)] text-[var(--accent)] hover:bg-[var(--accent)] hover:text-black bg-[var(--surface)] font-mono text-[11px] font-bold uppercase transition whitespace-nowrap">
                    <i class="fa-solid fa-print mr-1.5 text-xs"></i> CETAK PDF
                </a>
            </div>
        </div>

        <div class="box-industrial divide-y divide-[var(--border)] overflow-hidden">
            
            <!-- Table Header: RANK | NRP | KARYAWAN & DEPT | RINCIAN INDIKATOR TEMUAN / KEGIATAN | LAPORAN | TOTAL POIN -->
            <div class="hidden sm:grid sm:grid-cols-12 px-4 py-2.5 bg-[var(--surface)] text-[11px] font-mono uppercase tracking-wider text-[var(--text-secondary)] border-b border-[var(--border)]">
                <div class="col-span-1 text-center">RANK</div>
                <div class="col-span-2">NRP</div>
                <div class="col-span-3">KARYAWAN & DEPARTEMEN</div>
                <div class="col-span-4 text-center">RINCIAN INDIKATOR TEMUAN / KEGIATAN</div>
                <div class="col-span-1 text-center">LAPORAN</div>
                <div class="col-span-1 text-right">TOTAL POIN</div>
            </div>

            <!-- All Rows (Clickable to open Employee Detail Modal) -->
            @forelse($allLeaderboard as $row)
                <div class="relative group hover:bg-[var(--surface-hover)] transition-colors duration-150 cursor-pointer"
                     @click="activeEmployee = {
                                 name: '{{ addslashes($row->reporter_name) }}',
                                 nrp: '{{ addslashes($row->nrp ?? '-') }}',
                                 department: '{{ addslashes($row->department) }}',
                                 points: {{ $row->total_points }},
                                 reports_count: {{ $row->total_reports }},
                                 reports: {{ json_encode($row->reports_list ?? []) }}
                             };
                             employeeModal = true;"
                     title="Klik untuk melihat rincian laporan {{ $row->reporter_name }}">
                    
                    <!-- Relative percentage bar behind/under row -->
                    <div class="absolute inset-y-0 left-0 bg-[var(--accent)] opacity-10 pointer-events-none transition-all"
                         style="width: {{ max(1, $row->pct_of_first) }}%;"></div>

                    <div class="relative z-10 grid grid-cols-1 sm:grid-cols-12 px-4 py-3 items-center gap-2 sm:gap-0">
                        
                        <!-- 1. Rank Number -->
                        <div class="sm:col-span-1 font-mono text-xs flex items-center justify-start sm:justify-center">
                            @if($row->rank === 1)
                                <span class="font-bold text-[var(--accent)] text-sm">01</span>
                            @elseif($row->rank <= 3)
                                <span class="font-bold text-[var(--text-primary)] text-sm">{{ sprintf('%02d', $row->rank) }}</span>
                            @else
                                <span class="font-normal text-[var(--text-secondary)]">{{ sprintf('%02d', $row->rank) }}</span>
                            @endif
                        </div>

                        <!-- 2. NRP (Strictly between Rank & Nama Karyawan) -->
                        <div class="sm:col-span-2 font-mono text-xs">
                            @if(!empty($row->nrp))
                                <span class="px-2 py-0.5 bg-[var(--surface)] border border-[var(--border)] text-[var(--accent)] font-semibold inline-block">
                                    {{ $row->nrp }}
                                </span>
                            @else
                                <span class="text-[var(--text-secondary)] font-mono text-[11px]">—</span>
                            @endif
                        </div>

                        <!-- 3. Karyawan & Departemen -->
                        <div class="sm:col-span-3">
                            <span class="font-semibold text-sm text-[var(--text-primary)] group-hover:text-[var(--accent)] transition-colors block">
                                {{ $row->reporter_name }}
                            </span>
                            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mt-0.5 block">
                                DEPT. {{ $row->department }}
                            </span>
                        </div>

                        <!-- 4. Rincian Indikator (Clean, Non-cluttered) -->
                        <div class="sm:col-span-4 font-mono text-xs flex items-center justify-start sm:justify-center flex-wrap gap-1.5">
                            <!-- Kondisi (+1) -->
                            <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-[var(--border)]" title="Kondisi Tidak Aman (+1)">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--unsafe-cond);"></span>
                                <span class="text-[var(--text-primary)]">{{ $row->cond_count }}</span>
                            </span>
                            <!-- Tindakan (+2) -->
                            <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-[var(--border)]" title="Tindakan Tidak Aman / Near Miss (+2)">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--unsafe-act);"></span>
                                <span class="text-[var(--text-primary)]">{{ $row->act_count }}</span>
                            </span>
                            <!-- Saran (+3) -->
                            <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-[var(--border)]" title="Saran / Rekomendasi (+3)">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--suggestion);"></span>
                                <span class="text-[var(--text-primary)]">{{ $row->suggestion_count }}</span>
                            </span>
                            <!-- Action (+3) -->
                            <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-[var(--border)]" title="Penanganan Langsung / Action (+3)">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--action);"></span>
                                <span class="text-[#2ECC71] font-semibold">{{ $row->action_count }}</span>
                            </span>
                            @if(($row->speaker_count ?? 0) > 0)
                                <!-- Pemateri / Edukasi (+4) -->
                                <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-[#9333EA]/60" title="Pemateri / Edukasi (+4)">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#9333EA]"></span>
                                    <span class="text-[#9333EA] font-semibold">{{ $row->speaker_count }}</span>
                                </span>
                            @endif
                            @if(($row->ehs_count ?? 0) > 0 || ($row->csr_count ?? 0) > 0)
                                <!-- Kegiatan EHS / CSR (+2) -->
                                <span class="inline-flex items-center space-x-1 px-1.5 py-0.5 bg-[var(--bg)] border border-teal-700/50" title="Kegiatan EHS / CSR (+2)">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                                    <span class="text-teal-400 font-semibold">{{ ($row->ehs_count ?? 0) + ($row->csr_count ?? 0) }}</span>
                                </span>
                            @endif
                        </div>

                        <!-- 5. Total Laporan -->
                        <div class="sm:col-span-1 font-mono text-xs text-left sm:text-center text-[var(--text-secondary)]">
                            <span class="sm:hidden font-mono text-[10px] text-[var(--text-secondary)] uppercase mr-1">LAPORAN:</span>
                            <span class="text-[var(--text-primary)] font-semibold">{{ $row->total_reports }}</span>
                        </div>

                        <!-- 6. Total Poin -->
                        <div class="sm:col-span-1 text-left sm:text-right">
                            <span class="font-heading font-black text-xl sm:text-2xl text-[var(--accent)] tabular-nums">
                                {{ $row->total_points }}
                            </span>
                            <span class="font-mono text-[9px] text-[var(--text-secondary)] uppercase ml-0.5">PTS</span>
                        </div>

                    </div>
                </div>
            @empty
                <div class="p-8 text-center font-mono text-xs text-[var(--text-secondary)] uppercase">
                    Belum ada data laporan pada periode ini.
                </div>
            @endforelse

        </div>
    </div>

    <!-- 5. Galeri Temuan Foto (Dengan Filter Departemen & Urutan Abjad) -->
    <div id="gallery-section" class="space-y-4 pt-4 border-t border-[var(--border)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 font-mono text-xs uppercase text-[var(--text-secondary)]">
            <span class="flex items-center space-x-2">
                <i class="fa-solid fa-camera text-[var(--accent)]"></i>
                <span class="text-[var(--text-primary)] font-bold text-sm">GALERI DOKUMENTASI TEMUAN / KEGIATAN</span>
            </span>

            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('dashboard') }}#gallery-section" class="flex items-center space-x-1.5">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="department" value="{{ $selectedDept }}">
                    <input type="hidden" name="sort" value="{{ $sortBy }}">
                    
                    <span class="text-[10px]">URUTKAN FOTO:</span>
                    <select name="sort_gallery" onchange="this.form.submit()" class="bg-[var(--surface)] text-[var(--text-primary)] border border-[var(--border)] px-2 py-1 text-xs focus:outline-none">
                        <option value="latest" {{ $sortGallery == 'latest' ? 'selected' : '' }}>TERBARU</option>
                        <option value="name_asc" {{ $sortGallery == 'name_asc' ? 'selected' : '' }}>NAMA (A - Z)</option>
                        <option value="dept_asc" {{ $sortGallery == 'dept_asc' ? 'selected' : '' }}>DEPARTEMEN (A - Z)</option>
                    </select>
                </form>
                <span>({{ count($evidenceReports) }} FOTO)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($evidenceReports as $ev)
                <div class="box-industrial overflow-hidden flex flex-col justify-between cursor-pointer group"
                     @click="activePhoto = '{{ $ev->direct_photo_url }}'; 
                             activeCaption = '{{ addslashes($ev->description) }}'; 
                             activeReporter = '{{ addslashes($ev->reporter_name) }}'; 
                             activeDept = '{{ addslashes($ev->department) }}';
                             activeNrp = '{{ addslashes($ev->nrp ?? '') }}';
                             activeDate = '{{ $ev->reported_at->format('d/m/Y H:i') }}';
                             activePoints = {{ $ev->points }};
                             activeIndicator = '{{ addslashes($ev->activity_indicator ?: 'Kondisi Tidak Aman') }}';
                             activeCategory = '{{ addslashes($ev->indicator_category ?: 'EHS') }}';
                             photoModal = true;">
                    
                    <!-- Photo viewport -->
                    <div class="relative h-48 bg-black overflow-hidden">
                        @if($ev->photo_url)
                            <img src="{{ $ev->direct_photo_url }}" alt="Foto Temuan" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center font-mono text-xs text-[var(--text-secondary)]">
                                <i class="fa-solid fa-camera-slash text-xl mb-1"></i>
                                <span>TANPA LAMPIRAN FOTO</span>
                            </div>
                        @endif

                        <!-- Top-Right Point Tag -->
                        <div class="absolute top-2 right-2">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 bg-black/85 text-[var(--accent)] border border-[var(--border)]">
                                +{{ $ev->points }} POIN
                            </span>
                        </div>

                        <!-- Top-Left Semantic Category Indicators -->
                        <div class="absolute top-2 left-2 flex flex-wrap gap-1 font-mono text-[10px] uppercase">
                            <span class="px-1.5 py-0.5 bg-black/80 text-[var(--accent)] border border-[var(--accent)] font-bold">
                                {{ $ev->indicator_category ?: 'EHS' }}
                            </span>
                            @if($ev->has_unsafe_condition)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--unsafe-cond);">KONDISI</span>
                            @endif
                            @if($ev->has_unsafe_act)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--unsafe-act);">TINDAKAN</span>
                            @endif
                            @if($ev->has_suggestion)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--suggestion);">SARAN</span>
                            @endif
                            @if($ev->has_action || $ev->has_direct_action)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--action);">ACTION</span>
                            @endif
                            @if($ev->has_speaker_activity)
                                <span class="px-1.5 py-0.5 bg-[#9333EA] text-white font-bold">PEMATERI</span>
                            @endif
                            @if($ev->has_ehs_activity || $ev->has_csr_activity)
                                <span class="px-1.5 py-0.5 bg-teal-500 text-black font-bold">KEGIATAN</span>
                            @endif
                        </div>
                    </div>

                    <!-- Details Box (Detailed with NRP, Dept, Indicator, Description) -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between space-y-2">
                        <div>
                            <div class="flex items-center justify-between font-mono text-xs">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="font-bold text-[var(--text-primary)] truncate">{{ $ev->reporter_name }}</span>
                                    @if(!empty($ev->nrp))
                                        <span class="px-1 py-0.2 bg-[var(--surface)] text-[var(--accent)] border border-[var(--border)] font-mono text-[9px] font-bold">
                                            {{ $ev->nrp }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[var(--text-secondary)] uppercase text-[11px] flex-shrink-0 ml-1">DEPT. {{ $ev->department }}</span>
                            </div>

                            <div class="font-mono text-[11px] text-[var(--accent)] mt-1 font-medium">
                                {{ $ev->activity_indicator ?: 'Kondisi Tidak Aman' }}
                            </div>

                            <p class="text-xs text-[var(--text-secondary)] mt-1 line-clamp-2 leading-relaxed">
                                {{ $ev->description ?: 'Tidak ada keterangan tambahan.' }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-[var(--border)] flex items-center justify-between font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                            <span>{{ $ev->reported_at->format('d/m/Y H:i') }} WITA</span>
                            <span class="text-[var(--text-primary)] group-hover:text-[var(--accent)] font-semibold">KLIK DETAIL ↗</span>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-3 box-industrial p-8 text-center font-mono text-xs text-[var(--text-secondary)] uppercase">
                    Tidak ada arsip dokumentasi temuan untuk periode ini.
                </div>
            @endforelse
        </div>
    </div>

    <!-- 6. Statistik Departemen -->
    <div id="stats-section" class="space-y-4 pt-4 border-t border-[var(--border)]">
        <div class="flex items-center justify-between font-mono text-xs uppercase text-[var(--text-secondary)]">
            <span class="flex items-center space-x-2">
                <i class="fa-solid fa-chart-simple text-[var(--accent)]"></i>
                <span class="text-[var(--text-primary)] font-bold">DISTRIBUSI POIN PER DEPARTEMEN</span>
            </span>
            <span>{{ count($deptStats) }} DEPARTEMEN AKTIF</span>
        </div>

        <div class="box-industrial p-5 space-y-3.5">
            @forelse($deptStats as $ds)
                @php
                    $pct = $totalPointsCount > 0 ? round(($ds->total_points / $totalPointsCount) * 100) : 0;
                @endphp
                <div class="space-y-1">
                    <div class="flex items-center justify-between font-mono text-xs">
                        <span class="font-bold text-[var(--text-primary)] uppercase">
                            {{ $ds->department }} 
                            <span class="text-[var(--text-secondary)] font-normal text-[11px]">({{ $ds->total_reports }} TEMUAN / KEGIATAN)</span>
                        </span>
                        <span class="font-heading font-bold text-base text-[var(--accent)] tabular-nums">
                            {{ $ds->total_points }} POIN <span class="font-mono text-xs text-[var(--text-secondary)]">({{ $pct }}%)</span>
                        </span>
                    </div>
                    <div class="w-full bg-[var(--bg)] border border-[var(--border)] h-2 overflow-hidden" style="border-radius: 2px;">
                        <div class="bg-[var(--accent)] h-full transition-all duration-500" style="width: {{ max(2, $pct) }}%;"></div>
                    </div>
                </div>
            @empty
                <div class="text-center font-mono text-xs text-[var(--text-secondary)] py-4 uppercase">
                    Belum ada data statistik departemen.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
