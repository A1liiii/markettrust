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
                                <form action="{{ route('forum.negotiate', $post->post_id) }}" method="POST">
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

                <!-- Post Interactions Bar (Like & Author Controls) -->
                <div class="pt-5 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-4">
                    <!-- Post Like Button -->
                    @php
                        $userLikedPost = auth()->check() && $post->likes->contains('user_id', auth()->id());
                        $postLikesCount = $post->likes_count ?? $post->likes->count();
                    @endphp

                    <div class="flex items-center gap-3">
                        @auth
                            <form action="{{ route('forum.posts.like', $post->post_id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold transition border {{ $userLikedPost ? 'bg-rose-50 text-rose-600 border-rose-300 shadow-xs' : 'bg-white/80 text-slate-600 hover:text-rose-600 hover:bg-rose-50/50 border-slate-200/80' }}">
                                    <svg class="w-4 h-4 {{ $userLikedPost ? 'fill-rose-500 text-rose-500' : 'fill-none text-current' }}" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span>{{ $postLikesCount }} Menyukai</span>
                                </button>
                            </form>
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
                            {{ $post->comments_count ?? $post->comments->count() }} Komentar
                        </span>
                    </div>

                    <!-- Author Lifecycle Management -->
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
                                    <form action="{{ route('forum.close', $post->post_id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menutup postingan ini? Postingan tertutup tidak dapat diaktifkan kembali.')">
                                        @csrf
                                        <button type="submit" class="glass-btn-danger text-xs py-1.5 px-3">
                                            Tutup Postingan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endauth
                </div>
            </article>

            <!-- Comments Section -->
            <section class="glass-panel p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200/60">
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <span>Diskusi Komentar ({{ $post->comments->count() }})</span>
                    </h2>
                </div>

                <!-- New Comment Form -->
                @auth
                    @if ($post->status === 'ACTIVE')
                        <form action="{{ route('forum.comments.store', $post->post_id) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="comment-content" class="sr-only">Tulis Komentar</label>
                                <textarea id="comment-content"
                                          name="content"
                                          rows="3"
                                          required
                                          maxlength="2000"
                                          placeholder="Tulis tanggapan atau ajukan pertanyaan diskusi mengenai postingan ini..."
                                          class="glass-input w-full p-3.5 text-xs sm:text-sm leading-relaxed"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="glass-btn-primary text-xs py-2 px-4 shadow-sm">
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

                <!-- Comments List -->
                <div class="space-y-4 pt-2">
                    @forelse ($post->comments as $comment)
                        <div class="glass-card p-4 rounded-xl flex flex-col justify-between group">
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
                                        <span class="text-[11px] text-slate-400 ml-1">&bull; {{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <!-- Comment Delete Action -->
                                @auth
                                    @if (auth()->id() === $comment->author_id || auth()->user()->account_type === 'ADMIN')
                                        <form action="{{ route('forum.comments.destroy', $comment->comment_id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Hapus komentar ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-rose-600 text-xs transition p-1" title="Hapus Komentar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>

                            <!-- Comment Content -->
                            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-9 mb-3">
                                {{ $comment->content }}
                            </p>

                            <!-- Comment Like Action Footer -->
                            @php
                                $userLikedComment = auth()->check() && $comment->likes->contains('user_id', auth()->id());
                                $commentLikesCount = $comment->likes->count();
                            @endphp
                            <div class="pl-9 flex items-center gap-3 text-xs">
                                @auth
                                    <form action="{{ route('forum.comments.like', $comment->comment_id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold transition {{ $userLikedComment ? 'text-rose-600' : 'text-slate-500 hover:text-rose-600' }}">
                                            <svg class="w-3.5 h-3.5 {{ $userLikedComment ? 'fill-rose-500 text-rose-500' : 'fill-none text-current' }}" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <span>{{ $commentLikesCount > 0 ? $commentLikesCount : 'Suka' }}</span>
                                        </button>
                                    </form>
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
                        <div class="glass-card p-8 text-center text-slate-500 text-xs">
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
@endsection
