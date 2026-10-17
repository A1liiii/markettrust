@extends('layouts.app')

@section('title', 'Transaksi #' . $transaction->transaction_id . ' - Lembar Kesepakatan Final')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Top Navigation -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 overflow-x-auto pb-1" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>&rsaquo;</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-indigo-600 transition">Marketplace</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold">Transaksi #{{ $transaction->transaction_id }}</span>
        </nav>

        <!-- Status Header Card -->
        <div class="glass-card p-6 border border-white/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Sumber Transaksi: {{ $transaction->source_type->value }}
                    </span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-xs text-slate-500">ID Kesepakatan #{{ $agreement->agreement_id }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight" data-cy="transaction-title">
                    Transaksi #{{ $transaction->transaction_id }}
                </h1>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                @php
                    $statusBadgeClass = match($transaction->status->value) {
                        'WAITING_PAYMENT' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'PAID' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'DELIVERED' => 'bg-purple-100 text-purple-800 border-purple-200',
                        'CONFIRMED', 'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'CANCELLED', 'REFUNDED' => 'bg-rose-100 text-rose-800 border-rose-200',
                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                    };
                @endphp
                <span class="px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-wider border {{ $statusBadgeClass }}" data-cy="transaction-status-badge">
                    {{ $transaction->status->value }}
                </span>
            </div>
        </div>

        <!-- Waiting Payment Countdown Alert (If Status WAITING_PAYMENT) -->
        @if ($transaction->status->value === 'WAITING_PAYMENT')
            <div class="glass-card p-5 border-amber-200/80 bg-gradient-to-r from-amber-500/10 via-amber-50/50 to-white/70 shadow-sm" data-cy="alert-payment-due">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                                Menunggu Pembayaran Escrow
                            </div>
                            <div class="text-xs sm:text-sm text-slate-700 mt-0.5">
                                Batas Pembayaran: <strong>{{ $transaction->payment_due_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</strong> (1 Jam dari Finalisasi)
                            </div>
                        </div>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Total Tagihan</span>
                        <span class="text-lg font-black text-indigo-700">
                            Rp {{ number_format($agreement->buyer_total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Immutable Agreement Snapshot Details Panel -->
        <div class="glass-panel p-6 sm:p-8 space-y-6 border border-white/90 shadow-md">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-xs">
                        📜
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Lembar Kesepakatan Final (*Agreement Snapshot*)
                        </h2>
                        <span class="text-[11px] text-slate-500">
                            Status: <strong class="uppercase text-emerald-700">{{ $agreement->status->value }}</strong> &bull; Terkunci sejak {{ $agreement->finalized_at?->translatedFormat('d F Y, H:i') }} WIB
                        </span>
                    </div>
                </div>
                <span class="glass-badge glass-badge-primary text-[10px]">
                    Revisi #{{ $agreement->revision_no }} (Final)
                </span>
            </div>

            <!-- Agreement Parties -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-white/60 border border-slate-100 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Pembeli (Buyer)</span>
                    <div class="font-bold text-sm text-slate-900" data-cy="agreement-buyer-username">
                        @<span>{{ $agreement->buyer?->username }}</span>
                        @if ($isBuyer)
                            <span class="ml-1 text-[10px] px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold">Anda</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500">{{ $agreement->buyer?->email }}</div>
                </div>

                <div class="p-4 rounded-xl bg-white/60 border border-slate-100 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Penjual (Seller)</span>
                    <div class="font-bold text-sm text-slate-900" data-cy="agreement-seller-username">
                        @<span>{{ $agreement->seller?->username }}</span>
                        @if ($isSeller)
                            <span class="ml-1 text-[10px] px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold">Anda</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500">{{ $agreement->seller?->email }}</div>
                </div>
            </div>

            <!-- Item & Delivery Terms -->
            <div class="bg-white/40 p-4 rounded-xl border border-slate-100/80 space-y-3">
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Item Digital Yang Disepakati</span>
                    <h3 class="text-base font-bold text-slate-900 mt-0.5" data-cy="agreement-item-title">
                        {{ $agreement->item_title }}
                    </h3>
                    <p class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-relaxed">
                        {{ $agreement->item_description }}
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100 text-xs">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Varian Terpilih</span>
                        <span class="font-semibold text-slate-800">
                            {{ $agreement->variant?->variant_name ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Metode Penyerahan</span>
                        <span class="font-semibold text-slate-800">
                            {{ $agreement->delivery_method }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Komitmen SLA Kirim</span>
                        <span class="font-bold text-indigo-600">
                            ⚡ {{ $agreement->delivery_within_minutes }} Menit
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Table (Immutable Numbers) -->
            <div class="space-y-3">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                    Nilai Finansial Snapshot (Tidak Dapat Diubah)
                </span>

                <div class="bg-white/70 rounded-xl p-4 border border-slate-200/80 space-y-2.5 text-xs sm:text-sm">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Harga Dasar Kesepakatan:</span>
                        <span class="font-semibold text-slate-900" data-cy="snapshot-base-price">
                            Rp {{ number_format($agreement->base_price, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600">
                        <span class="flex items-center gap-1">
                            <span>Biaya Layanan Platform (Fee):</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                                {{ $agreement->fee_type->value }}
                            </span>
                        </span>
                        <span class="font-semibold text-indigo-600" data-cy="snapshot-fee-amount">
                            + Rp {{ number_format($agreement->fee_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200 pt-2 flex justify-between items-center font-bold">
                        <span class="text-slate-900">Total Pembayaran Buyer:</span>
                        <span class="text-base font-extrabold text-indigo-700" data-cy="snapshot-buyer-total">
                            Rp {{ number_format($agreement->buyer_total, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200 pt-2 flex justify-between items-center">
                        <span class="text-slate-600">Pencairan Hak Bersih Seller:</span>
                        <span class="font-bold text-emerald-600" data-cy="snapshot-seller-net">
                            Rp {{ number_format($agreement->seller_net, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline / Status History -->
            @if ($transaction->statusHistories->count() > 0)
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Riwayat Perjalanan Transaksi
                    </span>
                    <div class="space-y-2">
                        @foreach ($transaction->statusHistories as $history)
                            <div class="flex items-start gap-2.5 text-xs text-slate-600 bg-white/40 p-2.5 rounded-lg border border-slate-100">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-slate-800 uppercase text-[11px]">
                                            {{ $history->to_status }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            {{ $history->changed_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $history->note }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Direct Actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-200">
                <a
                    href="{{ route('marketplace.index') }}"
                    class="glass-btn-secondary px-4 py-2 text-xs font-semibold"
                >
                    &larr; Kembali ke Katalog Marketplace
                </a>

                @php
                    $transactionConvId = $transaction->agreement?->conversation?->conversation_id;
                @endphp

                @if ($transactionConvId)
                    <button
                        type="button"
                        class="glass-btn-primary px-4 py-2 text-xs font-semibold flex items-center gap-1.5"
                        data-open-dm-drawer
                        data-conversation-id="{{ $transactionConvId }}"
                        data-cy="btn-buka-chat-transaksi"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>Buka Chat / Diskusi Pesanan</span>
                    </button>
                @elseif ($transaction->listing_id)
                    <a
                        href="{{ route('conversations.from-listing', $transaction->listing_id) }}"
                        class="glass-btn-primary px-4 py-2 text-xs font-semibold flex items-center gap-1.5"
                        data-cy="btn-buka-chat-transaksi"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>Buka Chat / Diskusi Pesanan</span>
                    </a>
                @endif
            </div>

        </div>

    </div>
</div>
@endsection
