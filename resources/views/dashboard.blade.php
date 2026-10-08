@extends('layouts.app')

@section('title', 'Klasemen K3')

@section('content')
<div class="space-y-8">

    <!-- 1. Header Periode & Controls -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-[var(--border)]">
        <div>
            <div class="flex items-center space-x-2 font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">
                <span class="inline-flex items-center px-1.5 py-0.5 border border-[var(--border)] bg-[var(--surface)] text-[var(--text-primary)]">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isCutoffClosed ? 'bg-zinc-500' : 'bg-[#2ECC71]' }} mr-1.5"></span>
                    {{ $isCutoffClosed ? 'PERIODE DITUTUP' : 'PERIODE AKTIF' }}
                </span>
                <span>/ CUT-OFF TGL 25 (23:59 WIB)</span>
                @if(!$isCutoffClosed)
                    <span class="text-[var(--accent)] font-semibold">({{ max(0, $daysRemaining) }} HARI LAGI)</span>
                @endif
            </div>

            <h1 class="font-heading text-4xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tight text-[var(--text-primary)] mt-1.5">
                KLASEMEN {{ $availableMonths[$selectedMonth] ?? 'BULAN' }} {{ $selectedYear }}
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2 font-mono text-xs">
            <!-- Period Selector Form -->
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-1 bg-[var(--surface)] border border-[var(--border)] p-1">
                <select name="month" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-2 py-1 focus:outline-none cursor-pointer uppercase">
                    @foreach($availableMonths as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">{{ strtoupper($mName) }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="bg-transparent text-[var(--text-primary)] text-xs px-2 py-1 focus:outline-none cursor-pointer">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }} class="bg-[var(--surface)] text-[var(--text-primary)]">{{ $y }}</option>
                    @endfor
                </select>
            </form>

            <!-- Export CSV Button -->
            <a href="{{ route('export.csv', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" 
               class="inline-flex items-center px-3 py-1.5 border border-[var(--border)] hover:border-[var(--text-secondary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition uppercase tracking-wider">
                <i class="fa-solid fa-file-arrow-down mr-1.5 text-[10px]"></i> EXPORT CSV
            </a>
        </div>
    </div>

    <!-- 2. KPI Horizontal Strip (Single Strip with Vertical Dividers) -->
    <div class="box-industrial grid grid-cols-2 lg:grid-cols-6 divide-y lg:divide-y-0 lg:divide-x divide-[var(--border)]">
        
        <!-- Total Poin -->
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">TOTAL POIN</span>
            <div class="font-heading font-black text-4xl sm:text-5xl lg:text-6xl text-[var(--accent)] tabular-nums tracking-tight mt-1">
                <span data-countup="{{ $totalPointsCount }}">{{ $totalPointsCount }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mt-1">AKUMULASI BULAN INI</span>
        </div>

        <!-- Total Laporan -->
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)]">TOTAL LAPORAN</span>
            <div class="font-heading font-black text-4xl sm:text-5xl lg:text-6xl text-[var(--text-primary)] tabular-nums tracking-tight mt-1">
                <span data-countup="{{ $totalReportsCount }}">{{ $totalReportsCount }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mt-1">TEMUAN TERVALIDASI</span>
        </div>

        <!-- Tindakan Bahaya (+5) -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between">
            <div class="flex items-center space-x-1.5 font-mono text-[11px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--unsafe-act);"></span>
                <span class="truncate">TINDAKAN BAHAYA</span>
            </div>
            <div class="font-heading font-bold text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalUnsafeAct }}">{{ $totalUnsafeAct }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase">+5 POIN / TEMUAN</span>
        </div>

        <!-- Kondisi Bahaya (+3) -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between">
            <div class="flex items-center space-x-1.5 font-mono text-[11px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--unsafe-cond);"></span>
                <span class="truncate">KONDISI BAHAYA</span>
            </div>
            <div class="font-heading font-bold text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalUnsafeCondition }}">{{ $totalUnsafeCondition }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase">+3 POIN / TEMUAN</span>
        </div>

        <!-- Masukan/Solusi (+3) -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between">
            <div class="flex items-center space-x-1.5 font-mono text-[11px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--suggestion);"></span>
                <span class="truncate">MASUKAN / SOLUSI</span>
            </div>
            <div class="font-heading font-bold text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums mt-1">
                <span data-countup="{{ $totalSuggestion }}">{{ $totalSuggestion }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase">+3 POIN / SARAN</span>
        </div>

        <!-- Action Nyata (+4) -->
        <div class="p-3.5 sm:p-4 flex flex-col justify-between">
            <div class="flex items-center space-x-1.5 font-mono text-[11px] text-[var(--text-secondary)] uppercase">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--action);"></span>
                <span class="truncate">ACTION NYATA</span>
            </div>
            <div class="font-heading font-bold text-3xl sm:text-4xl text-[#2ECC71] tabular-nums mt-1">
                <span data-countup="{{ $totalAction }}">{{ $totalAction }}</span>
            </div>
            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase">+4 POIN / AKSI</span>
        </div>

    </div>

    <!-- 3. Hero Juara: Podium 3 Besar (Tegas, Field Scoreboard Style) -->
    @if($rank1)
        <div>
            <div class="font-mono text-[11px] uppercase tracking-wider text-[var(--text-secondary)] mb-3 flex items-center space-x-2">
                <i class="fa-solid fa-trophy text-[var(--accent)] text-xs"></i>
                <span>PODIUM SAFETY WARRIOR BULAN INI</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                
                <!-- Rank 2 Podium (Left on desktop) -->
                @if($rank2)
                    <div class="md:col-span-4 order-2 md:order-1 box-industrial p-5 flex flex-col justify-between relative border-t-2 border-t-zinc-400">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-[11px] uppercase text-[var(--text-secondary)]">PERINGKAT 02</span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl uppercase tracking-tight text-[var(--text-primary)] mt-1">
                                    {{ $rank2->reporter_name }}
                                </h3>
                                <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                    DEPT. {{ $rank2->department }}
                                </div>
                            </div>
                            <div class="font-heading font-black text-4xl sm:text-5xl stroke-num select-none">
                                02
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-[var(--border)] flex items-end justify-between">
                            <div>
                                <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mb-1">RINCIAN KATEGORI</div>
                                <!-- Clean dotted format: 2 · 2 · 3 · 3 -->
                                <div class="font-mono text-xs text-[var(--text-primary)] flex items-center space-x-1.5">
                                    <span style="color: var(--unsafe-act)">●</span> {{ $rank2->unsafe_act_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--unsafe-cond)">●</span> {{ $rank2->unsafe_condition_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--suggestion)">●</span> {{ $rank2->suggestion_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--action)">●</span> {{ $rank2->action_count }}
                                </div>
                            </div>
                            <div class="text-right font-heading font-black text-3xl sm:text-4xl text-[var(--text-primary)] tabular-nums">
                                <span data-countup="{{ $rank2->total_points }}">{{ $rank2->total_points }}</span>
                                <span class="text-xs font-mono font-normal text-[var(--text-secondary)] block">POIN</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Rank 1 Podium (Center / Largest) -->
                <div class="md:col-span-4 order-1 md:order-2 box-industrial p-6 flex flex-col justify-between relative border-2 border-[var(--accent)] bg-[var(--surface)]">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-[var(--accent)] bg-[var(--bg)] px-2 py-0.5 border border-[var(--accent)] inline-block">
                                JUARA 1 • SAFETY WARRIOR
                            </span>
                            <h2 class="font-heading font-black text-2xl sm:text-3xl uppercase tracking-tight text-[var(--text-primary)] mt-2">
                                {{ $rank1->reporter_name }}
                            </h2>
                            <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                DEPT. {{ $rank1->department }} • {{ $rank1->total_reports }} TEMUAN
                            </div>
                        </div>
                        <div class="font-heading font-black text-6xl stroke-num-gold select-none">
                            01
                        </div>
                    </div>

                    <div class="my-4 py-3 border-y border-[var(--border)] flex items-center justify-between">
                        <div>
                            <div class="font-mono text-[11px] text-[var(--text-secondary)] uppercase mb-1">KONTRIBUSI K3</div>
                            <div class="font-mono text-sm text-[var(--text-primary)] flex items-center space-x-2">
                                <span style="color: var(--unsafe-act)">●</span> {{ $rank1->unsafe_act_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--unsafe-cond)">●</span> {{ $rank1->unsafe_condition_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--suggestion)">●</span> {{ $rank1->suggestion_count }}
                                <span class="text-[var(--text-secondary)]">·</span>
                                <span style="color: var(--action)">●</span> {{ $rank1->action_count }}
                            </div>
                        </div>
                        <div class="text-right font-heading font-black text-5xl sm:text-6xl text-[var(--accent)] tabular-nums">
                            <span data-countup="{{ $rank1->total_points }}">{{ $rank1->total_points }}</span>
                            <span class="text-xs font-mono font-bold text-[var(--accent)] block tracking-widest">POIN</span>
                        </div>
                    </div>

                    <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                        KETERANGAN: <span style="color: var(--unsafe-act)">●</span> TINDAKAN &nbsp; <span style="color: var(--unsafe-cond)">●</span> KONDISI &nbsp; <span style="color: var(--suggestion)">●</span> SARAN &nbsp; <span style="color: var(--action)">●</span> ACTION
                    </div>
                </div>

                <!-- Rank 3 Podium (Right on desktop) -->
                @if($rank3)
                    <div class="md:col-span-4 order-3 box-industrial p-5 flex flex-col justify-between relative border-t-2 border-t-[#B45309]">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-[11px] uppercase text-[var(--text-secondary)]">PERINGKAT 03</span>
                                <h3 class="font-heading font-bold text-xl sm:text-2xl uppercase tracking-tight text-[var(--text-primary)] mt-1">
                                    {{ $rank3->reporter_name }}
                                </h3>
                                <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5">
                                    DEPT. {{ $rank3->department }}
                                </div>
                            </div>
                            <div class="font-heading font-black text-4xl sm:text-5xl stroke-num select-none">
                                03
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-[var(--border)] flex items-end justify-between">
                            <div>
                                <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase mb-1">RINCIAN KATEGORI</div>
                                <div class="font-mono text-xs text-[var(--text-primary)] flex items-center space-x-1.5">
                                    <span style="color: var(--unsafe-act)">●</span> {{ $rank3->unsafe_act_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--unsafe-cond)">●</span> {{ $rank3->unsafe_condition_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--suggestion)">●</span> {{ $rank3->suggestion_count }}
                                    <span class="text-[var(--text-secondary)]">·</span>
                                    <span style="color: var(--action)">●</span> {{ $rank3->action_count }}
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

    <!-- 4. Klasemen Rapat (Peringkat 4 ke Bawah dengan Bar Relatif) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between font-mono text-xs uppercase text-[var(--text-secondary)]">
            <span>DAFTAR KLASEMEN (PERINGKAT 4+)</span>
            <span>TOTAL {{ count($leaderboard) }} PESERTA</span>
        </div>

        <div class="box-industrial divide-y divide-[var(--border)] overflow-hidden">
            
            <!-- Table Header -->
            <div class="hidden sm:grid sm:grid-cols-12 px-4 py-2.5 bg-[var(--surface)] text-[11px] font-mono uppercase tracking-wider text-[var(--text-secondary)] border-b border-[var(--border)]">
                <div class="col-span-1">RANK</div>
                <div class="col-span-4">KARYAWAN / DEPARTEMEN</div>
                <div class="col-span-4 text-center">RINCIAN (TINDAKAN · KONDISI · SARAN · ACTION)</div>
                <div class="col-span-1 text-center">LAPORAN</div>
                <div class="col-span-2 text-right">TOTAL POIN</div>
            </div>

            <!-- Rows (Ranks 4 and below) -->
            @forelse($ranksRest as $row)
                <div class="relative group hover:bg-[var(--surface-hover)] transition-colors duration-150">
                    <!-- Relative percentage bar behind/under row -->
                    <div class="absolute inset-y-0 left-0 bg-[var(--border)] opacity-20 pointer-events-none transition-all"
                         style="width: {{ max(1, $row->pct_of_first) }}%;"></div>

                    <div class="relative z-10 grid grid-cols-1 sm:grid-cols-12 px-4 py-3 items-center gap-2 sm:gap-0">
                        
                        <!-- Rank Number & Trend -->
                        <div class="sm:col-span-1 font-mono text-xs flex items-center space-x-1.5">
                            <span class="font-bold text-[var(--text-secondary)] w-5 text-right">{{ sprintf('%02d', $row->rank) }}</span>
                            @if($row->trend === 'up')
                                <span class="text-[#2ECC71] text-[10px]" title="Naik dari bulan lalu">▲</span>
                            @elseif($row->trend === 'down')
                                <span class="text-[#FF5A36] text-[10px]" title="Turun dari bulan lalu">▼</span>
                            @else
                                <span class="text-[var(--text-secondary)] text-[10px]">—</span>
                            @endif
                        </div>

                        <!-- Name & Department -->
                        <div class="sm:col-span-4">
                            <div class="font-semibold text-sm text-[var(--text-primary)]">
                                {{ $row->reporter_name }}
                            </div>
                            <div class="font-mono text-[11px] text-[var(--text-secondary)] uppercase">
                                {{ $row->department }}
                            </div>
                        </div>

                        <!-- Dotted Category Breakdown -->
                        <div class="sm:col-span-4 font-mono text-xs text-center text-[var(--text-secondary)]">
                            <span style="color: var(--unsafe-act)">●</span> <span class="text-[var(--text-primary)]">{{ $row->unsafe_act_count }}</span>
                            <span class="mx-1 text-[var(--text-secondary)]">·</span>
                            <span style="color: var(--unsafe-cond)">●</span> <span class="text-[var(--text-primary)]">{{ $row->unsafe_condition_count }}</span>
                            <span class="mx-1 text-[var(--text-secondary)]">·</span>
                            <span style="color: var(--suggestion)">●</span> <span class="text-[var(--text-primary)]">{{ $row->suggestion_count }}</span>
                            <span class="mx-1 text-[var(--text-secondary)]">·</span>
                            <span style="color: var(--action)">●</span> <span class="text-[#2ECC71] font-semibold">{{ $row->action_count }}</span>
                        </div>

                        <!-- Total Reports -->
                        <div class="sm:col-span-1 font-mono text-xs text-center text-[var(--text-secondary)]">
                            {{ $row->total_reports }}
                        </div>

                        <!-- Total Points (Bright & Bold) -->
                        <div class="sm:col-span-2 text-right">
                            <span class="font-heading font-bold text-xl sm:text-2xl text-[var(--text-primary)] tabular-nums">
                                {{ $row->total_points }}
                            </span>
                            <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase ml-1">POIN</span>
                        </div>

                    </div>
                </div>
            @empty
                @if(count($leaderboard) <= 3)
                    <div class="p-6 text-center font-mono text-xs text-[var(--text-secondary)] uppercase">
                        Seluruh peserta berada pada podium 3 besar di atas.
                    </div>
                @endif
            @endforelse

        </div>
    </div>

    <!-- 5. Galeri Temuan Foto (Masonry / Crisp Card Grid) -->
    <div id="gallery-section" class="space-y-4 pt-4 border-t border-[var(--border)]">
        <div class="flex items-center justify-between font-mono text-xs uppercase text-[var(--text-secondary)]">
            <span class="flex items-center space-x-2">
                <i class="fa-solid fa-camera text-[var(--accent)]"></i>
                <span class="text-[var(--text-primary)] font-bold">GALERI BUKTI TEMUAN K3</span>
            </span>
            <span>{{ count($evidenceReports) }} DOKUMENTASI</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($evidenceReports as $ev)
                <div class="box-industrial overflow-hidden flex flex-col justify-between cursor-pointer group"
                     @click="activePhoto = '{{ $ev->direct_photo_url }}'; 
                             activeCaption = '{{ addslashes($ev->description) }}'; 
                             activeReporter = '{{ addslashes($ev->reporter_name) }}'; 
                             activeDept = '{{ addslashes($ev->department) }}';
                             activeDate = '{{ $ev->reported_at->format('d/m/Y H:i') }}';
                             activePoints = {{ $ev->points }};
                             photoModal = true;">
                    
                    <!-- Photo viewport with category chip in corner -->
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
                            @if($ev->has_unsafe_act)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--unsafe-act);">TINDAKAN</span>
                            @endif
                            @if($ev->has_unsafe_condition)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--unsafe-cond);">KONDISI</span>
                            @endif
                            @if($ev->has_suggestion)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--suggestion);">SARAN</span>
                            @endif
                            @if($ev->has_action)
                                <span class="px-1.5 py-0.5 text-black font-bold" style="background-color: var(--action);">ACTION</span>
                            @endif
                        </div>
                    </div>

                    <!-- Details in IBM Plex Mono & Body -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between space-y-2">
                        <div>
                            <div class="flex items-center justify-between font-mono text-xs">
                                <span class="font-bold text-[var(--text-primary)]">{{ $ev->reporter_name }}</span>
                                <span class="text-[var(--text-secondary)] uppercase">{{ $ev->department }}</span>
                            </div>

                            @if($ev->location)
                                <div class="font-mono text-[11px] text-[var(--text-secondary)] mt-0.5">
                                    LOKASI: <span class="text-[var(--text-primary)]">{{ $ev->location }}</span>
                                </div>
                            @endif

                            <p class="text-xs text-[var(--text-secondary)] mt-1.5 line-clamp-2 leading-relaxed">
                                {{ $ev->description ?: 'Tidak ada keterangan tambahan.' }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-[var(--border)] flex items-center justify-between font-mono text-[10px] text-[var(--text-secondary)] uppercase">
                            <span>{{ $ev->reported_at->format('d/m/Y H:i') }} WIB</span>
                            <span class="text-[var(--text-primary)] group-hover:text-[var(--accent)] font-semibold">DETAIL ↗</span>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-3 box-industrial p-8 text-center font-mono text-xs text-[var(--text-secondary)] uppercase">
                    Tidak ada arsip foto temuan untuk periode ini.
                </div>
            @endforelse
        </div>
    </div>

    <!-- 6. Statistik Departemen (Horizontal Bar Minimalis) -->
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
                            <span class="text-[var(--text-secondary)] font-normal text-[11px]">({{ $ds->total_reports }} TEMUAN)</span>
                        </span>
                        <span class="font-heading font-bold text-base text-[var(--accent)] tabular-nums">
                            {{ $ds->total_points }} POIN <span class="font-mono text-xs text-[var(--text-secondary)]">({{ $pct }}%)</span>
                        </span>
                    </div>
                    <!-- Minimalist bar without extra noise -->
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
