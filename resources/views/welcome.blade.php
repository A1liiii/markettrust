@extends('layouts.app')

@section('title', 'MarketTrust - Marketplace & Forum Digital Terpercaya')

@section('content')
<div class="py-10 sm:py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16 sm:space-y-20">

        <!-- Hero Section -->
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 mb-6">
                <span class="glass-badge glass-badge-primary px-3.5 py-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                    Platform Transaksi & Escrow Digital Terpercaya
                </span>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Marketplace & Forum Digital dengan <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Escrow Terpercaya</span>
            </h1>

            <p class="mt-5 sm:mt-6 text-sm sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Jual-beli item game, akun, voucher, dan produk digital tanpa rasa was-was. Dilengkapi rekening bersama otomatis, negosiasi forum transparan, dan jaminan perlindungan transaksi.
            </p>

            <!-- CTA / User Card Section -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <div class="glass-card glass-card-interactive p-5 w-full sm:w-auto text-left flex items-center gap-4 border border-white/80 shadow-md">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}" class="w-12 h-12 rounded-full object-cover shadow-sm ring-2 ring-white/80 shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-indigo-500/20 shrink-0">
                                {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-[140px]">
                            <h3 class="font-bold text-slate-900 text-base leading-tight">@<span>{{ auth()->user()->username }}</span></h3>
                            <p class="text-xs text-slate-500 mt-1 truncate">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 shrink-0">
                            <a href="{{ route('forum.index') }}" class="glass-btn-primary text-xs py-1.5 px-3.5 text-center flex items-center justify-center gap-1.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Forum</span>
                            </a>
                            <a href="#marketplace" class="glass-btn-secondary text-xs py-1.5 px-3.5 text-center flex items-center justify-center gap-1.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>Marketplace</span>
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('register') }}" class="glass-btn-primary w-full sm:w-auto px-7 py-3.5 text-sm sm:text-base shadow-lg shadow-indigo-500/25">
                        Daftar Akun Sekarang
                    </a>
                    <a href="{{ route('login') }}" class="glass-btn-secondary w-full sm:w-auto px-7 py-3.5 text-sm sm:text-base">
                        Masuk ke Akun
                    </a>
                @endauth
            </div>
        </div>

        <!-- Interactive Fee Simulation Calculator -->
        <div id="simulasi-fee" class="max-w-4xl mx-auto">
            <div class="glass-card p-6 sm:p-8 lg:p-10 border border-white/80 shadow-lg relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/60">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold border border-indigo-500/20 shadow-xs shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kalkulator Simulasi Fee Forum</h2>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Simulasi biaya layanan rekber transparan</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Kolom Input & Opsi -->
                    <div class="lg:col-span-7 space-y-5">
                        <div>
                            <label for="sim-price-input" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                Nominal Transaksi Forum (Rp)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm">Rp</span>
                                <input
                                    type="text"
                                    id="sim-price-input"
                                    value="100.000"
                                    class="w-full pl-11 pr-4 py-3 rounded-xl bg-white/80 border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-900 font-bold text-base transition outline-none"
                                    placeholder="Contoh: 100.000"
                                >
                            </div>
                            <p id="sim-min-error" class="hidden text-xs text-rose-600 mt-1.5 font-medium">
                                * Batas minimum transaksi adalah Rp5.000
                            </p>
                        </div>

                        <!-- Quick Nominal Buttons -->
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-2">Pilihan Cepat:</span>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-amount="25000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white text-slate-700 border border-slate-200/80 transition">25 Rb</button>
                                <button type="button" data-amount="50000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white text-slate-700 border border-slate-200/80 transition">50 Rb</button>
                                <button type="button" data-amount="100000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 transition">100 Rb</button>
                                <button type="button" data-amount="250000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white text-slate-700 border border-slate-200/80 transition">250 Rb</button>
                                <button type="button" data-amount="500000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white text-slate-700 border border-slate-200/80 transition">500 Rb</button>
                                <button type="button" data-amount="1000000" class="sim-quick-btn text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/60 hover:bg-white text-slate-700 border border-slate-200/80 transition">1 Jt</button>
                            </div>
                        </div>

                        <!-- Fee Mode Toggle (Exclude vs Include) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                                Penanggung Biaya Layanan
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label id="label-mode-exclude" class="flex items-center gap-3 p-3 rounded-xl border border-indigo-300 bg-indigo-50/70 cursor-pointer transition">
                                    <input type="radio" name="sim_fee_mode" value="EXCLUDE" checked class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-900">Buyer (EXCLUDE)</span>
                                        <span class="block text-[11px] text-slate-500">Harga + Fee ditanggung Buyer</span>
                                    </div>
                                </label>
                                <label id="label-mode-include" class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white/60 cursor-pointer transition">
                                    <input type="radio" name="sim_fee_mode" value="INCLUDE" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-900">Seller (INCLUDE)</span>
                                        <span class="block text-[11px] text-slate-500">Fee dipotong dari hak Seller</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Hasil Rincian -->
                    <div class="lg:col-span-5 bg-white/70 backdrop-blur-md rounded-2xl p-5 border border-white/90 shadow-sm space-y-4">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Estimasi Rincian Transaksi
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center text-slate-600">
                                <span>Harga Kesepakatan:</span>
                                <span id="res-base-price" class="font-semibold text-slate-900">Rp 100.000</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="flex items-center gap-1">
                                    Biaya Rekber (Fee):
                                    <span id="res-tier-info" class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">5%</span>
                                </span>
                                <span id="res-fee-amount" class="font-semibold text-indigo-600">Rp 5.000</span>
                            </div>

                            <div class="border-t border-slate-200/80 pt-3 flex justify-between items-center">
                                <div>
                                    <span class="block text-xs font-bold text-slate-900">Total Dibayar Buyer:</span>
                                    <span class="text-[10px] text-slate-500">Dana ditahan di escrow</span>
                                </div>
                                <span id="res-buyer-total" class="text-base font-extrabold text-slate-900">Rp 105.000</span>
                            </div>

                            <div class="border-t border-slate-200/80 pt-3 flex justify-between items-center">
                                <div>
                                    <span class="block text-xs font-bold text-slate-900">Diterima Bersih Seller:</span>
                                    <span class="text-[10px] text-slate-500">Pencairan setelah selesai</span>
                                </div>
                                <span id="res-seller-net" class="text-base font-extrabold text-emerald-600">Rp 100.000</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200/60 text-[11px] text-slate-500 leading-relaxed">
                            💡 Gunakan simulasi ini untuk menyepakati harga akhir di Forum atau percakapan DM sebelum membuat kesepakatan final.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Core Pillars (Marketplace, Forum, Escrow) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
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
                        Katalog produk digital terstruktur per kategori dan varian. Harga transparan dihitung langsung dari penawaran seller ditambah fee platform yang jelas.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60 flex items-center justify-between gap-2">
                    <a href="{{ route('seller.listings.create') }}" class="glass-btn-primary text-xs py-1.5 px-3.5 shadow-xs inline-flex items-center gap-1.5 font-semibold" title="Buka formulir pembuatan listing penawaran">
                        <span>Ingin berjualan?</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <span class="text-xs font-semibold text-slate-500 shrink-0">
                        {{ $activeListingsCount }} Listing Aktif
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
                        Ruang diskusi terbuka untuk WTB (Want to Buy), WTS (Want to Sell), dan General. Komentar, likes, dan tombol langsung beralih ke DM kesepakatan.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60 flex items-center justify-between">
                    <a href="{{ route('forum.index') }}" class="glass-btn-primary text-xs py-1 px-3">
                        Kunjungi Forum &rarr;
                    </a>
                    <span class="text-xs font-semibold text-slate-500">
                        {{ $forumPostsCount }} Diskusi
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
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-2">Escrow Rekber Otomatis</h3>
                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        Dana pembeli ditahan aman di sistem hingga barang digital diperiksa. Pembayaran seller dicatat transparan ke Bank, E-Wallet, atau QRIS.
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-200/60">
                    <span class="glass-badge glass-badge-success">
                        Proteksi Sengketa & Bukti
                    </span>
                </div>
            </div>
        </div>

        <!-- Alur Transaksi Aman 4 Langkah -->
        <div class="glass-card-dark p-6 sm:p-10 lg:p-12 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="max-w-4xl relative z-10">
                <span class="inline-block text-[11px] uppercase font-bold tracking-widest text-indigo-400 mb-2">
                    Standar Keamanan Transaksi
                </span>
                <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-3">
                    Bagaimana Transaksi Aman Dilakukan di MarketTrust?
                </h3>
                <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-8">
                    Setiap transaksi dilindungi oleh lembar kesepakatan (*Agreement Snapshot*) yang mengikat kedua belah pihak tanpa celah kecurangan.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 font-extrabold flex items-center justify-center text-xs mb-3 border border-indigo-400/20">1</span>
                        <h4 class="font-bold text-white text-sm mb-1">Sepakati Klausul</h4>
                        <p class="text-[12px] text-slate-300 leading-relaxed">Pembeli & penjual menyepakati harga, durasi pengiriman, dan penanggung fee via DM.</p>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 font-extrabold flex items-center justify-center text-xs mb-3 border border-indigo-400/20">2</span>
                        <h4 class="font-bold text-white text-sm mb-1">Setor ke Escrow</h4>
                        <p class="text-[12px] text-slate-300 leading-relaxed">Pembeli membayar total transaksi ke rekening bersama aman.</p>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 font-extrabold flex items-center justify-center text-xs mb-3 border border-indigo-400/20">3</span>
                        <h4 class="font-bold text-white text-sm mb-1">Serah Item Digital</h4>
                        <p class="text-[12px] text-slate-300 leading-relaxed">Penjual menyerahkan akun/item dan bukti digital melalui chat terenkripsi.</p>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                        <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 font-extrabold flex items-center justify-center text-xs mb-3 border border-indigo-400/20">4</span>
                        <h4 class="font-bold text-white text-sm mb-1">Konfirmasi & Payout</h4>
                        <p class="text-[12px] text-slate-300 leading-relaxed">Pembeli konfirmasi penerimaan dan dana langsung diteruskan ke penjual.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const priceInput = document.getElementById('sim-price-input');
    const minError = document.getElementById('sim-min-error');
    const resBasePrice = document.getElementById('res-base-price');
    const resTierInfo = document.getElementById('res-tier-info');
    const resFeeAmount = document.getElementById('res-fee-amount');
    const resBuyerTotal = document.getElementById('res-buyer-total');
    const resSellerNet = document.getElementById('res-seller-net');
    const modeExclude = document.getElementById('label-mode-exclude');
    const modeInclude = document.getElementById('label-mode-include');
    const quickButtons = document.querySelectorAll('.sim-quick-btn');

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number).replace('IDR', 'Rp');
    }

    function calculateNominalFee(price) {
        if (price < 5000) return 0;
        let rawFee = 0;
        if (price <= 10000) {
            rawFee = price * 0.10;
        } else if (price <= 50000) {
            rawFee = 1000 + ((price - 10000) / 40000) * 1500;
        } else if (price <= 100000) {
            rawFee = price * 0.05;
        } else if (price <= 499000) {
            rawFee = 5000 + ((price - 100000) / 399000) * 2000;
        } else if (price <= 1000000) {
            rawFee = 7000 + ((price - 499000) / 501000) * 3000;
        } else if (price < 10000000) {
            rawFee = 10000.0;
        } else {
            rawFee = 20000.0 + (Math.floor((price - 10000000) / 10000000) * 10000.0);
        }
        return Math.ceil(rawFee);
    }

    function getTierDescription(price) {
        if (price < 5000) return 'Di bawah min.';
        if (price <= 10000) return '10% Flat';
        if (price <= 50000) return 'Tier 10%-5%';
        if (price <= 100000) return '5% Flat';
        if (price <= 499000) return 'Tier 5%-1.4%';
        if (price <= 1000000) return 'Tier 1.4%-1%';
        if (price < 10000000) return 'Rp10 Rb Flat';
        return 'Tier ≥10 Jt';
    }

    function runSimulation() {
        const rawText = priceInput.value.replace(/[^0-9]/g, '');
        const price = parseInt(rawText || '0', 10);
        const selectedMode = document.querySelector('input[name="sim_fee_mode"]:checked')?.value || 'EXCLUDE';

        // Toggle UI states for mode radios
        if (selectedMode === 'EXCLUDE') {
            modeExclude.classList.add('border-indigo-300', 'bg-indigo-50/70');
            modeExclude.classList.remove('border-slate-200', 'bg-white/60');
            modeInclude.classList.add('border-slate-200', 'bg-white/60');
            modeInclude.classList.remove('border-indigo-300', 'bg-indigo-50/70');
        } else {
            modeInclude.classList.add('border-indigo-300', 'bg-indigo-50/70');
            modeInclude.classList.remove('border-slate-200', 'bg-white/60');
            modeExclude.classList.add('border-slate-200', 'bg-white/60');
            modeExclude.classList.remove('border-indigo-300', 'bg-indigo-50/70');
        }

        if (price < 5000) {
            minError.classList.remove('hidden');
            resBasePrice.textContent = formatRupiah(price);
            resTierInfo.textContent = 'Min Rp5 Rb';
            resFeeAmount.textContent = 'Rp 0';
            resBuyerTotal.textContent = formatRupiah(price);
            resSellerNet.textContent = formatRupiah(price);
            return;
        }

        minError.classList.add('hidden');
        const fee = calculateNominalFee(price);
        let buyerTotal = 0;
        let sellerNet = 0;

        if (selectedMode === 'EXCLUDE') {
            buyerTotal = price + fee;
            sellerNet = price;
        } else {
            buyerTotal = price;
            sellerNet = Math.max(0, price - fee);
        }

        resBasePrice.textContent = formatRupiah(price);
        resTierInfo.textContent = getTierDescription(price);
        resFeeAmount.textContent = formatRupiah(fee);
        resBuyerTotal.textContent = formatRupiah(buyerTotal);
        resSellerNet.textContent = formatRupiah(sellerNet);
    }

    priceInput.addEventListener('input', function () {
        const rawDigits = this.value.replace(/[^0-9]/g, '');
        if (rawDigits) {
            this.value = new Intl.NumberFormat('id-ID').format(parseInt(rawDigits, 10));
        } else {
            this.value = '';
        }
        runSimulation();
    });

    document.querySelectorAll('input[name="sim_fee_mode"]').forEach(radio => {
        radio.addEventListener('change', runSimulation);
    });

    quickButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const amount = parseInt(this.getAttribute('data-amount'), 10);
            priceInput.value = new Intl.NumberFormat('id-ID').format(amount);

            quickButtons.forEach(b => {
                b.classList.remove('bg-indigo-50', 'text-indigo-700', 'border-indigo-200');
                b.classList.add('bg-white/60', 'text-slate-700', 'border-slate-200/80');
            });
            this.classList.add('bg-indigo-50', 'text-indigo-700', 'border-indigo-200');
            this.classList.remove('bg-white/60', 'text-slate-700', 'border-slate-200/80');

            runSimulation();
        });
    });

    // Run initial simulation
    runSimulation();
});
</script>
@endsection
