@extends('layouts.app')

@section('title', 'Forum Komunitas Digital')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
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
            <!-- Navbar Kategori (TC-FR-07: Hapus "Semua", Terpusat & Melebar, TC-FR-10: Pindah Tab Bersihkan Query) -->
            <div class="grid grid-cols-3 gap-2 w-full max-w-lg mx-auto mb-5 p-1.5 bg-slate-100/90 rounded-2xl border border-slate-200/70 shadow-inner">
                <a href="{{ route('forum.index', ['category' => 'GENERAL']) }}"
                   class="text-xs sm:text-sm font-semibold text-center py-2 px-3 rounded-xl transition duration-200 {{ $category === 'GENERAL' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                    Umum
                </a>
                <a href="{{ route('forum.index', ['category' => 'WTB']) }}"
                   class="text-xs sm:text-sm font-semibold text-center py-2 px-3 rounded-xl transition duration-200 {{ $category === 'WTB' ? 'bg-amber-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                    WTB (Beli)
                </a>
                <a href="{{ route('forum.index', ['category' => 'WTS']) }}"
                   class="text-xs sm:text-sm font-semibold text-center py-2 px-3 rounded-xl transition duration-200 {{ $category === 'WTS' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                    WTS (Jual)
                </a>
            </div>

            <!-- Form Filter & Pencarian (TC-FR-08 & TC-FR-09) -->
            <form action="{{ route('forum.index') }}" method="GET" class="space-y-3 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
                <input type="hidden" name="category" value="{{ $category }}">

                <!-- Variant Filter for WTB/WTS -->
                @if (in_array($category, ['WTB', 'WTS']))
                    <div class="sm:w-64 shrink-0">
                        <select name="variant_id" onchange="this.form.submit()" class="glass-input w-full px-3 py-2 text-xs">
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
                           class="glass-input w-full pl-9 pr-4 py-2 text-xs sm:text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="glass-btn-primary text-xs py-2 px-4">
                        Cari
                    </button>

                    <!-- TC-FR-09: Tombol Reset Khusus Varian Saja -->
                    @if ($variantId)
                        <a href="{{ route('forum.index', array_filter(['category' => $category, 'q' => $search])) }}"
                           class="glass-btn-secondary text-xs py-2 px-3 text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200"
                           title="Hapus filter varian saja">
                            Reset Varian
                        </a>
                    @endif

                    @if ($search)
                        <a href="{{ route('forum.index', array_filter(['category' => $category, 'variant_id' => $variantId])) }}"
                           class="glass-btn-secondary text-xs py-2 px-3 text-slate-500 hover:text-slate-700"
                           title="Hapus pencarian teks">
                            Reset Cari
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Postings Feed (TC-FR-11: Card Kapsul Ramping Tanpa Foto) -->
        @if ($posts->count() > 0)
            <div class="space-y-3.5">
                @foreach ($posts as $post)
                    @php
                        $userLiked = auth()->check() && $post->likes->contains('user_id', auth()->id());
                    @endphp
                    <div class="glass-card glass-card-interactive p-4 sm:p-5 rounded-2xl transition hover:shadow-lg hover:border-indigo-300/60 cursor-pointer group"
                         onclick="handleCardClick(event, '{{ route('forum.show', $post->post_id) }}')"
                         id="post-card-{{ $post->post_id }}">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <!-- Info Utama Postingan -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
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

                                    @if ($post->variant)
                                        <span class="inline-flex items-center gap-1 text-[11px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200/50 truncate max-w-[220px]">
                                            {{ $post->variant->product->product_name ?? 'Produk' }} &bull; {{ $post->variant->variant_name }}
                                        </span>
                                    @endif

                                    @if (!is_null($post->price_offer))
                                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/50">
                                            Rp {{ number_format($post->price_offer, 0, ',', '.') }}
                                        </span>
                                    @endif

                                    <span class="text-[11px] text-slate-400 font-medium">
                                        &bull; {{ $post->formatted_time ?: $post->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight group-hover:text-indigo-600 transition truncate">
                                    {{ $post->title }}
                                </h2>

                                <p class="text-xs sm:text-sm text-slate-600 line-clamp-2 mt-1 leading-relaxed">
                                    {{ Str::limit(strip_tags($post->content), 160) }}
                                </p>
                            </div>

                            <!-- Footer Kanan: Author & Interaksi (TC-FR-01: Like & Komentar Tanpa Buka Detail) -->
                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                <!-- Author Identity -->
                                <div class="flex items-center gap-2">
                                    @if ($post->author->avatar_url)
                                        <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->username }}" class="w-6 h-6 rounded-full object-cover">
                                    @else
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-[10px] font-bold">
                                            {{ strtoupper(substr($post->author->username, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="font-semibold text-slate-700 text-xs truncate max-w-[90px]">
                                        @<span>{{ $post->author->username }}</span>
                                    </span>
                                </div>

                                <!-- Action Buttons: Like & Toggle Comment Drawer -->
                                <div class="flex items-center gap-2">
                                    <!-- Tombol Like (TC-FR-01 & TC-FR-15: AJAX Toggle) -->
                                    <button type="button"
                                            onclick="toggleFeedLike(event, {{ $post->post_id }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border transition text-xs font-semibold {{ $userLiked ? 'bg-rose-50 border-rose-200 text-rose-600' : 'bg-white/70 border-slate-200/60 text-slate-600 hover:text-rose-600 hover:bg-rose-50/50' }}"
                                            id="like-btn-{{ $post->post_id }}"
                                            title="Suka Postingan">
                                        <svg class="w-3.5 h-3.5 {{ $userLiked ? 'fill-rose-500 text-rose-500' : 'fill-none text-current' }}" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        <span id="like-count-{{ $post->post_id }}">{{ $post->likes_count ?? 0 }}</span>
                                    </button>

                                    <!-- Tombol Komentar (TC-FR-01: Buka Input Komentar Langsung) -->
                                    <button type="button"
                                            onclick="toggleCommentDrawer(event, {{ $post->post_id }})"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200/60 bg-white/70 hover:bg-indigo-50/50 hover:text-indigo-600 text-slate-600 transition text-xs font-semibold"
                                            title="Tulis Komentar Langsung">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span id="comment-count-{{ $post->post_id }}">{{ $post->comments_count ?? 0 }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Drawer Input Komentar Langsung (TC-FR-01, TC-FR-15, TC-FR-16) -->
                        <div id="comment-drawer-{{ $post->post_id }}"
                             class="hidden mt-3 pt-3 border-t border-slate-200/60"
                             onclick="event.stopPropagation()">
                            @auth
                                @if ($post->status === 'ACTIVE')
                                    <form onsubmit="submitFeedComment(event, {{ $post->post_id }})" class="flex items-center gap-2">
                                        <input type="text"
                                               id="comment-input-{{ $post->post_id }}"
                                               placeholder="Tulis komentar... (Tekan Enter untuk kirim)"
                                               class="glass-input flex-1 px-3 py-1.5 text-xs sm:text-sm"
                                               autocomplete="off"
                                               required
                                               onkeydown="handleFeedCommentKeyDown(event, {{ $post->post_id }})">
                                        <button type="submit"
                                                id="comment-submit-{{ $post->post_id }}"
                                                class="glass-btn-primary text-xs py-1.5 px-3 shrink-0">
                                            Kirim
                                        </button>
                                    </form>
                                    <p class="text-[10px] text-slate-400 mt-1 pl-1">
                                        Tekan <kbd class="px-1 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px]">Enter</kbd> untuk mengirim.
                                    </p>
                                @else
                                    <p class="text-xs text-slate-500 italic">
                                        Postingan ini sudah ditutup sehingga tidak dapat menerima komentar baru.
                                    </p>
                                @endif
                            @else
                                <div class="text-xs text-slate-600 flex items-center justify-between">
                                    <span>Masuk untuk menulis komentar langsung pada postingan ini.</span>
                                    <a href="{{ route('login') }}" class="glass-btn-secondary text-[11px] py-1 px-2.5 text-indigo-600 font-semibold">
                                        Masuk
                                    </a>
                                </div>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @else
            <!-- Empty State (TC-FR-08: Keadaan Kosong Informatif) -->
            <div class="glass-card p-12 text-center max-w-lg mx-auto">
                <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center mx-auto mb-4 border border-indigo-500/20 shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Tidak Ditemukan Postingan</h3>
                <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                    @if ($search)
                        Tidak ada postingan yang sesuai dengan kata kunci &ldquo;<span class="font-semibold text-slate-800">{{ $search }}</span>&rdquo;. Coba gunakan kata kunci lain atau reset pencarian.
                    @else
                        Belum ada postingan aktif pada kategori ini. Anda dapat menjadi orang pertama yang memulai diskusi!
                    @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    @if ($search || $variantId)
                        <a href="{{ route('forum.index', ['category' => $category]) }}" class="glass-btn-secondary text-xs sm:text-sm">
                            Reset Filter
                        </a>
                    @endif
                    @auth
                        <a href="{{ route('forum.create') }}" class="glass-btn-primary text-xs sm:text-sm">
                            Buat Postingan Baru
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="glass-btn-primary text-xs sm:text-sm">
                            Masuk Sekarang
                        </a>
                    @endauth
                </div>
            </div>
        @endif
    </div>
</div>

<!-- JavaScript Interaktivitas Feed (TC-FR-01, TC-FR-11, TC-FR-15, TC-FR-16) -->
<script>
    // TC-FR-11: Klik card membuka detail postingan, kecuali jika klik elemen interaktif
    function handleCardClick(event, url) {
        // Abaikan jika target klik adalah tombol, input, link, form, atau elemen di dalamnya
        if (event.target.closest('button') || event.target.closest('a') || event.target.closest('input') || event.target.closest('form')) {
            return;
        }
        window.location.href = url;
    }

    // TC-FR-01 & TC-FR-15: Toggle Like langsung dari feed via AJAX
    function toggleFeedLike(event, postId) {
        event.stopPropagation();

        @guest
            window.location.href = "{{ route('login') }}";
            return;
        @endguest

        const btn = document.getElementById('like-btn-' + postId);
        const countSpan = document.getElementById('like-count-' + postId);
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
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memperbarui suka');
            }
            return response.json();
        })
        .then(data => {
            countSpan.textContent = data.likes_count;
            const svg = btn.querySelector('svg');

            if (data.liked) {
                btn.classList.remove('bg-white/70', 'border-slate-200/60', 'text-slate-600');
                btn.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-600');
                svg.classList.remove('fill-none', 'text-current');
                svg.classList.add('fill-rose-500', 'text-rose-500');
            } else {
                btn.classList.remove('bg-rose-50', 'border-rose-200', 'text-rose-600');
                btn.classList.add('bg-white/70', 'border-slate-200/60', 'text-slate-600');
                svg.classList.remove('fill-rose-500', 'text-rose-500');
                svg.classList.add('fill-none', 'text-current');
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan saat memproses suka: ' + err.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
    }

    // TC-FR-01: Toggle Drawer Komentar Inline pada Card
    function toggleCommentDrawer(event, postId) {
        event.stopPropagation();
        const drawer = document.getElementById('comment-drawer-' + postId);
        drawer.classList.toggle('hidden');

        if (!drawer.classList.contains('hidden')) {
            const input = document.getElementById('comment-input-' + postId);
            if (input) {
                setTimeout(() => input.focus(), 50);
            }
        }
    }

    // TC-FR-16: Kirim komentar dengan Enter
    function handleFeedCommentKeyDown(event, postId) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitFeedComment(event, postId);
        }
    }

    // TC-FR-01, TC-FR-15, TC-FR-16: Submit Komentar langsung dari Card via AJAX
    function submitFeedComment(event, postId) {
        event.preventDefault();
        event.stopPropagation();

        @guest
            window.location.href = "{{ route('login') }}";
            return;
        @endguest

        const input = document.getElementById('comment-input-' + postId);
        const submitBtn = document.getElementById('comment-submit-' + postId);
        const countSpan = document.getElementById('comment-count-' + postId);
        const drawer = document.getElementById('comment-drawer-' + postId);
        const content = input.value.trim();

        if (content.length < 3) {
            alert('Komentar minimal 3 karakter.');
            input.focus();
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        input.disabled = true;
        submitBtn.disabled = true;

        fetch('/forum/' + postId + '/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ content: content })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message || 'Gagal mengirim komentar'); });
            }
            return response.json();
        })
        .then(data => {
            countSpan.textContent = data.comments_count;
            input.value = '';
            drawer.classList.add('hidden');
            alert('Komentar Anda berhasil dipublikasikan!');
        })
        .catch(err => {
            alert('Terjadi kesalahan: ' + err.message);
        })
        .finally(() => {
            input.disabled = false;
            submitBtn.disabled = false;
        });
    }
</script>
@endsection
