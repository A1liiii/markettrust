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
            <button type="button"
                    data-open-dm-drawer
                    data-conversation-id="{{ $conversation->conversation_id }}"
                    class="dm-floating-launcher text-white text-xs font-bold py-1.5 px-3 rounded-xl flex items-center gap-1.5 shadow-xs cursor-pointer hover:scale-105 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Buka di Side Panel</span>
            </button>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Terkoneksi
            </span>
        </div>
    </div>

    <!-- Context Banner (Listing / Forum Post) -->
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
    @endif

    <!-- Lembar Kesepakatan Transaksi Escrow (Agreement Sheet Card) -->
    @if($conversation->agreement)
        @php
            $agr = $conversation->agreement;
            $agrStatus = $agr->status instanceof \App\Enums\AgreementStatus ? $agr->status->value : (string) $agr->status;
            $isBuyer = auth()->id() === $agr->buyer_id;
            $isSeller = auth()->id() === $agr->seller_id;
            $myAccepted = ($isBuyer && $agr->buyer_accepted_at) || ($isSeller && $agr->seller_accepted_at);
        @endphp

        <div class="glass-card p-4 sm:p-5 mb-4 border-l-4 {{ $agrStatus === 'FINALIZED' ? 'border-l-emerald-500' : ($agrStatus === 'REJECTED' ? 'border-l-rose-500' : 'border-l-indigo-600') }} shadow-sm">
            <!-- Header Card -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $agrStatus === 'FINALIZED' ? 'bg-emerald-500/15 text-emerald-700' : ($agrStatus === 'REJECTED' ? 'bg-rose-500/15 text-rose-700' : 'bg-indigo-600/15 text-indigo-700') }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">Lembar Kesepakatan Transaksi</h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $agrStatus === 'FINALIZED' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($agrStatus === 'REJECTED' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-indigo-100 text-indigo-800 border border-indigo-200') }}">
                                {{ $agrStatus }}
                            </span>
                            <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                Rev #{{ $agr->revision_no }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $agr->item_title }}</p>
                    </div>
                </div>

                <!-- Action Buttons Header -->
                <div class="flex items-center gap-2 shrink-0">
                    @if($agrStatus === 'DRAFT')
                        @if(!$myAccepted)
                            <button type="button"
                                    id="btn-open-accept-modal"
                                    class="glass-btn-primary text-xs py-1.5 px-3 rounded-xl font-bold inline-flex items-center gap-1.5 shadow-xs cursor-pointer hover:scale-105 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Setujui (Rev #{{ $agr->revision_no }})</span>
                            </button>
                            <button type="button"
                                    id="btn-open-revise-modal"
                                    class="px-2.5 py-1.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                ✎ Revisi
                            </button>
                            <button type="button"
                                    id="btn-open-reject-modal"
                                    class="px-2.5 py-1.5 rounded-xl border border-rose-200 hover:border-rose-400 hover:bg-rose-50 text-xs font-semibold text-rose-700 transition cursor-pointer">
                                ✕ Tolak
                            </button>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Menunggu persetujuan @<span>{{ $otherUser?->username }}</span>
                            </span>
                            <button type="button"
                                    id="btn-open-revise-modal"
                                    class="px-2.5 py-1.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50 text-xs font-semibold text-slate-700 transition cursor-pointer">
                                ✎ Perbarui Revisi
                            </button>
                        @endif
                    @elseif($agrStatus === 'FINALIZED')
                        @if($agr->transaction)
                            <a href="{{ route('transactions.show', $agr->transaction) }}"
                               class="glass-btn-primary text-xs py-1.5 px-3 rounded-xl font-bold inline-flex items-center gap-1.5 shadow-xs hover:scale-105 transition">
                                <span>Lihat Transaksi #{{ $agr->transaction->transaction_id }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @else
                            <span class="px-2.5 py-1 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Snapshot Terkunci
                            </span>
                        @endif
                    @elseif($agrStatus === 'REJECTED')
                        <button type="button"
                                id="btn-open-revise-modal"
                                class="glass-btn-primary text-xs py-1.5 px-3 rounded-xl font-bold inline-flex items-center gap-1.5 shadow-xs cursor-pointer hover:scale-105 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Ajukan Revisi Baru</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Card Body / Detail Information -->
            <div class="mt-3.5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                <!-- Kolom 1: Peran & Katalog -->
                <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Pihak Transaksi</div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Pembeli:</span>
                        <span class="font-bold">@<span>{{ $agr->buyer?->username }}</span> {{ $isBuyer ? '(Anda)' : '' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Penjual:</span>
                        <span class="font-bold">@<span>{{ $agr->seller?->username }}</span> {{ $isSeller ? '(Anda)' : '' }}</span>
                    </div>
                    <div class="pt-1 border-t border-slate-200/60 text-slate-600 truncate">
                        <span class="text-slate-400">Varian:</span> {{ $agr->variant?->product?->product_name }} &bull; {{ $agr->variant?->variant_name }}
                    </div>
                </div>

                <!-- Kolom 2: Rincian Finansial & Fee -->
                <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Rincian Finansial</span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded {{ $agr->fee_type->value === 'EXCLUDE' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                            {{ $agr->fee_type->value }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Harga Dasar:</span>
                        <span class="font-bold">Rp {{ number_format($agr->base_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Fee Layanan:</span>
                        <span class="font-medium text-slate-600">Rp {{ number_format($agr->fee_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-1 border-t border-slate-200/60 flex items-center justify-between text-indigo-700 font-bold">
                        <span>Total Pembeli:</span>
                        <span>Rp {{ number_format($agr->buyer_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-emerald-700 font-semibold text-[11px]">
                        <span>Hak Bersih Penjual:</span>
                        <span>Rp {{ number_format($agr->seller_net, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Kolom 3: Penyerahan & Catatan -->
                <div class="p-2.5 rounded-xl bg-slate-50/80 border border-slate-200/60 space-y-1 sm:col-span-2 lg:col-span-1">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Ketentuan Penyerahan</div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Metode:</span>
                        <span class="font-medium text-slate-800">{{ $agr->deliveryMethodLabel() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-700">
                        <span class="text-slate-500">Batas Waktu:</span>
                        <span class="font-medium text-slate-800">{{ $agr->delivery_within_minutes }} Menit</span>
                    </div>
                    <div class="pt-1 border-t border-slate-200/60 text-slate-600">
                        <span class="text-slate-400">Deskripsi:</span>
                        <p class="text-[11px] text-slate-700 mt-0.5 line-clamp-2" title="{{ $agr->item_description }}">{{ $agr->item_description }}</p>
                    </div>
                </div>
            </div>

            <!-- Rejection Alert if REJECTED -->
            @if($agrStatus === 'REJECTED')
                <div class="mt-3 p-3 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-800 text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <div class="font-bold">Usulan kesepakatan ini ditolak oleh @<span>{{ $agr->rejectedByUser?->username ?? 'pihak lawan' }}</span></div>
                        <p class="text-[11px] mt-0.5 text-rose-700"><span class="font-semibold">Alasan Penolakan:</span> {{ $agr->rejection_reason }}</p>
                    </div>
                </div>
            @endif
        </div>
    @elseif(!$conversation->listing)
        <!-- Propose Agreement Callout CTA Banner (Jika belum ada kesepakatan pada DM non-marketplace) -->
        <div class="glass-card p-3.5 sm:p-4 mb-4 border border-indigo-200/60 bg-gradient-to-r from-indigo-50/60 via-white to-purple-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 text-indigo-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">Ingin bertransaksi dengan aman?</h4>
                    <p class="text-[11px] text-slate-600">Ajukan lembar kesepakatan transaksi escrow agar dana pembayaran terlindungi sampai pesanan diserahkan.</p>
                </div>
            </div>
            <button type="button"
                    id="btn-open-propose-modal"
                    class="glass-btn-primary text-xs py-2 px-3.5 rounded-xl font-bold inline-flex items-center gap-1.5 shrink-0 cursor-pointer shadow-xs hover:scale-105 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Ajukan Kesepakatan</span>
            </button>
        </div>
    @endif

    <!-- Chat Stream Box -->
    <div class="glass-panel p-4 sm:p-6 mb-4 flex flex-col min-h-[420px] max-h-[600px] overflow-y-auto" id="messages-container">
        @if($conversation->messages->isEmpty())
            <div class="m-auto text-center py-12 text-slate-400" id="empty-state">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium">Belum ada pesan di percakapan ini.</p>
                <p class="text-xs text-slate-400 mt-1">Kirim pesan pertama atau gunakan template pesan singkat untuk memulai.</p>
            </div>
            <div class="space-y-4 hidden" id="message-list"></div>
        @else
            <div class="space-y-4" id="message-list">
                @foreach($conversation->messages as $msg)
                    @php
                        $isMe = $msg->sender_id === auth()->user()->user_id;
                    @endphp

                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->message_id }}">
                        <div class="max-w-[85%] sm:max-w-[70%]">
                            <!-- Sender name label for other user -->
                            @if(! $isMe)
                                <div class="text-[11px] font-semibold text-slate-500 mb-1 ml-1">
                                    @<span>{{ $msg->sender ? $msg->sender->username : 'Peserta' }}</span>
                                </div>
                            @endif

                            <!-- Message Bubble -->
                            <div class="p-3.5 sm:p-4 rounded-2xl text-sm leading-relaxed shadow-xs {{ $isMe ? 'bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white rounded-tr-xs' : 'bg-white/85 backdrop-blur-md border border-slate-200/80 text-slate-800 rounded-tl-xs' }}">
                                @if($msg->content)
                                    <div class="whitespace-pre-wrap break-words">{{ $msg->content }}</div>
                                @endif

                                <!-- Attachment Card (If exists) -->
                                @if($msg->hasAttachment())
                                    <div class="mt-2.5 p-2.5 rounded-xl {{ $isMe ? 'bg-indigo-700/50 text-white' : 'bg-slate-50 border border-slate-200 text-slate-800' }} flex items-center justify-between gap-3 text-xs">
                                        <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                            <div class="w-8 h-8 rounded-lg {{ $isMe ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600' }} flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-medium truncate text-xs">{{ $msg->attachment_name }}</div>
                                                <div class="text-[10px] opacity-75">{{ $msg->formattedAttachmentSize() }}</div>
                                            </div>
                                        </div>
                                        <a href="{{ route('conversations.messages.attachment', [$conversation->conversation_id, $msg->message_id]) }}"
                                           class="px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 {{ $isMe ? 'bg-white/20 hover:bg-white/30 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Unduh</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Timestamp & Read Status (TC-DM-04) -->
                            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400 {{ $isMe ? 'justify-end mr-1' : 'justify-start ml-1' }}">
                                <span>{{ $msg->sent_at ? $msg->sent_at->format('H:i') : '' }}</span>
                                @if($isMe)
                                    <span>&bull;</span>
                                    <span id="msg-status-{{ $msg->message_id }}">
                                        @if($msg->read_at)
                                            <span class="text-indigo-600 font-semibold inline-flex items-center gap-1">
                                                <svg class="w-4 h-4 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 13l4.5 4.5L17 7"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l4.5 4.5L22 7"/>
                                                </svg>
                                                <span>Dibaca</span>
                                            </span>
                                        @else
                                            <span class="text-slate-400 inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Terkirim</span>
                                            </span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Message Input Box with Template Dropdown & Attachment Picker -->
    <div class="glass-panel p-3 sm:p-4">
        <!-- Quick Message Templates (TC-DM-03) -->
        <div class="mb-2.5 flex items-center justify-between text-xs">
            <div class="relative inline-block text-left" id="template-dropdown-wrapper">
                <button type="button"
                        id="template-dropdown-btn"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-slate-600 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50 border border-slate-200/80 transition text-xs font-medium cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Pilihan Pesan Singkat</span>
                    <svg class="w-3 h-3 ml-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="template-menu" class="hidden absolute left-0 bottom-full mb-1.5 w-72 sm:w-80 rounded-xl bg-white/95 backdrop-blur-md shadow-xl border border-slate-200/90 p-1.5 z-30 space-y-1">
                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih template kalimat</div>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, apakah produk/postingan ini masih tersedia?">
                        "Halo, apakah masih tersedia?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, saya berminat dengan penawaran ini. Apakah harga masih bisa dinegosiasikan?">
                        "Apakah harga masih bisa dinegosiasikan?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, bagaimana detail spesifikasi dan tata cara penyerahannya?">
                        "Bagaimana detail spesifikasi & penyerahannya?"
                    </button>
                    <button type="button" class="quick-template-item w-full text-left px-3 py-2 text-xs rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition" data-text="Halo, saya ingin mengajukan lembar kesepakatan transaksi escrow untuk ini.">
                        "Saya ingin ajukan kesepakatan escrow."
                    </button>
                </div>
            </div>

            <span class="text-[11px] text-slate-400 hidden sm:inline">
                Tekan <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">Enter</kbd> untuk kirim, <kbd class="px-1 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">Shift+Enter</kbd> baris baru
            </span>
        </div>

        <!-- Attachment Selected Preview Badge -->
        <div id="attachment-preview-badge" class="hidden mb-2 items-center justify-between px-3 py-1.5 bg-indigo-50 border border-indigo-200/80 rounded-xl text-xs text-indigo-700">
            <div class="flex items-center gap-2 truncate">
                <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <span id="attachment-preview-name" class="font-medium truncate">nama_file.pdf</span>
                <span id="attachment-preview-size" class="text-slate-500 text-[11px] shrink-0"></span>
            </div>
            <button type="button" id="remove-attachment-btn" class="p-1 text-slate-400 hover:text-rose-600 transition ml-2" title="Hapus lampiran">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Message Form -->
        <form action="{{ route('conversations.messages.send', $conversation->conversation_id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="message-form">
            @csrf

            <!-- Hidden File Input -->
            <input type="file"
                   name="attachment"
                   id="attachment-input"
                   class="hidden"
                   accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">

            <div class="flex items-end gap-2">
                <!-- Attach Document Button -->
                <button type="button"
                        id="attach-file-btn"
                        class="p-2.5 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 transition shrink-0 cursor-pointer"
                        title="Lampirkan Gambar atau PDF (Maks. 5 MB)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button>

                <!-- Textarea -->
                <div class="flex-1">
                    <textarea
                        name="content"
                        id="message-content"
                        rows="2"
                        placeholder="Tulis pesan Anda..."
                        class="glass-input resize-none w-full text-sm py-2.5 px-3.5 focus:ring-2 focus:ring-indigo-500"
                        autofocus></textarea>
                </div>

                <!-- Submit Button with TC-DM-06 Fixed Forward-Facing Centered Icon -->
                <button type="submit"
                        id="submit-message-btn"
                        class="glass-btn-primary py-2.5 px-4 shrink-0 inline-flex items-center justify-center gap-2 cursor-pointer transition">
                    <span id="btn-text">Kirim</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12zm0 0h7.5"/>
                    </svg>
                </button>
            </div>
            @error('content')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
            @error('attachment')
                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messages-container');
    const form = document.getElementById('message-form');
    const textarea = document.getElementById('message-content');
    const submitBtn = document.getElementById('submit-message-btn');
    const fileInput = document.getElementById('attachment-input');
    const attachBtn = document.getElementById('attach-file-btn');
    const previewBadge = document.getElementById('attachment-preview-badge');
    const previewName = document.getElementById('attachment-preview-name');
    const previewSize = document.getElementById('attachment-preview-size');
    const removeAttachmentBtn = document.getElementById('remove-attachment-btn');
    const templateDropdownBtn = document.getElementById('template-dropdown-btn');
    const templateMenu = document.getElementById('template-menu');
    const syncUrl = @json(route('conversations.sync', $conversation->conversation_id));

    let isSubmitting = false;

    // Scroll to bottom helper
    function scrollToBottom() {
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    scrollToBottom();

    // 1. TC-DM-03: Quick Template Selection (Fill textarea without auto-submit)
    if (templateDropdownBtn && templateMenu) {
        templateDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            templateMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function(e) {
            if (!templateMenu.contains(e.target) && e.target !== templateDropdownBtn) {
                templateMenu.classList.add('hidden');
            }
        });

        document.querySelectorAll('.quick-template-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                if (text && textarea) {
                    textarea.value = text;
                    textarea.focus();
                    templateMenu.classList.add('hidden');
                }
            });
        });
    }

    // 2. Attachment File Handling
    if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file lampiran maksimal 5 MB.');
                    clearAttachment();
                    return;
                }
                previewName.textContent = file.name;
                const sizeKB = (file.size / 1024).toFixed(0);
                previewSize.textContent = sizeKB > 1024 ? `(${(sizeKB / 1024).toFixed(1)} MB)` : `(${sizeKB} KB)`;
                previewBadge.classList.remove('hidden');
                previewBadge.classList.add('flex');
            } else {
                clearAttachment();
            }
        });

        if (removeAttachmentBtn) {
            removeAttachmentBtn.addEventListener('click', function() {
                clearAttachment();
            });
        }
    }

    function clearAttachment() {
        if (fileInput) fileInput.value = '';
        if (previewBadge) {
            previewBadge.classList.add('hidden');
            previewBadge.classList.remove('flex');
        }
    }

    // 3. TC-DM-01: Enter to submit, Shift+Enter for newline, prevent empty & double submission
    function triggerFormSubmit() {
        if (isSubmitting) return;

        const contentVal = textarea.value.trim();
        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

        // Prevent empty message without attachment
        if (contentVal.length === 0 && !hasFile) {
            textarea.focus();
            textarea.classList.add('ring-2', 'ring-rose-500');
            setTimeout(() => textarea.classList.remove('ring-2', 'ring-rose-500'), 1500);
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        form.submit();
    }

    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                triggerFormSubmit();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            const contentVal = textarea.value.trim();
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            if (contentVal.length === 0 && !hasFile) {
                e.preventDefault();
                textarea.focus();
                return;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        });
    }

    // 4. TC-DM-04: Realtime Read Receipt Sync & Live Incoming Messages Polling
    let lastKnownMessageId = 0;
    const allMsgNodes = document.querySelectorAll('[data-message-id]');
    if (allMsgNodes.length > 0) {
        lastKnownMessageId = parseInt(allMsgNodes[allMsgNodes.length - 1].getAttribute('data-message-id'), 10) || 0;
    }

    async function pollSync() {
        if (document.hidden) return; // Save resource if tab is in background

        try {
            const res = await fetch(`${syncUrl}?after_id=${lastKnownMessageId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;

            const data = await res.json();

            // Update Read Status on My Messages
            if (data.my_read_message_ids && Array.isArray(data.my_read_message_ids)) {
                data.my_read_message_ids.forEach(id => {
                    const statusElem = document.getElementById(`msg-status-${id}`);
                    if (statusElem) {
                        statusElem.innerHTML = `
                            <span class="text-indigo-600 font-semibold inline-flex items-center gap-1">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 13l4.5 4.5L17 7"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 13l4.5 4.5L22 7"/>
                                </svg>
                                <span>Dibaca</span>
                            </span>
                        `;
                    }
                });
            }

            // Append New Messages if received
            if (data.new_messages && data.new_messages.length > 0) {
                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.remove();

                const msgList = document.getElementById('message-list');
                if (msgList) {
                    msgList.classList.remove('hidden');

                    data.new_messages.forEach(msg => {
                        lastKnownMessageId = Math.max(lastKnownMessageId, msg.message_id);

                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'flex justify-start';
                        msgDiv.setAttribute('data-message-id', msg.message_id);

                        let attachmentHtml = '';
                        if (msg.has_attachment && msg.download_url) {
                            attachmentHtml = `
                                <div class="mt-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                        </div>
                                        <div class="truncate">
                                            <div class="font-medium truncate text-xs">${msg.attachment_name}</div>
                                            <div class="text-[10px] opacity-75">${msg.attachment_size}</div>
                                        </div>
                                    </div>
                                    <a href="${msg.download_url}" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span>Unduh</span>
                                    </a>
                                </div>
                            `;
                        }

                        msgDiv.innerHTML = `
                            <div class="max-w-[85%] sm:max-w-[70%]">
                                <div class="text-[11px] font-semibold text-slate-500 mb-1 ml-1">@${msg.sender_name || 'Peserta'}</div>
                                <div class="p-3.5 sm:p-4 rounded-2xl text-sm leading-relaxed shadow-xs bg-white/85 backdrop-blur-md border border-slate-200/80 text-slate-800 rounded-tl-xs">
                                    ${msg.content ? `<div class="whitespace-pre-wrap break-words">${msg.content}</div>` : ''}
                                    ${attachmentHtml}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400 justify-start ml-1">
                                    <span>${msg.sent_at || ''}</span>
                                </div>
                            </div>
                        `;

                        msgList.appendChild(msgDiv);
                    });

                    scrollToBottom();
                }
            }
        } catch (err) {
            // Silently catch sync network error
        }
    }

    // Run poll every 3.5 seconds
    setInterval(pollSync, 3500);

    // ========================================================
    // MT-A02: Agreement Modals & Live Fee Preview Integration
    // ========================================================
    const csrfToken = @json(csrf_token());
    const feePreviewUrl = @json(route('fee.preview'));

    // Helper: Format Rupiah
    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    // Generic live fee preview handler
    function setupLiveFeeCalculation(basePriceId, feeTypeRadiosName, feeAmtId, buyerTotId, sellerNetId) {
        const priceInput = document.getElementById(basePriceId);
        const feeRadios = document.querySelectorAll(`input[name="${feeTypeRadiosName}"]`);
        const feeAmtEl = document.getElementById(feeAmtId);
        const buyerTotEl = document.getElementById(buyerTotId);
        const sellerNetEl = document.getElementById(sellerNetId);

        if (!priceInput || feeRadios.length === 0 || !feeAmtEl) return;

        let debounceTimer = null;

        async function updateCalculation() {
            const rawVal = parseFloat(priceInput.value);
            if (isNaN(rawVal) || rawVal < 5000) {
                feeAmtEl.textContent = 'Rp 0';
                buyerTotEl.textContent = isNaN(rawVal) ? 'Rp 0' : formatRupiah(rawVal);
                sellerNetEl.textContent = isNaN(rawVal) ? 'Rp 0' : formatRupiah(rawVal);
                return;
            }

            let selectedFeeType = 'EXCLUDE';
            feeRadios.forEach(r => {
                if (r.checked) selectedFeeType = r.value.toUpperCase();
            });

            try {
                const res = await fetch(feePreviewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        base_price: rawVal,
                        fee_type: selectedFeeType,
                        source: 'FORUM'
                    })
                });

                if (res.ok) {
                    const result = await res.json();
                    if (result.status === 'success' && result.data) {
                        feeAmtEl.textContent = formatRupiah(result.data.fee_amount);
                        buyerTotEl.textContent = formatRupiah(result.data.buyer_total);
                        sellerNetEl.textContent = formatRupiah(result.data.seller_net);
                    }
                }
            } catch (err) {
                // Ignore preview network error
            }
        }

        priceInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updateCalculation, 250);
        });

        feeRadios.forEach(radio => {
            radio.addEventListener('change', updateCalculation);
        });

        // Trigger initial calculation
        updateCalculation();
    }

    // Modal Helpers
    function openModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.classList.remove('hidden');
            el.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.classList.add('hidden');
            el.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    // Hook Close Buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-close-modal');
            if (targetId) closeModal(targetId);
        });
    });

    // 1. Propose Modal
    const btnOpenPropose = document.getElementById('btn-open-propose-modal');
    if (btnOpenPropose) {
        btnOpenPropose.addEventListener('click', () => {
            openModal('modal-propose-agreement');
            setupLiveFeeCalculation('propose-base-price', 'fee_type', 'propose-fee-amount', 'propose-buyer-total', 'propose-seller-net');
        });
    }

    // 2. Revise Modal
    const btnOpenRevise = document.getElementById('btn-open-revise-modal');
    if (btnOpenRevise) {
        btnOpenRevise.addEventListener('click', () => {
            openModal('modal-revise-agreement');
            setupLiveFeeCalculation('revise-base-price', 'fee_type', 'revise-fee-amount', 'revise-buyer-total', 'revise-seller-net');
        });
    }

    // 3. Accept Modal
    const btnOpenAccept = document.getElementById('btn-open-accept-modal');
    if (btnOpenAccept) {
        btnOpenAccept.addEventListener('click', () => openModal('modal-accept-agreement'));
    }

    // 4. Reject Modal
    const btnOpenReject = document.getElementById('btn-open-reject-modal');
    if (btnOpenReject) {
        btnOpenReject.addEventListener('click', () => openModal('modal-reject-agreement'));
    }
});
</script>

<!-- ======================================================== -->
<!-- MODAL 1: AJUKAN KESEPAKATAN BARU (UC-10)                  -->
<!-- ======================================================== -->
<div id="modal-propose-agreement" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">Ajukan Lembar Kesepakatan</h3>
                    <p class="text-[11px] text-slate-500">Kesepakatan aman dilindungi rekening escrow MarketTrust</p>
                </div>
            </div>
            <button type="button" data-close-modal="modal-propose-agreement" class="p-1 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('conversations.agreement.store', $conversation->conversation_id) }}" method="POST" class="mt-4 space-y-3.5 text-xs">
            @csrf

            <!-- Pemilihan Peran -->
            <div>
                <label class="block font-bold text-slate-700 mb-1.5">Saya bertindak sebagai: <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition">
                        <input type="radio" name="role" value="BUYER" checked class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="font-bold text-slate-800">Pembeli (Buyer)</div>
                            <div class="text-[10px] text-slate-500">Membayar dana ke escrow</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition">
                        <input type="radio" name="role" value="SELLER" class="text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <div class="font-bold text-slate-800">Penjual (Seller)</div>
                            <div class="text-[10px] text-slate-500">Menyerahkan hasil item</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Varian Produk Digital (Katalog R1/R2) -->
            <div>
                <label for="propose-variant" class="block font-bold text-slate-700 mb-1">Pilih Produk & Varian Digital: <span class="text-rose-500">*</span></label>
                <select name="variant_id" id="propose-variant" required class="glass-input text-xs w-full py-2 px-3">
                    <option value="">-- Pilih Varian Produk Digital --</option>
                    @foreach($availableVariants as $v)
                        <option value="{{ $v->variant_id }}">
                            {{ $v->product?->product_name }} &bull; {{ $v->variant_name }} ({{ $v->product?->category?->category_name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Judul Item -->
            <div>
                <label for="propose-item-title" class="block font-bold text-slate-700 mb-1">Judul Item Kesepakatan: <span class="text-rose-500">*</span></label>
                <input type="text"
                       name="item_title"
                       id="propose-item-title"
                       required
                       maxlength="150"
                       value="{{ $conversation->post?->title ?? '' }}"
                       placeholder="Contoh: Akun Digital MLBB Mythic Glory 100+ Skin"
                       class="glass-input text-xs w-full py-2 px-3">
            </div>

            <!-- Deskripsi / Janji Hasil -->
            <div>
                <label for="propose-item-desc" class="block font-bold text-slate-700 mb-1">Rincian Hasil yang Dijanjikan: <span class="text-rose-500">*</span></label>
                <textarea name="item_description"
                          id="propose-item-desc"
                          required
                          rows="3"
                          placeholder="Jelaskan spesifikasi detail, kredensial, atau bentuk hasil penyerahan yang disepakati..."
                          class="glass-input text-xs w-full py-2 px-3 resize-none">{{ $conversation->post?->content ?? '' }}</textarea>
            </div>

            <!-- Harga Dasar & Mode Fee -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="propose-base-price" class="block font-bold text-slate-700 mb-1">Harga Dasar (Rp): <span class="text-rose-500">*</span></label>
                    <input type="number"
                           name="base_price"
                           id="propose-base-price"
                           required
                           min="5000"
                           step="1000"
                           value="{{ $conversation->post?->price_offer ? (int)$conversation->post->price_offer : 50000 }}"
                           class="glass-input text-xs w-full py-2 px-3 font-semibold">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Minimal Rp 5.000</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Pembebanan Fee: <span class="text-rose-500">*</span></label>
                    <div class="space-y-1 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="fee_type" value="EXCLUDE" checked class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold text-slate-700">EXCLUDE</span>
                            <span class="text-[10px] text-slate-500">(Ditanggung Pembeli)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="fee_type" value="INCLUDE" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold text-slate-700">INCLUDE</span>
                            <span class="text-[10px] text-slate-500">(Dipotong dari Penjual)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Realtime Fee Calculation Preview Card -->
            <div class="p-3 rounded-2xl bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-slate-50 border border-indigo-200/80">
                <div class="flex items-center justify-between pb-1.5 border-b border-indigo-200/50 text-[10px] uppercase font-bold text-indigo-700 tracking-wider">
                    <span>Pratinjau Kalkulasi Resmi (SKPL)</span>
                    <span class="font-mono text-indigo-500">Live Preview</span>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                    <div class="p-1.5 rounded-xl bg-white/80 border border-slate-200/60">
                        <div class="text-[10px] text-slate-500">Biaya Platform</div>
                        <div id="propose-fee-amount" class="font-bold text-slate-800 text-xs sm:text-sm mt-0.5">Rp 0</div>
                    </div>
                    <div class="p-1.5 rounded-xl bg-indigo-600/10 border border-indigo-200/80">
                        <div class="text-[10px] text-indigo-700 font-semibold">Total Pembeli</div>
                        <div id="propose-buyer-total" class="font-bold text-indigo-900 text-xs sm:text-sm mt-0.5">Rp 0</div>
                    </div>
                    <div class="p-1.5 rounded-xl bg-emerald-600/10 border border-emerald-200/80">
                        <div class="text-[10px] text-emerald-700 font-semibold">Hak Bersih Penjual</div>
                        <div id="propose-seller-net" class="font-bold text-emerald-900 text-xs sm:text-sm mt-0.5">Rp 0</div>
                    </div>
                </div>
            </div>

            <!-- Ketentuan Penyerahan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="propose-delivery-method" class="block font-bold text-slate-700 mb-1">Metode Penyerahan: <span class="text-rose-500">*</span></label>
                    <select name="delivery_method" id="propose-delivery-method" required class="glass-input text-xs w-full py-2 px-3">
                        <option value="DIRECT_CHAT" selected>Melalui Chat Transaksi</option>
                        <option value="INSTANT">Pengiriman Otomatis (Instan)</option>
                    </select>
                </div>
                <div>
                    <label for="propose-delivery-minutes" class="block font-bold text-slate-700 mb-1">Batas Waktu Penyerahan: <span class="text-rose-500">*</span></label>
                    <div class="flex items-center gap-1.5">
                        <input type="number"
                               name="delivery_within_minutes"
                               id="propose-delivery-minutes"
                               required
                               min="1"
                               max="10080"
                               value="60"
                               class="glass-input text-xs w-full py-2 px-3">
                        <span class="text-slate-500 shrink-0 font-medium">Menit</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Tambahan (Opsional) -->
            <div>
                <label for="propose-note" class="block font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional):</label>
                <input type="text"
                       name="agreement_note"
                       id="propose-note"
                       maxlength="1000"
                       placeholder="Contoh: Penyerahan akun lengkap dengan email pertamanya."
                       class="glass-input text-xs w-full py-2 px-3">
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" data-close-modal="modal-propose-agreement" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="glass-btn-primary py-2 px-4 font-bold rounded-xl cursor-pointer shadow-sm hover:scale-[1.02] transition">
                    Ajukan Kesepakatan (Rev #1)
                </button>
            </div>
        </form>
    </div>
</div>

@if($conversation->agreement)
<!-- ======================================================== -->
<!-- MODAL 2: REVISI KESEPAKATAN (UC-10 c)                     -->
<!-- ======================================================== -->
<div id="modal-revise-agreement" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">Revisi Lembar Kesepakatan (Rev #{{ $conversation->agreement->revision_no + 1 }})</h3>
                    <p class="text-[11px] text-slate-500">Mengubah ketentuan akan membatalkan persetujuan revisi lama</p>
                </div>
            </div>
            <button type="button" data-close-modal="modal-revise-agreement" class="p-1 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('conversations.agreement.update', $conversation->conversation_id) }}" method="POST" class="mt-4 space-y-3.5 text-xs">
            @csrf
            @method('PUT')

            <!-- Varian Produk Digital -->
            <div>
                <label for="revise-variant" class="block font-bold text-slate-700 mb-1">Pilih Produk & Varian Digital: <span class="text-rose-500">*</span></label>
                <select name="variant_id" id="revise-variant" required class="glass-input text-xs w-full py-2 px-3">
                    @foreach($availableVariants as $v)
                        <option value="{{ $v->variant_id }}" {{ $conversation->agreement->variant_id === $v->variant_id ? 'selected' : '' }}>
                            {{ $v->product?->product_name }} &bull; {{ $v->variant_name }} ({{ $v->product?->category?->category_name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Judul Item -->
            <div>
                <label for="revise-item-title" class="block font-bold text-slate-700 mb-1">Judul Item Kesepakatan: <span class="text-rose-500">*</span></label>
                <input type="text"
                       name="item_title"
                       id="revise-item-title"
                       required
                       maxlength="150"
                       value="{{ $conversation->agreement->item_title }}"
                       class="glass-input text-xs w-full py-2 px-3">
            </div>

            <!-- Deskripsi / Janji Hasil -->
            <div>
                <label for="revise-item-desc" class="block font-bold text-slate-700 mb-1">Rincian Hasil yang Dijanjikan: <span class="text-rose-500">*</span></label>
                <textarea name="item_description"
                          id="revise-item-desc"
                          required
                          rows="3"
                          class="glass-input text-xs w-full py-2 px-3 resize-none">{{ $conversation->agreement->item_description }}</textarea>
            </div>

            <!-- Harga Dasar & Mode Fee -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="revise-base-price" class="block font-bold text-slate-700 mb-1">Harga Dasar (Rp): <span class="text-rose-500">*</span></label>
                    <input type="number"
                           name="base_price"
                           id="revise-base-price"
                           required
                           min="5000"
                           step="1000"
                           value="{{ (int)$conversation->agreement->base_price }}"
                           class="glass-input text-xs w-full py-2 px-3 font-semibold">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Pembebanan Fee: <span class="text-rose-500">*</span></label>
                    <div class="space-y-1 mt-1">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="fee_type" value="EXCLUDE" {{ $conversation->agreement->fee_type->value === 'EXCLUDE' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold text-slate-700">EXCLUDE</span>
                            <span class="text-[10px] text-slate-500">(Ditanggung Pembeli)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="fee_type" value="INCLUDE" {{ $conversation->agreement->fee_type->value === 'INCLUDE' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold text-slate-700">INCLUDE</span>
                            <span class="text-[10px] text-slate-500">(Dipotong dari Penjual)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Realtime Fee Calculation Preview Card (Revise) -->
            <div class="p-3 rounded-2xl bg-gradient-to-r from-purple-50/70 via-indigo-50/50 to-slate-50 border border-purple-200/80">
                <div class="flex items-center justify-between pb-1.5 border-b border-purple-200/50 text-[10px] uppercase font-bold text-purple-700 tracking-wider">
                    <span>Pratinjau Kalkulasi Revisi Resmi</span>
                    <span class="font-mono text-purple-500">Live Preview</span>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                    <div class="p-1.5 rounded-xl bg-white/80 border border-slate-200/60">
                        <div class="text-[10px] text-slate-500">Biaya Platform</div>
                        <div id="revise-fee-amount" class="font-bold text-slate-800 text-xs sm:text-sm mt-0.5">Rp {{ number_format($conversation->agreement->fee_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="p-1.5 rounded-xl bg-indigo-600/10 border border-indigo-200/80">
                        <div class="text-[10px] text-indigo-700 font-semibold">Total Pembeli</div>
                        <div id="revise-buyer-total" class="font-bold text-indigo-900 text-xs sm:text-sm mt-0.5">Rp {{ number_format($conversation->agreement->buyer_total, 0, ',', '.') }}</div>
                    </div>
                    <div class="p-1.5 rounded-xl bg-emerald-600/10 border border-emerald-200/80">
                        <div class="text-[10px] text-emerald-700 font-semibold">Hak Bersih Penjual</div>
                        <div id="revise-seller-net" class="font-bold text-emerald-900 text-xs sm:text-sm mt-0.5">Rp {{ number_format($conversation->agreement->seller_net, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- Ketentuan Penyerahan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="revise-delivery-method" class="block font-bold text-slate-700 mb-1">Metode Penyerahan: <span class="text-rose-500">*</span></label>
                    <select name="delivery_method" id="revise-delivery-method" required class="glass-input text-xs w-full py-2 px-3">
                        <option value="DIRECT_CHAT" {{ $conversation->agreement->delivery_method === 'DIRECT_CHAT' ? 'selected' : '' }}>Melalui Chat Transaksi</option>
                        <option value="INSTANT" {{ $conversation->agreement->delivery_method === 'INSTANT' ? 'selected' : '' }}>Pengiriman Otomatis (Instan)</option>
                    </select>
                </div>
                <div>
                    <label for="revise-delivery-minutes" class="block font-bold text-slate-700 mb-1">Batas Waktu Penyerahan: <span class="text-rose-500">*</span></label>
                    <div class="flex items-center gap-1.5">
                        <input type="number"
                               name="delivery_within_minutes"
                               id="revise-delivery-minutes"
                               required
                               min="1"
                               max="10080"
                               value="{{ $conversation->agreement->delivery_within_minutes }}"
                               class="glass-input text-xs w-full py-2 px-3">
                        <span class="text-slate-500 shrink-0 font-medium">Menit</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Tambahan (Opsional) -->
            <div>
                <label for="revise-note" class="block font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional):</label>
                <input type="text"
                       name="agreement_note"
                       id="revise-note"
                       maxlength="1000"
                       value="{{ $conversation->agreement->agreement_note }}"
                       class="glass-input text-xs w-full py-2 px-3">
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" data-close-modal="modal-revise-agreement" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="glass-btn-primary py-2 px-4 font-bold rounded-xl cursor-pointer shadow-sm hover:scale-[1.02] transition">
                    Simpan & Ajukan Revisi #{{ $conversation->agreement->revision_no + 1 }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 3: SETUJUI KESEPAKATAN (UC-11)                      -->
<!-- ======================================================== -->
<div id="modal-accept-agreement" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center gap-3 pb-3 border-b border-slate-200">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Setujui Kesepakatan Final</h3>
                <p class="text-[11px] text-slate-500">Membentuk 1 pesanan resmi escrow</p>
            </div>
        </div>

        <div class="my-4 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
            <div class="font-bold text-slate-800 text-sm">{{ $conversation->agreement->item_title }}</div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Nomor Revisi:</span>
                <span class="font-bold text-slate-800">Rev #{{ $conversation->agreement->revision_no }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <span>Harga Dasar:</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($conversation->agreement->base_price, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between text-indigo-700 font-bold pt-1 border-t border-slate-200">
                <span>Total Bayar Pembeli:</span>
                <span>Rp {{ number_format($conversation->agreement->buyer_total, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between text-emerald-700 font-semibold text-[11px]">
                <span>Hak Bersih Penjual:</span>
                <span>Rp {{ number_format($conversation->agreement->seller_net, 0, ',', '.') }}</span>
            </div>
            <p class="text-[10px] text-slate-500 pt-2 border-t border-slate-200">
                Setelah disetujui, kesepakatan akan berstatus <strong>FINALIZED</strong> dan otomatis membuat transaksi dengan batas pembayaran <strong>1 Jam</strong>.
            </p>
        </div>

        <form action="{{ route('conversations.agreement.accept', $conversation->conversation_id) }}" method="POST">
            @csrf
            <input type="hidden" name="revision_no" value="{{ $conversation->agreement->revision_no }}">

            <div class="flex items-center justify-end gap-2 text-xs">
                <button type="button" data-close-modal="modal-accept-agreement" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="glass-btn-primary py-2 px-4 font-bold rounded-xl cursor-pointer shadow-sm hover:scale-[1.02] transition">
                    Ya, Setujui & Buat Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 4: TOLAK KESEPAKATAN (UC-11)                        -->
<!-- ======================================================== -->
<div id="modal-reject-agreement" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center gap-3 pb-3 border-b border-slate-200">
            <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Tolak Usulan Kesepakatan</h3>
                <p class="text-[11px] text-slate-500">Rev #{{ $conversation->agreement->revision_no }} &bull; Tanpa membuat transaksi pembayaran</p>
            </div>
        </div>

        <form action="{{ route('conversations.agreement.reject', $conversation->conversation_id) }}" method="POST" class="mt-4 space-y-3.5 text-xs">
            @csrf

            <div>
                <label for="rejection-reason" class="block font-bold text-slate-700 mb-1">Alasan Penolakan: <span class="text-rose-500">*</span></label>
                <textarea name="rejection_reason"
                          id="rejection-reason"
                          required
                          rows="3"
                          maxlength="500"
                          placeholder="Jelaskan secara sopan alasan menolak usulan kesepakatan ini (harga tidak cocok, syarat penyerahan kurang jelas, dsb)..."
                          class="glass-input text-xs w-full py-2 px-3 resize-none"></textarea>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Minimal 3 karakter, maksimal 500 karakter.</span>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" data-close-modal="modal-reject-agreement" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl font-bold bg-rose-600 hover:bg-rose-700 text-white cursor-pointer shadow-sm transition">
                    Tolak Kesepakatan
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
