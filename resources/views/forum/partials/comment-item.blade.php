@php
    $level = $level ?? 0;
    $userLikedComment = auth()->check() && $comment->likes->contains('user_id', auth()->id());
    $commentLikesCount = $comment->likes ? $comment->likes->count() : 0;
    $repliesCount = $comment->replies ? $comment->replies->count() : 0;
@endphp

<div class="glass-card p-3.5 sm:p-4 rounded-xl flex flex-col justify-between group transition hover:border-indigo-200/60 {{ $level > 0 ? 'bg-slate-50/70 border-slate-200/60' : '' }}"
     id="comment-item-{{ $comment->comment_id }}">
    <!-- Comment Header -->
    <div class="flex items-start justify-between gap-3 mb-2">
        <div class="flex items-center gap-2.5">
            @if ($comment->author && $comment->author->avatar_url)
                <img src="{{ $comment->author->avatar_url }}" alt="{{ $comment->author->username }}" class="w-6 h-6 sm:w-7 sm:h-7 rounded-full object-cover">
            @else
                <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-[10px] sm:text-xs font-bold">
                    {{ strtoupper(substr($comment->author->username ?? 'U', 0, 1)) }}
                </div>
            @endif
            <div>
                <span class="font-bold text-slate-800 text-xs">@<span>{{ $comment->author->username ?? 'User' }}</span></span>
                @if ($comment->author_id === $post->author_id)
                    <span class="glass-badge glass-badge-primary text-[9px] py-0 px-1 ml-1">Penulis</span>
                @endif
                <span class="text-[11px] text-slate-400 ml-1" title="{{ $comment->exact_wib_time ?? '' }}">&bull; {{ $comment->formatted_time ?: $comment->created_at->diffForHumans() }}</span>
            </div>
        </div>

        <!-- Comment Delete Action -->
        @auth
            @if (auth()->id() === $comment->author_id || (auth()->user()->account_type ?? null) === 'ADMIN')
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
    <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-8 sm:pl-9 mb-3">
        {{ $comment->content }}
    </p>

    <!-- Comment Actions Footer: Like, Balas, Toggle Replies -->
    <div class="pl-8 sm:pl-9 flex flex-wrap items-center gap-3 text-xs">
        <!-- Like Button -->
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

        <span class="text-slate-300">&bull;</span>

        <!-- Tombol Buka Form Balas -->
        @auth
            @if ($post->status === 'ACTIVE')
                <button type="button"
                        onclick="toggleReplyForm({{ $comment->comment_id }})"
                        class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <span>Balas</span>
                </button>
            @endif
        @else
            <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">
                Balas
            </a>
        @endauth

        <!-- Toggle Collapse/Expand Replies: Reply (jumlah) -->
        <button type="button"
                id="replies-toggle-btn-{{ $comment->comment_id }}"
                onclick="toggleReplies({{ $comment->comment_id }})"
                class="{{ $repliesCount > 0 ? 'inline-flex' : 'hidden' }} items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/70 transition">
            <svg class="w-3 h-3 transition-transform duration-200" id="replies-arrow-{{ $comment->comment_id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            <span id="replies-counter-text-{{ $comment->comment_id }}">Reply ({{ $repliesCount }})</span>
        </button>
    </div>

    <!-- Inline Reply Form (Collapsed by default) -->
    @auth
        @if ($post->status === 'ACTIVE')
            <div id="reply-form-{{ $comment->comment_id }}" class="hidden mt-3 pl-8 sm:pl-9 pt-3 border-t border-slate-100">
                <form onsubmit="submitCommentReply(event, {{ $post->post_id }}, {{ $comment->comment_id }})" class="space-y-2">
                    <div>
                        <textarea id="reply-input-{{ $comment->comment_id }}"
                                  rows="2"
                                  required
                                  maxlength="2000"
                                  onkeydown="handleReplyKeyDown(event, {{ $post->post_id }}, {{ $comment->comment_id }})"
                                  placeholder="Tulis balasan untuk @{{ $comment->author->username ?? 'user' }}... (Enter kirim, Shift+Enter baris baru)"
                                  class="glass-input w-full p-2.5 text-xs sm:text-sm leading-relaxed"></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">Tekan Enter untuk kirim</span>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="toggleReplyForm({{ $comment->comment_id }})"
                                    class="glass-btn-secondary text-[11px] py-1 px-2.5">
                                Batal
                            </button>
                            <button type="submit"
                                    id="reply-submit-btn-{{ $comment->comment_id }}"
                                    class="glass-btn-primary text-[11px] py-1 px-3">
                                Kirim Balasan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    @endauth

    <!-- Nested Replies Container (Collapsed by default as per requirement) -->
    <div id="replies-container-{{ $comment->comment_id }}"
         class="hidden mt-3 pl-4 sm:pl-8 space-y-3 border-l-2 border-indigo-200/70"
         data-replies-count="{{ $repliesCount }}">
        @if ($comment->replies && $comment->replies->count() > 0)
            @foreach ($comment->replies as $nestedReply)
                @include('forum.partials.comment-item', [
                    'comment' => $nestedReply,
                    'post' => $post,
                    'level' => $level + 1
                ])
            @endforeach
        @endif
    </div>
</div>

