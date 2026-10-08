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
    activeDate: '',
    activePoints: 0,
    isSyncing: false,
    mobileTab: 'leaderboard'
}" x-init="if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Safety Warrior') — K3 Scoreboard & Award</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
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
                        <img src="{{ asset('images/logo.png') }}" alt="Safety Warrior Logo" class="w-8 h-8 object-contain">
                        <div class="flex items-baseline space-x-2">
                            <span class="font-heading font-extrabold text-xl tracking-wider uppercase text-[var(--text-primary)]">
                                SAFETY WARRIOR
                            </span>
                            <span class="hidden md:inline font-mono text-[11px] text-[var(--text-secondary)] uppercase tracking-wider">
                                / K3 SCOREBOARD
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

    <!-- Mobile Bottom Navigation Bar (Fixed on small screens) -->
    <div class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-[var(--bg)] border-t border-[var(--border)] px-4 py-2 flex items-center justify-around font-mono text-[11px] uppercase">
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
        <button @click="mobileTab = 'stats'; document.getElementById('stats-section')?.scrollIntoView({behavior: 'smooth'});"
                class="flex flex-col items-center py-1 text-[var(--text-secondary)] hover:text-[var(--text-primary)]">
            <i class="fa-solid fa-chart-column text-sm mb-0.5"></i>
            <span>STATISTIK</span>
        </button>
        <a href="{{ route('settings') }}" 
           class="flex flex-col items-center py-1 {{ request()->routeIs('settings') ? 'text-[var(--accent)] font-bold' : 'text-[var(--text-secondary)]' }}">
            <i class="fa-solid fa-database text-sm mb-0.5"></i>
            <span>SHEET</span>
        </a>
    </div>

    <!-- Image Lightbox Modal -->
    <div x-cloak x-show="photoModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/90" @keydown.escape.window="photoModal = false">
        <div class="relative max-w-3xl w-full bg-[var(--surface)] border border-[var(--border)] overflow-hidden flex flex-col max-h-[90vh]" style="border-radius: var(--radius);" @click.away="photoModal = false">
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

            <div class="p-4 border-t border-[var(--border)] text-xs text-[var(--text-primary)]">
                <div class="font-mono text-[10px] text-[var(--text-secondary)] uppercase tracking-wider mb-1" x-text="'TANGGAL: ' + activeDate"></div>
                <p x-text="activeCaption" class="leading-relaxed"></p>
            </div>
        </div>
    </div>

    <!-- Minimal Industrial Footer -->
    <footer class="border-t border-[var(--border)] py-4 text-center font-mono text-[11px] text-[var(--text-secondary)] uppercase">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center justify-center space-x-2">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-4 h-4 object-contain inline">
                <span>SAFETY WARRIOR — EVALUASI BULANAN K3 (PERIODE TGL 1 S.D. 25)</span>
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
</body>
</html>
