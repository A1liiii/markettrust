<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MarketTrust') - Marketplace & Forum Digital Aman</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="glass-canvas-bg text-slate-900 font-sans min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white relative">
    <!-- Ambient Glassmorphism Glow Spheres -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-indigo-400/20 blur-3xl"></div>
        <div class="absolute top-1/4 -right-32 w-96 h-96 rounded-full bg-purple-400/15 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 w-[36rem] h-[36rem] rounded-full bg-sky-300/15 blur-3xl"></div>
    </div>

    <!-- Glass Navbar -->
    <header class="glass-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Brand Logo -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-6 h-6 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016A11.955 11.955 0 0112 2.944z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5v9M13.5 10h-2.5a1.5 1.5 0 000 3h2a1.5 1.5 0 010 3h-2.5"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold tracking-tight text-slate-900">Market<span class="text-indigo-600">Trust</span></span>
                            @if (auth()->check() && auth()->user()->isAdmin())
                                <span class="hidden sm:inline-block glass-badge glass-badge-primary">Panel Admin</span>
                            @else
                                <span class="hidden sm:inline-block glass-badge glass-badge-primary">Digital Escrow</span>
                            @endif
                        </div>
                    </a>
                </div>

                <!-- Nav Menu (Desktop) -->
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
                    @if (auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="glass-btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5 shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Panel Admin (Control Panel)
                        </a>
                    @else
                        <a href="{{ route('home') }}#marketplace" class="hover:text-indigo-600 transition {{ request()->routeIs('home') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}">Marketplace</a>
                        <a href="{{ route('forum.index') }}" class="hover:text-indigo-600 transition {{ request()->routeIs('forum.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}">Forum</a>
                        @auth
                            @if (auth()->user()->hasListings())
                                <a href="{{ route('seller.listings.index') }}" class="hover:text-indigo-600 transition flex items-center gap-1 {{ request()->routeIs('seller.listings.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    Listing
                                </a>
                            @endif
                        @endauth
                    @endif
                </nav>

                <!-- Auth Buttons & Mobile Toggle -->
                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <div class="flex items-center gap-2">
                            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 glass-card px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full border border-white/80 hover:bg-white/90 hover:scale-[1.02] transition" title="Lihat Profil Saya" data-cy="nav-user-profile">
                                @if (auth()->user()->avatar_url)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}" class="w-6 h-6 sm:w-7 sm:h-7 rounded-full object-cover">
                                @else
                                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-xs font-semibold shadow-xs">
                                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="text-xs sm:text-sm font-semibold text-slate-800 max-w-[100px] sm:max-w-[140px] truncate">@<span>{{ auth()->user()->username }}</span></span>
                                <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded-full {{ auth()->user()->account_status === 'ACTIVE' ? 'bg-emerald-500/15 text-emerald-700 border border-emerald-500/20' : 'bg-rose-500/15 text-rose-700 border border-rose-500/20' }}">
                                    {{ auth()->user()->account_status }}
                                </span>
                            </a>

                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="glass-btn-danger text-xs sm:text-sm py-1.5 px-2.5 sm:px-3" data-cy="btn-logout">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="glass-btn-secondary text-xs sm:text-sm py-1.5 sm:py-2 px-3 sm:px-4" data-cy="nav-login">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="glass-btn-primary text-xs sm:text-sm py-1.5 sm:py-2 px-3 sm:px-4" data-cy="nav-register">
                            Daftar
                        </a>
                    @endauth

                    <!-- Mobile Hamburger Toggle -->
                    <button id="mobile-menu-toggle" type="button" class="md:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/60 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] border border-transparent hover:border-white/60 active:scale-95" aria-label="Toggle navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div id="mobile-menu" class="md:hidden overflow-hidden max-h-0 opacity-0 -translate-y-2 pointer-events-none transition-all duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] px-4 space-y-1 border-t border-white/50 bg-white/70 backdrop-blur-xl will-change-[max-height,opacity,transform]">
            @auth
                <a href="{{ route('profile.show') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('profile.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}">Profil Saya (@<span>{{ auth()->user()->username }}</span>)</a>
            @endauth

            @if (auth()->check() && auth()->user()->isAdmin())
                <div class="pt-2 pb-1 px-3 text-[10px] uppercase font-bold tracking-wider text-slate-400">Pusat Kontrol</div>
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-indigo-600 bg-indigo-50/80 transition">
                    Buka Panel Admin (Control Panel)
                </a>
            @else
                <a href="{{ route('home') }}#marketplace" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 text-slate-700 transition">Marketplace</a>
                <a href="{{ route('forum.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('forum.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}">Forum</a>
                @auth
                    @if (auth()->user()->hasListings())
                        <a href="{{ route('seller.listings.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 text-slate-700 transition {{ request()->routeIs('seller.listings.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : '' }}">
                            Listing
                        </a>
                    @endif
                @endauth
            @endif
        </div>
    </header>

    <!-- Flash Alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('success'))
            <div class="glass-alert glass-alert-success mb-4" data-cy="alert-success">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="glass-alert glass-alert-warning mb-4" data-cy="alert-warning">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm font-medium">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('info'))
            <div class="glass-alert glass-alert-info mb-4" data-cy="alert-info">
                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-medium">{{ session('info') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="glass-alert glass-alert-error mb-4" data-cy="alert-error">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <div class="font-semibold text-sm mb-1 text-rose-900">Periksa kembali data Anda:</div>
                    <ul class="list-disc list-inside text-xs sm:text-sm space-y-0.5 text-rose-800">
                        @foreach ($errors->all() as $error)
                            <li data-cy="error-message">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Glass Footer -->
    <footer class="mt-auto border-t border-white/60 bg-white/50 backdrop-blur-md py-8 text-slate-600 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2 font-medium">
                <span class="font-bold text-slate-900">Market<span class="text-indigo-600">Trust</span></span> &copy; 2026. Platform Marketplace & Escrow Digital Terpercaya.
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-4 text-xs text-slate-500">
                <span class="glass-badge glass-badge-neutral">Transaksi Item Digital Aman</span>
                <span class="glass-badge glass-badge-neutral">Escrow Rekber Otomatis</span>
                <span class="glass-badge glass-badge-neutral">Resolusi Sengketa Terpercaya</span>
            </div>
        </div>
    </footer>

    @auth
        @include('conversations.partials.global-drawer')
    @endauth

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileToggle && mobileMenu) {
            mobileToggle.addEventListener('click', function () {
                const isOpen = mobileMenu.classList.contains('opacity-100');
                if (isOpen) {
                    mobileMenu.classList.remove('max-h-96', 'opacity-100', 'translate-y-0', 'pointer-events-auto', 'py-3');
                    mobileMenu.classList.add('max-h-0', 'opacity-0', '-translate-y-2', 'pointer-events-none');
                } else {
                    mobileMenu.classList.remove('max-h-0', 'opacity-0', '-translate-y-2', 'pointer-events-none');
                    mobileMenu.classList.add('max-h-96', 'opacity-100', 'translate-y-0', 'pointer-events-auto', 'py-3');
                }
            });
        }
    });
    </script>
</body>
</html>
