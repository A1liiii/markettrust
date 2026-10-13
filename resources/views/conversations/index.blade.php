@extends('layouts.app')

@section('title', 'Pesan Langsung (DM)')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60 mb-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Direct Messaging (UC-08)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">Kotak Masuk Pesan</h1>
            <p class="text-sm text-slate-600 mt-1">Komunikasi aman antar peserta transaksi dan diskusi produk digital.</p>
        </div>
        <div>
            <button type="button"
                    data-open-dm-drawer
                    class="dm-floating-launcher text-white text-xs font-bold py-2.5 px-4 rounded-2xl flex items-center gap-2 shadow-md hover:scale-105 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Buka di Side Panel</span>
            </button>
        </div>
    </div>

    <!-- Conversation List -->
    @if($conversations->isEmpty())
        <div class="glass-panel p-10 text-center max-w-xl mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-900 mb-1">Belum Ada Percakapan</h3>
            <p class="text-sm text-slate-500 mb-6">Mulai diskusi langsung dengan penjual melalui katalog Marketplace atau thread Forum.</p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('home') }}#marketplace" class="glass-btn-primary text-xs py-2 px-4">
                    Jelajahi Marketplace
                </a>
                <a href="{{ route('home') }}#forum" class="glass-btn-secondary text-xs py-2 px-4">
                    Buka Forum
                </a>
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach($conversations as $conversation)
                @php
                    $otherUser = $conversation->getOtherParticipant(auth()->user());
                    $unreadCount = $conversation->unreadCountFor(auth()->user());
                    $latestMsg = $conversation->messages->first();
                @endphp
                <a href="{{ route('conversations.show', $conversation->conversation_id) }}"
                   data-open-dm-drawer
                   data-conversation-id="{{ $conversation->conversation_id }}"
                   class="dm-bubble-pop-card block p-4 sm:p-5 transition">
                    <div class="flex items-start gap-4">
                        <!-- User Avatar -->
                        <div class="shrink-0">
                            @if($otherUser && $otherUser->avatar_url)
                                <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->username }}" class="w-12 h-12 rounded-2xl object-cover border border-white/80 shadow-xs">
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white flex items-center justify-center font-bold text-base shadow-md shadow-indigo-500/20">
                                    {{ $otherUser ? strtoupper(substr($otherUser->username, 0, 1)) : '?' }}
                                </div>
                            @endif
                        </div>

                        <!-- Conversation Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <div class="flex items-center gap-2 truncate">
                                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 truncate">
                                        @<span>{{ $otherUser ? $otherUser->username : 'Pengguna' }}</span>
                                    </h2>
                                    @if($otherUser && $otherUser->account_status === 'ACTIVE')
                                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" title="Aktif"></span>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-400 shrink-0">
                                    {{ $latestMsg ? $latestMsg->sent_at->diffForHumans() : $conversation->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <!-- Context Pill -->
                            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                @if($conversation->listing)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        Listing: {{ Str::limit($conversation->listing->title, 28) }}
                                    </span>
                                @elseif($conversation->post)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200/60">
                                        <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        Forum: {{ Str::limit($conversation->post->title, 28) }}
                                    </span>
                                @elseif($conversation->agreement)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Agreement #{{ $conversation->agreement_id }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200/60">
                                        Percakapan Langsung
                                    </span>
                                @endif
                            </div>

                            <!-- Message Snippet -->
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-xs sm:text-sm text-slate-600 truncate">
                                    @if($latestMsg)
                                        @if($latestMsg->sender_id === auth()->user()->user_id)
                                            <span class="text-slate-400">Anda:</span>
                                        @endif
                                        {{ $latestMsg->content }}
                                    @else
                                        <span class="italic text-slate-400">Belum ada pesan terkirim. Mulai percakapan sekarang.</span>
                                    @endif
                                </p>
                                @if($unreadCount > 0)
                                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-600 text-white shadow-xs">
                                        {{ $unreadCount }} baru
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
