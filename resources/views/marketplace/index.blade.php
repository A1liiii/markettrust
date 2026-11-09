@extends('layouts.app')

@section('title', 'Marketplace Digital - Beli Item, Akun & Voucher Aman')

@section('content')
<div class="py-4 sm:py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-5">

        <!-- Header / Banner Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-white/60">
            <div>
                <div class="inline-flex items-center gap-2 mb-1">
                    <span class="glass-badge glass-badge-primary text-[11px] py-0.5" data-cy="badge-katalog-terverifikasi">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                        Katalog Marketplace Terverifikasi
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        {{ $totalActiveListings }} Listing Aktif
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Marketplace <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600">Produk Digital</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl leading-relaxed mt-0.5">
                    Jual-beli akun, item game, in-game currency, dan voucher digital terpercaya dilindungi rekening bersama.
                </p>
            </div>

            <!-- CTA Seller -->
            <div class="shrink-0">
                <a href="{{ route('seller.listings.create') }}" class="glass-btn-primary py-2 px-3.5 text-xs sm:text-sm font-semibold flex items-center justify-center shadow-sm" data-cy="btn-ingin-berjualan" id="marketplace-cta-jual">
                    <span>Ingin Berjualan?</span>
                </a>
            </div>
        </div>

        <!-- Filter & Search Toolbar Glass Panel -->
        <div class="glass-panel p-3.5 sm:p-4 border border-white/80 shadow-sm relative z-20" id="marketplace-filter-toolbar">
            <form action="{{ route('marketplace.index') }}" method="GET" id="marketplace-search-form" class="space-y-3">
                <!-- Hidden inputs for active product filters -->
                <input type="hidden" name="category_id" id="hidden-category-id" value="{{ request('category_id') }}" data-cy="select-category">
                <input type="hidden" name="product_id" id="hidden-product-id" value="{{ request('product_id') }}" data-cy="select-product">
                <input type="hidden" name="variant_id" id="hidden-variant-id" value="{{ request('variant_id') }}" data-cy="select-variant">

                <!-- Search Keyword -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="q"
                        id="search-input"
                        value="{{ request('q') }}"
                        placeholder="Cari judul listing, varian, atau nama game..."
                        class="w-full pl-10 pr-24 py-2 sm:py-2.5 rounded-xl bg-white/80 border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs sm:text-sm font-medium transition"
                        data-cy="input-search-marketplace"
                    >
                    @if (request('q'))
                        <div class="absolute inset-y-0 right-14 pr-2 flex items-center">
                            <a href="{{ route('marketplace.index', array_filter(request()->except('q'))) }}" class="text-xs text-slate-400 hover:text-slate-600 transition" title="Hapus kata kunci">
                                &times; Bersihkan
                            </a>
                        </div>
                    @endif
                    <button type="submit" class="absolute inset-y-1 right-1 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1 transition shadow-xs" data-cy="btn-submit-search">
                        Cari
                    </button>
                </div>

                <!-- Unified Filter Row: Filter "Produk" (Cascading Dropdown) + Urutkan + Reset -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-1">
                    
                    <!-- Unified Filter Button "Produk" with Cascading Right Flyout -->
                    <div class="relative flex-1">
                        @php
                            $activeFilterName = 'Semua Produk';
                            if (request('variant_id')) {
                                foreach ($categories as $cat) {
                                    foreach ($cat->products as $p) {
                                        foreach ($p->variants as $v) {
                                            if ((string)$v->variant_id === (string)request('variant_id')) {
                                                $activeFilterName = $p->product_name . ' › ' . $v->variant_name;
                                                break 3;
                                            }
                                        }
                                    }
                                }
                            } elseif (request('product_id')) {
                                foreach ($categories as $cat) {
                                    foreach ($cat->products as $p) {
                                        if ((string)$p->product_id === (string)request('product_id')) {
                                            $activeFilterName = $cat->category_name . ' › ' . $p->product_name;
                                            break 2;
                                        }
                                    }
                                }
                            } elseif (request('category_id')) {
                                foreach ($categories as $cat) {
                                    if ((string)$cat->category_id === (string)request('category_id')) {
                                        $activeFilterName = 'Kategori: ' . $cat->category_name;
                                        break;
                                    }
                                }
                            }
                        @endphp

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                id="btn-toggle-produk-filter"
                                class="inline-flex items-center justify-between gap-2 w-full sm:w-auto min-w-[240px] px-4 py-2 rounded-xl bg-white/90 border border-slate-200 text-xs sm:text-sm font-semibold text-slate-800 hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition shadow-2xs text-left"
                                data-cy="btn-filter-produk"
                                aria-expanded="false"
                            >
                                <span class="flex items-center gap-2 truncate">
                                    <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                    <span class="text-slate-400 font-normal">Filter:</span>
                                    <span class="font-bold text-slate-900 truncate" id="label-active-product">{{ $activeFilterName }}</span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" id="icon-chevron-produk" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            @if (request('category_id') || request('product_id') || request('variant_id'))
                                <button
                                    type="button"
                                    onclick="clearProductFilter()"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Bersihkan filter produk"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <!-- Dropdown Panel Multi-Level (Kategori -> Produk Master -> Varian ke Kanan) -->
                        <div
                            id="dropdown-produk-panel"
                            class="hidden absolute top-full left-0 mt-2 w-full sm:w-[680px] max-w-[95vw] rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-2xl p-3 z-50 text-xs transition-all duration-200"
                            data-cy="dropdown-produk-panel"
                        >
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 px-2">
                                <span class="font-bold text-slate-900 text-xs">Pilih Kategori, Produk & Varian</span>
                                <button type="button" onclick="closeProductDropdown()" class="text-slate-400 hover:text-slate-600 text-xs">&times; Tutup</button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 h-72 sm:h-80">
                                <!-- Kolom 1: Kategori Produk -->
                                <div class="overflow-y-auto pr-1 border-r border-slate-100 space-y-1">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 px-2 py-1 sticky top-0 bg-white/90">
                                        1. Kategori
                                    </div>
                                    <button
                                        type="button"
                                        onclick="selectCategoryFilter('', 'Semua Kategori')"
                                        class="w-full text-left px-2.5 py-2 rounded-lg transition font-medium hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between {{ !request('category_id') && !request('product_id') && !request('variant_id') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-700' }}"
                                    >
                                        <span>Semua Kategori</span>
                                    </button>
                                    @foreach ($categories as $cat)
                                        <button
                                            type="button"
                                            onmouseenter="showCategoryProducts({{ $cat->category_id }})"
                                            onclick="showCategoryProducts({{ $cat->category_id }})"
                                            class="cat-item-btn w-full text-left px-2.5 py-2 rounded-lg transition font-medium hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between text-slate-700 group"
                                            data-cat-id="{{ $cat->category_id }}"
                                            data-cat-name="{{ $cat->category_name }}"
                                            data-cy="filter-cat-{{ $cat->category_id }}"
                                        >
                                            <span class="truncate">{{ $cat->category_name }}</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>

                                <!-- Kolom 2: Produk Master (Muncul di kanan saat kategori dipilih) -->
                                <div class="overflow-y-auto px-1 border-r border-slate-100 space-y-1" id="container-products-col">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 px-2 py-1 sticky top-0 bg-white/90">
                                        2. Produk Master
                                    </div>
                                    <div id="products-list-placeholder" class="text-[11px] text-slate-400 p-3 italic">
                                        Sorot atau klik kategori di sebelah kiri untuk melihat produk.
                                    </div>
                                    @foreach ($categories as $cat)
                                        <div id="cat-products-{{ $cat->category_id }}" class="hidden cat-product-group space-y-1">
                                            <button
                                                type="button"
                                                onclick="selectCategoryFilter({{ $cat->category_id }}, '{{ $cat->category_name }}')"
                                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] text-indigo-600 hover:bg-indigo-50 font-semibold mb-1"
                                            >
                                                &bull; Semua di {{ $cat->category_name }}
                                            </button>
                                            @forelse ($cat->products as $prod)
                                                <button
                                                    type="button"
                                                    onmouseenter="showProductVariants({{ $prod->product_id }})"
                                                    onclick="showProductVariants({{ $prod->product_id }})"
                                                    class="prod-item-btn w-full text-left px-2.5 py-2 rounded-lg transition font-medium hover:bg-indigo-50 hover:text-indigo-700 flex items-center justify-between text-slate-700 group"
                                                    data-prod-id="{{ $prod->product_id }}"
                                                    data-prod-name="{{ $prod->product_name }}"
                                                    data-cy="filter-prod-{{ $prod->product_id }}"
                                                >
                                                    <span class="truncate">{{ $prod->product_name }}</span>
                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            @empty
                                                <div class="text-[11px] text-slate-400 p-2 italic">Belum ada produk.</div>
                                            @endforelse
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Kolom 3: Varian Item (Muncul di kanan saat produk dipilih) -->
                                <div class="overflow-y-auto pl-1 space-y-1" id="container-variants-col">
                                    <div class="text-[10px] uppercase font-bold text-slate-400 px-2 py-1 sticky top-0 bg-white/90">
                                        3. Varian Item
                                    </div>
                                    <div id="variants-list-placeholder" class="text-[11px] text-slate-400 p-3 italic">
                                        Sorot atau klik produk di kolom tengah untuk memilih varian.
                                    </div>
                                    @foreach ($categories as $cat)
                                        @foreach ($cat->products as $prod)
                                            <div id="prod-variants-{{ $prod->product_id }}" class="hidden prod-variant-group space-y-1">
                                                <button
                                                    type="button"
                                                    onclick="selectProductFilter({{ $cat->category_id }}, {{ $prod->product_id }}, '{{ $prod->product_name }}')"
                                                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] text-indigo-600 hover:bg-indigo-50 font-semibold mb-1"
                                                >
                                                    &bull; Semua di {{ $prod->product_name }}
                                                </button>
                                                @forelse ($prod->variants as $var)
                                                    <button
                                                        type="button"
                                                        onclick="selectVariantFilter({{ $cat->category_id }}, {{ $prod->product_id }}, {{ $var->variant_id }}, '{{ $prod->product_name }} › {{ $var->variant_name }}')"
                                                        class="var-item-btn w-full text-left px-2.5 py-2 rounded-lg transition font-medium hover:bg-indigo-600 hover:text-white text-slate-700 flex items-center justify-between"
                                                        data-var-id="{{ $var->variant_id }}"
                                                        data-var-name="{{ $var->variant_name }}"
                                                        data-cy="filter-var-{{ $var->variant_id }}"
                                                    >
                                                        <span class="truncate">{{ $var->variant_name }}</span>
                                                        <span class="text-[10px] opacity-75">Pilih &rsaquo;</span>
                                                    </button>
                                                @empty
                                                    <div class="text-[11px] text-slate-400 p-2 italic">Belum ada varian.</div>
                                                @endforelse
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengurutan & Aksi Reset -->
                    <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                        <div class="relative min-w-[150px]">
                            <select
                                name="sort"
                                id="filter-sort"
                                onchange="this.form.submit()"
                                class="w-full appearance-none pl-3 pr-8 py-2 rounded-xl bg-white/90 border border-slate-200 text-xs sm:text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer"
                                data-cy="select-sort"
                            >
                                <option value="latest" @selected(request('sort') === 'latest' || !request('sort'))>Terbaru</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Harga Terendah</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Harga Tertinggi</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>

                        <a
                            href="{{ route('marketplace.index') }}"
                            class="glass-btn-secondary py-2 px-3 text-xs font-semibold flex items-center justify-center shrink-0 h-[38px] rounded-xl"
                            title="Reset semua filter"
                            data-cy="btn-reset-filter"
                        >
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Listing Grid Section (4 Kolom x 3 Baris = 12 Item per Halaman) -->
        <div>
            @if ($listings->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="marketplace-grid" data-cy="marketplace-grid">
                    @foreach ($listings as $listing)
                        <a
                            href="{{ route('marketplace.show', $listing) }}"
                            class="glass-card glass-card-interactive flex flex-col justify-between overflow-hidden group border border-white/80 hover:border-indigo-300 transition-all duration-300 cursor-pointer text-left block"
                            data-cy="listing-card-{{ $listing->listing_id }}"
                        >
                            <!-- Image Header / Fallback Cover -->
                            <div class="relative aspect-[16/10] bg-gradient-to-tr from-slate-100 via-slate-50 to-indigo-50/40 border-b border-slate-100 flex items-center justify-center overflow-hidden">
                                @if ($listing->cover_image_url)
                                    <img
                                        src="{{ $listing->cover_image_url }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex flex-col items-center justify-center text-slate-400 p-3 text-center">
                                        <svg class="w-8 h-8 mb-1 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider truncate max-w-[140px]">
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
                            <div class="p-3.5 flex-1 flex flex-col justify-between space-y-2.5">
                                <div>
                                    <div class="text-[11px] font-medium text-slate-500 mb-0.5 flex items-center gap-1">
                                        <span class="truncate max-w-[120px]">{{ $listing->variant?->product?->product_name }}</span>
                                        <span>&bull;</span>
                                        <span class="text-indigo-600 font-semibold truncate max-w-[100px]">{{ $listing->variant?->variant_name }}</span>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600 transition-colors" data-cy="listing-card-title-{{ $listing->listing_id }}">
                                        {{ $listing->title }}
                                    </h3>
                                    <p class="text-xs text-slate-500 line-clamp-1 mt-1 leading-relaxed">
                                        {{ $listing->description }}
                                    </p>
                                </div>

                                <!-- Price & Seller Footer -->
                                <div class="pt-2.5 border-t border-slate-100 flex items-end justify-between gap-2">
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Harga</span>
                                        <span class="text-base font-extrabold text-slate-900" data-cy="listing-card-price-{{ $listing->listing_id }}">
                                            Rp {{ number_format($listing->buyer_total, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] font-semibold text-slate-700 truncate max-w-[100px]">
                                            @<span>{{ $listing->seller?->username }}</span>
                                        </div>
                                        <div class="text-[10px] text-emerald-600 font-medium">
                                            ✓ {{ $listing->seller?->seller_transactions_count ?? 0 }} Terjual
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Action Button -->
                            <div class="px-3.5 pb-3.5 pt-0.5">
                                <span
                                    class="w-full glass-btn-primary py-1.5 text-xs font-semibold flex items-center justify-center gap-1.5 group-hover:bg-indigo-700 transition"
                                    data-cy="btn-view-listing-{{ $listing->listing_id }}"
                                >
                                    <span>Lihat Rincian</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination (Warna Putih Elegan) -->
                <div class="mt-8 flex justify-center" data-cy="marketplace-pagination">
                    {{ $listings->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <!-- Empty State -->
                <div class="glass-card p-10 text-center max-w-xl mx-auto border border-white/80" data-cy="marketplace-empty-state">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3 border border-indigo-100 shadow-2xs">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 14v-4m0 0l-2 2m2-2l2 2"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1.5">Tidak Ada Listing yang Sesuai</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mb-5 leading-relaxed">
                        Kami tidak menemukan penawaran produk digital yang cocok dengan filter atau kata kunci pencarian Anda. Coba gunakan kata kunci lain atau reset filter.
                    </p>
                    <a href="{{ route('marketplace.index') }}" class="glass-btn-secondary px-4 py-2 text-xs sm:text-sm font-semibold inline-flex items-center gap-1.5" data-cy="btn-clear-search">
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
let activeCatId = null;
let activeProdId = null;

function toggleProductDropdown() {
    const panel = document.getElementById('dropdown-produk-panel');
    const chevron = document.getElementById('icon-chevron-produk');
    const btn = document.getElementById('btn-toggle-produk-filter');

    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        chevron.classList.add('rotate-180');
        btn.setAttribute('aria-expanded', 'true');
    } else {
        closeProductDropdown();
    }
}

function closeProductDropdown() {
    const panel = document.getElementById('dropdown-produk-panel');
    const chevron = document.getElementById('icon-chevron-produk');
    const btn = document.getElementById('btn-toggle-produk-filter');
    if (panel) panel.classList.add('hidden');
    if (chevron) chevron.classList.remove('rotate-180');
    if (btn) btn.setAttribute('aria-expanded', 'false');
}

function showCategoryProducts(catId) {
    activeCatId = catId;
    // Highlight category button
    document.querySelectorAll('.cat-item-btn').forEach(btn => {
        if (btn.getAttribute('data-cat-id') == catId) {
            btn.classList.add('bg-indigo-50', 'text-indigo-700', 'font-bold');
        } else {
            btn.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-bold');
        }
    });

    // Hide all product groups
    document.querySelectorAll('.cat-product-group').forEach(el => el.classList.add('hidden'));
    const placeholder = document.getElementById('products-list-placeholder');
    if (placeholder) placeholder.classList.add('hidden');

    const targetGroup = document.getElementById('cat-products-' + catId);
    if (targetGroup) targetGroup.classList.remove('hidden');

    // Reset variant column
    document.querySelectorAll('.prod-variant-group').forEach(el => el.classList.add('hidden'));
    const varPlaceholder = document.getElementById('variants-list-placeholder');
    if (varPlaceholder) varPlaceholder.classList.remove('hidden');
}

function showProductVariants(prodId) {
    activeProdId = prodId;
    // Highlight product button
    document.querySelectorAll('.prod-item-btn').forEach(btn => {
        if (btn.getAttribute('data-prod-id') == prodId) {
            btn.classList.add('bg-indigo-50', 'text-indigo-700', 'font-bold');
        } else {
            btn.classList.remove('bg-indigo-50', 'text-indigo-700', 'font-bold');
        }
    });

    // Hide all variant groups
    document.querySelectorAll('.prod-variant-group').forEach(el => el.classList.add('hidden'));
    const placeholder = document.getElementById('variants-list-placeholder');
    if (placeholder) placeholder.classList.add('hidden');

    const targetGroup = document.getElementById('prod-variants-' + prodId);
    if (targetGroup) targetGroup.classList.remove('hidden');
}

function selectCategoryFilter(catId, catName) {
    document.getElementById('hidden-category-id').value = catId;
    document.getElementById('hidden-product-id').value = '';
    document.getElementById('hidden-variant-id').value = '';
    closeProductDropdown();
    document.getElementById('marketplace-search-form').submit();
}

function selectProductFilter(catId, prodId, prodName) {
    document.getElementById('hidden-category-id').value = catId;
    document.getElementById('hidden-product-id').value = prodId;
    document.getElementById('hidden-variant-id').value = '';
    closeProductDropdown();
    document.getElementById('marketplace-search-form').submit();
}

function selectVariantFilter(catId, prodId, varId, label) {
    document.getElementById('hidden-category-id').value = catId;
    document.getElementById('hidden-product-id').value = prodId;
    document.getElementById('hidden-variant-id').value = varId;
    closeProductDropdown();
    document.getElementById('marketplace-search-form').submit();
}

function clearProductFilter() {
    document.getElementById('hidden-category-id').value = '';
    document.getElementById('hidden-product-id').value = '';
    document.getElementById('hidden-variant-id').value = '';
    document.getElementById('marketplace-search-form').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('btn-toggle-produk-filter');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleProductDropdown();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        const panel = document.getElementById('dropdown-produk-panel');
        const toggleBtn = document.getElementById('btn-toggle-produk-filter');
        if (panel && !panel.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
            closeProductDropdown();
        }
    });
});
</script>
@endsection
