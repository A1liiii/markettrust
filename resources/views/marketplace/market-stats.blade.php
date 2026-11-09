@extends('layouts.app')

@section('title', 'Informasi Pasar Produk Digital - Statistik Supply & Demand')

@section('content')
<div class="py-6 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 overflow-x-auto pb-1" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold">Informasi Pasar Produk</span>
        </nav>

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/60">
            <div>
                <div class="inline-flex items-center gap-2 mb-1">
                    <span class="glass-badge glass-badge-primary text-[11px] py-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-600 animate-pulse"></span>
                        Pasar Digital Real-time
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Informasi Pasar <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-600 to-indigo-600">Produk Digital</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl leading-relaxed mt-0.5">
                    Transparansi data pasokan (supply), permintaan (demand), dan rata-rata harga transaksi nyata dari escrow MarketTrust.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('marketplace.index') }}" class="glass-btn-secondary py-2 px-3.5 text-xs sm:text-sm font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Katalog Marketplace</span>
                </a>
                <a href="{{ route('forum.index') }}" class="glass-btn-secondary py-2 px-3.5 text-xs sm:text-sm font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Forum Komunitas</span>
                </a>
            </div>
        </div>

        <!-- Filter & Selector Card -->
        <div class="glass-panel p-4 sm:p-5 border border-white/80 shadow-sm" id="market-stats-filter">
            <form action="{{ route('market-statistics.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
                    <!-- Variant Selector -->
                    <div class="lg:col-span-8">
                        <label for="variant-select" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Pilih Varian Item / Produk Digital
                        </label>
                        <div class="relative">
                            <select
                                name="variant_id"
                                id="variant-select"
                                onchange="this.form.submit()"
                                class="w-full appearance-none pl-3.5 pr-10 py-2.5 rounded-xl bg-white/90 border border-slate-200 text-xs sm:text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition cursor-pointer"
                                data-cy="select-market-variant"
                            >
                                @foreach ($categories as $cat)
                                    <optgroup label="{{ $cat->category_name }}">
                                        @foreach ($cat->products as $prod)
                                            @foreach ($prod->variants as $var)
                                                <option value="{{ $var->variant_id }}" @selected($selectedVariantId == $var->variant_id)>
                                                    {{ $prod->product_name }} &rsaquo; {{ $var->variant_name }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Period Selector -->
                    <div class="lg:col-span-4">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Periode Transaksi
                        </label>
                        <div class="grid grid-cols-4 gap-1 p-1 bg-slate-100/90 rounded-xl border border-slate-200/70">
                            @foreach (['7d' => '7 Hari', '30d' => '30 Hari', '90d' => '90 Hari', 'all' => 'Semua'] as $pVal => $pText)
                                <button
                                    type="submit"
                                    name="period"
                                    value="{{ $pVal }}"
                                    class="py-1.5 px-2 text-center rounded-lg text-xs font-semibold transition {{ $period === $pVal ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}"
                                    data-cy="period-btn-{{ $pVal }}"
                                >
                                    {{ $pText }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedVariant)
            <!-- Active Selected Header Card -->
            <div class="glass-card p-5 border border-white/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-violet-700 mb-1">
                        <span>{{ $selectedVariant->product?->category?->category_name }}</span>
                        <span>&bull;</span>
                        <span>{{ $selectedVariant->product?->product_name }}</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" data-cy="market-variant-title">
                        {{ $selectedVariant->variant_name }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Statistik transaksi dihitung berdasarkan periode: <strong class="text-slate-700">{{ $marketStats['period_label'] }}</strong>
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a
                        href="{{ route('marketplace.index', ['variant_id' => $selectedVariant->variant_id]) }}"
                        class="glass-btn-primary py-2 px-3 text-xs font-semibold flex items-center gap-1.5 shadow-xs"
                        data-cy="btn-view-variant-listings"
                    >
                        <span>Cari di Marketplace</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a
                        href="{{ route('forum.index', ['category' => 'WTB', 'variant_id' => $selectedVariant->variant_id]) }}"
                        class="glass-btn-secondary py-2 px-3 text-xs font-semibold flex items-center gap-1.5"
                    >
                        <span>Diskusi Forum WTB/WTS</span>
                    </a>
                </div>
            </div>

            <!-- 3 Core Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6" data-cy="market-stats-cards">

                <!-- 1. Permintaan Pasar (Demand) -->
                <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-sm space-y-3 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold border border-amber-500/20 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Permintaan Pasar (Demand)
                        </span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1" data-cy="stat-demand-value">
                            {{ $marketStats['demand'] }}
                        </div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100 text-xs text-slate-500 leading-relaxed">
                        Total postingan <strong>WTB (Want to Buy)</strong> aktif di forum yang sedang mencari varian ini.
                    </div>
                </div>

                <!-- 2. Pasokan Tersedia (Supply) -->
                <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-sm space-y-3 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold border border-blue-500/20 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Pasokan Tersedia (Supply)
                        </span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1" data-cy="stat-supply-value">
                            {{ $marketStats['supply'] }}
                        </div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-100 text-xs text-slate-500 leading-relaxed">
                        Terdiri dari <strong>{{ $marketStats['active_listings_count'] }}</strong> listing marketplace aktif & <strong>{{ $marketStats['wts_count'] }}</strong> post WTS di forum.
                    </div>
                </div>

                <!-- 3. Rata-rata Harga Transaksi -->
                <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-sm space-y-3 relative overflow-hidden group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold border border-emerald-500/20 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Rata-Rata Harga Riil
                        </span>
                        @if ($marketStats['has_price_data'])
                            <div class="text-2xl sm:text-3xl font-black text-emerald-700 tracking-tight mt-1" data-cy="stat-avg-price">
                                Rp {{ number_format($marketStats['avg_price'], 0, ',', '.') }}
                            </div>
                        @else
                            <div class="text-xl font-bold text-slate-400 tracking-tight mt-1" data-cy="stat-avg-price-empty">
                                Belum Ada Data
                            </div>
                        @endif
                    </div>
                    <div class="pt-2.5 border-t border-slate-100 text-xs text-slate-500 leading-relaxed">
                        @if ($marketStats['has_price_data'])
                            <span class="text-emerald-700 font-medium">✓ Dihitung dari {{ $marketStats['sample_count'] }} transaksi sukses ({{ $marketStats['period_label'] }}).</span>
                        @else
                            <span>Belum ada transaksi selesai pada varian ini dalam {{ strtolower($marketStats['period_label']) }}.</span>
                        @endif
                    </div>
                </div>

            </div>
        @else
            <!-- Empty state jika tidak ada varian -->
            <div class="glass-card p-12 text-center max-w-lg mx-auto">
                <p class="text-sm text-slate-500">Belum ada varian produk aktif di sistem.</p>
            </div>
        @endif

    </div>
</div>
@endsection

