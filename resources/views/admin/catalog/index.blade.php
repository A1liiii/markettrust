@extends('layouts.admin')

@section('title', 'Kelola Master Katalog - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb & Page Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Master Data</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Kelola Katalog Digital
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    Struktur hierarki terpadu untuk Kategori, Identitas Produk, dan Spesifikasi Varian.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @if ($currentTab === 'categories')
                    <button type="button" onclick="openModal('modal-add-category')" class="glass-btn-primary text-xs sm:text-sm py-2 px-3.5 shadow-md shadow-indigo-500/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Kategori
                    </button>
                @elseif ($currentTab === 'products')
                    <button type="button" onclick="openModal('modal-add-product')" class="glass-btn-primary text-xs sm:text-sm py-2 px-3.5 shadow-md shadow-indigo-500/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Produk
                    </button>
                @else
                    <button type="button" onclick="openModal('modal-add-variant')" class="glass-btn-primary text-xs sm:text-sm py-2 px-3.5 shadow-md shadow-indigo-500/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Varian
                    </button>
                @endif
            </div>
        </div>

        <!-- Metric Stat Cards (TC-CAT-01) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="glass-card p-5 sm:p-6 flex items-center justify-between h-full shadow-xs">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori Produk</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">{{ $categories->total() }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $allActiveCategories->count() }} kategori aktif</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold border border-indigo-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
            </div>

            <div class="glass-card p-5 sm:p-6 flex items-center justify-between h-full shadow-xs">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Identitas Produk</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">{{ $products->total() }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $allActiveProducts->count() }} produk aktif</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-violet-500/10 text-violet-600 flex items-center justify-center font-bold border border-violet-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>

            <div class="glass-card p-5 sm:p-6 flex items-center justify-between h-full shadow-xs">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Spesifikasi Varian</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">{{ $variants->total() }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Siap digunakan listing seller</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold border border-emerald-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Main Glass Panel (Tabs + Search + Table) -->
        <div class="glass-panel overflow-hidden border border-white/80">
            <!-- Navigation Tabs & Search Toolbar (TC-CAT-02) -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200/70 bg-white/40 px-4 sm:px-6 pt-3 pb-2.5">
                <nav class="flex space-x-2 sm:space-x-6 -mb-px overflow-x-auto" aria-label="Tabs">
                    <a href="{{ route('admin.catalog.index', ['tab' => 'categories']) }}"
                       class="py-2.5 px-3 text-xs sm:text-sm font-semibold border-b-2 transition flex items-center gap-2 shrink-0 {{ $currentTab === 'categories' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        <span>Kategori</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $currentTab === 'categories' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200/70 text-slate-600' }}">
                            {{ $categories->total() }}
                        </span>
                    </a>

                    <a href="{{ route('admin.catalog.index', ['tab' => 'products']) }}"
                       class="py-2.5 px-3 text-xs sm:text-sm font-semibold border-b-2 transition flex items-center gap-2 shrink-0 {{ $currentTab === 'products' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        <span>Identitas Produk</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $currentTab === 'products' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200/70 text-slate-600' }}">
                            {{ $products->total() }}
                        </span>
                    </a>

                    <a href="{{ route('admin.catalog.index', ['tab' => 'variants']) }}"
                       class="py-2.5 px-3 text-xs sm:text-sm font-semibold border-b-2 transition flex items-center gap-2 shrink-0 {{ $currentTab === 'variants' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        <span>Varian Produk</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $currentTab === 'variants' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200/70 text-slate-600' }}">
                            {{ $variants->total() }}
                        </span>
                    </a>
                </nav>

                <!-- Search Input per Tab (Konsisten di Desktop & Mobile) -->
                @php
                    $tabLabels = [
                        'categories' => 'kategori',
                        'products' => 'identitas produk',
                        'variants' => 'spesifikasi varian',
                    ];
                    $currentPlaceholder = $tabLabels[$currentTab] ?? 'katalog';
                @endphp
                <form action="{{ route('admin.catalog.index') }}" method="GET" class="w-full md:w-auto">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <div class="relative w-full md:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Cari {{ $currentPlaceholder }}..."
                               class="glass-input text-xs sm:text-sm py-1.5 pl-9 pr-3 w-full">
                    </div>
                </form>
            </div>

            <!-- TAB 1: KATEGORI PRODUK -->
            @if ($currentTab === 'categories')
                <div class="p-4 sm:p-6">
                    @if ($categories->isEmpty())
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900">Belum ada kategori produk</h3>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tambahkan kategori pertama untuk mulai mengelompokkan produk digital.</p>
                            <button type="button" onclick="openModal('modal-add-category')" class="glass-btn-primary text-xs mt-4 py-2 px-4">
                                Tambah Kategori Baru
                            </button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200/60 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                        <th class="py-3 px-3">Nama Kategori</th>
                                        <th class="py-3 px-3">Deskripsi</th>
                                        <th class="py-3 px-3">Jumlah Produk</th>
                                        <th class="py-3 px-3">Status</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($categories as $cat)
                                        <tr class="hover:bg-white/60 transition">
                                            <td class="py-3.5 px-3 font-semibold text-slate-900">
                                                {{ $cat->category_name }}
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-600 max-w-xs truncate">
                                                {{ $cat->description ?: '-' }}
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-700">
                                                <span class="font-medium">{{ $cat->products_count }}</span> produk
                                            </td>
                                            <td class="py-3.5 px-3">
                                                <span class="glass-badge {{ $cat->is_active ? 'glass-badge-success' : 'glass-badge-danger' }} text-[10px] py-0.5 px-2">
                                                    {{ $cat->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-right space-x-1">
                                                <button type="button"
                                                        onclick="openEditCategoryModal({{ $cat->category_id }}, '{{ addslashes($cat->category_name) }}', '{{ addslashes($cat->description ?? '') }}', {{ $cat->is_active ? 'true' : 'false' }})"
                                                        class="glass-btn-secondary text-xs py-1 px-2.5">
                                                    Edit
                                                </button>

                                                <form action="{{ route('admin.catalog.categories.toggle', $cat->category_id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-xs py-1 px-2.5 rounded-lg border transition {{ $cat->is_active ? 'border-amber-300 text-amber-700 bg-amber-50/60 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100' }}">
                                                        {{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $categories->links() }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- TAB 2: IDENTITAS PRODUK -->
            @if ($currentTab === 'products')
                <div class="p-4 sm:p-6">
                    @if ($products->isEmpty())
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900">Belum ada identitas produk</h3>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tambahkan produk pertama yang terhubung ke kategori yang aktif.</p>
                            <button type="button" onclick="openModal('modal-add-product')" class="glass-btn-primary text-xs mt-4 py-2 px-4">
                                Tambah Produk Baru
                            </button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200/60 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                        <th class="py-3 px-3">Nama Produk</th>
                                        <th class="py-3 px-3">Kategori</th>
                                        <th class="py-3 px-3">Deskripsi</th>
                                        <th class="py-3 px-3">Jumlah Varian</th>
                                        <th class="py-3 px-3">Status</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($products as $prod)
                                        <tr class="hover:bg-white/60 transition">
                                            <td class="py-3.5 px-3 font-semibold text-slate-900">
                                                {{ $prod->product_name }}
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-700">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                                    {{ $prod->category->category_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-600 max-w-xs truncate">
                                                {{ $prod->description ?: '-' }}
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-700">
                                                <span class="font-medium">{{ $prod->variants_count }}</span> varian
                                            </td>
                                            <td class="py-3.5 px-3">
                                                <span class="glass-badge {{ $prod->is_active ? 'glass-badge-success' : 'glass-badge-danger' }} text-[10px] py-0.5 px-2">
                                                    {{ $prod->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-right space-x-1">
                                                <button type="button"
                                                        onclick="openEditProductModal({{ $prod->product_id }}, {{ $prod->category_id }}, '{{ addslashes($prod->product_name) }}', '{{ addslashes($prod->description ?? '') }}', {{ $prod->is_active ? 'true' : 'false' }})"
                                                        class="glass-btn-secondary text-xs py-1 px-2.5">
                                                    Edit
                                                </button>

                                                <form action="{{ route('admin.catalog.products.toggle', $prod->product_id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-xs py-1 px-2.5 rounded-lg border transition {{ $prod->is_active ? 'border-amber-300 text-amber-700 bg-amber-50/60 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100' }}">
                                                        {{ $prod->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- TAB 3: VARIAN PRODUK -->
            @if ($currentTab === 'variants')
                <div class="p-4 sm:p-6">
                    @if ($variants->isEmpty())
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900">Belum ada varian produk</h3>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tambahkan spesifikasi varian pertama agar seller dapat membuat penawaran listing.</p>
                            <button type="button" onclick="openModal('modal-add-variant')" class="glass-btn-primary text-xs mt-4 py-2 px-4">
                                Tambah Varian Baru
                            </button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200/60 text-slate-500 uppercase tracking-wider text-[11px] font-bold">
                                        <th class="py-3 px-3">Nama Varian</th>
                                        <th class="py-3 px-3">Produk</th>
                                        <th class="py-3 px-3">Kategori</th>
                                        <th class="py-3 px-3">Jumlah Listing</th>
                                        <th class="py-3 px-3">Status</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($variants as $var)
                                        <tr class="hover:bg-white/60 transition">
                                            <td class="py-3.5 px-3 font-semibold text-slate-900">
                                                {{ $var->variant_name }}
                                            </td>
                                            <td class="py-3.5 px-3 font-medium text-slate-800">
                                                {{ $var->product->product_name ?? '-' }}
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-600">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ $var->product->category->category_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-slate-700">
                                                <span class="font-medium">{{ $var->listings_count }}</span> listing
                                            </td>
                                            <td class="py-3.5 px-3">
                                                <span class="glass-badge {{ $var->is_active ? 'glass-badge-success' : 'glass-badge-danger' }} text-[10px] py-0.5 px-2">
                                                    {{ $var->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-3 text-right space-x-1">
                                                <button type="button"
                                                        onclick="openEditVariantModal({{ $var->variant_id }}, {{ $var->product_id }}, '{{ addslashes($var->variant_name) }}', {{ $var->is_active ? 'true' : 'false' }})"
                                                        class="glass-btn-secondary text-xs py-1 px-2.5">
                                                    Edit
                                                </button>

                                                <form action="{{ route('admin.catalog.variants.toggle', $var->variant_id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-xs py-1 px-2.5 rounded-lg border transition {{ $var->is_active ? 'border-amber-300 text-amber-700 bg-amber-50/60 hover:bg-amber-100' : 'border-emerald-300 text-emerald-700 bg-emerald-50/60 hover:bg-emerald-100' }}">
                                                        {{ $var->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $variants->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL SECTION -->
<!-- ========================================================================= -->

<!-- 1. Modal Tambah Kategori -->
<div id="modal-add-category" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Tambah Kategori Baru</h3>
            <button type="button" onclick="closeModal('modal-add-category')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.catalog.categories.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="glass-label">Nama Kategori <span class="text-rose-500">*</span></label>
                <input type="text" name="category_name" required maxlength="100" placeholder="Contoh: Akun Game, Lisensi Software" class="glass-input w-full text-sm">
            </div>

            <div>
                <label class="glass-label">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Deskripsi jenis produk dalam kategori ini..." class="glass-input w-full text-sm"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="add-cat-active" name="is_active" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="add-cat-active" class="text-xs font-semibold text-slate-700">Aktifkan kategori ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-add-category')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Edit Kategori -->
<div id="modal-edit-category" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Edit Kategori</h3>
            <button type="button" onclick="closeModal('modal-edit-category')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-edit-category" action="" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="glass-label">Nama Kategori <span class="text-rose-500">*</span></label>
                <input type="text" id="edit-cat-name" name="category_name" required maxlength="100" class="glass-input w-full text-sm">
            </div>

            <div>
                <label class="glass-label">Deskripsi</label>
                <textarea id="edit-cat-desc" name="description" rows="3" class="glass-input w-full text-sm"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit-cat-active" name="is_active" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="edit-cat-active" class="text-xs font-semibold text-slate-700">Aktifkan kategori ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-edit-category')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Perbarui Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Tambah Produk -->
<div id="modal-add-product" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Tambah Produk Master Baru</h3>
            <button type="button" onclick="closeModal('modal-add-product')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.catalog.products.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="glass-label">Kategori <span class="text-rose-500">*</span></label>
                <select name="category_id" required class="glass-input w-full text-sm">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($allActiveCategories as $cat)
                        <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="glass-label">Nama Produk <span class="text-rose-500">*</span></label>
                <input type="text" name="product_name" required maxlength="150" placeholder="Contoh: Mobile Legends, Valorant, Windows 11" class="glass-input w-full text-sm">
            </div>

            <div>
                <label class="glass-label">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Informasi umum produk master..." class="glass-input w-full text-sm"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="add-prod-active" name="is_active" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="add-prod-active" class="text-xs font-semibold text-slate-700">Aktifkan produk ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-add-product')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Edit Produk -->
<div id="modal-edit-product" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Edit Produk Master</h3>
            <button type="button" onclick="closeModal('modal-edit-product')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-edit-product" action="" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="glass-label">Kategori <span class="text-rose-500">*</span></label>
                <select id="edit-prod-category" name="category_id" required class="glass-input w-full text-sm">
                    @foreach ($allActiveCategories as $cat)
                        <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="glass-label">Nama Produk <span class="text-rose-500">*</span></label>
                <input type="text" id="edit-prod-name" name="product_name" required maxlength="150" class="glass-input w-full text-sm">
            </div>

            <div>
                <label class="glass-label">Deskripsi</label>
                <textarea id="edit-prod-desc" name="description" rows="3" class="glass-input w-full text-sm"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit-prod-active" name="is_active" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="edit-prod-active" class="text-xs font-semibold text-slate-700">Aktifkan produk ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-edit-product')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Perbarui Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal Tambah Varian -->
<div id="modal-add-variant" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Tambah Varian Baru</h3>
            <button type="button" onclick="closeModal('modal-add-variant')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.catalog.variants.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="glass-label">Produk Master <span class="text-rose-500">*</span></label>
                <select name="product_id" required class="glass-input w-full text-sm">
                    <option value="">-- Pilih Produk Master --</option>
                    @foreach ($allActiveProducts as $prod)
                        <option value="{{ $prod->product_id }}">{{ $prod->product_name }} ({{ $prod->category->category_name ?? '' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="glass-label">Nama Varian <span class="text-rose-500">*</span></label>
                <input type="text" name="variant_name" required maxlength="100" placeholder="Contoh: Akun Rank Mythic, 1000 Diamond, Lisensi Pro" class="glass-input w-full text-sm">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="add-var-active" name="is_active" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="add-var-active" class="text-xs font-semibold text-slate-700">Aktifkan varian ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-add-variant')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Simpan Varian</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Modal Edit Varian -->
<div id="modal-edit-variant" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel max-w-md w-full p-6 bg-white/95 border border-white/90 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Edit Varian</h3>
            <button type="button" onclick="closeModal('modal-edit-variant')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-edit-variant" action="" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="glass-label">Produk Master <span class="text-rose-500">*</span></label>
                <select id="edit-var-product" name="product_id" required class="glass-input w-full text-sm">
                    @foreach ($allActiveProducts as $prod)
                        <option value="{{ $prod->product_id }}">{{ $prod->product_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="glass-label">Nama Varian <span class="text-rose-500">*</span></label>
                <input type="text" id="edit-var-name" name="variant_name" required maxlength="100" class="glass-input w-full text-sm">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit-var-active" name="is_active" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="edit-var-active" class="text-xs font-semibold text-slate-700">Aktifkan varian ini</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('modal-edit-variant')" class="glass-btn-secondary text-xs py-2 px-3.5">Batal</button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-4">Perbarui Varian</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId)?.classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId)?.classList.add('hidden');
    }

    function openEditCategoryModal(id, name, desc, isActive) {
        const form = document.getElementById('form-edit-category');
        form.action = "{{ url('admin/catalog/categories') }}/" + id;
        document.getElementById('edit-cat-name').value = name;
        document.getElementById('edit-cat-desc').value = desc;
        document.getElementById('edit-cat-active').checked = isActive;
        openModal('modal-edit-category');
    }

    function openEditProductModal(id, catId, name, desc, isActive) {
        const form = document.getElementById('form-edit-product');
        form.action = "{{ url('admin/catalog/products') }}/" + id;
        document.getElementById('edit-prod-category').value = catId;
        document.getElementById('edit-prod-name').value = name;
        document.getElementById('edit-prod-desc').value = desc;
        document.getElementById('edit-prod-active').checked = isActive;
        openModal('modal-edit-product');
    }

    function openEditVariantModal(id, prodId, name, isActive) {
        const form = document.getElementById('form-edit-variant');
        form.action = "{{ url('admin/catalog/variants') }}/" + id;
        document.getElementById('edit-var-product').value = prodId;
        document.getElementById('edit-var-name').value = name;
        document.getElementById('edit-var-active').checked = isActive;
        openModal('modal-edit-variant');
    }
</script>
@endsection
