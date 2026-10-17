@extends('layouts.app')

@section('title', 'Marketplace Digital - Beli Item, Akun & Voucher Aman')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header / Banner Section -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-white/60">
            <div>
                <div class="inline-flex items-center gap-2 mb-3">
                    <span class="glass-badge glass-badge-primary">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                        Katalog Terverifikasi Escrow
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        {{ $totalActiveListings }} Listing Aktif
                    </span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Marketplace <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Produk Digital</span>
                </h1>
                <p class="mt-2 text-sm sm:text-base text-slate-600 max-w-2xl leading-relaxed">
                    Jual-beli akun, item game, in-game currency, dan voucher digital terstruktur. Dilindungi rekening bersama otomatis dan rincian biaya yang transparan.
                </p>
            </div>

            <!-- CTA Seller -->
            <div class="shrink-0">
                <a href="{{ route('seller.listings.create') }}" class="glass-btn-primary py-2.5 px-4 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-sm" data-cy="btn-ingin-berjualan" id="marketplace-cta-jual">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Ingin Berjualan?</span>
                </a>
            </div>
        </div>

        <!-- Filter & Search Toolbar Glass Panel -->
        <div class="glass-panel p-5 sm:p-6 border border-white/80 shadow-sm" id="marketplace-filter-toolbar">
            <form action="{{ route('marketplace.index') }}" method="GET" id="marketplace-search-form" class="space-y-4">
                <!-- Search Keyword -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="q"
                        id="search-input"
                        value="{{ request('q') }}"
                        placeholder="Cari judul listing, varian, atau nama game..."
                        class="w-full pl-11 pr-24 py-2.5 sm:py-3 rounded-xl bg-white/80 border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-medium transition"
                        data-cy="input-search-marketplace"
                    >
                    @if (request('q'))
                        <div class="absolute inset-y-0 right-14 pr-2 flex items-center">
                            <a href="{{ route('marketplace.index', array_filter(request()->except('q'))) }}" class="text-xs text-slate-400 hover:text-slate-600 transition" title="Hapus kata kunci">
                                &times; Bersihkan
                            </a>
                        </div>
                    @endif
                    <button type="submit" class="absolute inset-y-1.5 right-1.5 px-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1 transition shadow-xs" data-cy="btn-submit-search">
                        Cari
                    </button>
                </div>

                <!-- Hierarchical Filter Selectors -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                    <!-- Kategori -->
                    <div>
                        <label for="filter-category" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Kategori Produk
                        </label>
                        <select
                            name="category_id"
                            id="filter-category"
                            class="w-full py-2 px-3 rounded-lg bg-white/90 border border-slate-200 text-xs sm:text-sm text-slate-700 focus:outline-none focus:border-indigo-500 transition"
                            data-cy="select-category"
                        >
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->category_id }}" @selected(request('category_id') == $cat->category_id)>
                                    {{ $cat->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Produk -->
                    <div>
                        <label for="filter-product" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Produk Master
                        </label>
                        <select
                            name="product_id"
                            id="filter-product"
                            class="w-full py-2 px-3 rounded-lg bg-white/90 border border-slate-200 text-xs sm:text-sm text-slate-700 focus:outline-none focus:border-indigo-500 transition"
                            data-cy="select-product"
                        >
                            <option value="">Semua Produk</option>
                            @foreach ($categories as $cat)
                                @foreach ($cat->products as $prod)
                                    <option
                                        value="{{ $prod->product_id }}"
                                        data-category="{{ $cat->category_id }}"
                                        @selected(request('product_id') == $prod->product_id)
                                    >
                                        {{ $cat->category_name }} &rsaquo; {{ $prod->product_name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <!-- Varian -->
                    <div>
                        <label for="filter-variant" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Varian Item
                        </label>
                        <select
                            name="variant_id"
                            id="filter-variant"
                            class="w-full py-2 px-3 rounded-lg bg-white/90 border border-slate-200 text-xs sm:text-sm text-slate-700 focus:outline-none focus:border-indigo-500 transition"
                            data-cy="select-variant"
                        >
                            <option value="">Semua Varian</option>
                            @foreach ($categories as $cat)
                                @foreach ($cat->products as $prod)
                                    @foreach ($prod->variants as $var)
                                        <option
                                            value="{{ $var->variant_id }}"
                                            data-product="{{ $prod->product_id }}"
                                            data-category="{{ $cat->category_id }}"
                                            @selected(request('variant_id') == $var->variant_id)
                                        >
                                            {{ $prod->product_name }} - {{ $var->variant_name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <!-- Pengurutan & Aksi Reset -->
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label for="filter-sort" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Urutkan
                            </label>
                            <select
                                name="sort"
                                id="filter-sort"
                                class="w-full py-2 px-3 rounded-lg bg-white/90 border border-slate-200 text-xs sm:text-sm text-slate-700 focus:outline-none focus:border-indigo-500 transition"
                                data-cy="select-sort"
                            >
                                <option value="latest" @selected(request('sort') === 'latest' || !request('sort'))>Terbaru</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Harga Terendah</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Harga Tertinggi</option>
                            </select>
                        </div>
                        <a
                            href="{{ route('marketplace.index') }}"
                            class="glass-btn-secondary py-2 px-3 text-xs font-semibold flex items-center justify-center shrink-0 h-[38px]"
                            title="Reset semua filter"
                            data-cy="btn-reset-filter"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Listing Grid Section -->
        <div>
            @if ($listings->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="marketplace-grid" data-cy="marketplace-grid">
                    @foreach ($listings as $listing)
                        <div class="glass-card glass-card-interactive flex flex-col justify-between overflow-hidden group border border-white/80 hover:border-indigo-300 transition-all duration-300">
                            <!-- Image Header / Fallback Cover -->
                            <div class="relative aspect-video bg-gradient-to-tr from-slate-100 via-slate-50 to-indigo-50/40 border-b border-slate-100 flex items-center justify-center overflow-hidden">
                                @if ($listing->cover_image_url)
                                    <img
                                        src="{{ $listing->cover_image_url }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                                        <svg class="w-10 h-10 mb-1 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                            {{ $listing->variant?->product?->product_name ?? 'Digital Item' }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Category & Delivery Badge Overlays -->
                                <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                                    <span class="px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-sm text-[10px] font-bold text-indigo-700 shadow-2xs border border-white/60">
                                        {{ $listing->variant?->product?->category?->category_name ?? 'Item' }}
                                    </span>
                                </div>

                                <div class="absolute bottom-2 right-2">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-900/80 backdrop-blur-sm text-[10px] font-medium text-white shadow-2xs flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $listing->estimated_delivery_formatted }}
                                    </span>
                                </div>
                            </div>

                            <!-- Body Content -->
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="text-[11px] font-medium text-slate-500 mb-1 flex items-center gap-1">
                                        <span>{{ $listing->variant?->product?->product_name }}</span>
                                        <span>&bull;</span>
                                        <span class="text-indigo-600 font-semibold truncate">{{ $listing->variant?->variant_name }}</span>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600 transition-colors">
                                        <a href="{{ route('marketplace.show', $listing) }}" class="focus:outline-none" data-cy="listing-card-title-{{ $listing->listing_id }}">
                                            {{ $listing->title }}
                                        </a>
                                    </h3>
                                    <p class="text-xs text-slate-500 line-clamp-2 mt-1.5 leading-relaxed">
                                        {{ $listing->description }}
                                    </p>
                                </div>

                                <!-- Price & Seller Footer -->
                                <div class="pt-3 border-t border-slate-100 flex items-end justify-between gap-2">
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Harga Bersih</span>
                                        <span class="text-base font-extrabold text-slate-900" data-cy="listing-card-price-{{ $listing->listing_id }}">
                                            Rp {{ number_format($listing->price, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] font-semibold text-slate-700 truncate max-w-[110px]">
                                            @<span>{{ $listing->seller?->username }}</span>
                                        </div>
                                        <div class="text-[10px] text-emerald-600 font-medium">
                                            ✓ {{ $listing->seller?->seller_transactions_count ?? 0 }} Terjual
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Action Link -->
                            <div class="px-4 pb-4 pt-1">
                                <a
                                    href="{{ route('marketplace.show', $listing) }}"
                                    class="w-full glass-btn-primary py-2 text-xs font-semibold flex items-center justify-center gap-1.5"
                                    data-cy="btn-view-listing-{{ $listing->listing_id }}"
                                >
                                    <span>Lihat Rincian</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center" data-cy="marketplace-pagination">
                    {{ $listings->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="glass-card p-12 text-center max-w-xl mx-auto border border-white/80" data-cy="marketplace-empty-state">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-4 border border-indigo-100 shadow-2xs">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 14v-4m0 0l-2 2m2-2l2 2"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Tidak Ada Listing yang Sesuai</h3>
                    <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                        Kami tidak menemukan penawaran produk digital yang cocok dengan filter atau kata kunci pencarian Anda. Coba gunakan kata kunci lain atau reset filter.
                    </p>
                    <a href="{{ route('marketplace.index') }}" class="glass-btn-secondary px-5 py-2.5 text-xs sm:text-sm font-semibold inline-flex items-center gap-1.5" data-cy="btn-clear-search">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Tampilkan Semua Listing Aktif</span>
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('filter-category');
    const productSelect = document.getElementById('filter-product');
    const variantSelect = document.getElementById('filter-variant');
    const form = document.getElementById('marketplace-search-form');

    // Auto-filter dependent options
    if (categorySelect && productSelect) {
        categorySelect.addEventListener('change', function () {
            const selectedCat = this.value;
            // Clear product and variant selection
            productSelect.value = '';
            if (variantSelect) variantSelect.value = '';
            form.submit();
        });
    }

    if (productSelect) {
        productSelect.addEventListener('change', function () {
            if (variantSelect) variantSelect.value = '';
            form.submit();
        });
    }

    if (variantSelect) {
        variantSelect.addEventListener('change', function () {
            form.submit();
        });
    }

    const sortSelect = document.getElementById('filter-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            form.submit();
        });
    }
});
</script>
@endsection
