<!DOCTYPE html>
<html lang="id" class="overflow-x-clip w-full max-w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MarketTrust') - Marketplace & Forum Digital Aman</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="glass-canvas-bg text-slate-900 font-sans min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white relative overflow-x-clip w-full max-w-full">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2.8a11.95 11.95 0 01-8.62 3.04A12.02 12.02 0 003 9c0 6.2 4.1 11.4 9 12.8 4.9-1.4 9-6.6 9-12.8 0-1.04-.13-2.05-.38-3.02A11.95 11.95 0 0112 2.8z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.5v8.5M13.5 9.25h-2.5a1.5 1.5 0 000 3h2a1.5 1.5 0 010 3h-2.5"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold tracking-tight text-slate-900">Market<span class="text-indigo-600">Trust</span></span>
                            @if (auth()->check() && auth()->user()->isAdmin())
                                <span class="hidden sm:inline-block glass-badge glass-badge-primary">Panel Admin</span>
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
                        <a href="{{ route('marketplace.index') }}" class="hover:text-indigo-600 transition {{ request()->routeIs('marketplace.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}">Marketplace</a>
                        <a href="{{ route('forum.index') }}" class="hover:text-indigo-600 transition {{ request()->routeIs('forum.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}">Forum</a>
                        <a href="{{ route('market-statistics.index') }}" class="hover:text-indigo-600 transition {{ request()->routeIs('market-statistics.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}" data-cy="nav-informasi-pasar">Informasi Pasar</a>
                        @auth
                            <a href="{{ route('transactions.index') }}" class="hover:text-indigo-600 transition flex items-center gap-1.5 {{ request()->routeIs('transactions.*') ? 'text-indigo-600 font-semibold' : 'text-slate-600' }}" data-cy="nav-transactions">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>Transaksi</span>
                            </a>
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
                        @php
                            $navbarUnreadNotifs = auth()->user()->notifications()->whereNull('read_at')->count();
                        @endphp
                        <div class="flex items-center gap-2">
                            <!-- Notification Bell Icon (Desktop & Tablet) -->
                            <div class="relative" id="navbar-notif-wrapper">
                                <a href="{{ route('notifications.index') }}"
                                   id="nav-notif-toggle-btn"
                                   onclick="return toggleNavbarNotifPopup(event)"
                                   class="relative p-2 rounded-xl text-slate-600 hover:text-indigo-600 hover:bg-white/80 active:scale-95 transition-all duration-200 flex items-center justify-center cursor-pointer {{ request()->routeIs('notifications.*') ? 'text-indigo-600 bg-white/80 shadow-xs' : '' }}" 
                                   title="Pusat Notifikasi" 
                                   aria-label="Buka notifikasi"
                                   aria-haspopup="dialog"
                                   aria-expanded="false"
                                   data-cy="nav-notifications">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    @if ($navbarUnreadNotifs > 0)
                                        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold leading-tight text-white bg-rose-500 rounded-full flex items-center justify-center border-2 border-white shadow-xs" data-cy="nav-unread-badge">
                                            {{ $navbarUnreadNotifs > 99 ? '99+' : $navbarUnreadNotifs }}
                                        </span>
                                    @endif
                                </a>

                                @include('notifications.partials.navbar-popup')
                            </div>

                            <a href="{{ route('profile.show') }}" class="hidden sm:flex items-center gap-2 glass-card px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full border border-white/80 hover:bg-white/90 hover:scale-[1.02] transition" title="Lihat Profil Saya" data-cy="nav-user-profile">
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

                            <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline">
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
                <a href="{{ route('notifications.index') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('notifications.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}" data-cy="nav-notifications-mobile">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        Pusat Notifikasi
                    </span>
                    @if (isset($navbarUnreadNotifs) && $navbarUnreadNotifs > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold text-white bg-rose-500 rounded-full" data-cy="nav-mobile-unread-badge">
                            {{ $navbarUnreadNotifs }} baru
                        </span>
                    @endif
                </a>
            @endauth

            @if (auth()->check() && auth()->user()->isAdmin())
                <div class="pt-2 pb-1 px-3 text-[10px] uppercase font-bold tracking-wider text-slate-400">Pusat Kontrol</div>
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-indigo-600 bg-indigo-50/80 transition">
                    Buka Panel Admin (Control Panel)
                </a>
            @endif

            <a href="{{ route('marketplace.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('marketplace.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}">Marketplace</a>
            <a href="{{ route('forum.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('forum.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}">Forum</a>
            <a href="{{ route('market-statistics.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 transition {{ request()->routeIs('market-statistics.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : 'text-slate-700' }}" data-cy="nav-informasi-pasar-mobile">Informasi Pasar</a>
            @auth
                <a href="{{ route('transactions.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 text-slate-700 transition {{ request()->routeIs('transactions.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : '' }}" data-cy="nav-transactions-mobile">
                    Transaksi Saya
                </a>
                @if (auth()->user()->hasListings())
                    <a href="{{ route('seller.listings.index') }}" class="block px-3 py-2 rounded-xl text-sm font-medium hover:bg-white/80 hover:text-indigo-600 text-slate-700 transition {{ request()->routeIs('seller.listings.*') ? 'bg-indigo-50/80 text-indigo-600 font-semibold' : '' }}">
                        Listing
                    </a>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="pt-2 pb-1 border-t border-white/50">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-sm font-medium text-rose-600 hover:bg-rose-50 transition" data-cy="btn-logout-mobile">
                        Keluar
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <!-- Flash Alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('success'))
            <div class="glass-alert glass-alert-success mb-4" data-cy="alert-success">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div class="text-sm font-medium flex-1">{{ session('success') }}</div>
                <button type="button" onclick="dismissGlassAlert(this)" class="p-1 -mr-1 text-emerald-700/60 hover:text-emerald-900 hover:bg-emerald-100/50 rounded-lg transition-all active:scale-95 cursor-pointer" aria-label="Tutup pesan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div class="glass-alert glass-alert-warning mb-4" data-cy="alert-warning">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm font-medium flex-1">{{ session('warning') }}</div>
                <button type="button" onclick="dismissGlassAlert(this)" class="p-1 -mr-1 text-amber-700/60 hover:text-amber-900 hover:bg-amber-100/50 rounded-lg transition-all active:scale-95 cursor-pointer" aria-label="Tutup pesan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if (session('info'))
            <div class="glass-alert glass-alert-info mb-4" data-cy="alert-info">
                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-medium flex-1">{{ session('info') }}</div>
                <button type="button" onclick="dismissGlassAlert(this)" class="p-1 -mr-1 text-indigo-700/60 hover:text-indigo-900 hover:bg-indigo-100/50 rounded-lg transition-all active:scale-95 cursor-pointer" aria-label="Tutup pesan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
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
                <button type="button" onclick="dismissGlassAlert(this)" class="p-1 -mr-1 text-rose-700/60 hover:text-rose-900 hover:bg-rose-100/50 rounded-lg transition-all active:scale-95 cursor-pointer" aria-label="Tutup pesan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-full overflow-x-hidden">
        @yield('content')
    </main>

    <!-- Glass Footer -->
    @unless (View::hasSection('hide_footer'))
    <footer class="mt-auto border-t border-white/60 bg-white/50 backdrop-blur-md py-8 text-slate-600 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2 font-medium">
                <span class="font-bold text-slate-900">Market<span class="text-indigo-600">Trust</span></span> &copy; 2026. Platform Marketplace & Rekber Digital Terpercaya.
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-4 text-xs text-slate-500">
                <span class="glass-badge glass-badge-neutral">Transaksi Item Aman</span>
                <span class="glass-badge glass-badge-neutral">Rekber Otomatis</span>
                <span class="glass-badge glass-badge-neutral">Komplain</span>
            </div>
        </div>
    </footer>
    @endunless

    @auth
        @include('conversations.partials.global-drawer')
    @endauth

    <script>
    function dismissGlassAlert(btn) {
        const alert = btn.closest('.glass-alert');
        if (!alert) return;
        alert.classList.add('is-dismissing');
        setTimeout(() => alert.remove(), 240);
    }

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
    @stack('scripts')
</body>
</html>
