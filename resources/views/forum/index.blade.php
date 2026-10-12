@extends('layouts.app')

@section('title', 'Forum Komunitas Digital')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <div class="inline-flex items-center gap-2 mb-2">
                    <span class="glass-badge glass-badge-primary">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        Komunitas Terpercaya
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Forum Komunitas & Negosiasi
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    Diskusi umum, pencarian produk (WTB), dan penawaran jual (WTS) untuk item digital terverifikasi.
                </p>
            </div>

            <div class="shrink-0">
                @auth
                    <a href="{{ route('forum.create') }}" class="glass-btn-primary w-full sm:w-auto shadow-md shadow-indigo-500/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Postingan Baru</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="glass-btn-primary w-full sm:w-auto">
                        <span>Masuk untuk Posting</span>
                    </a>
                @endauth
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="glass-panel p-4 sm:p-5 mb-8">
            <form action="{{ route('forum.index') }}" method="GET" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:gap-4">
                <!-- Category Tabs -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <a href="{{ route('forum.index', array_merge(request()->except(['category', 'page']), [])) }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-full transition {{ empty($category) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/70 text-slate-600 hover:bg-white hover:text-slate-900 border border-slate-200/60' }}">
                        Semua
                    </a>
                    <a href="{{ route('forum.index', array_merge(request()->except(['category', 'page']), ['category' => 'GENERAL'])) }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-full transition {{ $category === 'GENERAL' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/70 text-slate-600 hover:bg-white hover:text-slate-900 border border-slate-200/60' }}">
                        Umum
                    </a>
                    <a href="{{ route('forum.index', array_merge(request()->except(['category', 'page']), ['category' => 'WTB'])) }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-full transition {{ $category === 'WTB' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white/70 text-slate-600 hover:bg-white hover:text-slate-900 border border-slate-200/60' }}">
                        Want To Buy (WTB)
                    </a>
                    <a href="{{ route('forum.index', array_merge(request()->except(['category', 'page']), ['category' => 'WTS'])) }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-full transition {{ $category === 'WTS' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white/70 text-slate-600 hover:bg-white hover:text-slate-900 border border-slate-200/60' }}">
                        Want To Sell (WTS)
                    </a>
                </div>

                <!-- Hidden category state if already filtered -->
                @if ($category)
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif

                <!-- Variant Filter for WTB/WTS -->
                @if (in_array($category, ['WTB', 'WTS']))
                    <div class="sm:w-56 shrink-0">
                        <select name="variant_id" onchange="this.form.submit()" class="glass-input w-full px-3 py-1.5 text-xs">
                            <option value="">Semua Varian Produk</option>
                            @foreach ($variants as $v)
                                <option value="{{ $v->variant_id }}" {{ (string) $variantId === (string) $v->variant_id ? 'selected' : '' }}>
                                    {{ $v->product->product_name ?? 'Produk' }} - {{ $v->variant_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Keyword Search Input -->
                <div class="flex-1 relative">
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Cari judul atau isi postingan..."
                           class="glass-input w-full pl-9 pr-4 py-1.5 text-xs sm:text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="glass-btn-secondary text-xs py-1.5 px-3">
                        Filter
                    </button>
                    @if ($category || $search || $variantId)
                        <a href="{{ route('forum.index') }}" class="glass-btn-secondary text-xs py-1.5 px-3 text-slate-500 hover:text-slate-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Postings Feed Grid -->
        @if ($posts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($posts as $post)
                    <article class="glass-card glass-card-interactive p-5 flex flex-col justify-between group">
                        <div>
                            <!-- Card Header: Category & Date -->
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-1.5">
                                    @if ($post->category_code === 'GENERAL')
                                        <span class="glass-badge glass-badge-primary text-[11px] py-0.5 px-2">Umum</span>
                                    @elseif ($post->category_code === 'WTB')
                                        <span class="glass-badge glass-badge-warning text-[11px] py-0.5 px-2">WTB (Beli)</span>
                                    @elseif ($post->category_code === 'WTS')
                                        <span class="glass-badge glass-badge-success text-[11px] py-0.5 px-2">WTS (Jual)</span>
                                    @endif

                                    @if ($post->status === 'CLOSED')
                                        <span class="glass-badge glass-badge-neutral text-[10px] py-0.5 px-1.5">Ditutup</span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-500 font-medium">
                                    {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <!-- Image Thumbnail (if available) -->
                            @if (!empty($post->image_urls) && count($post->image_urls) > 0)
                                <div class="mb-3 overflow-hidden rounded-xl bg-slate-100 border border-slate-200/60 aspect-video relative">
                                    <img src="{{ $post->image_urls[0] }}"
                                         alt="{{ $post->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @if (count($post->image_urls) > 1)
                                        <span class="absolute bottom-2 right-2 glass-badge glass-badge-neutral text-[10px] px-2 py-0.5 bg-slate-900/70 text-white border-none backdrop-blur-md">
                                            +{{ count($post->image_urls) - 1 }} Foto
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <!-- Title -->
                            <h2 class="text-base font-bold text-slate-900 tracking-tight group-hover:text-indigo-600 transition mb-2">
                                <a href="{{ route('forum.show', $post->post_id) }}" class="focus:outline-hidden">
                                    {{ $post->title }}
                                </a>
                            </h2>

                            <!-- Content Snippet -->
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed line-clamp-3 mb-4">
                                {{ Str::limit(strip_tags($post->content), 140) }}
                            </p>

                            <!-- Variant Tag & Price Offer (if applicable) -->
                            @if ($post->variant)
                                <div class="mb-3 inline-flex items-center gap-1.5 text-xs text-indigo-700 bg-indigo-50/80 px-2.5 py-1 rounded-lg border border-indigo-200/50">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <span class="font-medium truncate max-w-[200px]">
                                        {{ $post->variant->product->product_name ?? 'Produk' }} &bull; {{ $post->variant->variant_name }}
                                    </span>
                                </div>
                            @endif

                            @if (!is_null($post->price_offer))
                                <div class="mb-3">
                                    <span class="text-xs text-slate-500 font-medium">Tawaran Harga:</span>
                                    <span class="text-sm font-bold text-emerald-600 ml-1">
                                        Rp {{ number_format($post->price_offer, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Card Footer: Author Profile & Action -->
                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                @if ($post->author->avatar_url)
                                    <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->username }}" class="w-6 h-6 rounded-full object-cover">
                                @else
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-[10px] font-bold">
                                        {{ strtoupper(substr($post->author->username, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-semibold text-slate-700 truncate max-w-[100px]">
                                    @<span>{{ $post->author->username }}</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 text-slate-500 text-[11px]">
                                    <span class="inline-flex items-center gap-1" title="Suka">
                                        <svg class="w-3.5 h-3.5 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        {{ $post->likes_count ?? 0 }}
                                    </span>
                                    <span class="inline-flex items-center gap-1" title="Komentar">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        {{ $post->comments_count ?? 0 }}
                                    </span>
                                </div>

                                <a href="{{ route('forum.show', $post->post_id) }}" class="text-indigo-600 font-semibold hover:text-indigo-700 inline-flex items-center gap-0.5 group-hover:translate-x-0.5 transition-transform">
                                    Detail
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="glass-card p-12 text-center max-w-lg mx-auto">
                <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center mx-auto mb-4 border border-indigo-500/20 shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Belum Ada Postingan</h3>
                <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                    Tidak ditemukan postingan aktif pada kategori atau kata kunci yang dipilih. Anda dapat membuat postingan baru pertama sekarang.
                </p>
                @auth
                    <a href="{{ route('forum.create') }}" class="glass-btn-primary text-xs sm:text-sm">
                        Buat Postingan Baru
                    </a>
                @else
                    <a href="{{ route('login') }}" class="glass-btn-primary text-xs sm:text-sm">
                        Masuk untuk Memulai Diskusi
                    </a>
                @endauth
            </div>
        @endif
    </div>
</div>
@endsection
