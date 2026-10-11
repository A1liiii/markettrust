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
            <form action="{{ route('seller.listings.update', $listing->listing_id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- 1. Pemilihan Varian Produk Master -->
                <div>
                    <label for="variant_id" class="glass-label">
                        Pilih Varian & Produk Master <span class="text-rose-500">*</span>
                    </label>
                    <select id="variant_id" name="variant_id" required class="glass-input w-full text-sm">
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
                </div>

                <!-- 2. Judul Penawaran -->
                <div>
                    <label for="title" class="glass-label">
                        Judul Penawaran Listing <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title', $listing->title) }}" required maxlength="150"
                           class="glass-input w-full text-sm">
                </div>

                <!-- 3. Deskripsi Penawaran -->
                <div>
                    <label for="description" class="glass-label">
                        Deskripsi Lengkap & Spesifikasi Item <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="description" name="description" rows="5" required
                              class="glass-input w-full text-sm">{{ old('description', $listing->description) }}</textarea>
                </div>

                <!-- 4. Harga Bersih Seller -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2 border-t border-slate-200/60">
                    <div>
                        <label for="price" class="glass-label">
                            Harga Penawaran Bersih Seller (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-xs font-bold text-slate-500">Rp</span>
                            <input type="number" id="price" name="price" value="{{ old('price', (int) $listing->price) }}" required min="1000" step="500"
                                   class="glass-input w-full pl-10 text-sm font-semibold text-slate-900">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Nominal bersih yang Anda terima (Platform fee dihitung otomatis saat buyer checkout).
                        </p>
                    </div>

                    <!-- 5. Status Penawaran -->
                    <div>
                        <label class="glass-label">Status Penawaran <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2 mt-1">
                            <label class="flex flex-col items-center justify-center p-2 rounded-xl border border-slate-200 bg-white/70 hover:bg-white cursor-pointer transition text-center">
                                <input type="radio" name="status" value="ACTIVE" {{ old('status', $listing->status) === 'ACTIVE' ? 'checked' : '' }}
                                       class="text-indigo-600 focus:ring-indigo-500 mb-1">
                                <span class="text-xs font-bold text-emerald-700">AKTIF</span>
                            </label>

                            <label class="flex flex-col items-center justify-center p-2 rounded-xl border border-slate-200 bg-white/70 hover:bg-white cursor-pointer transition text-center">
                                <input type="radio" name="status" value="DRAFT" {{ old('status', $listing->status) === 'DRAFT' ? 'checked' : '' }}
                                       class="text-indigo-600 focus:ring-indigo-500 mb-1">
                                <span class="text-xs font-bold text-amber-700">DRAF</span>
                            </label>

                            <label class="flex flex-col items-center justify-center p-2 rounded-xl border border-slate-200 bg-white/70 hover:bg-white cursor-pointer transition text-center">
                                <input type="radio" name="status" value="CLOSED" {{ old('status', $listing->status) === 'CLOSED' ? 'checked' : '' }}
                                       class="text-indigo-600 focus:ring-indigo-500 mb-1">
                                <span class="text-xs font-bold text-rose-700">DITUTUP</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 6. Metode & Estimasi Penyerahan Digital -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2 border-t border-slate-200/60">
                    <div>
                        <label for="delivery_method" class="glass-label">
                            Metode Penyerahan Digital <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="delivery_method" name="delivery_method"
                               value="{{ old('delivery_method', $listing->delivery_method) }}"
                               required maxlength="100"
                               class="glass-input w-full text-sm">
                        
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" onclick="setDeliveryMethod('Kredensial Akun via Catatan Terenkripsi')"
                                    class="text-[10px] py-0.5 px-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                + Kredensial Akun
                            </button>
                            <button type="button" onclick="setDeliveryMethod('Lisensi Key / Serial Token')"
                                    class="text-[10px] py-0.5 px-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                + Lisensi Key
                            </button>
                            <button type="button" onclick="setDeliveryMethod('Transfer In-Game / Party Gift')"
                                    class="text-[10px] py-0.5 px-2 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                + Transfer In-Game
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="glass-label">
                            Estimasi Durasi Penyerahan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="number" name="estimated_delivery_value"
                                       value="{{ old('estimated_delivery_value', $listing->estimated_delivery_value) }}"
                                       required min="1" max="999"
                                       class="glass-input w-full text-sm font-semibold">
                            </div>
                            <div>
                                <select name="estimated_delivery_unit" required class="glass-input w-full text-sm">
                                    <option value="MINUTE" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'MINUTE' ? 'selected' : '' }}>Menit (MINUTE)</option>
                                    <option value="HOUR" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'HOUR' ? 'selected' : '' }}>Jam (HOUR)</option>
                                    <option value="DAY" {{ old('estimated_delivery_unit', $listing->estimated_delivery_unit) === 'DAY' ? 'selected' : '' }}>Hari (DAY)</option>
                                </select>
                            </div>
                        </div>
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
</script>
@endsection
