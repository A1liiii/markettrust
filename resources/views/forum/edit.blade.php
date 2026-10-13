@extends('layouts.app')

@section('title', 'Edit Postingan - ' . $post->title)

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>/</span>
            <a href="{{ route('forum.index') }}" class="hover:text-indigo-600 transition">Forum</a>
            <span>/</span>
            <a href="{{ route('forum.show', $post->post_id) }}" class="hover:text-indigo-600 transition truncate max-w-xs">{{ $post->title }}</a>
            <span>/</span>
            <span class="text-slate-800">Edit</span>
        </nav>

        <div class="glass-panel p-6 sm:p-10">
            <!-- Header -->
            <div class="mb-8 pb-5 border-b border-slate-200/60 text-center sm:text-left">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 mb-2 border border-blue-500/20 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                    Edit Postingan Forum
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Perbarui judul, isi, kategori, atau lampiran foto pada postingan Anda.
                </p>
            </div>

            <!-- Form -->
            <form action="{{ route('forum.update', $post->post_id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- TC-FR-12: Pilihan Kategori Tiga Tombol Biru di Tengah -->
                <div>
                    <label class="glass-label text-center block mb-2.5">
                        Pilih Kategori Postingan <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex justify-center">
                        <div class="inline-flex p-1.5 bg-blue-50 rounded-2xl border border-blue-200 shadow-inner gap-1.5 sm:gap-2">
                            <button type="button"
                                    onclick="selectCategory('GENERAL')"
                                    id="btn-cat-GENERAL"
                                    class="category-btn px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200">
                                Umum
                            </button>
                            <button type="button"
                                    onclick="selectCategory('WTB')"
                                    id="btn-cat-WTB"
                                    class="category-btn px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200">
                                WTB (Beli)
                            </button>
                            <button type="button"
                                    onclick="selectCategory('WTS')"
                                    id="btn-cat-WTS"
                                    class="category-btn px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200">
                                WTS (Jual)
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="category_code" id="category_code" value="{{ old('category_code', $post->category_code) }}">
                </div>

                <!-- TC-FR-06: Pilihan Katalog Bertingkat pada Form WTB/WTS -->
                @php
                    $initCategory = old('category_code', $post->category_code);
                @endphp
                <div id="catalog-section" class="{{ in_array($initCategory, ['WTB', 'WTS']) ? '' : 'hidden' }} space-y-4 p-4 sm:p-5 rounded-2xl bg-blue-50/40 border border-blue-200/70">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Katalog Produk & Varian (Wajib untuk WTB/WTS)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Step 1: Kategori Produk -->
                        <div>
                            <label for="catalog_category_id" class="glass-label text-[11px]">1. Kategori Produk</label>
                            <select id="catalog_category_id" onchange="handleCatalogCategoryChange(this.value)" class="glass-input w-full px-3 py-2 text-xs">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->category_id }}">
                                        {{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Step 2: Produk -->
                        <div>
                            <label for="catalog_product_id" class="glass-label text-[11px]">2. Produk</label>
                            <select id="catalog_product_id" onchange="handleCatalogProductChange(this.value)" class="glass-input w-full px-3 py-2 text-xs" disabled>
                                <option value="">-- Pilih Produk Dulu --</option>
                            </select>
                        </div>

                        <!-- Step 3: Varian Produk (Nilai yang disimpan ke DB) -->
                        <div>
                            <label for="variant_id" class="glass-label text-[11px]">3. Varian Produk <span class="text-rose-500">*</span></label>
                            <select name="variant_id" id="variant_id" class="glass-input w-full px-3 py-2 text-xs" disabled>
                                <option value="">-- Pilih Varian --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="glass-label">Judul Postingan <span class="text-rose-500">*</span></label>
                    <input type="text"
                           id="title"
                           name="title"
                           value="{{ old('title', $post->title) }}"
                           required
                           maxlength="150"
                           class="glass-input w-full px-3.5 py-2.5 text-sm">
                </div>

                <!-- Price Offer (Optional) -->
                <div>
                    <label for="price_offer" class="glass-label">Tawaran Harga (Rp) <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input type="number"
                           id="price_offer"
                           name="price_offer"
                           value="{{ old('price_offer', $post->price_offer) }}"
                           min="0"
                           step="1000"
                           class="glass-input w-full px-3.5 py-2.5 text-sm">
                </div>

                <!-- Content -->
                <div>
                    <label for="content" class="glass-label">Isi Postingan / Deskripsi <span class="text-rose-500">*</span></label>
                    <textarea id="content"
                              name="content"
                              rows="6"
                              required
                              class="glass-input w-full px-3.5 py-2.5 text-sm leading-relaxed">{{ old('content', $post->content) }}</textarea>
                </div>

                <!-- Existing Images Management -->
                @if (!empty($post->image_urls) && count($post->image_urls) > 0)
                    <div>
                        <label class="glass-label">Foto Lampiran Eksisting</label>
                        <p class="text-[11px] text-slate-500 mb-2">Hilangkan centang pada foto yang ingin dihapus dari postingan.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ($post->image_urls as $idx => $url)
                                <div class="relative rounded-xl overflow-hidden border border-slate-200/80 bg-white p-2">
                                    <img src="{{ $url }}" alt="Foto {{ $idx + 1 }}" class="w-full h-24 object-cover rounded-lg mb-2">
                                    <label class="flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer select-none">
                                        <input type="checkbox" name="keep_images[]" value="{{ $url }}" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500/20">
                                        <span>Pertahankan</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Upload Additional Images (TC-FR-13: Preview Foto) -->
                <div>
                    <label class="glass-label">
                        Tambah / Ganti Foto Lampiran <span class="text-slate-400 font-normal lowercase">(total foto maksimal 4 file, format JPG/PNG/WEBP, per file maks 3 MB)</span>
                    </label>
                    <div class="glass-card p-4 border-dashed border-2 border-slate-300/80 rounded-xl">
                        <input type="file"
                               name="images[]"
                               id="images"
                               multiple
                               accept="image/jpeg,image/png,image/webp"
                               onchange="handleImagePreview(event)"
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-2">
                            <span>Format: JPG, PNG, WEBP</span>
                            <span>&bull;</span>
                            <span>Maksimal 3 MB per foto</span>
                            <span>&bull;</span>
                            <span>Maksimal total 4 foto per postingan</span>
                        </div>
                    </div>

                    <!-- Container Thumbnail Preview (TC-FR-13) -->
                    <div id="image-preview-container" class="hidden mt-3">
                        <p class="text-xs font-semibold text-slate-700 mb-2">Pratinjau Foto Baru yang Ditambahkan:</p>
                        <div id="preview-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-4 border-t border-slate-200/60 flex items-center justify-end gap-3">
                    <a href="{{ route('forum.show', $post->post_id) }}" class="glass-btn-secondary text-sm">
                        Batal
                    </a>
                    <button type="submit" class="glass-btn-primary text-sm shadow-md shadow-blue-500/20 bg-blue-600 hover:bg-blue-700 text-white">
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const catalogData = @json($categories);
    const activeVariantId = "{{ old('variant_id', $post->variant_id) }}";

    // TC-FR-12: Pilihan Kategori Gaya Biru di Tengah
    function selectCategory(category) {
        document.getElementById('category_code').value = category;

        ['GENERAL', 'WTB', 'WTS'].forEach(cat => {
            const btn = document.getElementById('btn-cat-' + cat);
            if (cat === category) {
                btn.className = 'category-btn px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200 bg-blue-600 text-white shadow-md';
            } else {
                btn.className = 'category-btn px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200 text-blue-900/70 hover:text-blue-950 hover:bg-blue-100/60';
            }
        });

        // Toggle Catalog Dropdown (TC-FR-06)
        const catalogSection = document.getElementById('catalog-section');
        const variantSelect = document.getElementById('variant_id');
        if (category === 'WTB' || category === 'WTS') {
            catalogSection.classList.remove('hidden');
            variantSelect.setAttribute('required', 'required');
        } else {
            catalogSection.classList.add('hidden');
            variantSelect.removeAttribute('required');
        }
    }

    // Inisialisasi saat load
    document.addEventListener('DOMContentLoaded', () => {
        selectCategory(document.getElementById('category_code').value || 'GENERAL');

        if (activeVariantId) {
            restoreCatalogSelection(activeVariantId);
        }
    });

    // TC-FR-06: Cascading Catalog Dropdown
    function handleCatalogCategoryChange(catId) {
        const productSelect = document.getElementById('catalog_product_id');
        const variantSelect = document.getElementById('variant_id');

        productSelect.innerHTML = '<option value="">-- Pilih Produk --</option>';
        variantSelect.innerHTML = '<option value="">-- Pilih Varian --</option>';
        productSelect.disabled = true;
        variantSelect.disabled = true;

        if (!catId) return;

        const category = catalogData.find(c => String(c.category_id) === String(catId));
        if (category && category.products && category.products.length > 0) {
            category.products.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.product_id;
                opt.textContent = p.product_name;
                productSelect.appendChild(opt);
            });
            productSelect.disabled = false;
        }
    }

    function handleCatalogProductChange(prodId) {
        const catId = document.getElementById('catalog_category_id').value;
        const variantSelect = document.getElementById('variant_id');

        variantSelect.innerHTML = '<option value="">-- Pilih Varian --</option>';
        variantSelect.disabled = true;

        if (!prodId || !catId) return;

        const category = catalogData.find(c => String(c.category_id) === String(catId));
        if (!category) return;

        const product = category.products.find(p => String(p.product_id) === String(prodId));
        if (product && product.variants && product.variants.length > 0) {
            product.variants.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.variant_id;
                opt.textContent = v.variant_name;
                variantSelect.appendChild(opt);
            });
            variantSelect.disabled = false;
        }
    }

    function restoreCatalogSelection(variantIdToFind) {
        for (const cat of catalogData) {
            for (const prod of (cat.products || [])) {
                for (const v of (prod.variants || [])) {
                    if (String(v.variant_id) === String(variantIdToFind)) {
                        document.getElementById('catalog_category_id').value = cat.category_id;
                        handleCatalogCategoryChange(cat.category_id);
                        document.getElementById('catalog_product_id').value = prod.product_id;
                        handleCatalogProductChange(prod.product_id);
                        document.getElementById('variant_id').value = v.variant_id;
                        return;
                    }
                }
            }
        }
    }

    // TC-FR-13: Preview Foto Instan
    function handleImagePreview(event) {
        const container = document.getElementById('image-preview-container');
        const grid = document.getElementById('preview-grid');
        const files = Array.from(event.target.files);

        grid.innerHTML = '';

        if (files.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');

        files.slice(0, 4).forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-video shadow-xs';

            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.src = URL.createObjectURL(file);

            const badge = document.createElement('span');
            badge.className = 'absolute bottom-1 right-1 bg-slate-900/80 text-white text-[9px] font-bold px-1.5 py-0.5 rounded backdrop-blur-xs';
            badge.textContent = '+' + (index + 1);

            card.appendChild(img);
            card.appendChild(badge);
            grid.appendChild(card);
        });
    }
</script>
@endsection
