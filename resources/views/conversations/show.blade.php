@extends('layouts.app')

@section('title', 'Percakapan dengan @' . ($otherUser ? $otherUser->username : 'Pengguna'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Top Bar: Back & Opponent Info -->
    <div class="glass-panel p-4 mb-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('conversations.index') }}"
               class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-white/60 transition border border-transparent hover:border-white/60"
               title="Kembali ke Kotak Masuk">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <!-- Other User Avatar & Info -->
            <div class="flex items-center gap-3">
                @if($otherUser && $otherUser->avatar_url)
                    <img src="{{ $otherUser->avatar_url }}" alt="{{ $otherUser->username }}" class="w-10 h-10 rounded-full object-cover border border-white/80 shadow-xs">
                @else
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        {{ $otherUser ? strtoupper(substr($otherUser->username, 0, 1)) : '?' }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">
                            @<span>{{ $otherUser ? $otherUser->username : 'Pengguna' }}</span>
                        </h2>
                        @if($otherUser)
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded-full {{ $otherUser->account_status === 'ACTIVE' ? 'bg-emerald-500/15 text-emerald-700 border border-emerald-500/20' : 'bg-rose-500/15 text-rose-700 border border-rose-500/20' }}">
                                {{ $otherUser->account_status }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500">Percakapan Pribadi Terverifikasi</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Terkoneksi
            </span>
        </div>
    </div>

    <!-- Context Attachment Banner (Listing / Post / Agreement) -->
    @if($conversation->listing)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-emerald-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-emerald-700">Konteks: Produk Marketplace</div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $conversation->listing->title }}</h3>
                    <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                        <span class="font-bold text-slate-800">Rp {{ number_format($conversation->listing->price, 0, ',', '.') }}</span>
                        <span>&bull;</span>
                        <span>Metode: {{ $conversation->listing->delivery_method }}</span>
                    </div>
                </div>
            </div>
            <div class="text-xs text-slate-500 shrink-0">
                Estimasi: {{ $conversation->listing->estimated_delivery_value }} {{ strtolower($conversation->listing->estimated_delivery_unit) }}
            </div>
        </div>
    @elseif($conversation->post)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-purple-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-purple-700">Konteks: Thread Forum ({{ $conversation->post->category_code }})</div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $conversation->post->title }}</h3>
                    @if($conversation->post->price_offer)
                        <div class="text-xs text-slate-600 mt-0.5">
                            Penawaran: <span class="font-bold text-slate-800">Rp {{ number_format($conversation->post->price_offer, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($conversation->agreement)
        <div class="glass-card p-3 sm:p-4 mb-4 border-l-4 border-l-indigo-500 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-indigo-700">Konteks: Sheet Kesepakatan Transaksi</div>
                    <h3 class="text-sm font-semibold text-slate-900">Agreement #{{ $conversation->agreement->agreement_id }} &bull; {{ $conversation->agreement->item_title }}</h3>
                </div>
            </div>
            <span class="glass-badge glass-badge-primary text-[11px]">{{ $conversation->agreement->status }}</span>
        </div>
    @endif

    <!-- Chat Stream Box -->
    <div class="glass-panel p-4 sm:p-6 mb-4 flex flex-col min-h-[420px] max-h-[600px] overflow-y-auto" id="messages-container">
        @if($conversation->messages->isEmpty())
            <div class="m-auto text-center py-12 text-slate-400">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium">Belum ada pesan di percakapan ini.</p>
                <p class="text-xs text-slate-400 mt-1">Kirim pesan pertama untuk memulai negosiasi atau diskusi.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($conversation->messages as $msg)
                    @php
                        $isMe = $msg->sender_id === auth()->user()->user_id;
                    @endphp

                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] sm:max-w-[70%]">
                            <!-- Sender name label for other user -->
                            @if(! $isMe)
                                <div class="text-[11px] font-semibold text-slate-500 mb-1 ml-1">
                                    @<span>{{ $msg->sender ? $msg->sender->username : 'Peserta' }}</span>
                                </div>
                            @endif

                            <!-- Message Bubble -->
                            <div class="p-3.5 sm:p-4 rounded-2xl text-sm leading-relaxed shadow-xs {{ $isMe ? 'bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white rounded-tr-xs' : 'bg-white/85 backdrop-blur-md border border-slate-200/80 text-slate-800 rounded-tl-xs' }}">
                                <div class="whitespace-pre-wrap break-words">{{ $msg->content }}</div>
                            </div>

                            <!-- Timestamp & Read Status -->
                            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400 {{ $isMe ? 'justify-end mr-1' : 'justify-start ml-1' }}">
                                <span>{{ $msg->sent_at ? $msg->sent_at->format('H:i') : '' }}</span>
                                @if($isMe)
                                    <span>&bull;</span>
                                    @if($msg->read_at)
                                        <span class="text-indigo-600 font-medium inline-flex items-center gap-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Dibaca
                                        </span>
                                    @else
                                        <span class="text-slate-400 inline-flex items-center gap-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Terkirim
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Message Input Form -->
    <div class="glass-panel p-3 sm:p-4">
        <form action="{{ route('conversations.messages.send', $conversation->conversation_id) }}" method="POST" id="message-form">
            @csrf
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <textarea
                        name="content"
                        id="message-content"
                        rows="2"
                        placeholder="Tulis pesan Anda..."
                        class="glass-input resize-none w-full text-sm py-2.5 px-3.5"
                        required
                        autofocus></textarea>
                </div>
                <button type="submit" class="glass-btn-primary py-2.5 px-4 shrink-0 flex items-center gap-1.5">
                    <span>Kirim</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            @error('content')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>

<script>
    // Auto scroll chat to bottom
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }

        // Allow sending message with Cmd/Ctrl + Enter
        const textarea = document.getElementById('message-content');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('message-form').submit();
                }
            });
        }
    });
</script>
@endsection
