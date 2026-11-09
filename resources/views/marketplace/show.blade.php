@extends('layouts.app')

@section('title', $listing->title . ' - Marketplace MarketTrust')

@section('content')
<div class="pt-4 pb-12 sm:pt-4 sm:pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Top Section: Breadcrumbs Navigation -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 overflow-x-auto pb-1" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>&rsaquo;</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-indigo-600 transition">Marketplace</a>
            <span>&rsaquo;</span>
            <a href="{{ route('marketplace.index', ['category_id' => $listing->variant?->product?->category_id]) }}" class="hover:text-indigo-600 transition">
                {{ $listing->variant?->product?->category?->category_name ?? 'Kategori' }}
            </a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold truncate max-w-[200px]">{{ $listing->title }}</span>
        </nav>

        <!-- Preview Mode Alert Banner -->
        @if ($isPreview ?? false)
            <div class="glass-alert glass-alert-warning" data-cy="alert-preview-mode">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <div class="text-sm font-medium">
                    <strong>Mode Pratinjau Seller:</strong> Listing ini berstatus <span class="uppercase font-bold underline">{{ $listing->status }}</span> dan tidak terlihat oleh publik.
                </div>
            </div>
        @endif

        <!-- Image Gallery Component (Top Hero) -->
        <div class="glass-card p-4 sm:p-6 overflow-hidden border border-white/80 shadow-sm" id="listing-gallery">
            @php
                $images = $listing->all_image_urls;
            @endphp

            <!-- Main Image View -->
            <div class="relative aspect-video max-h-[440px] w-full rounded-2xl bg-slate-900 overflow-hidden flex items-center justify-center border border-slate-100">
                @if (!empty($images))
                    <img
                        id="gallery-main-image"
                        src="{{ $images[0] }}"
                        alt="{{ $listing->title }}"
                        class="w-full h-full object-contain transition-all duration-300"
                        data-cy="gallery-main-image"
                    >
                @else
                    <div class="flex flex-col items-center justify-center text-slate-400 p-8 text-center">
                        <svg class="w-16 h-16 mb-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-semibold text-slate-400">Tidak ada lampiran foto untuk listing ini</span>
                    </div>
                @endif

                <span class="absolute top-3 left-3 glass-badge glass-badge-primary">
                    {{ $listing->variant?->product?->product_name ?? 'Produk Digital' }}
                </span>
            </div>

            <!-- Thumbnails Strip (if multiple images) -->
            @if (count($images) > 1)
                <div class="mt-4 flex gap-3 overflow-x-auto pb-2" id="gallery-thumbnails">
                    @foreach ($images as $index => $imgUrl)
                        <button
                            type="button"
                            class="gallery-thumb-btn w-20 h-14 rounded-xl overflow-hidden border-2 transition-all shrink-0 {{ $index === 0 ? 'border-indigo-600 scale-102 ring-2 ring-indigo-500/20' : 'border-transparent opacity-70 hover:opacity-100' }}"
                            data-full-src="{{ $imgUrl }}"
                            aria-label="Lihat gambar {{ $index + 1 }}"
                        >
                            <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Aligned Two-Column Section: Detail Card (Left 8 Cols) & Product Pricing/Seller (Right 4 Cols) Sejajar -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- Left Column: Product Specifications & Description Card (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                <div class="glass-card p-6 sm:p-8 space-y-6 border border-white/80 shadow-sm" id="card-detail-produk">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">
                                {{ $listing->variant?->product?->category?->category_name }}
                            </span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-xs font-medium text-slate-500">
                                {{ $listing->variant?->product?->product_name }}
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug" data-cy="listing-detail-title">
                            {{ $listing->title }}
                        </h1>
                    </div>

                    <!-- Key Spec Pills -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 pb-4 border-y border-slate-100">
                        <div class="bg-white/60 p-3 rounded-xl border border-slate-100">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Varian Produk</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 truncate block mt-0.5">
                                {{ $listing->variant?->variant_name ?? '-' }}
                            </span>
                        </div>
                        <div class="bg-white/60 p-3 rounded-xl border border-slate-100">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Metode Penyerahan</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 truncate block mt-0.5">
                                {{ $listing->delivery_method }}
                            </span>
                        </div>
                        <div class="bg-white/60 p-3 rounded-xl border border-slate-100 col-span-2 sm:col-span-1">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Komitmen SLA Kirim</span>
                            <span class="text-xs sm:text-sm font-bold text-indigo-600 block mt-0.5 flex items-center gap-1">
                                ⚡ {{ $listing->estimated_delivery_formatted }}
                            </span>
                        </div>
                    </div>

                    <!-- Description Body -->
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3">
                            Deskripsi & Spesifikasi Penawaran
                        </h2>
                        <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-white/40 p-4 rounded-xl border border-slate-100/80" data-cy="listing-detail-description">
                            {{ $listing->description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Card Produk (Harga/Aksi) & Card Informasi Penjual (4 Cols, Sejajar dengan Card Kiri) -->
            <div class="lg:col-span-4 space-y-4 lg:sticky lg:top-20 self-start" id="column-card-produk-penjual">

                <!-- Pricing & Action Card (Card Produk) -->
                <div class="glass-card p-5 border border-white/90 shadow-md space-y-4" id="card-produk-harga">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga Produk</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $listing->status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $listing->status === 'ACTIVE' ? 'Tersedia' : $listing->status }}
                            </span>
                        </div>
                        <div class="text-3xl font-black text-slate-900 tracking-tight" data-cy="listing-price-display">
                            Rp {{ number_format($listing->buyer_total, 0, ',', '.') }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Sudah termasuk perlindungan rekening bersama (escrow) MarketTrust.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2.5 pt-3 border-t border-slate-100">
                        @if ($listing->status === 'ACTIVE')
                            @auth
                                @if (auth()->user()->user_id === $listing->seller_id)
                                    <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200 text-xs text-amber-800 font-medium">
                                        Anda adalah pemilik listing ini. Anda tidak dapat membeli produk sendiri.
                                    </div>
                                    <a href="{{ route('seller.listings.edit', $listing) }}" class="w-full glass-btn-primary py-3 text-center text-xs sm:text-sm font-semibold flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Kelola & Edit Listing</span>
                                    </a>
                                @else
                                    <a
                                        href="{{ route('marketplace.checkout.review', $listing) }}"
                                        class="w-full glass-btn-primary py-3 text-center text-xs sm:text-sm font-bold flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20"
                                        data-cy="btn-beli-sekarang"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span>Beli Sekarang (Escrow)</span>
                                    </a>

                                    <a
                                        href="{{ route('conversations.from-listing', $listing) }}"
                                        id="btn-contact-seller"
                                        class="w-full glass-btn-secondary py-2.5 text-center text-xs sm:text-sm font-semibold flex items-center justify-center gap-2"
                                        data-cy="btn-hubungi-seller"
                                    >
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span>Hubungi Penjual</span>
                                    </a>
                                @endif
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="w-full glass-btn-primary py-3 text-center text-xs sm:text-sm font-bold flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20"
                                    data-cy="btn-login-to-buy"
                                >
                                    <span>Masuk untuk Membeli</span>
                                </a>
                                <a
                                    href="{{ route('login') }}"
                                    class="w-full glass-btn-secondary py-2.5 text-center text-xs sm:text-sm font-semibold flex items-center justify-center gap-2"
                                >
                                    <span>Masuk untuk Chat Penjual</span>
                                </a>
                            @endauth
                        @else
                            <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 font-medium">
                                Listing ini sedang ditutup atau tidak aktif. Transaksi baru tidak dapat dimulai.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Seller Profile Card (Informasi Penjual) -->
                <div class="glass-card p-5 border border-white/80 shadow-sm space-y-3.5" id="card-informasi-penjual">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Informasi Penjual
                    </span>

                    <div class="flex items-center gap-3">
                        @if ($listing->seller?->avatar_url)
                            <img src="{{ $listing->seller->avatar_url }}" alt="{{ $listing->seller->username }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-white/80 shadow-xs">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-base shadow-xs">
                                {{ strtoupper(substr($listing->seller?->username ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-sm truncate">
                                <a href="{{ route('profile.show', $listing->seller_id) }}" class="hover:text-indigo-600 transition" data-cy="link-seller-profile">
                                    @<span>{{ $listing->seller?->username }}</span>
                                </a>
                            </h3>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $listing->seller?->reputation_status === 'LEGIT' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $listing->seller?->reputation_status ?? 'UNREVIEWED' }}
                                </span>
                                <span class="text-[11px] font-semibold text-indigo-600">
                                    ★ {{ $listing->seller?->reputation_points ?? 0 }} Pts
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-center">
                        <div class="bg-white/60 p-2.5 rounded-lg border border-slate-100">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Penjualan Sukses</span>
                            <span class="text-sm font-extrabold text-slate-800">
                                {{ $listing->seller?->seller_transactions_count ?? 0 }}
                            </span>
                        </div>
                        <div class="bg-white/60 p-2.5 rounded-lg border border-slate-100">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Listing Aktif</span>
                            <span class="text-sm font-extrabold text-slate-800">
                                {{ $listing->seller?->active_listings_count ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-400 text-center">
                        Bergabung sejak {{ $listing->seller?->created_at?->translatedFormat('F Y') ?? '-' }}
                    </div>

                    <a href="{{ route('profile.show', $listing->seller_id) }}" class="block text-center text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline pt-2 border-t border-slate-100" data-cy="btn-view-seller-profile">
                        Lihat Profil & Riwayat Legitimasi &rarr;
                    </a>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainImg = document.getElementById('gallery-main-image');
    const thumbBtns = document.querySelectorAll('.gallery-thumb-btn');

    if (mainImg && thumbBtns.length > 0) {
        thumbBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const newSrc = this.getAttribute('data-full-src');
                if (newSrc) {
                    mainImg.src = newSrc;
                    thumbBtns.forEach(b => {
                        b.classList.remove('border-indigo-600', 'scale-102', 'ring-2', 'ring-indigo-500/20');
                        b.classList.add('border-transparent', 'opacity-70');
                    });
                    this.classList.add('border-indigo-600', 'scale-102', 'ring-2', 'ring-indigo-500/20');
                    this.classList.remove('border-transparent', 'opacity-70');
                }
            });
        });
    }
});
</script>
@endsection
