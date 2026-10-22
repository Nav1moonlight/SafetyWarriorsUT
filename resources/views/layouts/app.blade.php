<!DOCTYPE html>
<html lang="id" class="h-full" x-data="{ 
    theme: localStorage.getItem('sw_theme') || 'dark',
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('sw_theme', this.theme);
        if (this.theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    },
    photoModal: false,
    activePhoto: '',
    activeCaption: '',
    activeReporter: '',
    activeDept: '',
    activeNrp: '',
    activeDate: '',
    activePoints: 0,
    activeIndicator: '',
    activeCategory: 'EHS',
    employeeModal: false,
    activeEmployee: {
        name: '',
        nrp: '',
        department: '',
        points: 0,
        reports_count: 0,
        reports: []
    },
    isSyncing: false,
    mobileTab: 'leaderboard'
}" x-init="if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Impact Warrior') — K3 Scoreboard & Award</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    
    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#D49B00">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ImpactWarrior">
    
    <!-- Google Fonts: Barlow Condensed, IBM Plex Sans, IBM Plex Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['class', '[data-theme="dark"]'],
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['"Barlow Condensed"', 'sans-serif'],
                        body: ['"IBM Plex Sans"', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'monospace'],
                    },
                    borderRadius: {
                        DEFAULT: '4px',
                        'sm': '4px',
                        'md': '4px',
                        'lg': '6px',
                        'xl': '6px',
                        '2xl': '6px',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg: #000000;
            --surface: #0C0C0C;
            --surface-hover: #161616;
            --border: #232323;
            --text-primary: #F5F5F5;
            --text-secondary: #8A8A8A;
            --accent: #FFC400;
            --hazard-stripe: repeating-linear-gradient(45deg, #FFC400, #FFC400 8px, #000000 8px, #000000 16px);
            
            --unsafe-act: #FF5A36;
            --unsafe-cond: #FFB020;
            --suggestion: #4CC9F0;
            --action: #2ECC71;
            
            --radius: 4px;
        }

        [data-theme="light"] {
            --bg: #F4F1EA;
            --surface: #EAE6DC;
            --surface-hover: #DFD9CC;
            --border: #D4CEBF;
            --text-primary: #111111;
            --text-secondary: #666666;
            --accent: #D49B00;
            --hazard-stripe: repeating-linear-gradient(45deg, #D49B00, #D49B00 8px, #111111 8px, #111111 16px);
        }

        body {
            background-color: var(--bg);
            color: var(--text-primary);
            font-family: 'IBM Plex Sans', sans-serif;
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        .font-heading {
            font-family: 'Barlow Condensed', sans-serif;
        }

        .font-mono {
            font-family: 'IBM Plex Mono', monospace;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        .hazard-bar {
            background: var(--hazard-stripe);
            height: 4px;
            width: 100%;
        }

        /* 1px crisp borders, no shadow */
        .box-industrial {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }

        .box-industrial:hover {
            border-color: #333333;
        }

        [data-theme="light"] .box-industrial:hover {
            border-color: #BEB7A6;
        }

        /* Outline Stroke Big Numbers */
        .stroke-num {
            -webkit-text-stroke: 1.5px var(--text-secondary);
            color: transparent;
        }

        .stroke-num-gold {
            -webkit-text-stroke: 1.5px var(--accent);
            color: transparent;
        }

        [x-cloak] { display: none !important; }

        /* Minimal scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="min-h-full flex flex-col antialiased selection:bg-amber-400 selection:text-black">

    <!-- Top Industrial Header -->
    <header class="sticky top-0 z-40 bg-[var(--bg)] border-b border-[var(--border)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Impact Warrior Logo" class="w-8 h-8 object-contain">
                        <div class="flex items-baseline space-x-2">
                            <span class="font-heading font-extrabold text-xl tracking-wider uppercase text-[var(--text-primary)]">
                                IMPACT WARRIOR
                            </span>
                            <span class="hidden md:inline font-mono text-[11px] text-[var(--accent)] uppercase tracking-wider font-semibold">
                                / EHS & CSR SCOREBOARD
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Nav & Controls -->
                <div class="flex items-center space-x-2 sm:space-x-3 font-mono text-xs">
                    
                    <!-- Desktop Nav Links -->
                    <div class="hidden sm:flex items-center space-x-1 mr-2">
                        <a href="{{ route('dashboard') }}" 
                           class="px-3 py-1.5 uppercase font-medium transition {{ request()->routeIs('dashboard') ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]' }}">
                            KLASEMEN
                        </a>
                        <a href="{{ route('settings') }}" 
                           class="px-3 py-1.5 uppercase font-medium transition {{ request()->routeIs('settings') ? 'text-[var(--accent)] border-b-2 border-[var(--accent)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]' }}">
                            SUMBER DATA
                        </a>
                        @if(!empty($globalGoogleFormUrl))
                            <a href="{{ $globalGoogleFormUrl }}" target="_blank" rel="noopener noreferrer"
                               class="px-3 py-1.5 bg-[var(--accent)]/15 border border-[var(--accent)] text-[var(--accent)] hover:bg-[var(--accent)] hover:text-black uppercase font-bold transition flex items-center space-x-1"
                               style="border-radius: var(--radius);"
                               title="Buka Google Form Input Temuan K3 & CSR">
                                <i class="fa-solid fa-file-pen text-xs mr-1"></i>
                                <span>ISI FORM</span>
                            </a>
                        @endif
                    </div>

                    <!-- Theme Toggle Button -->
                    <button @click="toggleTheme()" 
                            class="px-2.5 py-1.5 border border-[var(--border)] hover:border-[var(--text-secondary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition"
                            style="border-radius: var(--radius);"
                            title="Ganti Tema (Dark / Light)">
                        <i class="fa-solid" :class="theme === 'dark' ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <!-- Outline Sync Button -->
                    <form action="{{ route('sync') }}" method="POST" @submit="isSyncing = true" class="inline">
                        @csrf
                        <button type="submit" :disabled="isSyncing" 
                                class="inline-flex items-center px-3 py-1.5 border border-[var(--border)] hover:border-[var(--accent)] hover:text-[var(--accent)] text-[var(--text-primary)] font-mono uppercase tracking-wider transition active:scale-95 disabled:opacity-50"
                                style="border-radius: var(--radius);">
                            <i class="fa-solid fa-arrows-rotate mr-1.5 text-[10px]" :class="isSyncing ? 'animate-spin text-[var(--accent)]' : ''"></i>
                            <span x-text="isSyncing ? 'SINKRONISASI...' : 'SINKRON'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- 4px Hazard Line (Yellow & Black diagonal stripe) -->
        <div class="hazard-bar"></div>
    </header>

    <!-- Flash Alert Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 w-full">
        @if(session('success'))
            <div class="p-3 bg-[var(--surface)] border border-[#2ECC71]/40 text-[#2ECC71] flex items-center justify-between text-xs font-mono" style="border-radius: var(--radius);" x-data="{ show: true }" x-show="show">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-[var(--surface)] border border-[#FF5A36]/40 text-[#FF5A36] flex items-center justify-between text-xs font-mono" style="border-radius: var(--radius);" x-data="{ show: true }" x-show="show">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 pb-20 sm:pb-8">
        @yield('content')
    </main>

    <!-- Floating Action Button (Quick Google Form Navigation) -->
    @if(!empty($globalGoogleFormUrl))
        <div class="fixed bottom-16 sm:bottom-6 right-4 sm:right-6 z-40">
            <a href="{{ $globalGoogleFormUrl }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center space-x-2 px-4 py-2.5 bg-[#D49B00] hover:bg-yellow-400 text-black font-mono font-bold text-xs uppercase tracking-wider shadow-2xl transition-all transform hover:scale-105 active:scale-95 border-2 border-black/30"
               style="border-radius: 9999px;"
               title="Buka Google Form Input Temuan K3 & CSR">
                <i class="fa-solid fa-file-pen text-sm"></i>
                <span class="inline">ISI LAPORAN K3</span>
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
        </div>
    @endif

    <!-- Mobile Bottom Navigation Bar (Fixed on small screens) -->
    <div class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-[var(--bg)] border-t border-[var(--border)] px-3 py-2 flex items-center justify-around font-mono text-[10px] uppercase">
        <a href="{{ route('dashboard') }}" 
           class="flex flex-col items-center py-1 {{ request()->routeIs('dashboard') ? 'text-[var(--accent)] font-bold' : 'text-[var(--text-secondary)]' }}">
            <i class="fa-solid fa-list-ol text-sm mb-0.5"></i>
            <span>KLASEMEN</span>
        </a>
        <button @click="mobileTab = 'gallery'; document.getElementById('gallery-section')?.scrollIntoView({behavior: 'smooth'});"
                class="flex flex-col items-center py-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
            <i class="fa-solid fa-camera text-sm mb-0.5"></i>
            <span>GALERI</span>
        </button>
        @if(!empty($globalGoogleFormUrl))
            <a href="{{ $globalGoogleFormUrl }}" target="_blank" rel="noopener noreferrer"
               class="flex flex-col items-center py-1 text-[var(--accent)] font-bold">
                <i class="fa-solid fa-file-pen text-sm mb-0.5 animate-pulse"></i>
                <span>FORM</span>
            </a>
        @endif
        <button @click="mobileTab = 'stats'; document.getElementById('stats-section')?.scrollIntoView({behavior: 'smooth'});"
                class="flex flex-col items-center py-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
            <i class="fa-solid fa-chart-column text-sm mb-0.5"></i>
            <span>STATISTIK</span>
        </button>
        <a href="{{ route('settings') }}" 
           class="flex flex-col items-center py-1 {{ request()->routeIs('settings') ? 'text-[var(--accent)] font-bold' : 'text-[var(--text-secondary)]' }}">
            <i class="fa-solid fa-gear text-sm mb-0.5"></i>
            <span>SETTING</span>
        </a>
    </div>

    <!-- Image Lightbox Modal (Higher z-index so it sits safely above employeeModal) -->
    <div x-cloak x-show="photoModal" class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-6 bg-black/95 backdrop-blur-sm" @keydown.escape.window="photoModal = false">
        <div class="relative max-w-3xl w-full bg-[var(--surface)] border border-[var(--border)] overflow-hidden flex flex-col max-h-[90vh] shadow-2xl" style="border-radius: var(--radius);" @click.away="photoModal = false">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-[var(--border)] font-mono text-xs">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 bg-[var(--accent)]"></span>
                    <span class="font-bold text-[var(--text-primary)]" x-text="activeReporter"></span>
                    <span class="text-[var(--text-secondary)]" x-text="'/ ' + activeDept"></span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-[var(--accent)] font-bold" x-text="'+' + activePoints + ' POIN'"></span>
                    <button @click="photoModal = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-black flex items-center justify-center overflow-hidden p-2 min-h-[250px]">
                <img :src="activePhoto" alt="Foto Temuan K3" class="max-h-[65vh] w-auto max-w-full object-contain">
            </div>

            <!-- Complete Detail Footer Below Photo -->
            <div class="p-4 border-t border-[var(--border)] text-xs text-[var(--text-primary)] space-y-3 bg-[var(--surface)]">
                <!-- Meta Grid (Nama, Departemen, Indikator, Poin, Tanggal) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-[11px] p-3 bg-[var(--bg)] border border-[var(--border)]" style="border-radius: var(--radius);">
                    <div>
                        <span class="text-[var(--text-secondary)] uppercase text-[9px] block">NAMA PELAPOR</span>
                        <div class="flex items-center space-x-1.5 mt-0.5">
                            <span class="font-bold text-[var(--text-primary)] text-xs truncate" x-text="activeReporter"></span>
                            <span x-show="activeNrp" class="px-1 py-0.2 bg-[var(--surface)] text-[var(--accent)] border border-[var(--border)] font-mono text-[9px] font-bold" x-text="'NRP: ' + activeNrp"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[var(--text-secondary)] uppercase text-[9px] block">DEPARTEMEN</span>
                        <span class="font-bold text-[var(--text-primary)] text-xs truncate block mt-0.5" x-text="activeDept"></span>
                    </div>
                    <div>
                        <span class="text-[var(--text-secondary)] uppercase text-[9px] block">INDIKATOR KEGIATAN</span>
                        <span class="font-bold text-[var(--accent)] text-xs truncate block mt-0.5" x-text="activeIndicator"></span>
                    </div>
                    <div>
                        <span class="text-[var(--text-secondary)] uppercase text-[9px] block">WAKTU PELAPORAN</span>
                        <span class="text-[var(--text-secondary)] text-xs block mt-0.5" x-text="activeDate + ' WITA'"></span>
                    </div>
                </div>

                <!-- Keterangan / Penjelasan Temuan -->
                <div>
                    <span class="font-mono text-[10px] text-[var(--text-secondary)] uppercase tracking-wider block mb-1">
                        KETERANGAN / PENJELASAN TEMUAN:
                    </span>
                    <p x-text="activeCaption || 'Tidak ada keterangan tambahan.'" class="leading-relaxed text-xs p-3 bg-[var(--bg)] border border-[var(--border)] whitespace-pre-wrap"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Employee Reports Detail Modal (When row in leaderboard is clicked) -->
    <div x-cloak x-show="employeeModal && !photoModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/80 backdrop-blur-sm"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="box-industrial w-full max-w-3xl max-h-[90vh] flex flex-col bg-[var(--surface)] border border-[var(--border)] shadow-2xl overflow-hidden" 
             @click.away="if (!photoModal) employeeModal = false">
            
            <!-- Modal Header -->
            <div class="p-4 border-b border-[var(--border)] bg-[var(--bg)] flex items-start justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-heading font-black text-2xl uppercase tracking-tight text-[var(--text-primary)]" x-text="activeEmployee.name"></span>
                        <span x-show="activeEmployee.nrp" class="px-2 py-0.5 bg-[var(--surface)] text-[var(--accent)] border border-[var(--accent)] font-mono text-xs font-bold" x-text="'NRP: ' + activeEmployee.nrp"></span>
                    </div>
                    <div class="font-mono text-xs text-[var(--text-secondary)] uppercase mt-0.5 flex items-center space-x-2">
                        <span>DEPARTEMEN <strong class="text-[var(--text-primary)]" x-text="activeEmployee.department"></strong></span>
                        <span>•</span>
                        <span class="text-[var(--accent)] font-bold" x-text="activeEmployee.points + ' TOTAL POIN'"></span>
                        <span>•</span>
                        <span x-text="activeEmployee.reports_count + ' LAPORAN TERVALIDASI'"></span>
                    </div>
                </div>

                <button @click="employeeModal = false" class="text-[var(--text-secondary)] hover:text-white px-2 py-1 text-sm border border-[var(--border)] bg-[var(--surface)] hover:bg-zinc-800 transition">
                    <i class="fa-solid fa-xmark mr-1"></i> TUTUP
                </button>
            </div>

            <!-- Modal Content: List of Reports with Evidence -->
            <div class="p-4 overflow-y-auto space-y-3 divide-y divide-[var(--border)]">
                <template x-if="!activeEmployee.reports || activeEmployee.reports.length === 0">
                    <div class="py-8 text-center font-mono text-xs text-[var(--text-secondary)] uppercase">
                        Tidak ada rincian laporan yang ditemukan.
                    </div>
                </template>

                <template x-for="(rpt, idx) in activeEmployee.reports" :key="rpt.id || idx">
                    <div class="pt-3 first:pt-0 flex flex-col md:flex-row gap-4">
                        <!-- Evidence photo preview if exists -->
                        <div class="w-full md:w-36 flex-shrink-0">
                            <template x-if="rpt.photo_url">
                                <div class="relative group cursor-pointer border border-[var(--border)] overflow-hidden bg-black h-24"
                                     @click="activePhoto = rpt.photo_url;
                                             activeCaption = rpt.description;
                                             activeReporter = activeEmployee.name;
                                             activeDept = activeEmployee.department;
                                             activeNrp = activeEmployee.nrp;
                                             activeDate = rpt.reported_at;
                                             activePoints = rpt.points;
                                             activeIndicator = rpt.activity_indicator;
                                             activeCategory = rpt.indicator_category;
                                             photoModal = true;">
                                    <img :src="rpt.photo_url" class="w-full h-full object-cover group-hover:scale-105 transition duration-200">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition text-[10px] font-mono font-bold text-white uppercase">
                                        <i class="fa-solid fa-magnifying-glass mr-1"></i> LIHAT
                                    </div>
                                </div>
                            </template>
                            <template x-if="!rpt.photo_url">
                                <div class="h-24 border border-dashed border-[var(--border)] bg-[var(--bg)] flex flex-col items-center justify-center text-[var(--text-secondary)] text-[10px] font-mono">
                                    <i class="fa-solid fa-image-slash text-base mb-1"></i>
                                    <span>TANPA FOTO</span>
                                </div>
                            </template>
                        </div>

                        <!-- Report details -->
                        <div class="flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center space-x-1.5 font-mono text-[11px]">
                                    <span class="px-1.5 py-0.2 bg-[var(--bg)] text-[var(--accent)] border border-[var(--border)] font-bold uppercase" x-text="rpt.indicator_category"></span>
                                    <span class="font-bold text-[var(--text-primary)]" x-text="rpt.activity_indicator"></span>
                                </div>
                                <div class="flex items-center space-x-2 font-mono text-xs">
                                    <span class="px-2 py-0.5 bg-[var(--accent)] text-black font-bold" x-text="'+' + rpt.points + ' POIN'"></span>
                                    <span class="text-[var(--text-secondary)] text-[10px]" x-text="rpt.reported_at + ' WITA'"></span>
                                </div>
                            </div>

                            <p class="text-xs text-[var(--text-primary)] leading-relaxed bg-[var(--bg)] p-2.5 border border-[var(--border)] font-body whitespace-pre-wrap" x-text="rpt.description || 'Tidak ada keterangan tambahan.'"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 border-t border-[var(--border)] bg-[var(--bg)] flex items-center justify-between font-mono text-[11px] text-[var(--text-secondary)]">
                <span>IMPACT WARRIOR AUDIT TRAIL</span>
                <button @click="employeeModal = false" class="px-3 py-1 border border-[var(--border)] hover:bg-[var(--surface)] text-[var(--text-primary)] font-mono uppercase text-xs">
                    Tutup Jendela
                </button>
            </div>
        </div>
    </div>

    <!-- Minimal Industrial Footer -->
    <footer class="border-t border-[var(--border)] py-4 text-center font-mono text-[11px] text-[var(--text-secondary)] uppercase">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center justify-center space-x-2">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-4 h-4 object-contain inline">
                <span>IMPACT WARRIOR — EVALUASI BULANAN EHS & CSR (PERIODE TGL 1 S.D. 25)</span>
            </div>
            <div class="text-[10px]">INTEGRASI GOOGLE FORMS & SHEETS</div>
        </div>
    </footer>

    <!-- Count-Up Animation Script (600ms ease-out) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) return;

            const counters = document.querySelectorAll('[data-countup]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-countup'), 10) || 0;
                const duration = 600; // ms
                const startTime = performance.now();

                const updateCount = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    const currentVal = Math.floor(easeOut * target);

                    counter.innerText = currentVal.toLocaleString('id-ID');

                    if (progress < 1) {
                        requestAnimationFrame(updateCount);
                    } else {
                        counter.innerText = target.toLocaleString('id-ID');
                    }
                };
                requestAnimationFrame(updateCount);
            });
        });
    </script>
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('PWA ServiceWorker registered with scope:', reg.scope))
                    .catch((err) => console.log('PWA ServiceWorker registration failed:', err));
            });
        }
    </script>
</body>
</html>
