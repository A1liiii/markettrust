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
            <div class="mb-8 pb-5 border-b border-slate-200/60">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 mb-2 border border-indigo-500/20 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                    Buat Postingan Forum Baru
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Bagikan diskusi umum atau ajukan penawaran jual/beli produk digital terverifikasi.
                </p>
            </div>

            <!-- Form -->
            <form action="{{ route('forum.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Category Selection -->
                <div>
                    <label class="glass-label">Pilih Kategori Postingan <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="category_code" value="GENERAL"
                                   {{ old('category_code', 'GENERAL') === 'GENERAL' ? 'checked' : '' }}
                                   onchange="handleCategoryChange(this.value)"
                                   class="sr-only peer">
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white/60 peer-checked:border-indigo-600 peer-checked:bg-indigo-50/70 peer-checked:text-indigo-900 transition flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full border-2 border-slate-400 peer-checked:border-indigo-600 peer-checked:bg-indigo-600"></span>
                                <div>
                                    <span class="text-xs font-bold block">Umum</span>
                                    <span class="text-[11px] text-slate-500">Diskusi komunitas</span>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="category_code" value="WTB"
                                   {{ old('category_code') === 'WTB' ? 'checked' : '' }}
                                   onchange="handleCategoryChange(this.value)"
                                   class="sr-only peer">
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white/60 peer-checked:border-amber-600 peer-checked:bg-amber-50/70 peer-checked:text-amber-900 transition flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full border-2 border-slate-400 peer-checked:border-amber-600 peer-checked:bg-amber-600"></span>
                                <div>
                                    <span class="text-xs font-bold block">WTB</span>
                                    <span class="text-[11px] text-slate-500">Want To Buy (Cari beli)</span>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="category_code" value="WTS"
                                   {{ old('category_code') === 'WTS' ? 'checked' : '' }}
                                   onchange="handleCategoryChange(this.value)"
                                   class="sr-only peer">
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-white/60 peer-checked:border-emerald-600 peer-checked:bg-emerald-50/70 peer-checked:text-emerald-900 transition flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full border-2 border-slate-400 peer-checked:border-emerald-600 peer-checked:bg-emerald-600"></span>
                                <div>
                                    <span class="text-xs font-bold block">WTS</span>
                                    <span class="text-[11px] text-slate-500">Want To Sell (Jual)</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Product Variant Dropdown (Conditional for WTB/WTS) -->
                <div id="variant-group" class="{{ in_array(old('category_code'), ['WTB', 'WTS']) ? '' : 'hidden' }}">
                    <label for="variant_id" class="glass-label">
                        Katalog Produk & Varian Terverifikasi <span class="text-rose-500">*</span>
                    </label>
                    <select name="variant_id" id="variant_id" class="glass-input w-full px-3.5 py-2.5 text-sm">
                        <option value="">-- Pilih Varian Produk (Katalog Fadhil) --</option>
                        @foreach ($variants as $v)
                            <option value="{{ $v->variant_id }}" {{ (string) old('variant_id') === (string) $v->variant_id ? 'selected' : '' }}>
                                {{ $v->product->product_name ?? 'Produk' }} &mdash; {{ $v->variant_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Pilihan varian diperlukan agar postingan Anda terhitung dalam statistik penawaran & permintaan pasar.
                    </p>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="glass-label">Judul Postingan <span class="text-rose-500">*</span></label>
                    <input type="text"
                           id="title"
                           name="title"
                           value="{{ old('title') }}"
                           required
                           maxlength="150"
                           placeholder="Contoh: [WTS] Akun Mobile Legends Mythic Skin Legend Lengkap"
                           class="glass-input w-full px-3.5 py-2.5 text-sm">
                </div>

                <!-- Price Offer (Optional) -->
                <div>
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
                    <label for="content" class="glass-label">Isi Postingan / Deskripsi <span class="text-rose-500">*</span></label>
                    <textarea id="content"
                              name="content"
                              rows="6"
                              required
                              placeholder="Tuliskan rincian diskusi atau spesifikasi item digital yang ingin Anda tawarkan/cari..."
                              class="glass-input w-full px-3.5 py-2.5 text-sm leading-relaxed">{{ old('content') }}</textarea>
                </div>

                <!-- Image Upload (Max 4 photos) -->
                <div>
                    <label class="glass-label">
                        Unggah Foto Lampiran <span class="text-slate-400 font-normal lowercase">(maksimal 4 file)</span>
                    </label>
                    <div class="glass-card p-4 border-dashed border-2 border-slate-300/80 rounded-xl">
                        <input type="file"
                               name="images[]"
                               id="images"
                               multiple
                               accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-2">
                            <span>Format: JPG, PNG, WEBP</span>
                            <span>&bull;</span>
                            <span>Maksimal 3 MB per foto (KNF-04)</span>
                            <span>&bull;</span>
                            <span>Maksimal 4 foto per postingan</span>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-4 border-t border-slate-200/60 flex items-center justify-end gap-3">
                    <a href="{{ route('forum.index') }}" class="glass-btn-secondary text-sm">
                        Batal
                    </a>
                    <button type="submit" class="glass-btn-primary text-sm shadow-md shadow-indigo-500/20">
                        <span>Publikasikan Postingan</span>
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
    function handleCategoryChange(category) {
        const variantGroup = document.getElementById('variant-group');
        const variantSelect = document.getElementById('variant_id');
        if (category === 'WTB' || category === 'WTS') {
            variantGroup.classList.remove('hidden');
        } else {
            variantGroup.classList.add('hidden');
            if (variantSelect) {
                variantSelect.value = '';
            }
        }
    }
</script>
@endsection
