@extends('layouts.app')

@section('title', 'Buat Postingan Baru - Forum MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>/</span>
            <a href="{{ route('forum.index') }}" class="hover:text-indigo-600 transition">Forum</a>
            <span>/</span>
            <span class="text-slate-800">Buat Postingan</span>
        </nav>

        <div class="glass-panel p-6 sm:p-10">
            <!-- Header -->
            <div class="mb-8 pb-5 border-b border-slate-200/60 text-center sm:text-left">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 mb-2 border border-blue-500/20 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                    Buat Postingan Forum Baru
                </h1>
                <p id="form-subtitle" class="text-xs sm:text-sm text-slate-600 mt-1">
                    Bagikan diskusi umum atau ajukan penawaran jual/beli produk digital terverifikasi.
                </p>
            </div>

            <!-- Form -->
            <form action="{{ route('forum.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

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
                    <input type="hidden" name="category_code" id="category_code" value="{{ old('category_code', request()->query('category', 'GENERAL')) }}">
                </div>

                <!-- Discussion Type Selector for Forum Umum (GENERAL) -->
                <div id="discussion-type-section" class="space-y-2.5 p-4 rounded-2xl bg-blue-50/50 border border-blue-200/70">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <label class="glass-label text-xs font-bold text-slate-800 mb-0">
                            Jenis Diskusi <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <span class="text-[11px] text-slate-500">Pilih jenis untuk mempermudah anggota komunitas memahami topik</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button"
                                onclick="toggleDiscussionType('Pertanyaan')"
                                id="dtype-Pertanyaan"
                                class="dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-semibold transition bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/50">
                            <span>❓</span>
                            <span>Pertanyaan</span>
                        </button>
                        <button type="button"
                                onclick="toggleDiscussionType('Berbagi Pengalaman')"
                                id="dtype-Berbagi-Pengalaman"
                                class="dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-semibold transition bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/50">
                            <span>💡</span>
                            <span>Pengalaman</span>
                        </button>
                        <button type="button"
                                onclick="toggleDiscussionType('Tips')"
                                id="dtype-Tips"
                                class="dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-semibold transition bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/50">
                            <span>📌</span>
                            <span>Tips</span>
                        </button>
                        <button type="button"
                                onclick="toggleDiscussionType('Opini')"
                                id="dtype-Opini"
                                class="dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-semibold transition bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/50">
                            <span>💬</span>
                            <span>Opini</span>
                        </button>
                    </div>
                    <input type="hidden" name="discussion_type" id="discussion_type" value="">
                </div>

                <!-- TC-FR-06: Pilihan Katalog Bertingkat pada Form WTB/WTS -->
                <div id="catalog-section" class="{{ in_array(old('category_code', request()->query('category', 'GENERAL')), ['WTB', 'WTS']) ? '' : 'hidden' }} space-y-4 p-4 sm:p-5 rounded-2xl bg-blue-50/40 border border-blue-200/70">
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
                    <p class="text-[11px] text-slate-500">
                        Varian produk wajib dipilih secara berurutan agar transaksi penawaran dapat diproses secara akurat.
                    </p>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" id="title-label" class="glass-label">Judul Diskusi <span class="text-rose-500">*</span></label>
                    <input type="text"
                           id="title"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           maxlength="150"
                           placeholder="Apa yang ingin kamu diskusikan?"
                           class="glass-input w-full px-3.5 py-2.5 text-sm">
                    <p id="title-helper" class="text-[11px] text-slate-500 mt-1">
                        Tuliskan pertanyaan atau topik yang jelas dan santun agar mudah dipahami komunitas.
                    </p>
                </div>

                <!-- Price Offer (Hanya untuk WTB/WTS, disembunyikan untuk Forum Umum) -->
                <div id="price-section" class="hidden">
                    <label for="price_offer" class="glass-label">Tawaran Harga (Rp) <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input type="number"
                           id="price_offer"
                           name="price_offer"
                           value="{{ old('price_offer') }}"
                           min="0"
                           step="1000"
                           placeholder="Contoh: 150000"
                           class="glass-input w-full px-3.5 py-2.5 text-sm">
                </div>

                <!-- Content -->
                <div>
                    <label for="content" id="content-label" class="glass-label">Isi Diskusi <span class="text-rose-500">*</span></label>
                    <textarea id="content"
                              name="content"
                              rows="6"
                              required
                              placeholder="Ceritakan pertanyaan, pengalaman, atau pendapatmu di sini."
                              class="glass-input w-full px-3.5 py-2.5 text-sm leading-relaxed">{{ old('content') }}</textarea>
                </div>

                <!-- TC-FR-13: Upload Foto & Preview Thumbnail Instan Sebelum Submit -->
                <div>
                    <label class="glass-label">
                        Unggah Foto Lampiran <span class="text-slate-400 font-normal lowercase">(opsional, maksimal 4 file, format JPG/PNG/WEBP, per file maks 3 MB)</span>
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
                            <span>Maksimal 4 foto per postingan</span>
                        </div>
                    </div>

                    <!-- Container Thumbnail Preview (TC-FR-13) -->
                    <div id="image-preview-container" class="hidden mt-3">
                        <p class="text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Pratinjau Foto Terpilih (<span id="preview-count">0</span>/4 foto):</span>
                        </p>
                        <div id="preview-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-4 border-t border-slate-200/60 flex items-center justify-end gap-3">
                    <a href="{{ route('forum.index') }}" class="glass-btn-secondary text-sm">
                        Batal
                    </a>
                    <button type="submit" id="submit-btn" class="glass-btn-primary text-sm shadow-md shadow-blue-500/20 bg-blue-600 hover:bg-blue-700 text-white">
                        <span id="submit-btn-text">Publikasikan Diskusi</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const catalogData = @json($categories);
    const oldVariantId = "{{ old('variant_id') }}";
    let currentDiscussionType = '';

    // Toggle Jenis Diskusi (TC-FR discussion chip selector)
    function toggleDiscussionType(type) {
        const inputTitle = document.getElementById('title');
        const knownTypes = ['Pertanyaan', 'Berbagi Pengalaman', 'Tips', 'Opini'];

        if (currentDiscussionType === type) {
            currentDiscussionType = '';
        } else {
            currentDiscussionType = type;
        }

        const dtypeHidden = document.getElementById('discussion_type');
        if (dtypeHidden) dtypeHidden.value = currentDiscussionType;

        knownTypes.forEach(t => {
            const btnId = 'dtype-' + t.replace(/\s+/g, '-');
            const btn = document.getElementById(btnId);
            if (btn) {
                if (t === currentDiscussionType) {
                    btn.className = 'dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-bold transition bg-blue-600 text-white border-blue-600 shadow-sm';
                } else {
                    btn.className = 'dtype-btn flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl border text-xs font-semibold transition bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/50';
                }
            }
        });

        if (inputTitle) {
            let val = inputTitle.value.trim();
            knownTypes.forEach(t => {
                const prefix = `[${t}]`;
                if (val.startsWith(prefix)) {
                    val = val.substring(prefix.length).trim();
                }
            });

            if (currentDiscussionType) {
                inputTitle.value = `[${currentDiscussionType}] ${val}`.trim();
            } else {
                inputTitle.value = val;
            }
        }
    }

    // TC-FR-12: Pilihan Kategori Gaya Biru di Tengah & Penyesuaian Form Dinamis
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

        const catalogSection = document.getElementById('catalog-section');
        const variantSelect = document.getElementById('variant_id');
        const priceSection = document.getElementById('price-section');
        const priceInput = document.getElementById('price_offer');
        const discussionTypeSection = document.getElementById('discussion-type-section');
        const titleLabel = document.getElementById('title-label');
        const titleInput = document.getElementById('title');
        const titleHelper = document.getElementById('title-helper');
        const contentLabel = document.getElementById('content-label');
        const contentInput = document.getElementById('content');
        const formSubtitle = document.getElementById('form-subtitle');
        const submitBtnText = document.getElementById('submit-btn-text');

        if (category === 'GENERAL') {
            // Mode Diskusi Forum Umum
            if (discussionTypeSection) discussionTypeSection.classList.remove('hidden');
            if (catalogSection) catalogSection.classList.add('hidden');
            if (variantSelect) variantSelect.removeAttribute('required');
            if (priceSection) priceSection.classList.add('hidden');
            if (priceInput) priceInput.value = '';

            if (titleLabel) titleLabel.innerHTML = 'Judul Diskusi <span class="text-rose-500">*</span>';
            if (titleInput) titleInput.placeholder = 'Apa yang ingin kamu diskusikan?';
            if (titleHelper) titleHelper.textContent = 'Tuliskan pertanyaan atau topik yang jelas dan santun agar mudah dipahami komunitas.';

            if (contentLabel) contentLabel.innerHTML = 'Isi Diskusi <span class="text-rose-500">*</span>';
            if (contentInput) contentInput.placeholder = 'Ceritakan pertanyaan, pengalaman, atau pendapatmu di sini.';

            if (formSubtitle) formSubtitle.textContent = 'Mulai percakapan baru, tanyakan pertanyaan, atau bagikan pengalaman Anda bersama komunitas MarketTrust.';
            if (submitBtnText) submitBtnText.textContent = 'Publikasikan Diskusi';
        } else {
            // Mode Marketplace WTB/WTS
            if (discussionTypeSection) discussionTypeSection.classList.add('hidden');
            if (catalogSection) catalogSection.classList.remove('hidden');
            if (variantSelect) variantSelect.setAttribute('required', 'required');
            if (priceSection) priceSection.classList.remove('hidden');

            if (titleLabel) titleLabel.innerHTML = 'Judul Postingan <span class="text-rose-500">*</span>';
            if (titleInput) {
                titleInput.placeholder = category === 'WTB'
                    ? 'Contoh: [WTB] Cari Akun Mobile Legends Mythic Immortal Siap Rekber'
                    : 'Contoh: [WTS] Akun Mobile Legends Mythic Skin Legend Lengkap';
            }
            if (titleHelper) titleHelper.textContent = 'Gunakan judul yang jelas mencantumkan nama produk dan status penawaran Anda.';

            if (contentLabel) contentLabel.innerHTML = 'Isi Postingan / Deskripsi <span class="text-rose-500">*</span>';
            if (contentInput) contentInput.placeholder = 'Tuliskan rincian diskusi atau spesifikasi item digital yang ingin Anda tawarkan/cari...';

            if (formSubtitle) {
                formSubtitle.textContent = category === 'WTB'
                    ? 'Ajukan permintaan pembelian item digital terverifikasi dengan kesepakatan harga dan rekber MarketTrust.'
                    : 'Tawarkan produk digital akun, item, atau voucher terverifikasi dengan proteksi escrow MarketTrust.';
            }
            if (submitBtnText) submitBtnText.textContent = 'Publikasikan Postingan';
        }
    }

    // Inisialisasi Kategori saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
        selectCategory(document.getElementById('category_code').value || 'GENERAL');

        // Jika ada old('variant_id'), pulihkan pilihan kategori, produk, dan varian
        if (oldVariantId) {
            restoreCatalogSelection(oldVariantId);
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

    // TC-FR-13: Preview Foto Instan Sebelum Submit
    function handleImagePreview(event) {
        const container = document.getElementById('image-preview-container');
        const grid = document.getElementById('preview-grid');
        const countSpan = document.getElementById('preview-count');
        const files = Array.from(event.target.files);

        grid.innerHTML = '';

        if (files.length === 0) {
            container.classList.add('hidden');
            return;
        }

        if (files.length > 4) {
            alert('Maksimal foto yang dapat diunggah adalah 4 file. Hanya 4 foto pertama yang akan dipratinjau.');
        }

        const previewFiles = files.slice(0, 4);
        countSpan.textContent = previewFiles.length;
        container.classList.remove('hidden');

        previewFiles.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-video shadow-xs';

            const img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.src = URL.createObjectURL(file);

            const badge = document.createElement('span');
            badge.className = 'absolute bottom-1 right-1 bg-slate-900/80 text-white text-[9px] font-bold px-1.5 py-0.5 rounded backdrop-blur-xs';
            badge.textContent = '#' + (index + 1);

            card.appendChild(img);
            card.appendChild(badge);
            grid.appendChild(card);
        });
    }
</script>
@endsection
