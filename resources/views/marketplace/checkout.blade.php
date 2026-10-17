@extends('layouts.app')

@section('title', 'Tinjauan Kesepakatan Checkout - ' . $listing->title)

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 overflow-x-auto pb-1" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>&rsaquo;</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-indigo-600 transition">Marketplace</a>
            <span>&rsaquo;</span>
            <a href="{{ route('marketplace.show', $listing) }}" class="hover:text-indigo-600 transition truncate max-w-[150px]">{{ $listing->title }}</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold">Tinjauan Checkout</span>
        </nav>

        <!-- Header -->
        <div class="border-b border-white/60 pb-6">
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="glass-badge glass-badge-primary">
                    Kesepakatan Final Marketplace (UC09)
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Tinjauan Rincian & Konfirmasi Transaksi
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                Periksa item digital, rincian biaya layanan platform, dan komitmen SLA penyerahan sebelum menyetujui transaksi escrow.
            </p>
        </div>

        <!-- Checkout Card -->
        <div class="glass-panel p-6 sm:p-8 space-y-8 border border-white/90 shadow-md">

            <!-- Item Summary -->
            <div class="bg-white/70 p-5 rounded-2xl border border-white/90 space-y-3">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    Item Penawaran Terpilih
                </span>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-xs text-indigo-600 font-semibold mb-0.5">
                            {{ $listing->variant?->product?->category?->category_name }} &bull; {{ $listing->variant?->product?->product_name }} - {{ $listing->variant?->variant_name }}
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900" data-cy="checkout-item-title">
                            {{ $listing->title }}
                        </h2>
                        <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                            <span>Penjual: <strong>@<span>{{ $listing->seller?->username }}</span></strong></span>
                            <span>&bull;</span>
                            <span>Metode: <strong>{{ $listing->delivery_method }}</strong></span>
                            <span>&bull;</span>
                            <span>Estimasi Kirim: <strong class="text-indigo-600">⚡ {{ $listing->estimated_delivery_formatted }}</strong></span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Harga Bersih Listing</span>
                        <span class="text-xl font-black text-slate-900" data-cy="checkout-base-price">
                            Rp {{ number_format($feeCalculation->basePrice, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Calculation Table -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Rincian Biaya Transaksi Resmi (Marketplace EXCLUDE)
                </h3>

                <div class="bg-white/50 rounded-2xl p-5 border border-slate-200/60 space-y-3 text-sm">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Harga Dasar Produk:</span>
                        <span class="font-semibold text-slate-900">
                            Rp {{ number_format($feeCalculation->basePrice, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600">
                        <span class="flex items-center gap-1.5">
                            <span>Biaya Layanan Platform (Rekber Escrow):</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                7 Jenjang SKPL
                            </span>
                        </span>
                        <span class="font-semibold text-indigo-600" data-cy="checkout-fee-amount">
                            + Rp {{ number_format($feeCalculation->feeAmount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200/80 pt-3 flex justify-between items-center">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Total Yang Wajib Dibayar Buyer:</span>
                            <span class="text-[10px] text-slate-500">Ditahan aman di rekening bersama MarketTrust</span>
                        </div>
                        <span class="text-xl sm:text-2xl font-black text-slate-900 text-indigo-700" data-cy="checkout-buyer-total">
                            Rp {{ number_format($feeCalculation->buyerTotal, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200/80 pt-3 flex justify-between items-center text-xs">
                        <div>
                            <span class="block font-semibold text-slate-700">Penerimaan Bersih Penjual:</span>
                            <span class="text-[10px] text-slate-500">Dicairkan ke seller setelah transaksi selesai</span>
                        </div>
                        <span class="font-bold text-emerald-600" data-cy="checkout-seller-net">
                            Rp {{ number_format($feeCalculation->sellerNet, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Escrow Guarantees & Terms -->
            <div class="bg-indigo-50/60 rounded-2xl p-5 border border-indigo-100 space-y-2 text-xs text-indigo-900 leading-relaxed">
                <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                    <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Jaminan Keamanan Rekening Bersama (Escrow):</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                    <li>Dana Anda akan disimpan aman di escrow dan tidak langsung dikirim ke penjual.</li>
                    <li>Penjual diwajibkan menyerahkan data akun/item dalam batas waktu estimasi (<strong>{{ $listing->estimated_delivery_formatted }}</strong>).</li>
                    <li>Tenggat waktu pembayaran awal adalah <strong>1 jam</strong> terhitung sejak kesepakatan final dikonfirmasi.</li>
                </ul>
            </div>

            <!-- Seller Payout Validation Check (TC-09-06) -->
            @if (!$hasSellerPayout)
                <div class="glass-alert glass-alert-warning" data-cy="alert-no-payout">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-xs sm:text-sm font-medium">
                        <strong>Perhatian:</strong> Penjual belum melengkapi rekening/tujuan pencairan (payout) yang aktif di platform. Sesuai kebijakan keamanan escrow (TC-09-06), pembayaran baru diizinkan setelah penjual mendaftarkan rekening payout.
                    </div>
                </div>
            @endif

            <!-- Confirmation Form -->
            <form action="{{ route('marketplace.checkout.confirm', $listing) }}" method="POST" id="checkout-confirm-form">
                @csrf
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                <input type="hidden" name="expected_price" value="{{ $listing->price }}">

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200">
                    <a
                        href="{{ route('marketplace.show', $listing) }}"
                        class="glass-btn-secondary w-full sm:w-auto px-6 py-3 text-xs sm:text-sm font-semibold text-center"
                        data-cy="btn-cancel-checkout"
                    >
                        &larr; Batalkan / Kembali ke Listing
                    </a>

                    @if ($hasSellerPayout)
                        <button
                            type="submit"
                            id="btn-confirm-checkout"
                            class="glass-btn-primary w-full sm:w-auto px-8 py-3 text-xs sm:text-sm font-bold flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25"
                            data-cy="btn-confirm-checkout"
                        >
                            <span>Setuju & Mulai Transaksi</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    @else
                        <button
                            type="button"
                            disabled
                            class="glass-btn-neutral w-full sm:w-auto px-8 py-3 text-xs sm:text-sm font-bold opacity-60 cursor-not-allowed"
                            title="Menunggu penjual melengkapi data payout"
                            data-cy="btn-checkout-blocked"
                        >
                            Pembayaran Belum Diizinkan
                        </button>
                    @endif
                </div>
            </form>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkout-confirm-form');
    const submitBtn = document.getElementById('btn-confirm-checkout');

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Memproses Kesepakatan Final...</span>
            `;
        });
    }
});
</script>
@endsection
