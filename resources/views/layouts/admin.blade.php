<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Control Panel') - MarketTrust</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="glass-canvas-bg text-slate-900 font-sans min-h-screen antialiased selection:bg-indigo-500 selection:text-white relative flex flex-col md:flex-row overflow-x-hidden">

    <!-- Ambient Background Glow Spheres -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-indigo-400/20 blur-3xl"></div>
        <div class="absolute top-1/4 -right-32 w-96 h-96 rounded-full bg-purple-400/15 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 w-[36rem] h-[36rem] rounded-full bg-sky-300/15 blur-3xl"></div>
    </div>

    <!-- Mobile Top Navigation Header (md:hidden) -->
    <header class="md:hidden glass-nav sticky top-0 z-40 w-full px-4 h-16 flex items-center justify-between border-b border-white/60 bg-white/70 backdrop-blur-xl">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016A11.955 11.955 0 0112 2.944z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5v9M13.5 10h-2.5a1.5 1.5 0 000 3h2a1.5 1.5 0 010 3h-2.5"/>
                </svg>
            </div>
            <div>
                <span class="text-base font-bold text-slate-900 tracking-tight">Market<span class="text-indigo-600">Trust</span></span>
                <span class="block text-[10px] uppercase font-bold text-indigo-600 tracking-wider">Control Panel</span>
            </div>
        </div>

        <button id="admin-mobile-toggle" type="button" class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/60 transition border border-transparent hover:border-white/60" aria-label="Toggle Navigation">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </header>

    <!-- Desktop Sidebar (Docked Flush with Top Navbar) -->
    <aside id="admin-sidebar" class="hidden md:flex flex-col w-64 lg:w-72 h-screen sticky top-0 left-0 z-30 shrink-0 border-r border-white/60 bg-white/70 backdrop-blur-xl transition-[margin,opacity] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] justify-between overflow-hidden will-change-[margin,opacity] transform-gpu">
        <!-- Top Section: Brand Header, Search, Navigation -->
        <div class="flex flex-col min-h-0 flex-1 w-64 lg:w-72 shrink-0">
            <!-- Sidebar Brand Header (h-16 matches Top Navbar height perfectly) -->
            <div class="h-16 px-4 border-b border-white/60 flex items-center justify-between gap-2 shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 group min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016A11.955 11.955 0 0112 2.944z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8.5v9M13.5 10h-2.5a1.5 1.5 0 000 3h2a1.5 1.5 0 010 3h-2.5"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-base font-bold tracking-tight text-slate-900 truncate">Market<span class="text-indigo-600">Trust</span></span>
                        </div>
                        <span class="inline-block glass-badge glass-badge-primary text-[9px] py-0.2 px-1.5 font-bold">
                            Control Panel
                        </span>
                    </div>
                </a>

                <button id="admin-sidebar-collapse" type="button" class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-white/90 border border-slate-200/60 hover:border-slate-300 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] shadow-2xs shrink-0 active:scale-95" title="Tutup Sidebar (Ctrl+B)" aria-label="Tutup Sidebar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v18M15 9l-3 3 3 3"/>
                    </svg>
                </button>
            </div>

            <!-- Search / Quick Filter Input (Workly Style) -->
            <div class="relative my-3 px-3 shrink-0">
                <span class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input id="admin-menu-search" type="text" placeholder="Search..." class="w-full pl-9 pr-12 py-2 text-xs rounded-xl bg-white/70 hover:bg-white focus:bg-white border border-slate-200/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 placeholder-slate-400 text-slate-800 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] outline-hidden">
                <span class="absolute inset-y-0 right-0 pr-5.5 flex items-center pointer-events-none">
                    <kbd class="px-1.5 py-0.5 text-[9px] font-semibold text-slate-400 bg-white border border-slate-200/80 rounded shadow-2xs">⌘K</kbd>
                </span>
            </div>

            <!-- Navigation Links List (Scrollable) -->
            <div id="admin-menu-list" class="flex-1 overflow-y-auto space-y-4 px-3 pr-2">
                <!-- Group: Main -->
                <div>
                    <span class="block px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Pusat Kontrol
                    </span>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}" data-label="overview analitik pusat kontrol dashboard" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.dashboard') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span class="truncate">Overview Analitik</span>
                            </div>
                            @if (request()->routeIs('admin.dashboard'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>
                    </nav>
                </div>

                <!-- Group: Inventaris & Transaksi -->
                <div>
                    <span class="block px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Inventaris & Finansial
                    </span>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.catalog.index') }}" data-label="katalog digital produk varian kategori inventaris" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.catalog.*') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.catalog.*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 00-2-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="truncate">Katalog Digital</span>
                            </div>
                            @if (request()->routeIs('admin.catalog.*'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>

                        <a href="{{ route('admin.transactions.index') }}" data-label="transaksi & escrow rekber pembayaran keuangan" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.transactions.*') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.transactions.*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="truncate">Transaksi & Escrow</span>
                            </div>
                            @if (request()->routeIs('admin.transactions.*'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>

                        <a href="{{ route('admin.disputes.index') }}" data-label="sengketa & komplain dispute resolusi masalah" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.disputes.*') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.disputes.*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                </svg>
                                <span class="truncate">Sengketa & Komplain</span>
                            </div>
                            @if (request()->routeIs('admin.disputes.*'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>
                    </nav>
                </div>

                <!-- Group: Sistem & Keamanan -->
                <div>
                    <span class="block px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                        Sistem & Keamanan
                    </span>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.users.index') }}" data-label="pengguna & akun user member hak akses" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.users.*') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span class="truncate">Pengguna & Akun</span>
                            </div>
                            @if (request()->routeIs('admin.users.*'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>

                        <a href="{{ route('admin.audit-logs.index') }}" data-label="audit log riwayat aktivitas keamanan sistem" class="sidebar-menu-item relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] hover:translate-x-1 group {{ request()->routeIs('admin.audit-logs.*') ? 'text-indigo-700 bg-white/95 border border-indigo-200/80 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200 {{ request()->routeIs('admin.audit-logs.*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <span class="truncate">Audit Log</span>
                            </div>
                            @if (request()->routeIs('admin.audit-logs.*'))
                                <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-indigo-500/25 via-indigo-400/10 to-transparent pointer-events-none"></div>
                                <div class="w-1.5 h-5 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50 shrink-0"></div>
                            @endif
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Engine Status Card & User Profile Card (Workly Style) -->
        <div class="p-3 border-t border-slate-200/60 space-y-3 shrink-0 w-64 lg:w-72">
            <!-- System Action / Status Card (Boost with AI Style) -->
            <div class="rounded-2xl p-3 bg-gradient-to-br from-white/90 to-indigo-50/40 border border-white/90 shadow-2xs space-y-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-indigo-500/15 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900 tracking-tight">Escrow Engine Active</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Sistem transaksi rekber digital aktif & terpantau otomatis 24/7.
                </p>
                <a href="{{ route('home') }}" class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-xs font-bold shadow-sm shadow-indigo-500/20 transition">
                    <span>Buka Platform Publik</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            @auth
                <!-- Admin User Profile Card (Damir S. Style) -->
                <div class="rounded-2xl p-2.5 bg-white/80 border border-white/90 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2.5 min-w-0 flex-1 group" title="Lihat Profil Saya">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}" class="w-9 h-9 rounded-full object-cover shrink-0 ring-1 ring-slate-200">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0">
                                    {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="block text-xs font-bold text-slate-900 truncate group-hover:text-indigo-600 transition">
                                        {{ '@' . auth()->user()->username }}
                                    </span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-indigo-100 text-indigo-700 shrink-0">
                                        ADMIN
                                    </span>
                                </div>
                                <span class="block text-[11px] text-slate-500 truncate mt-0.5">{{ auth()->user()->email }}</span>
                            </div>
                        </a>

                        <a href="{{ route('profile.show') }}" class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition shrink-0" title="Pengaturan Profil">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Direct Clean Logout Form & Button -->
                    <form action="{{ route('logout') }}" method="POST" class="pt-2 border-t border-slate-200/70">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 text-xs font-bold transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Mobile Off-Canvas Sidebar Drawer (md:hidden) -->
    <div id="admin-mobile-drawer" class="md:hidden fixed inset-0 z-50 pointer-events-none transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <!-- Backdrop -->
        <div id="admin-mobile-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"></div>

        <!-- Slide-out Drawer Panel (Floating Rounded Card) -->
        <div id="admin-mobile-panel" class="fixed inset-y-3 left-3 w-72 rounded-3xl bg-white/95 backdrop-blur-xl shadow-2xl border border-white/80 flex flex-col justify-between p-3.5 z-50 transform -translate-x-full transition-transform duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] overflow-hidden will-change-transform">
            <!-- Top Section -->
            <div class="flex flex-col min-h-0 flex-1">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold">
                            MT
                        </div>
                        <div>
                            <span class="text-sm font-bold text-slate-900">Control Panel</span>
                            <span class="block text-[10px] text-indigo-600 font-semibold">MarketTrust</span>
                        </div>
                    </div>
                    <button id="admin-mobile-close" type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition" aria-label="Tutup Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Mobile Search Input -->
                <div class="relative my-3 shrink-0">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input id="admin-mobile-menu-search" type="text" placeholder="Search..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-white border border-slate-200/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 placeholder-slate-400 text-slate-800 transition outline-hidden">
                </div>

                <!-- Mobile Navigation List -->
                <div id="admin-mobile-menu-list" class="flex-1 overflow-y-auto space-y-3 pr-1 py-1">
                    <div>
                        <span class="block px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pusat Kontrol</span>
                        <a href="{{ route('admin.dashboard') }}" data-label="overview analitik" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Overview Analitik</a>
                    </div>
                    <div>
                        <span class="block px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Inventaris & Finansial</span>
                        <div class="space-y-1">
                            <a href="{{ route('admin.catalog.index') }}" data-label="katalog digital" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.catalog.*') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Katalog Digital</a>
                            <a href="{{ route('admin.transactions.index') }}" data-label="transaksi & escrow" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.transactions.*') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Transaksi & Escrow</a>
                            <a href="{{ route('admin.disputes.index') }}" data-label="sengketa & komplain" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.disputes.*') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Sengketa & Komplain</a>
                        </div>
                    </div>
                    <div>
                        <span class="block px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sistem & Keamanan</span>
                        <div class="space-y-1">
                            <a href="{{ route('admin.users.index') }}" data-label="pengguna & akun" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Pengguna & Akun</a>
                            <a href="{{ route('admin.audit-logs.index') }}" data-label="audit log" class="sidebar-menu-item block px-3 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Audit Log</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Mobile Profile -->
            <div class="pt-3 border-t border-slate-200/80 space-y-2 shrink-0">
                @auth
                    <div class="flex items-center justify-between text-xs text-slate-600">
                        <span>Login: <strong class="text-slate-900">{{ '@' . auth()->user()->username }}</strong></span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-indigo-100 text-indigo-700">ADMIN</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-1.5 py-2 text-center text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-xl transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Viewport Area -->
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        <!-- Top Status Bar for Desktop (Sticky at Top) -->
        <header class="hidden md:flex sticky top-0 z-20 h-16 border-b border-white/60 bg-white/70 backdrop-blur-xl px-6 lg:px-8 items-center justify-between">
            <div class="flex items-center gap-3">
                <!-- Desktop Sidebar Toggle Button (Hidden when sidebar is open, appears smoothly when collapsed) -->
                <div id="admin-toggle-container" class="transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] overflow-hidden flex items-center w-0 max-w-0 opacity-0 -translate-x-3 pointer-events-none -mr-3 shrink-0 will-change-[width,max-width,opacity,transform,margin]">
                    <button id="admin-desktop-toggle" type="button" class="group p-2 rounded-xl text-slate-600 hover:text-indigo-600 hover:bg-white/80 border border-slate-200/60 hover:border-indigo-200 transition shadow-2xs focus:outline-hidden shrink-0 active:scale-95" title="Buka Sidebar (Ctrl+B)" aria-label="Buka Sidebar">
                        <svg class="w-5 h-5 text-slate-600 group-hover:text-indigo-600 transition-transform duration-200 group-hover:scale-105" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v18"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3-3 3"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>System Health: Operational</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="glass-badge glass-badge-neutral text-xs">
                    {{ now()->translatedFormat('l, d F Y') }}
                </span>
                <a href="{{ route('profile.show') }}" class="p-2 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-white/80 border border-slate-200/60 hover:border-indigo-200 transition shadow-2xs group flex items-center justify-center" title="Pengaturan Akun" aria-label="Pengaturan Akun">
                    <svg class="w-4 h-4 group-hover:rotate-45 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Flash Alerts -->
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            @if (session('success'))
                <div class="glass-alert glass-alert-success mb-4">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div class="text-sm font-medium">{{ session('success') }}</div>
                </div>
            @endif

            @if (session('warning'))
                <div class="glass-alert glass-alert-warning mb-4">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm font-medium">{{ session('warning') }}</div>
                </div>
            @endif

            @if (session('info'))
                <div class="glass-alert glass-alert-info mb-4">
                    <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm font-medium">{{ session('info') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="glass-alert glass-alert-error mb-4">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <div class="font-semibold text-sm mb-1 text-rose-900">Periksa kesalahan input:</div>
                        <ul class="list-disc list-inside text-xs sm:text-sm space-y-0.5 text-rose-800">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Page Content -->
        <main class="flex-grow">
            @yield('content')
        </main>

        <!-- Compact Admin Footer -->
        <footer class="mt-auto border-t border-white/60 bg-white/40 py-4 px-6 text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>
                <strong>MarketTrust Control Panel</strong> &copy; 2026. Hak Cipta Terlindungi.
            </div>
        </footer>
    </div>

    <!-- Toggle Script for Sidebar & Mobile Drawer -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Mobile Drawer Elements & Toggle
        const mobileToggleBtn = document.getElementById('admin-mobile-toggle');
        const mobileCloseBtn = document.getElementById('admin-mobile-close');
        const mobileDrawer = document.getElementById('admin-mobile-drawer');
        const mobileBackdrop = document.getElementById('admin-mobile-backdrop');
        const mobilePanel = document.getElementById('admin-mobile-panel');

        function openMobileDrawer() {
            if (!mobileDrawer || !mobileBackdrop || !mobilePanel) return;
            mobileDrawer.classList.remove('pointer-events-none');
            mobileDrawer.classList.add('pointer-events-auto');
            mobileBackdrop.classList.remove('opacity-0');
            mobileBackdrop.classList.add('opacity-100');
            mobilePanel.classList.remove('-translate-x-full');
            mobilePanel.classList.add('translate-x-0');
        }

        function closeMobileDrawer() {
            if (!mobileDrawer || !mobileBackdrop || !mobilePanel) return;
            mobileDrawer.classList.remove('pointer-events-auto');
            mobileDrawer.classList.add('pointer-events-none');
            mobileBackdrop.classList.remove('opacity-100');
            mobileBackdrop.classList.add('opacity-0');
            mobilePanel.classList.remove('translate-x-0');
            mobilePanel.classList.add('-translate-x-full');
        }

        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', openMobileDrawer);
        }
        if (mobileCloseBtn) {
            mobileCloseBtn.addEventListener('click', closeMobileDrawer);
        }
        if (mobileBackdrop) {
            mobileBackdrop.addEventListener('click', closeMobileDrawer);
        }

        // 2. Desktop Sidebar Elements & Toggle
        const desktopToggleBtn = document.getElementById('admin-desktop-toggle');
        const toggleContainer = document.getElementById('admin-toggle-container');
        const sidebarCollapseBtn = document.getElementById('admin-sidebar-collapse');
        const desktopSidebar = document.getElementById('admin-sidebar');
        const STORAGE_KEY = 'markettrust_admin_sidebar_collapsed';

        const TOGGLE_HIDDEN_CLASSES = ['w-0', 'max-w-0', 'opacity-0', '-translate-x-3', 'pointer-events-none', '-mr-3'];
        const TOGGLE_VISIBLE_CLASSES = ['w-9', 'max-w-[50px]', 'opacity-100', 'translate-x-0', 'pointer-events-auto', 'mr-0'];

        function setDesktopSidebarCollapsed(isCollapsed, immediate = false) {
            if (!desktopSidebar) return;

            if (immediate) {
                desktopSidebar.classList.add('transition-none');
                if (toggleContainer) toggleContainer.classList.add('transition-none');
            }

            if (isCollapsed) {
                // Sembunyikan sidebar ke kiri dengan transisi geser super smooth
                desktopSidebar.classList.add('-ml-64', 'lg:-ml-72', 'opacity-0', 'pointer-events-none');
                localStorage.setItem(STORAGE_KEY, 'true');

                // Munculkan tombol di navbar
                if (toggleContainer) {
                    toggleContainer.classList.remove(...TOGGLE_HIDDEN_CLASSES);
                    toggleContainer.classList.add(...TOGGLE_VISIBLE_CLASSES);
                }
            } else {
                // Munculkan sidebar kembali
                desktopSidebar.classList.remove('-ml-64', 'lg:-ml-72', 'opacity-0', 'pointer-events-none');
                localStorage.setItem(STORAGE_KEY, 'false');

                // Hilangkan tombol di navbar ketika sidebar muncul
                if (toggleContainer) {
                    toggleContainer.classList.remove(...TOGGLE_VISIBLE_CLASSES);
                    toggleContainer.classList.add(...TOGGLE_HIDDEN_CLASSES);
                }
            }

            if (immediate) {
                void desktopSidebar.offsetHeight;
                desktopSidebar.classList.remove('transition-none');
                if (toggleContainer) {
                    void toggleContainer.offsetHeight;
                    toggleContainer.classList.remove('transition-none');
                }
            }
        }

        // Restore saved desktop state on page load
        const savedState = localStorage.getItem(STORAGE_KEY);
        if (savedState === 'true') {
            setDesktopSidebarCollapsed(true, true);
        } else {
            setDesktopSidebarCollapsed(false, true);
        }

        function toggleDesktopSidebar() {
            if (!desktopSidebar) return;
            const isCurrentlyCollapsed = desktopSidebar.classList.contains('-ml-64') || desktopSidebar.classList.contains('-ml-72');
            setDesktopSidebarCollapsed(!isCurrentlyCollapsed);
        }

        if (desktopToggleBtn) {
            desktopToggleBtn.addEventListener('click', function () {
                setDesktopSidebarCollapsed(false);
            });
        }
        if (sidebarCollapseBtn) {
            sidebarCollapseBtn.addEventListener('click', function () {
                setDesktopSidebarCollapsed(true);
            });
        }

        // 3. Client-Side Instant Menu Search / Filter
        function setupMenuFilter(inputId, listId) {
            const searchInput = document.getElementById(inputId);
            const menuList = document.getElementById(listId);
            if (!searchInput || !menuList) return;

            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const items = menuList.querySelectorAll('.sidebar-menu-item');
                items.forEach(function (item) {
                    const label = (item.getAttribute('data-label') || item.textContent).toLowerCase();
                    if (!query || label.includes(query)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        setupMenuFilter('admin-menu-search', 'admin-menu-list');
        setupMenuFilter('admin-mobile-menu-search', 'admin-mobile-menu-list');

        // 4. Keyboard Shortcuts: Cmd+B / Ctrl+B to toggle sidebar, Cmd+K / Ctrl+K to search, Esc to close
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
                e.preventDefault();
                toggleDesktopSidebar();
            } else if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const searchEl = document.getElementById('admin-menu-search');
                if (searchEl && !desktopSidebar?.classList.contains('pointer-events-none')) {
                    searchEl.focus();
                } else {
                    const mobSearch = document.getElementById('admin-mobile-menu-search');
                    if (mobSearch) mobSearch.focus();
                }
            } else if (e.key === 'Escape') {
                closeMobileDrawer();
            }
        });
    });
    </script>
</body>
</html>
