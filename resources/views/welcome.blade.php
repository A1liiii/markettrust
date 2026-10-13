@extends('layouts.app')

@section('title', 'MarketTrust - Marketplace & Escrow Digital')

@section('content')
<div class="py-10 sm:py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 mb-6">
                <span class="glass-badge glass-badge-primary px-3.5 py-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    Riset PBL College: Platform Transaksi Digital Terpercaya
                </span>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Marketplace & Forum Digital dengan <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Escrow Aman</span>
            </h1>

            <p class="mt-5 sm:mt-6 text-sm sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Jual-beli item game, akun, dan produk digital tanpa rasa cemas. Dilengkapi kesepakatan sheet transparan, pembayaran gateway sandbox, dan pencatatan payout manual terverifikasi.
            </p>

            <!-- CTA Actions -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <div class="glass-card glass-card-interactive p-5 w-full sm:w-auto text-left flex items-center gap-4 border border-white/80 shadow-md">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}" class="w-12 h-12 rounded-full object-cover shadow-sm ring-2 ring-white/80">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-indigo-500/20">
                                {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900 text-base">@<span>{{ auth()->user()->username }}</span></h3>
                                <span class="glass-badge {{ auth()->user()->account_status === 'ACTIVE' ? 'glass-badge-success' : 'glass-badge-danger' }} text-[11px] py-0.5 px-2">
                                    {{ auth()->user()->account_status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Tipe Akun: <span class="font-semibold text-slate-700">{{ auth()->user()->account_type }}</span> &bull; {{ auth()->user()->email }}
                            </p>
                        </div>
                        <a href="{{ route('conversations.index') }}" data-open-dm-drawer class="glass-btn-primary text-xs py-2 px-3.5 shrink-0 flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <span>Kotak Pesan</span>
                        </a>
                    </div>
                @else
                    <a href="{{ route('register') }}" class="glass-btn-primary w-full sm:w-auto px-7 py-3.5 text-sm sm:text-base shadow-lg shadow-indigo-500/25">
                        Daftar Akun Baru Sekarang
                    </a>
                    <a href="{{ route('login') }}" class="glass-btn-secondary w-full sm:w-auto px-7 py-3.5 text-sm sm:text-base">
                        Masuk ke Akun
                    </a>
                @endauth
            </div>
        </div>

        <!-- 3 Core Pillars -->
        <div class="mt-16 sm:mt-20 grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
            <!-- Pillar 1: Marketplace -->
            <div id="marketplace" class="glass-card glass-card-interactive p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold mb-6 border border-indigo-500/20 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">Marketplace Digital</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        Katalog produk digital terstruktur per kategori dan varian. Harga transparan dihitung dari penawaran seller ditambah fee platform yang jelas.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60">
                    <span class="glass-badge glass-badge-primary">
                        Estimasi durasi: Menit / Jam / Hari
                    </span>
                </div>
            </div>

            <!-- Pillar 2: Forum WTB/WTS -->
            <div id="forum" class="glass-card glass-card-interactive p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold mb-6 border border-amber-500/20 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">Forum Komunitas & Negosiasi</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        Ruang diskusi publik untuk WTB (Want to Buy), WTS (Want to Sell), dan General. Lengkap dengan komentar, likes, dan unggah foto bukti transaksi.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60">
                    <span class="glass-badge glass-badge-warning">
                        Bisa langsung lanjut ke DM negosiasi
                    </span>
                </div>
            </div>

            <!-- Pillar 3: Escrow & Settlement -->
            <div id="escrow" class="glass-card glass-card-interactive p-6 sm:p-8 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold mb-6 border border-emerald-500/20 shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">Escrow & Settlement Aman</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        Dana buyer ditahan aman di payment gateway sandbox sampai buyer mengonfirmasi item digital diterima. Payout seller dicatat secara simulatif ke Bank/E-wallet/QRIS.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60">
                    <span class="glass-badge glass-badge-success">
                        Perlindungan komplain & bukti terpusat
                    </span>
                </div>
            </div>
        </div>

        <!-- Scope & Rules Banner (Dark Glass Surface) -->
        <div class="mt-14 sm:mt-16 glass-card-dark p-6 sm:p-10 lg:p-12 relative overflow-hidden">
            <!-- Subtle accent light inside card -->
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="max-w-3xl relative z-10">
                <span class="inline-block text-[11px] uppercase font-bold tracking-widest text-indigo-400 mb-2">
                    Arsitektur & Scope PBL
                </span>
                <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-3">
                    Dirancang Khusus untuk Transaksi Item Digital
                </h3>
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-6">
                    MarketTrust mengeliminasi kerumitan kurir fisik, resi pengiriman, dan QC admin fisik. Fokus utama adalah pada kepercayaan transaksi melalui sistem reputasi poin, penyerahan bukti digital (link/kredensial), dan mitigasi sengketa buyer-seller.
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4 border-t border-white/10">
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 sm:p-4 backdrop-blur-sm">
                        <span class="block font-bold text-white text-xs sm:text-sm mb-0.5">Google OAuth</span>
                        <span class="text-[11px] sm:text-xs text-slate-300">Login cepat & aman</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 sm:p-4 backdrop-blur-sm">
                        <span class="block font-bold text-white text-xs sm:text-sm mb-0.5">Agreement Sheet</span>
                        <span class="text-[11px] sm:text-xs text-slate-300">Klausul dikunci final</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 sm:p-4 backdrop-blur-sm">
                        <span class="block font-bold text-white text-xs sm:text-sm mb-0.5">Dispute Review</span>
                        <span class="text-[11px] sm:text-xs text-slate-300">Resolusi komplain adil</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 sm:p-4 backdrop-blur-sm">
                        <span class="block font-bold text-white text-xs sm:text-sm mb-0.5">Audit Trail</span>
                        <span class="text-[11px] sm:text-xs text-slate-300">Log aktivitas tercatat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
