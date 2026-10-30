@extends('layouts.app')

@section('title', 'Transaksi #' . $transaction->transaction_id . ' - ' . $transaction->status->label())

@section('content')
<div class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Top Navigation -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 overflow-x-auto pb-1" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Beranda</a>
            <span>&rsaquo;</span>
            <a href="{{ route('transactions.index') }}" class="hover:text-indigo-600 transition">Transaksi Saya</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold">Transaksi #{{ $transaction->transaction_id }}</span>
        </nav>

        <!-- Status Header Card -->
        <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    @if ($transaction->source_type->value === 'MARKETPLACE')
                        <span class="text-xs font-bold text-indigo-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Pesanan Marketplace
                        </span>
                    @else
                        <span class="text-xs font-bold text-indigo-600">
                            {{ $transaction->source_type->label() }}
                        </span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-xs text-slate-500">ID Kesepakatan #{{ $agreement->agreement_id }}</span>
                    @endif
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-xs text-slate-400">{{ $transaction->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight" data-cy="transaction-title">
                    Transaksi #{{ $transaction->transaction_id }}
                </h1>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold border {{ $transaction->status->badgeClass() }}" data-cy="transaction-status-badge">
                    {{ $transaction->status->label() }}
                </span>
            </div>
        </div>

        <!-- Process Line / Stepper Card (Tahapan Transaksi Escrow) -->
        @php
            $currentStep = $transaction->status->stepIndex();
            $isTerminalFailed = in_array($transaction->status->value, ['CANCELLED', 'REFUNDED', 'DISPUTED']);
        @endphp

        <div class="glass-card p-5 sm:p-6 border border-white/80 shadow-sm space-y-4" data-cy="transaction-stepper-card">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    Proses Transaksi Escrow
                </span>
                @if ($isTerminalFailed)
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">
                        Status Khusus: {{ $transaction->status->label() }}
                    </span>
                @else
                    <span class="text-xs font-semibold text-indigo-600">
                        Tahap {{ min(4, max(1, $currentStep)) }} dari 4
                    </span>
                @endif
            </div>

            <!-- Stepper Progress Bar & Steps -->
            <div class="relative">
                <!-- Desktop Connected Stepper -->
                <div class="hidden sm:grid sm:grid-cols-4 gap-2 relative">
                    <!-- Progress Line Behind Steps -->
                    <div class="absolute top-4 left-[12.5%] right-[12.5%] h-0.5 bg-slate-200 -z-0">
                        @php
                            $progressWidth = match($currentStep) {
                                1 => '0%',
                                2 => '33.33%',
                                3 => '66.66%',
                                4 => '100%',
                                default => '0%',
                            };
                        @endphp
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-indigo-600 transition-all duration-500" style="width: {{ $progressWidth }}"></div>
                    </div>

                    <!-- Step 1: Pembayaran -->
                    <div class="flex flex-col items-center text-center relative z-10 space-y-2">
                        @if ($currentStep > 1)
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-emerald-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @elseif ($currentStep === 1)
                            <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-amber-100 animate-pulse">
                                1
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-xs ring-4 ring-slate-50">
                                1
                            </div>
                        @endif
                        <div>
                            <div class="text-xs font-bold {{ $currentStep >= 1 ? 'text-slate-900' : 'text-slate-400' }}">
                                Pembayaran Escrow
                            </div>
                            <div class="text-[10px] {{ $currentStep > 1 ? 'text-emerald-700 font-medium' : ($currentStep === 1 ? 'text-amber-800 font-semibold' : 'text-slate-400') }}">
                                {{ $currentStep > 1 ? 'Dana Diamankan' : ($currentStep === 1 ? 'Menunggu Pembeli' : 'Belum Bayar') }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Penyerahan Item -->
                    <div class="flex flex-col items-center text-center relative z-10 space-y-2">
                        @if ($currentStep > 2)
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-emerald-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @elseif ($currentStep === 2)
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-blue-100 animate-pulse">
                                2
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-xs ring-4 ring-slate-50">
                                2
                            </div>
                        @endif
                        <div>
                            <div class="text-xs font-bold {{ $currentStep >= 2 ? 'text-slate-900' : 'text-slate-400' }}">
                                Penyerahan Item
                            </div>
                            <div class="text-[10px] {{ $currentStep > 2 ? 'text-emerald-700 font-medium' : ($currentStep === 2 ? 'text-blue-700 font-semibold' : 'text-slate-400') }}">
                                {{ $currentStep > 2 ? 'Item Telah Dikirim' : ($currentStep === 2 ? 'Penjual Menyiapkan' : 'Menunggu Bayar') }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Konfirmasi Pembeli -->
                    <div class="flex flex-col items-center text-center relative z-10 space-y-2">
                        @if ($currentStep > 3)
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-emerald-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @elseif ($currentStep === 3)
                            <div class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-purple-100 animate-pulse">
                                3
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-xs ring-4 ring-slate-50">
                                3
                            </div>
                        @endif
                        <div>
                            <div class="text-xs font-bold {{ $currentStep >= 3 ? 'text-slate-900' : 'text-slate-400' }}">
                                Konfirmasi Pembeli
                            </div>
                            <div class="text-[10px] {{ $currentStep > 3 ? 'text-emerald-700 font-medium' : ($currentStep === 3 ? 'text-purple-700 font-semibold' : 'text-slate-400') }}">
                                {{ $currentStep > 3 ? 'Telah Dikonfirmasi' : ($currentStep === 3 ? 'Pengecekan Item' : 'Menunggu Kirim') }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Selesai -->
                    <div class="flex flex-col items-center text-center relative z-10 space-y-2">
                        @if ($currentStep >= 4)
                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-emerald-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-xs ring-4 ring-slate-50">
                                4
                            </div>
                        @endif
                        <div>
                            <div class="text-xs font-bold {{ $currentStep >= 4 ? 'text-slate-900' : 'text-slate-400' }}">
                                Selesai
                            </div>
                            <div class="text-[10px] {{ $currentStep >= 4 ? 'text-emerald-700 font-semibold' : 'text-slate-400' }}">
                                {{ $currentStep >= 4 ? 'Dana Diteruskan ke Penjual' : 'Pencairan Saldo' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Compact Stepper -->
                <div class="sm:hidden grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl border {{ $currentStep === 1 ? 'bg-amber-50/80 border-amber-300 font-bold text-amber-900' : ($currentStep > 1 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-500') }}">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black {{ $currentStep > 1 ? 'bg-emerald-600 text-white' : ($currentStep === 1 ? 'bg-amber-500 text-white' : 'bg-slate-300 text-slate-600') }}">1</span>
                            <span>1. Bayar Escrow</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl border {{ $currentStep === 2 ? 'bg-blue-50/80 border-blue-300 font-bold text-blue-900' : ($currentStep > 2 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-500') }}">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black {{ $currentStep > 2 ? 'bg-emerald-600 text-white' : ($currentStep === 2 ? 'bg-blue-600 text-white' : 'bg-slate-300 text-slate-600') }}">2</span>
                            <span>2. Serah Item</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl border {{ $currentStep === 3 ? 'bg-purple-50/80 border-purple-300 font-bold text-purple-900' : ($currentStep > 3 ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-500') }}">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black {{ $currentStep > 3 ? 'bg-emerald-600 text-white' : ($currentStep === 3 ? 'bg-purple-600 text-white' : 'bg-slate-300 text-slate-600') }}">3</span>
                            <span>3. Konfirmasi</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl border {{ $currentStep >= 4 ? 'bg-emerald-50 border-emerald-300 font-bold text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-500' }}">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black {{ $currentStep >= 4 ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-600' }}">4</span>
                            <span>4. Selesai</span>
                        </div>
                    </div>
                </div>
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
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 shrink-0">
                        <div class="text-left sm:text-right">
                            <span class="block text-[10px] uppercase font-bold text-slate-400">Total Tagihan</span>
                            <span class="text-lg font-black text-indigo-700">
                                Rp {{ number_format($agreement->buyer_total, 0, ',', '.') }}
                            </span>
                        </div>

                        @if ($isBuyer)
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    id="btn-pay-midtrans"
                                    class="glass-btn-primary px-5 py-2.5 text-xs sm:text-sm font-bold flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20 active:scale-95 transition"
                                    data-cy="btn-pay-midtrans"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span id="btn-pay-text">Bayar Sekarang (Escrow)</span>
                                </button>

                                @if ($transaction->payment?->gateway_order_id)
                                    <button
                                        type="button"
                                        id="btn-sync-payment"
                                        class="glass-btn-secondary px-3 py-2.5 text-xs font-semibold flex items-center gap-1.5"
                                        title="Periksa konfirmasi pembayaran dari gateway"
                                        data-cy="btn-sync-payment"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <span id="btn-sync-text">Cek Status</span>
                                    </button>
                                @endif
                            </div>
                        @elseif ($isSeller)
                            <div class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 border border-amber-200/80 text-xs font-semibold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>Menunggu Pembeli Membayar</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div id="payment-error-alert" class="hidden mt-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="payment-error-message"></span>
                </div>
            </div>
        @endif

        <!-- Cancellation Status & Action Modal (Pra-Bayar & Pasca-Bayar) -->
        @include('transactions.partials.cancellation-modal')

        <!-- Delivery Section (Form Seller pada PAID & Hasil Penyerahan pada DELIVERED dst) -->
        @include('transactions.partials.delivery-section')

        <!-- Immutable Agreement Snapshot Details Panel -->
        <div class="glass-panel p-6 sm:p-8 space-y-6 border border-white/90 shadow-md">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200/60">
                <div class="flex items-center gap-2">
                    @if ($transaction->source_type->value === 'MARKETPLACE')
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
                            📦
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Detail Pesanan Produk
                            </h2>
                            <span class="text-[11px] text-slate-500">
                                Pesanan Marketplace &bull; Dipesan pada {{ $transaction->created_at->translatedFormat('d F Y, H:i') }} WIB
                            </span>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
                            📜
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Lembar Kesepakatan Final
                            </h2>
                            <span class="text-[11px] text-slate-500">
                                Status: <strong class="text-emerald-700">{{ $agreement->status->label() }}</strong> &bull; Terkunci sejak {{ $agreement->finalized_at?->translatedFormat('d F Y, H:i') }} WIB
                            </span>
                        </div>
                    @endif
                </div>

                @if ($transaction->source_type->value !== 'MARKETPLACE')
                    <span class="glass-badge glass-badge-primary text-[10px]">
                        Revisi #{{ $agreement->revision_no }} (Final)
                    </span>
                @else
                    <span class="glass-badge glass-badge-neutral text-[10px]">
                        Pesanan Terverifikasi
                    </span>
                @endif
            </div>

            <!-- Agreement Parties -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-white/60 border border-slate-100 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Pembeli</span>
                        @if (! $isBuyer && $agreement->buyer)
                            <button
                                type="button"
                                onclick="openAccountReportModal({{ $agreement->buyer->user_id }}, '{{ addslashes($agreement->buyer->username) }}', {{ $transaction->transaction_id }})"
                                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 hover:underline"
                                title="Laporkan akun pembeli atas transaksi ini"
                                data-cy="btn-report-buyer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Laporkan</span>
                            </button>
                        @endif
                    </div>
                    <div class="font-bold text-sm text-slate-900" data-cy="agreement-buyer-username">
                        @<span>{{ $agreement->buyer?->username }}</span>
                        @if ($isBuyer)
                            <span class="ml-1 text-[10px] px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold">Anda</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500">{{ $agreement->buyer?->email }}</div>
                </div>

                <div class="p-4 rounded-xl bg-white/60 border border-slate-100 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Penjual</span>
                        @if (! $isSeller && $agreement->seller)
                            <button
                                type="button"
                                onclick="openAccountReportModal({{ $agreement->seller->user_id }}, '{{ addslashes($agreement->seller->username) }}', {{ $transaction->transaction_id }})"
                                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 hover:underline"
                                title="Laporkan akun penjual atas transaksi ini"
                                data-cy="btn-report-seller"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Laporkan</span>
                            </button>
                        @endif
                    </div>
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
                    <span class="block text-[10px] uppercase font-bold text-slate-400">
                        {{ $transaction->source_type->value === 'MARKETPLACE' ? 'Item Produk Digital' : 'Item Digital Yang Disepakati' }}
                    </span>
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
                            {{ $agreement->deliveryMethodLabel() }}
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

            <!-- Financial Table -->
            <div class="space-y-3">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                    {{ $transaction->source_type->value === 'MARKETPLACE' ? 'Rincian Pembayaran' : 'Nilai Finansial Kesepakatan' }}
                </span>

                <div class="bg-white/70 rounded-xl p-4 border border-slate-200/80 space-y-2.5 text-xs sm:text-sm">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Harga Produk:</span>
                        <span class="font-semibold text-slate-900" data-cy="snapshot-base-price">
                            Rp {{ number_format($agreement->base_price, 0, ',', '.') }}
                        </span>
                    </div>

                    @if ($agreement->fee_amount > 0)
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Biaya Layanan Platform:</span>
                            <span class="font-semibold text-indigo-600" data-cy="snapshot-fee-amount">
                                + Rp {{ number_format($agreement->fee_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    <div class="border-t border-slate-200 pt-2 flex justify-between items-center font-bold">
                        <span class="text-slate-900">Total Pembayaran:</span>
                        <span class="text-base font-extrabold text-indigo-700" data-cy="snapshot-buyer-total">
                            Rp {{ number_format($agreement->buyer_total, 0, ',', '.') }}
                        </span>
                    </div>

                    @if ($isSeller)
                        <div class="border-t border-slate-200 pt-2 flex justify-between items-center">
                            <span class="text-slate-600">Pencairan Hak Bersih Penjual:</span>
                            <span class="font-bold text-emerald-600" data-cy="snapshot-seller-net">
                                Rp {{ number_format($agreement->seller_net, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
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
                                        <span class="font-bold text-slate-800 text-[11px]">
                                            {{ \App\Enums\TransactionStatus::tryFrom($history->to_status)?->label() ?? $history->to_status }}
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
                    href="{{ route('transactions.index') }}"
                    class="glass-btn-secondary px-4 py-2 text-xs font-semibold flex items-center gap-1.5"
                >
                    &larr; Kembali ke Daftar Transaksi
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

@include('account-reports.partials.report-modal')
@endsection

@if ($isBuyer && $transaction->status->value === 'WAITING_PAYMENT')
@push('scripts')
<script
    src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('services.midtrans.client_key') }}"
></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const payBtn = document.getElementById('btn-pay-midtrans');
    const payText = document.getElementById('btn-pay-text');
    const errorAlert = document.getElementById('payment-error-alert');
    const errorMessage = document.getElementById('payment-error-message');

    if (!payBtn) return;

    function showError(msg) {
        if (errorMessage && errorAlert) {
            errorMessage.textContent = msg;
            errorAlert.classList.remove('hidden');
        } else {
            alert(msg);
        }
    }

    function clearError() {
        if (errorAlert) {
            errorAlert.classList.add('hidden');
        }
    }

    payBtn.addEventListener('click', async function () {
        clearError();
        const originalText = payText.textContent;
        payBtn.disabled = true;
        payText.textContent = 'Memuat Gateway...';

        try {
            const response = await fetch('{{ route('transactions.payment-token', $transaction) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Gagal menyiapkan token pembayaran.');
            }

            if (!data.snap_token) {
                throw new Error('Token pembayaran tidak valid.');
            }

            if (typeof window.snap === 'undefined') {
                throw new Error('SDK Midtrans Snap belum berhasil dimuat. Periksa koneksi internet Anda.');
            }

            async function syncAndReload() {
                try {
                    const syncRes = await fetch('{{ route('transactions.sync-payment', $transaction) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const syncData = await syncRes.json();
                    if (['success', 'already_paid', 'paid', 'expired', 'cancelled', 'held_for_reconciliation'].includes(syncData.status)) {
                        window.location.reload();
                        return true;
                    }
                } catch (e) {
                    console.error('Auto-sync error:', e);
                }
                return false;
            }

            window.snap.pay(data.snap_token, {
                onSuccess: async function (result) {
                    payText.textContent = 'Pembayaran Berhasil! Memperbarui status...';
                    await syncAndReload();
                    window.location.reload();
                },
                onPending: async function (result) {
                    payText.textContent = 'Menunggu Pembayaran...';
                    await syncAndReload();
                    window.location.reload();
                },
                onError: function (result) {
                    showError('Pembayaran gagal atau terjadi kendala di gateway.');
                    payBtn.disabled = false;
                    payText.textContent = originalText;
                },
                onClose: async function () {
                    payBtn.disabled = false;
                    payText.textContent = originalText;
                    // Cek barangkali pembeli telah menyelesaikan pembayaran di tab lain / simulator
                    await syncAndReload();
                }
            });
        } catch (err) {
            showError(err.message || 'Terjadi kesalahan saat memproses pembayaran.');
            payBtn.disabled = false;
            payText.textContent = originalText;
        }
    });

    const syncBtn = document.getElementById('btn-sync-payment');
    const syncText = document.getElementById('btn-sync-text');
    if (syncBtn) {
        syncBtn.addEventListener('click', async function () {
            clearError();
            syncBtn.disabled = true;
            syncText.textContent = 'Memeriksa...';

            try {
                const response = await fetch('{{ route('transactions.sync-payment', $transaction) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (['success', 'already_paid', 'paid', 'expired', 'cancelled', 'held_for_reconciliation'].includes(data.status)) {
                    syncText.textContent = 'Diperbarui!';
                    window.location.reload();
                } else if (data.status === 'pending') {
                    alert('Pembayaran belum terdeteksi selesai di Midtrans. Jika menggunakan simulator/VA, pastikan Anda telah menekan tombol Bayar di simulator.');
                    syncBtn.disabled = false;
                    syncText.textContent = 'Cek Status';
                } else {
                    showError(data.message || 'Belum ada pembaruan status pembayaran.');
                    syncBtn.disabled = false;
                    syncText.textContent = 'Cek Status';
                }
            } catch (e) {
                showError('Gagal memeriksa status ke server.');
                syncBtn.disabled = false;
                syncText.textContent = 'Cek Status';
            }
        });
    }
});
</script>
@endpush
@endif

@if ($transaction->status->value === 'WAITING_PAYMENT')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const syncUrl = '{{ route('transactions.sync-payment', $transaction) }}';
    const csrfToken = '{{ csrf_token() }}';
    const initialRemainingMs = {{ $transaction->payment_due_at ? max(0, $transaction->payment_due_at->getTimestampMs() - now()->getTimestampMs()) : 'null' }};
    let isReconciling = false;

    async function autoReconcilePayment() {
        if (isReconciling) return;
        isReconciling = true;

        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (['success', 'already_paid', 'paid', 'expired', 'cancelled', 'held_for_reconciliation'].includes(data.status)) {
                    window.location.reload();
                    return;
                }
            }
        } catch (err) {
            // Abaikan gangguan jaringan sementara pada auto-reconcile background
        } finally {
            isReconciling = false;
        }
    }

    if (initialRemainingMs !== null) {
        if (initialRemainingMs <= 0) {
            autoReconcilePayment();
        } else {
            setTimeout(function () {
                autoReconcilePayment();
            }, initialRemainingMs + 500);
        }
    }

    setInterval(function () {
        autoReconcilePayment();
    }, 20000);
});
</script>
@endpush
@endif
