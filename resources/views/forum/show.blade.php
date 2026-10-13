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
                <!-- Header: Badges & TC-FR-02: Waktu Publikasi Indonesia (WIB) -->
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
                        Dipublikasikan {{ $post->exact_wib_time }} ({{ $post->formatted_time }})
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
                                <form action="{{ route('forum.negotiate', $post->post_id) }}" method="POST" id="forum-negotiate-form">
                                    @csrf
                                    <button type="submit" class="glass-btn-primary text-xs py-2 px-3.5 shadow-md shadow-indigo-500/20">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span>Kirim Pesan / Negosiasi</span>
                                    </button>
                                </form>
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

                <!-- TC-FR-14: Image Gallery with Lightbox Popup -->
                @if (!empty($post->image_urls) && count($post->image_urls) > 0)
                    <div class="mb-8">
                        <h3 class="text-xs uppercase font-bold tracking-wider text-slate-500 mb-3 flex items-center justify-between">
                            <span>Lampiran Foto ({{ count($post->image_urls) }} foto)</span>
                            <span class="text-[11px] text-slate-400 font-normal lowercase">klik foto untuk memperbesar</span>
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            @foreach ($post->image_urls as $idx => $url)
                                <button type="button"
                                        onclick="openLightbox({{ $idx }})"
                                        class="block aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 hover:shadow-md hover:border-indigo-400 transition group relative text-left focus:outline-hidden">
                                    <img src="{{ $url }}" alt="Foto {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-6 h-6 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Post Interactions Bar (TC-FR-15: Like Post via AJAX) -->
                <div class="pt-5 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-4">
                    @php
                        $userLikedPost = auth()->check() && $post->likes->contains('user_id', auth()->id());
                        $postLikesCount = $post->likes_count ?? $post->likes->count();
                    @endphp

                    <div class="flex items-center gap-3">
                        @auth
                            <button type="button"
                                    onclick="toggleDetailPostLike({{ $post->post_id }})"
                                    id="post-like-btn"
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold transition border {{ $userLikedPost ? 'bg-rose-50 text-rose-600 border-rose-300 shadow-xs' : 'bg-white/80 text-slate-600 hover:text-rose-600 hover:bg-rose-50/50 border-slate-200/80' }}">
                                <svg class="w-4 h-4 {{ $userLikedPost ? 'fill-rose-500 text-rose-500' : 'fill-none text-current' }}" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span id="post-like-count">{{ $postLikesCount }} Menyukai</span>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white/80 text-slate-600 hover:text-indigo-600 border border-slate-200/80 transition">
                                <svg class="w-4 h-4 fill-none text-current" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span>{{ $postLikesCount }} Menyukai</span>
                            </a>
                        @endauth

                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs text-slate-500 font-medium">
                            <span id="detail-comments-count-badge">{{ $post->comments_count ?? $post->comments->count() }}</span> Komentar
                        </span>
                    </div>

                    <!-- Author Lifecycle Management (TC-FR-05: Modal Popup Tutup Postingan) -->
                    @auth
                        @if (auth()->id() === $post->author_id)
                            <div class="flex items-center gap-2">
                                <a href="{{ route('forum.edit', $post->post_id) }}" class="glass-btn-secondary text-xs py-1.5 px-3">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>Edit</span>
                                </a>

                                @if ($post->status === 'ACTIVE')
                                    <button type="button"
                                            onclick="openClosePostModal()"
                                            class="glass-btn-danger text-xs py-1.5 px-3">
                                        Tutup Postingan
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endauth
                </div>
            </article>

            <!-- Comments Section (TC-FR-15 & TC-FR-16: Komentar AJAX & Enter Kirim) -->
            <section class="glass-panel p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200/60">
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <span>Diskusi Komentar (<span id="comments-header-count">{{ $post->comments->count() }}</span>)</span>
                    </h2>
                </div>

                <!-- New Comment Form -->
                @auth
                    @if ($post->status === 'ACTIVE')
                        <form onsubmit="submitDetailComment(event, {{ $post->post_id }})" class="space-y-3">
                            <div>
                                <label for="comment-content" class="sr-only">Tulis Komentar</label>
                                <textarea id="comment-content"
                                          name="content"
                                          rows="3"
                                          required
                                          maxlength="2000"
                                          onkeydown="handleDetailCommentKeyDown(event, {{ $post->post_id }})"
                                          placeholder="Tulis tanggapan atau ajukan pertanyaan... (Tekan Enter untuk kirim, Shift+Enter untuk baris baru)"
                                          class="glass-input w-full p-3.5 text-xs sm:text-sm leading-relaxed"></textarea>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-400">
                                    Tekan <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px]">Enter</kbd> untuk kirim, <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px]">Shift+Enter</kbd> baris baru.
                                </span>
                                <button type="submit" id="comment-submit-btn" class="glass-btn-primary text-xs py-2 px-4 shadow-sm">
                                    <span>Kirim Komentar</span>
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="p-3.5 rounded-xl bg-slate-100 text-slate-600 text-xs text-center border border-slate-200/80">
                            Postingan ini telah ditutup sehingga tidak dapat menerima komentar baru.
                        </div>
                    @endif
                @else
                    <div class="p-4 rounded-xl glass-card text-center text-xs text-slate-600">
                        <span>Ingin ikut berdiskusi? </span>
                        <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-indigo-700 underline">
                            Masuk ke Akun Anda
                        </a>
                        <span> atau </span>
                        <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-700 underline">
                            Daftar Akun Baru
                        </a>.
                    </div>
                @endauth

                <!-- Comments List (TC-FR-03: <7 hari relatif, >=7 hari format lengkap) -->
                <div id="comments-container" class="space-y-4 pt-2">
                    @forelse ($post->comments as $comment)
                        <div class="glass-card p-4 rounded-xl flex flex-col justify-between group" id="comment-item-{{ $comment->comment_id }}">
                            <!-- Comment Header -->
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2.5">
                                    @if ($comment->author->avatar_url)
                                        <img src="{{ $comment->author->avatar_url }}" alt="{{ $comment->author->username }}" class="w-7 h-7 rounded-full object-cover">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-xs font-bold">
                                            {{ strtoupper(substr($comment->author->username, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-bold text-slate-800 text-xs">@<span>{{ $comment->author->username }}</span></span>
                                        @if ($comment->author_id === $post->author_id)
                                            <span class="glass-badge glass-badge-primary text-[9px] py-0 px-1 ml-1">Penulis</span>
                                        @endif
                                        <span class="text-[11px] text-slate-400 ml-1" title="{{ $comment->exact_wib_time }}">&bull; {{ $comment->formatted_time }}</span>
                                    </div>
                                </div>

                                <!-- Comment Delete Action -->
                                @auth
                                    @if (auth()->id() === $comment->author_id || auth()->user()->account_type === 'ADMIN')
                                        <button type="button"
                                                onclick="deleteCommentAjax({{ $comment->comment_id }})"
                                                class="text-slate-400 hover:text-rose-600 text-xs transition p-1"
                                                title="Hapus Komentar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                @endauth
                            </div>

                            <!-- Comment Content -->
                            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-9 mb-3">
                                {{ $comment->content }}
                            </p>

                            <!-- Comment Like Action Footer (TC-FR-15: AJAX Toggle Like Comment) -->
                            @php
                                $userLikedComment = auth()->check() && $comment->likes->contains('user_id', auth()->id());
                                $commentLikesCount = $comment->likes->count();
                            @endphp
                            <div class="pl-9 flex items-center gap-3 text-xs">
                                @auth
                                    <button type="button"
                                            onclick="toggleCommentLikeAjax({{ $comment->comment_id }})"
                                            id="comment-like-btn-{{ $comment->comment_id }}"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold transition {{ $userLikedComment ? 'text-rose-600' : 'text-slate-500 hover:text-rose-600' }}">
                                        <svg class="w-3.5 h-3.5 {{ $userLikedComment ? 'fill-rose-500 text-rose-500' : 'fill-none text-current' }}" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span id="comment-likes-count-{{ $comment->comment_id }}">{{ $commentLikesCount > 0 ? $commentLikesCount : 'Suka' }}</span>
                                    </button>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-slate-500 hover:text-indigo-600">
                                        <svg class="w-3.5 h-3.5 fill-none text-current" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span>{{ $commentLikesCount > 0 ? $commentLikesCount : 'Suka' }}</span>
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div id="no-comments-placeholder" class="glass-card p-8 text-center text-slate-500 text-xs">
                            <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p class="font-medium text-slate-700 mb-1">Belum Ada Komentar</p>
                            <p>Jadilah yang pertama memberikan tanggapan atau pertanyaan pada diskusi ini!</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>

<!-- TC-FR-05: Modal Popup Tutup Postingan yang Elegan -->
<div id="close-post-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="glass-card p-6 max-w-md w-full bg-white shadow-2xl rounded-2xl border border-slate-200" onclick="event.stopPropagation()">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">Tutup Postingan Forum?</h3>
                <p class="text-xs text-slate-500">Konfirmasi status penutupan postingan</p>
            </div>
        </div>

        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
            Postingan yang ditutup tidak dapat menerima komentar baru atau diubah kembali. Apakah Anda yakin ingin menutup postingan ini?
        </p>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" onclick="closeClosePostModal()" class="glass-btn-secondary text-xs sm:text-sm py-2 px-4">
                Batal
            </button>
            <form action="{{ route('forum.close', $post->post_id) }}" method="POST">
                @csrf
                <button type="submit" class="glass-btn-danger text-xs sm:text-sm py-2 px-4">
                    Tutup Postingan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- TC-FR-14: Modal Lightbox Preview Foto dengan Navigasi Panah & Swipe -->
<div id="lightbox-modal"
     class="fixed inset-0 z-50 hidden bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 select-none"
     onclick="closeLightbox()">

    <!-- Tombol Tutup (X) -->
    <button type="button"
            onclick="closeLightbox()"
            class="absolute top-4 right-4 text-white/70 hover:text-white p-2.5 rounded-full bg-slate-900/60 hover:bg-slate-900 transition z-50"
            title="Tutup (Esc)">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Tombol Panah Kiri (Desktop) -->
    <button type="button"
            id="lightbox-prev-btn"
            onclick="event.stopPropagation(); prevLightboxImage()"
            class="absolute left-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white p-3 rounded-full bg-slate-900/60 hover:bg-slate-900 transition z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    <!-- Container Gambar Utama -->
    <div class="relative max-w-4xl max-h-[85vh] flex flex-col items-center justify-center" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" alt="Pratinjau Foto" class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-2xl">
        <div id="lightbox-counter" class="text-white/80 text-xs mt-3 px-3 py-1 bg-slate-900/80 rounded-full font-semibold"></div>
    </div>

    <!-- Tombol Panah Kanan (Desktop) -->
    <button type="button"
            id="lightbox-next-btn"
            onclick="event.stopPropagation(); nextLightboxImage()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-white/80 hover:text-white p-3 rounded-full bg-slate-900/60 hover:bg-slate-900 transition z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>

<!-- JavaScript Interaktivitas Detail (TC-FR-05, TC-FR-14, TC-FR-15, TC-FR-16 & DM Negosiasi) -->
<script>
    // Inisialisasi DM Negosiasi Quick-Start (Zinky DM)
    document.addEventListener('DOMContentLoaded', function() {
        const negotiateForm = document.getElementById('forum-negotiate-form');
        if (negotiateForm) {
            negotiateForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const startUrl = @json(route('conversations.from-post.quick', $post->post_id));
                const csrfToken = @json(csrf_token());

                try {
                    const res = await fetch(startUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (window.openGlobalDmDrawer) {
                            window.openGlobalDmDrawer(data.conversation_id);
                        }
                    } else {
                        negotiateForm.submit();
                    }
                } catch (err) {
                    negotiateForm.submit();
                }
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('open_dm') && window.openGlobalDmDrawer) {
            window.openGlobalDmDrawer(urlParams.get('open_dm'));
        }
    });

    const postImages = @json($post->image_urls ?? []);
    let currentImageIndex = 0;
    let touchStartX = 0;
    let touchEndX = 0;

    // TC-FR-05: Modal Tutup Postingan
    function openClosePostModal() {
        document.getElementById('close-post-modal').classList.remove('hidden');
    }
    function closeClosePostModal() {
        document.getElementById('close-post-modal').classList.add('hidden');
    }

    // TC-FR-14: Lightbox Modal Foto
    function openLightbox(index) {
        if (!postImages || postImages.length === 0) return;
        currentImageIndex = index;
        updateLightboxView();
        document.getElementById('lightbox-modal').classList.remove('hidden');
    }

    function closeLightbox() {
        document.getElementById('lightbox-modal').classList.add('hidden');
    }

    function prevLightboxImage() {
        if (currentImageIndex > 0) {
            currentImageIndex--;
        } else {
            currentImageIndex = postImages.length - 1; // loop ke akhir
        }
        updateLightboxView();
    }

    function nextLightboxImage() {
        if (currentImageIndex < postImages.length - 1) {
            currentImageIndex++;
        } else {
            currentImageIndex = 0; // loop ke awal
        }
        updateLightboxView();
    }

    function updateLightboxView() {
        const img = document.getElementById('lightbox-img');
        const counter = document.getElementById('lightbox-counter');
        img.src = postImages[currentImageIndex];
        counter.textContent = `Foto ${currentImageIndex + 1} dari ${postImages.length}`;
    }

    // Navigasi Keyboard Lightbox
    document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('lightbox-modal');
        if (!modal.classList.contains('hidden')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevLightboxImage();
            if (e.key === 'ArrowRight') nextLightboxImage();
        }
    });

    // Touch Swipe untuk Mobile Lightbox
    const lightboxModal = document.getElementById('lightbox-modal');
    lightboxModal.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    }, false);

    lightboxModal.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipeGesture();
    }, false);

    function handleSwipeGesture() {
        if (touchEndX < touchStartX - 50) {
            nextLightboxImage(); // Swipe kiri -> foto berikutnya
        }
        if (touchEndX > touchStartX + 50) {
            prevLightboxImage(); // Swipe kanan -> foto sebelumnya
        }
    }

    // TC-FR-15: Toggle Like Post via AJAX
    function toggleDetailPostLike(postId) {
        const btn = document.getElementById('post-like-btn');
        const countSpan = document.getElementById('post-like-count');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        btn.disabled = true;

        fetch('/forum/' + postId + '/like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        })
        .then(res => res.json())
        .then(data => {
            countSpan.textContent = data.likes_count + ' Menyukai';
            const svg = btn.querySelector('svg');

            if (data.liked) {
                btn.className = 'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold transition border bg-rose-50 text-rose-600 border-rose-300 shadow-xs';
                svg.className = 'w-4 h-4 fill-rose-500 text-rose-500';
            } else {
                btn.className = 'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold transition border bg-white/80 text-slate-600 hover:text-rose-600 hover:bg-rose-50/50 border-slate-200/80';
                svg.className = 'w-4 h-4 fill-none text-current';
            }
        })
        .catch(err => {
            alert('Gagal memproses suka: ' + err.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
    }

    // TC-FR-16: Kirim Komentar dengan Enter (Shift+Enter Baris Baru)
    function handleDetailCommentKeyDown(event, postId) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitDetailComment(event, postId);
        }
    }

    // TC-FR-15 & TC-FR-16: Submit Komentar via AJAX
    function submitDetailComment(event, postId) {
        event.preventDefault();
        const textarea = document.getElementById('comment-content');
        const submitBtn = document.getElementById('comment-submit-btn');
        const content = textarea.value.trim();

        if (content.length < 3) {
            alert('Komentar minimal 3 karakter.');
            textarea.focus();
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        textarea.disabled = true;
        if (submitBtn) submitBtn.disabled = true;

        fetch('/forum/' + postId + '/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ content: content })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(e => { throw new Error(e.message || 'Gagal mengirim komentar'); });
            }
            return res.json();
        })
        .then(data => {
            textarea.value = '';
            document.getElementById('comments-header-count').textContent = data.comments_count;
            document.getElementById('detail-comments-count-badge').textContent = data.comments_count;

            const placeholder = document.getElementById('no-comments-placeholder');
            if (placeholder) placeholder.remove();

            // Append elemen komentar baru ke daftar
            appendNewComment(data.comment);
        })
        .catch(err => {
            alert('Terjadi kesalahan: ' + err.message);
        })
        .finally(() => {
            textarea.disabled = false;
            if (submitBtn) submitBtn.disabled = false;
            textarea.focus();
        });
    }

    function appendNewComment(comment) {
        const container = document.getElementById('comments-container');
        const card = document.createElement('div');
        card.className = 'glass-card p-4 rounded-xl flex flex-col justify-between group';
        card.id = 'comment-item-' + comment.comment_id;

        const avatarInitial = comment.author_username ? comment.author_username.charAt(0).toUpperCase() : 'U';
        const avatarHtml = comment.author_avatar
            ? `<img src="${comment.author_avatar}" alt="${comment.author_username}" class="w-7 h-7 rounded-full object-cover">`
            : `<div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-xs font-bold">${avatarInitial}</div>`;

        const authorBadge = comment.is_post_author ? '<span class="glass-badge glass-badge-primary text-[9px] py-0 px-1 ml-1">Penulis</span>' : '';

        card.innerHTML = `
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="flex items-center gap-2.5">
                    ${avatarHtml}
                    <div>
                        <span class="font-bold text-slate-800 text-xs">@<span>${comment.author_username}</span></span>
                        ${authorBadge}
                        <span class="text-[11px] text-slate-400 ml-1">&bull; ${comment.formatted_time}</span>
                    </div>
                </div>
                <button type="button"
                        onclick="deleteCommentAjax(${comment.comment_id})"
                        class="text-slate-400 hover:text-rose-600 text-xs transition p-1"
                        title="Hapus Komentar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-9 mb-3">
                ${comment.content}
            </p>
            <div class="pl-9 flex items-center gap-3 text-xs">
                <button type="button"
                        onclick="toggleCommentLikeAjax(${comment.comment_id})"
                        id="comment-like-btn-${comment.comment_id}"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold transition text-slate-500 hover:text-rose-600">
                    <svg class="w-3.5 h-3.5 fill-none text-current" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span id="comment-likes-count-${comment.comment_id}">Suka</span>
                </button>
            </div>
        `;

        container.appendChild(card);
    }

    // TC-FR-15: Toggle Like Comment via AJAX
    function toggleCommentLikeAjax(commentId) {
        const btn = document.getElementById('comment-like-btn-' + commentId);
        const countSpan = document.getElementById('comment-likes-count-' + commentId);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        btn.disabled = true;

        fetch('/forum/comments/' + commentId + '/like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        })
        .then(res => res.json())
        .then(data => {
            countSpan.textContent = data.likes_count > 0 ? data.likes_count : 'Suka';
            const svg = btn.querySelector('svg');

            if (data.liked) {
                btn.className = 'inline-flex items-center gap-1.5 text-xs font-semibold transition text-rose-600';
                svg.className = 'w-3.5 h-3.5 fill-rose-500 text-rose-500';
            } else {
                btn.className = 'inline-flex items-center gap-1.5 text-xs font-semibold transition text-slate-500 hover:text-rose-600';
                svg.className = 'w-3.5 h-3.5 fill-none text-current';
            }
        })
        .catch(err => {
            alert('Gagal memproses suka komentar: ' + err.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
    }

    // Hapus Komentar via AJAX
    function deleteCommentAjax(commentId) {
        if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('/forum/comments/' + commentId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        })
        .then(res => res.json())
        .then(data => {
            const item = document.getElementById('comment-item-' + commentId);
            if (item) item.remove();
            document.getElementById('comments-header-count').textContent = data.comments_count;
            document.getElementById('detail-comments-count-badge').textContent = data.comments_count;
        })
        .catch(err => {
            alert('Gagal menghapus komentar: ' + err.message);
        });
    }
</script>
@endsection
