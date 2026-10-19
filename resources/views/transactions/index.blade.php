@extends('layouts.app')

@section('title', 'Transaksi & Pesanan Saya - MarketTrust')

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Breadcrumb & Page Header -->
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-2" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-semibold">Transaksi Saya</span>
            </nav>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" data-cy="transactions-index-title">
                        Transaksi & Pesanan Saya
                    </h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Pantau status pembayaran escrow, proses penyerahan item, dan riwayat pesanan Anda secara aman.
                    </p>
                </div>

                <a href="{{ route('marketplace.index') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-1.5 self-start sm:self-auto">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Katalog Marketplace</span>
                </a>
            </div>
        </div>

        <!-- Filter Navigation Toolbar -->
        <div class="glass-panel p-4 sm:p-5 border border-white/80 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Role Filter (Semua / Pembelian / Penjualan) -->
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('transactions.index', array_merge(request()->query(), ['role' => 'all', 'page' => 1])) }}"
                        class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition {{ $currentRole === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                        data-cy="filter-role-all"
                    >
                        Semua Transaksi
                    </a>
                    <a
                        href="{{ route('transactions.index', array_merge(request()->query(), ['role' => 'buyer', 'page' => 1])) }}"
                        class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition {{ $currentRole === 'buyer' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                        data-cy="filter-role-buyer"
                    >
                        Pembelian Saya (Buyer)
                    </a>
                    <a
                        href="{{ route('transactions.index', array_merge(request()->query(), ['role' => 'seller', 'page' => 1])) }}"
                        class="text-xs px-3.5 py-1.5 rounded-xl font-semibold transition {{ $currentRole === 'seller' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/80 text-slate-600 hover:bg-white' }}"
                        data-cy="filter-role-seller"
                    >
                        Penjualan Saya (Seller)
                    </a>
                </div>

                <!-- Status Filter Options -->
                <div class="flex items-center gap-2">
                    <label for="status-filter" class="text-xs font-semibold text-slate-500 whitespace-nowrap">Status:</label>
                    <select
                        id="status-filter"
                        class="glass-input text-xs py-1.5 px-3 rounded-xl border border-slate-200/80 bg-white/80 text-slate-800"
                        onchange="window.location.href = this.value"
                    >
                        <option value="{{ route('transactions.index', array_merge(request()->query(), ['status' => '', 'page' => 1])) }}">
                            Semua Status
                        </option>
                        @foreach (\App\Enums\TransactionStatus::cases() as $statusOption)
                            <option
                                value="{{ route('transactions.index', array_merge(request()->query(), ['status' => $statusOption->value, 'page' => 1])) }}"
                                {{ $currentStatus === $statusOption->value ? 'selected' : '' }}
                            >
                                {{ $statusOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        @if ($transactions->count() > 0)
            <div class="space-y-4" data-cy="transactions-list">
                @foreach ($transactions as $tx)
                    @php
                        $isBuyer = (int) $tx->agreement?->buyer_id === (int) $user->user_id;
                        $isSeller = (int) $tx->agreement?->seller_id === (int) $user->user_id;
                    @endphp
                    <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-xs hover:border-indigo-200 hover:shadow-md transition group">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <!-- Left Metadata -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                @if ($isBuyer)
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Pembelian
                                    </span>
                                @elseif ($isSeller)
                                    <span class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Penjualan
                                    </span>
                                @endif

                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600">
                                    {{ $tx->source_type->label() }}
                                </span>

                                <span class="text-slate-300">&bull;</span>
                                <span class="font-bold text-slate-800">#TRX-{{ $tx->transaction_id }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-500">{{ $tx->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                            </div>

                            <!-- Right Status Badge -->
                            <div class="shrink-0">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $tx->status->badgeClass() }}" data-cy="transaction-status-badge">
                                    {{ $tx->status->label() }}
                                </span>
                            </div>
                        </div>

                        <!-- Main Content Details -->
                        <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1.5 flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition">
                                    <a href="{{ route('transactions.show', $tx) }}">
                                        {{ $tx->agreement?->item_title ?? 'Item Transaksi' }}
                                    </a>
                                </h3>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    @if ($tx->agreement?->variant)
                                        <span>Varian: <strong class="text-slate-700">{{ $tx->agreement->variant->variant_name }}</strong></span>
                                        <span class="text-slate-300">&bull;</span>
                                    @endif

                                    @if ($isBuyer)
                                        <span>Penjual: <strong class="text-slate-700">@<span>{{ $tx->agreement?->seller?->username }}</span></strong></span>
                                    @elseif ($isSeller)
                                        <span>Pembeli: <strong class="text-slate-700">@<span>{{ $tx->agreement?->buyer?->username }}</span></strong></span>
                                    @endif

                                    <span class="text-slate-300">&bull;</span>
                                    <span>Metode: <strong class="text-slate-700">{{ $tx->agreement?->deliveryMethodLabel() }}</strong></span>
                                </div>
                            </div>

                            <!-- Total Price -->
                            <div class="text-left md:text-right shrink-0">
                                <span class="block text-[10px] uppercase font-bold text-slate-400">Total Pembayaran</span>
                                <span class="text-lg sm:text-xl font-extrabold text-indigo-700">
                                    Rp {{ number_format($tx->agreement?->buyer_total ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                @if ($tx->status === \App\Enums\TransactionStatus::WAITING_PAYMENT)
                                    <span class="text-amber-700 font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Batas Pembayaran: {{ $tx->payment_due_at?->translatedFormat('d M, H:i') ?? '-' }} WIB
                                    </span>
                                @elseif ($tx->status === \App\Enums\TransactionStatus::PAID)
                                    <span class="text-blue-700 font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Escrow Terverifikasi &bull; Penjual Menyiapkan Item
                                    </span>
                                @else
                                    <span>Status Terkini: <strong>{{ $tx->status->label() }}</strong></span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($isBuyer && $tx->status === \App\Enums\TransactionStatus::WAITING_PAYMENT)
                                    <a
                                        href="{{ route('transactions.show', $tx) }}"
                                        class="glass-btn-primary px-3.5 py-1.5 text-xs font-bold shadow-xs"
                                        data-cy="btn-pay-now-index"
                                    >
                                        Bayar Sekarang &rarr;
                                    </a>
                                @endif

                                <a
                                    href="{{ route('transactions.show', $tx) }}"
                                    class="glass-btn-secondary px-3.5 py-1.5 text-xs font-semibold"
                                >
                                    Lihat Rincian Pesanan
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="glass-panel p-12 text-center border border-white/80 max-w-lg mx-auto space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Belum Ada Transaksi</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xs mx-auto">
                        Anda belum memiliki riwayat transaksi untuk filter yang dipilih. Jelajahi katalog marketplace kami untuk menemukan item digital terpercaya.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('marketplace.index') }}" class="glass-btn-primary px-5 py-2 text-xs font-bold shadow-md shadow-indigo-500/20 inline-flex items-center gap-1.5">
                        <span>Jelajahi Marketplace</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
