@extends('layouts.app')

@section('title', $post->title . ' - Forum MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>/</span>
            <a href="{{ route('forum.index') }}" class="hover:text-indigo-600 transition">Forum</a>
            <span>/</span>
            <span class="text-slate-800 truncate max-w-xs">{{ $post->title }}</span>
        </nav>

        <div class="space-y-6">
            <!-- Main Post Card -->
            <article class="glass-panel p-6 sm:p-8">
                <!-- Header: Badges & Date -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/60 mb-5">
                    <div class="flex items-center gap-2">
                        @if ($post->category_code === 'GENERAL')
                            <span class="glass-badge glass-badge-primary">Diskusi Umum</span>
                        @elseif ($post->category_code === 'WTB')
                            <span class="glass-badge glass-badge-warning">Want To Buy (Cari Beli)</span>
                        @elseif ($post->category_code === 'WTS')
                            <span class="glass-badge glass-badge-success">Want To Sell (Penawaran Jual)</span>
                        @endif

                        @if ($post->status === 'ACTIVE')
                            <span class="glass-badge glass-badge-success text-[10px]">Aktif</span>
                        @elseif ($post->status === 'CLOSED')
                            <span class="glass-badge glass-badge-neutral text-[10px]">Ditutup</span>
                        @else
                            <span class="glass-badge glass-badge-danger text-[10px]">{{ $post->status }}</span>
                        @endif
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Dipublikasikan {{ $post->created_at->translatedFormat('d M Y, H:i') }} ({{ $post->created_at->diffForHumans() }})
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug mb-6">
                    {{ $post->title }}
                </h1>

                <!-- Author Identity Card -->
                <div class="glass-card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        @if ($post->author->avatar_url)
                            <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->username }}" class="w-10 h-10 rounded-full object-cover shadow-sm">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                {{ strtoupper(substr($post->author->username, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm">@<span>{{ $post->author->username }}</span></span>
                                <span class="glass-badge {{ $post->author->account_status === 'ACTIVE' ? 'glass-badge-success' : 'glass-badge-danger' }} text-[10px] py-0 px-1.5">
                                    {{ $post->author->account_status }}
                                </span>
                            </div>
                            <span class="text-xs text-slate-500">Anggota Terdaftar MarketTrust</span>
                        </div>
                    </div>

                    <!-- DM / Negotiation Action (Non-author) -->
                    @auth
                        @if (auth()->id() !== $post->author_id)
                            <div class="shrink-0">
                                <button type="button"
                                        onclick="alert('Integrasi Direct Message (DM) sedang disiapkan bersama modul percakapan Zinky pada Tahap 2.')"
                                        class="glass-btn-primary text-xs py-2 px-3.5">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span>Kirim Pesan / Negosiasi</span>
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>

                <!-- Commercial Spec Banner (for WTB / WTS) -->
                @if ($post->variant || !is_null($post->price_offer))
                    <div class="glass-card-dark p-5 mb-6 text-white rounded-2xl relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            @if ($post->variant)
                                <div>
                                    <span class="text-[11px] uppercase tracking-wider text-indigo-300 font-bold block mb-1">
                                        Identitas Produk Terverifikasi
                                    </span>
                                    <div class="text-base sm:text-lg font-bold text-white">
                                        {{ $post->variant->product->product_name ?? 'Produk' }}
                                    </div>
                                    <div class="text-xs text-slate-300 mt-0.5">
                                        Varian: <span class="font-semibold text-white">{{ $post->variant->variant_name }}</span>
                                    </div>
                                </div>
                            @endif

                            @if (!is_null($post->price_offer))
                                <div class="sm:text-right shrink-0">
                                    <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-bold block mb-1">
                                        Tawaran Harga
                                    </span>
                                    <div class="text-xl sm:text-2xl font-extrabold text-emerald-400">
                                        Rp {{ number_format($post->price_offer, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Post Content Description -->
                <div class="text-slate-800 text-sm sm:text-base leading-relaxed whitespace-pre-line mb-8">
                    {{ $post->content }}
                </div>

                <!-- Image Gallery (Max 4 photos) -->
                @if (!empty($post->image_urls) && count($post->image_urls) > 0)
                    <div class="mb-8">
                        <h3 class="text-xs uppercase font-bold tracking-wider text-slate-500 mb-3">
                            Lampiran Foto ({{ count($post->image_urls) }} berkas)
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            @foreach ($post->image_urls as $idx => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                   class="block aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 hover:shadow-md hover:border-indigo-400 transition group relative">
                                    <img src="{{ $url }}" alt="Foto {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-6 h-6 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Author Lifecycle Management -->
                @auth
                    @if (auth()->id() === $post->author_id)
                        <div class="pt-5 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-3">
                            <span class="text-xs text-slate-500 font-medium">Anda adalah pembuat postingan ini.</span>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('forum.edit', $post->post_id) }}" class="glass-btn-secondary text-xs py-1.5 px-3">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>Edit Postingan</span>
                                </a>

                                @if ($post->status === 'ACTIVE')
                                    <form action="{{ route('forum.close', $post->post_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menutup postingan ini? Postingan tertutup tidak dapat diaktifkan kembali.')">
                                        @csrf
                                        <button type="submit" class="glass-btn-danger text-xs py-1.5 px-3">
                                            Tutup Postingan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @endauth
            </article>

            <!-- Tahap 2 Area Placeholder: Komentar & Like -->
            <div class="glass-card p-6 text-center">
                <div class="inline-flex items-center gap-2 text-slate-500 text-xs font-medium">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Fitur Komentar dan Like/Unlike postingan akan diaktifkan pada implementasi <strong>Tahap 2</strong>.</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
