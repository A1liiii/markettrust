@extends('layouts.app')

@section('title', 'Edit Penawaran Listing - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                <a href="{{ route('seller.listings.index') }}" class="hover:underline">Panel Seller</a>
                <span>&bull;</span>
                <span>Edit Penawaran #{{ $listing->listing_id }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Perbarui Penawaran Listing
            </h1>
            <p class="text-sm text-slate-600 mt-1">
                Ubah informasi rincian item, penyesuaian harga pasar, atau alihkan status penawaran Anda.
            </p>
        </div>

        <!-- Form Card Container -->
        <div class="glass-panel p-6 sm:p-8 bg-white/95 border border-white/90 shadow-xl">
            <form id="edit-listing-form" action="{{ route('seller.listings.update', $listing->listing_id) }}" method="POST" class="space-y-8">
                @csrf
                @method('PUT')

                <!-- ========================================== -->
                <!-- BAGIAN 1: IDENTITAS & SPESIFIKASI PRODUK -->
                <!-- ========================================== -->
                <div class="space-y-5">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold border border-indigo-100">1</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Identitas & Spesifikasi Produk</h2>
                    </div>

                    <!-- 1.1 Pemilihan Varian Master -->
                    <div>
                        <label for="variant_id" class="glass-label flex items-center justify-between">
                            <span>Pilih Varian & Produk Master <span class="text-rose-500 font-bold">*</span></span>
                            <span class="text-[11px] text-slate-400 font-normal">Dari katalog master aktif</span>
                        </label>
                        <select id="variant_id" name="variant_id" required class="glass-input w-full text-sm @error('variant_id') border-rose-400 ring-rose-200 @enderror">
                            @foreach ($categories as $category)
                                @foreach ($category->products as $product)
                                    @if ($product->variants->isNotEmpty())
                                        <optgroup label="{{ $product->product_name }} ({{ $category->category_name }})">
                                            @foreach ($product->variants as $variant)
                                                <option value="{{ $variant->variant_id }}" {{ (string) old('variant_id', $listing->variant_id) === (string) $variant->variant_id ? 'selected' : '' }}>
                                                    {{ $variant->variant_name }} &bull; {{ $product->product_name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Varian saat ini: <strong class="text-slate-800">{{ $listing->variant->variant_name ?? '-' }}</strong> ({{ $listing->variant->product->product_name ?? '-' }}).
                        </p>
                        @error('variant_id')
                            <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 1.2 Judul Penawaran -->
                    <div>
                        <label for="title" class="glass-label flex items-center justify-between">
                            <span>Judul Penawaran Listing <span class="text-rose-500 font-bold">*</span></span>
                            <span class="text-[11px] text-slate-400 font-normal">Maks. 150 karakter</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title', $listing->title) }}" required maxlength="150"
                               class="glass-input w-full text-sm @error('title') border-rose-400 ring-rose-200 @enderror">
                        <p class="text-[11px] text-slate-500 mt-1">Buat judul ringkas dan menarik yang memuat poin keunggulan utama item digital Anda.</p>
                        @error('title')
                            <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 1.3 Deskripsi Penawaran -->
                    <div>
                        <label for="description" class="glass-label flex items-center justify-between">
                            <span>Deskripsi Lengkap & Spesifikasi Item <span class="text-rose-500 font-bold">*</span></span>
                            <span class="text-[11px] text-slate-400 font-normal">Spesifikasi transparan</span>
                        </label>
                        <textarea id="description" name="description" rows="5" required
                                  class="glass-input w-full text-sm @error('description') border-rose-400 ring-rose-200 @enderror">{{ old('description', $listing->description) }}</textarea>
                        <p class="text-[11px] text-slate-500 mt-1">Cantumkan semua informasi penting agar buyer mendapatkan gambaran akurat sebelum melakukan checkout.</p>
                        @error('description')
                            <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- BAGIAN 2: PENETAPAN HARGA BERSIH & PREVIEW FEE MARKETPLACE -->
                <!-- ======================================================= -->
                <div class="space-y-4 pt-4 border-t border-slate-200/80">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold border border-indigo-100">2</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Penetapan Harga & Fee Marketplace</h2>
                    </div>

                    <div>
                        <label for="price_display" class="glass-label flex items-center justify-between">
                            <span>Harga Penawaran Bersih Seller (Rp) <span class="text-rose-500 font-bold">*</span></span>
                            <span class="text-[11px] text-slate-500 font-medium">Minimal Rp 5.000</span>
                        </label>

                        <!-- Input Container dengan prefix Rp terpisah (TC-LS-04) -->
                        <div class="relative rounded-xl">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <span class="text-xs font-bold text-slate-500">Rp</span>
                            </div>
                            <input type="text" id="price_display" inputmode="numeric" autocomplete="off"
                                   placeholder="150.000"
                                   class="glass-input w-full pl-11 pr-4 text-sm font-semibold text-slate-900 @error('price') border-rose-400 ring-rose-200 @enderror">
                            <input type="hidden" id="price" name="price" value="{{ old('price', (int) $listing->price) }}">
                        </div>

                        <p class="text-[11px] text-slate-500 mt-1">
                            Nominal yang akan Anda terima utuh setelah pembeli mengonfirmasi pesanan.
                        </p>
                        @error('price')
                            <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Live Breakdown Preview Card (TC-LS-05) -->
                    <div id="fee_preview_card" class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50/70 to-slate-50/80 border border-indigo-100/90 shadow-xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Simulasi Rincian Pembayaran Transaksi</h3>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-semibold self-start sm:self-auto">
                                Mode Fee: EXCLUDE (Wajib Marketplace)
                            </span>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="bg-white/90 p-3 rounded-xl border border-indigo-100/60 shadow-xs">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider">1. Harga Penawaran</span>
                                <span id="preview_base_price" class="text-sm font-black text-slate-900 mt-1 block">Rp 0</span>
                                <span class="text-[10px] text-slate-400">Harga dasar item</span>
                            </div>

                            <div class="bg-white/90 p-3 rounded-xl border border-indigo-100/60 shadow-xs">
                                <span class="block text-[10px] text-indigo-600 uppercase font-bold tracking-wider">2. Fee MarketTrust</span>
                                <span id="preview_fee_amount" class="text-sm font-black text-indigo-600 mt-1 block">+ Rp 0</span>
                                <span class="text-[10px] text-slate-400">Layanan escrow platform</span>
                            </div>

                            <div class="bg-emerald-50/90 p-3 rounded-xl border border-emerald-200/70 shadow-xs">
                                <span class="block text-[10px] text-emerald-800 uppercase font-bold tracking-wider">3. Total Bayar Buyer</span>
                                <span id="preview_buyer_total" class="text-sm font-black text-emerald-700 mt-1 block">Rp 0</span>
                                <span class="text-[10px] text-emerald-600/90 font-medium">Ditagihkan saat checkout</span>
                            </div>

                            <div class="bg-indigo-600 text-white p-3 rounded-xl shadow-sm">
                                <span class="block text-[10px] text-indigo-100 uppercase font-bold tracking-wider">4. Hak Bersih Seller</span>
                                <span id="preview_seller_net" class="text-sm font-black text-white mt-1 block">Rp 0</span>
                                <span class="text-[10px] text-indigo-200 font-medium">Diterima utuh oleh Anda</span>
                            </div>
                        </div>

                        <div id="fee_status_hint" class="text-[11px] text-slate-600 mt-3 pt-2.5 border-t border-indigo-100/80 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Aturan Platform: Fee layanan dihitung otomatis dari sistem dan dibebankan kepada pembeli. Penjual menerima hak bersih sesuai harga dasar.</span>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- BAGIAN 3: METODE & ESTIMASI WAKTU PENYERAHAN DIGITAL -->
                <!-- ======================================================= -->
                <div class="space-y-5 pt-4 border-t border-slate-200/80">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold border border-indigo-100">3</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Penyerahan & Estimasi Durasi Escrow</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Metode Penyerahan -->
                        <div>
                            <label for="delivery_method" class="glass-label">
                                Metode Penyerahan Digital <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <input type="text" id="delivery_method" name="delivery_method"
                                   value="{{ old('delivery_method', $listing->delivery_method) }}"
                                   required maxlength="100"
                                   class="glass-input w-full text-sm @error('delivery_method') border-rose-400 ring-rose-200 @enderror">
                            
                            <!-- Quick Suggestion Chips -->
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <button type="button" onclick="setDeliveryMethod('Kredensial Akun via Catatan Terenkripsi')"
                                        class="text-[10px] py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 transition border border-slate-200/60">
                                    + Kredensial Akun
                                </button>
                                <button type="button" onclick="setDeliveryMethod('Lisensi Key / Serial Token')"
                                        class="text-[10px] py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 transition border border-slate-200/60">
                                    + Lisensi Key
                                </button>
                                <button type="button" onclick="setDeliveryMethod('Transfer In-Game / Party Gift')"
                                        class="text-[10px] py-1 px-2.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 text-slate-700 transition border border-slate-200/60">
                                    + Transfer In-Game
                                </button>
                            </div>
                            @error('delivery_method')
                                <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Estimasi Durasi -->
                        <div>
                            <label class="glass-label">
                                Batas Maksimal Penyerahan <span class="text-rose-500 font-bold">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <input type="number" id="estimated_delivery_value" name="estimated_delivery_value"
                                           value="{{ old('estimated_delivery_value', $listing->estimated_delivery_value) }}"
                                           required min="1" max="999"
                                           class="glass-input w-full text-sm font-semibold @error('estimated_delivery_value') border-rose-400 ring-rose-200 @enderror">
                                </div>
                                <div>
                                    <select id="estimated_delivery_unit" name="estimated_delivery_unit" required class="glass-input w-full text-sm @error('estimated_delivery_unit') border-rose-400 ring-rose-200 @enderror">
                                        <option value="MINUTE" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'MINUTE' ? 'selected' : '' }}>Menit (MINUTE)</option>
                                        <option value="HOUR" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'HOUR' ? 'selected' : '' }}>Jam (HOUR)</option>
                                        <option value="DAY" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'DAY' ? 'selected' : '' }}>Hari (DAY)</option>
                                    </select>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">Batas waktu maksimal bagi Anda untuk menyerahkan item digital setelah pembeli membayar.</p>
                            @error('estimated_delivery_value')
                                <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                            @enderror
                            @error('estimated_delivery_unit')
                                <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- BAGIAN 4: STATUS PENAWARAN LISTING -->
                <!-- ========================================== -->
                <div class="space-y-4 pt-4 border-t border-slate-200/80">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold border border-indigo-100">4</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Status Publikasi Listing</h2>
                    </div>

                    <div>
                        <label class="glass-label">Pilih Status Penayangan <span class="text-rose-500 font-bold">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
                            <label class="flex flex-col p-3 rounded-xl border border-slate-200/80 bg-white/70 hover:bg-white cursor-pointer transition">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="status" value="ACTIVE" {{ old('status', $listing->status) === 'ACTIVE' ? 'checked' : '' }}
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs font-bold text-emerald-700">AKTIF</span>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1">Dapat dicari & dibeli langsung.</span>
                            </label>

                            <label class="flex flex-col p-3 rounded-xl border border-slate-200/80 bg-white/70 hover:bg-white cursor-pointer transition">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="status" value="DRAFT" {{ old('status', $listing->status) === 'DRAFT' ? 'checked' : '' }}
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs font-bold text-amber-700">DRAF</span>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1">Disimpan privat untuk diedit nanti.</span>
                            </label>

                            <label class="flex flex-col p-3 rounded-xl border border-slate-200/80 bg-white/70 hover:bg-white cursor-pointer transition">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="status" value="CLOSED" {{ old('status', $listing->status) === 'CLOSED' ? 'checked' : '' }}
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs font-bold text-rose-700">DITUTUP</span>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1">Nonaktif dan tidak tampil di pasar.</span>
                            </label>
                        </div>
                        @error('status')
                            <p class="text-rose-600 text-xs mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                    <a href="{{ route('seller.listings.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-4">
                        Batal
                    </a>
                    <button type="submit" class="glass-btn-primary text-xs sm:text-sm py-2 px-6 shadow-md shadow-indigo-500/25">
                        Perbarui Penawaran Listing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setDeliveryMethod(val) {
        const input = document.getElementById('delivery_method');
        if (input) {
            input.value = val;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const priceDisplay = document.getElementById('price_display');
        const priceHidden = document.getElementById('price');
        const previewBasePrice = document.getElementById('preview_base_price');
        const previewFeeAmount = document.getElementById('preview_fee_amount');
        const previewBuyerTotal = document.getElementById('preview_buyer_total');
        const previewSellerNet = document.getElementById('preview_seller_net');
        const feeStatusHint = document.getElementById('fee_status_hint');
        const form = document.getElementById('edit-listing-form');

        const feePreviewUrl = "{{ route('fee.preview') }}";
        const csrfToken = "{{ csrf_token() }}";

        let debounceTimer = null;

        function formatNumber(num) {
            if (!num && num !== 0) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function syncPreview(basePrice, feeAmount, buyerTotal, sellerNet, message = null) {
            if (previewBasePrice) previewBasePrice.textContent = 'Rp ' + formatNumber(basePrice);
            if (previewFeeAmount) previewFeeAmount.textContent = '+ Rp ' + formatNumber(feeAmount);
            if (previewBuyerTotal) previewBuyerTotal.textContent = 'Rp ' + formatNumber(buyerTotal);
            if (previewSellerNet) previewSellerNet.textContent = 'Rp ' + formatNumber(sellerNet);

            if (feeStatusHint && message) {
                feeStatusHint.innerHTML = `<span class="text-amber-600 font-semibold">${message}</span>`;
            }
        }

        async function fetchFeePreview(rawPrice) {
            if (rawPrice < 5000) {
                syncPreview(rawPrice, 0, rawPrice, rawPrice, 'Harga di bawah batas minimum Rp 5.000 untuk transaksi marketplace.');
                return;
            }

            try {
                const response = await fetch(feePreviewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        base_price: rawPrice,
                        source: 'MARKETPLACE'
                    })
                });

                if (!response.ok) {
                    const errData = await response.json().catch(() => ({}));
                    const errMsg = errData.message || 'Nominal tidak valid untuk perhitungan fee.';
                    syncPreview(rawPrice, 0, rawPrice, rawPrice, errMsg);
                    return;
                }

                const result = await response.json();
                if (result.status === 'success' && result.data) {
                    const d = result.data;
                    syncPreview(d.base_price, d.fee_amount, d.buyer_total, d.seller_net, null);
                }
            } catch (err) {
                console.warn('Gagal memuat preview fee:', err);
            }
        }

        // Caret-preserving Rupiah input handler (TC-LS-04)
        function handlePriceInput() {
            const originalVal = priceDisplay.value;
            const originalCursor = priceDisplay.selectionStart || 0;

            const digitsBeforeCursor = originalVal.slice(0, originalCursor).replace(/\D/g, '').length;
            const rawDigits = originalVal.replace(/\D/g, '');

            priceHidden.value = rawDigits;

            if (!rawDigits) {
                priceDisplay.value = '';
                syncPreview(0, 0, 0, 0);
                return;
            }

            const formatted = formatNumber(rawDigits);
            priceDisplay.value = formatted;

            let newCursor = 0;
            let counted = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    counted++;
                }
                if (counted === digitsBeforeCursor) {
                    newCursor = i + 1;
                    break;
                }
            }
            if (digitsBeforeCursor === 0) newCursor = 0;

            priceDisplay.setSelectionRange(newCursor, newCursor);

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchFeePreview(parseInt(rawDigits, 10));
            }, 300);
        }

        priceDisplay.addEventListener('input', handlePriceInput);

        const initialVal = priceHidden.value || "{{ old('price', (int) $listing->price) }}";
        if (initialVal) {
            const cleanInit = initialVal.toString().replace(/\D/g, '');
            if (cleanInit) {
                priceHidden.value = cleanInit;
                priceDisplay.value = formatNumber(cleanInit);
                fetchFeePreview(parseInt(cleanInit, 10));
            }
        }

        if (form) {
            form.addEventListener('submit', function () {
                if (priceDisplay.value) {
                    priceHidden.value = priceDisplay.value.replace(/\D/g, '');
                }
            });
        }
    });
</script>
@endsection
