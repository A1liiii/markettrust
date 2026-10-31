@extends('layouts.admin')

@section('title', 'Pengawasan Transaksi, Escrow & Settlement - Panel Administrasi')

@section('content')
<div class="py-8 sm:py-12" data-cy="admin-transactions-page">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">
                    <span>Panel Administrasi</span>
                    <span>&bull;</span>
                    <span>Modul Tahap Lanjutan</span>
                    <span>&bull;</span>
                    <span>Operasional Escrow & Settlement (UC-19 &bull; UC-20 &bull; UC-28 &bull; UC-29)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Pengawasan Transaksi, Settlement & KEEP
                </h1>
                <p class="text-sm text-slate-600 mt-1 max-w-3xl">
                    Catat pencairan dana penjual (payout simulatif) beserta pemotongan kewajiban KEEP, kelola percobaan payout gagal/retry, proses pengembalian dana pembeli (refund simulatif), dan tinjau pembatalan pasca-bayar.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    onclick="document.getElementById('modal-announce-disruption')?.classList.remove('hidden')"
                    class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition"
                    data-cy="btn-open-disruption-modal"
                >
                    📢 Pengumuman Gangguan Sistem Payout
                </button>
                <a href="{{ route('admin.dashboard') }}" class="glass-btn-secondary text-xs sm:text-sm py-2 px-3.5">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs" data-cy="alert-success">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs" data-cy="alert-error">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1 shadow-xs" data-cy="alert-validation-error">
                <div class="font-bold">Terdapat kesalahan input:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- SECTION A: ANTREAN PAYOUT SIMULATIF SELLER & PENYELESAIAN KEEP (UC-19, UC-29, UC-29B) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4" data-cy="section-payout-queue">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-sm">
                        💸
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Antrean Payout Simulatif Penjual (UC-19 &bull; UC-29 &bull; UC-29B)
                        </h2>
                        <p class="text-xs text-slate-500">
                            Transaksi berstatus CONFIRMED atau keputusan komplain PAYOUT tanpa komplain aktif. Potongan KEEP dihitung otomatis dari transaksi asal lain hingga maksimal sebesar hak kotor penjual.
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $payoutCandidates->count() > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $payoutCandidates->count() }} Siap / Diproses Payout
                </span>
            </div>

            @if ($payoutCandidates->isEmpty())
                <div class="text-center py-8 text-slate-500">
                    <p class="text-xs font-semibold">Tidak ada transaksi dalam antrean pencairan payout saat ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">Transaksi</th>
                                <th class="py-2.5 px-3">Penjual & Tujuan Aktif</th>
                                <th class="py-2.5 px-3">Hak Kotor (`seller_net`)</th>
                                <th class="py-2.5 px-3">Potongan KEEP</th>
                                <th class="py-2.5 px-3">Nominal Bersih Payout</th>
                                <th class="py-2.5 px-3">Status & Percobaan</th>
                                <th class="py-2.5 px-3 text-right">Aksi Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($payoutCandidates as $tx)
                                @php
                                    $preview = $payoutPreviews[$tx->transaction_id] ?? [
                                        'gross_amount' => (float) ($tx->agreement?->seller_net ?? 0),
                                        'keep_deducted_amount' => 0.0,
                                        'net_amount' => (float) ($tx->agreement?->seller_net ?? 0),
                                        'total_open_keep_before' => 0.0,
                                        'remaining_keep_after' => 0.0,
                                        'has_valid_destination' => false,
                                        'destination' => null,
                                    ];
                                    $dest = $preview['destination'];
                                    $settlement = $tx->settlement;
                                    $latestAttempt = $settlement?->relationLoaded('attempts')
                                        ? $settlement->attempts->first()
                                        : null;
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition" data-cy="payout-row-{{ $tx->transaction_id }}">
                                    <td class="py-3 px-3">
                                        <a href="{{ route('transactions.show', $tx) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                            #TX-{{ $tx->transaction_id }}
                                        </a>
                                        <div class="text-[11px] font-semibold text-slate-800 line-clamp-1">
                                            {{ $tx->agreement?->item_title ?? 'Produk Digital' }}
                                        </div>
                                        <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $tx->complaint && strtoupper((string) $tx->complaint->decision) === 'PAYOUT' ? 'Keputusan Komplain: PAYOUT' : 'Buyer Confirmed' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900">{{ $tx->agreement?->seller?->username ?? '-' }}</div>
                                        @if ($preview['has_valid_destination'] && $dest)
                                            <div class="text-[11px] text-emerald-700 font-semibold" data-cy="payout-destination-valid-{{ $tx->transaction_id }}">
                                                {{ $dest['destination_type'] }}{{ ! empty($dest['destination_provider']) ? ' • ' . $dest['destination_provider'] : '' }}
                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                {{ $dest['destination_number'] ?? 'QRIS' }} a.n. {{ $dest['destination_holder'] }}
                                            </div>
                                        @else
                                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200" data-cy="payout-destination-missing-{{ $tx->transaction_id }}">
                                                Ditahan: Tujuan Belum Lengkap
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-slate-900" data-cy="payout-gross-{{ $tx->transaction_id }}">
                                        Rp {{ number_format($preview['gross_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3" data-cy="payout-keep-{{ $tx->transaction_id }}">
                                        <div class="font-bold {{ $preview['keep_deducted_amount'] > 0 ? 'text-rose-600' : 'text-slate-600' }}">
                                            - Rp {{ number_format($preview['keep_deducted_amount'], 0, ',', '.') }}
                                        </div>
                                        @if ($preview['remaining_keep_after'] > 0)
                                            <div class="text-[10px] text-amber-700 font-semibold" data-cy="payout-keep-carryover-{{ $tx->transaction_id }}">
                                                Sisa dibawa berikutnya: Rp {{ number_format($preview['remaining_keep_after'], 0, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-extrabold text-emerald-700 text-sm" data-cy="payout-net-{{ $tx->transaction_id }}">
                                        Rp {{ number_format($preview['net_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3">
                                        @if ($latestAttempt)
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $latestAttempt->status === 'FAILED' ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-amber-100 text-amber-800 border-amber-200' }}"
                                                data-cy="payout-attempt-badge-{{ $tx->transaction_id }}"
                                            >
                                                Attempt #{{ $latestAttempt->attempt_no }}: {{ $latestAttempt->status }}
                                            </span>
                                            <div class="text-[10px] text-slate-500 mt-0.5 line-clamp-2">
                                                {{ $latestAttempt->failure_reason }}
                                            </div>
                                        @elseif ($settlement)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ $settlement->status }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                Menunggu Eksekusi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap space-x-1">
                                        @if ($preview['has_valid_destination'])
                                            <form action="{{ route('admin.settlements.sync-destination', $tx) }}" method="POST" class="inline">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition"
                                                    title="Salin/Sinkronkan tujuan payout aktif terbaru milik Penjual ke Settlement"
                                                    data-cy="btn-sync-destination-{{ $tx->transaction_id }}"
                                                >
                                                    🔄 Sinkron Tujuan
                                                </button>
                                            </form>
                                        @endif
                                        <button
                                            type="button"
                                            onclick="openPayoutModal(
                                                {{ $tx->transaction_id }},
                                                '{{ addslashes($tx->agreement?->seller?->username ?? '') }}',
                                                {{ $preview['gross_amount'] }},
                                                {{ $preview['keep_deducted_amount'] }},
                                                {{ $preview['net_amount'] }},
                                                {{ $preview['has_valid_destination'] ? 'true' : 'false' }},
                                                '{{ addslashes(($dest['destination_type'] ?? '-') . ' ' . ($dest['destination_provider'] ?? '') . ' - ' . ($dest['destination_number'] ?? '') . ' a.n. ' . ($dest['destination_holder'] ?? 'Belum Lengkap')) }}'
                                            )"
                                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition"
                                            data-cy="btn-open-payout-modal-{{ $tx->transaction_id }}"
                                        >
                                            {{ $latestAttempt ? 'Retry / Catat Payout' : 'Catat Payout' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SECTION B: ANTREAN REFUND SIMULATIF BUYER (UC-20) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4" data-cy="section-refund-queue">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        ↩️
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Antrean Refund Simulatif Pembeli (UC-20)
                        </h2>
                        <p class="text-xs text-slate-500">
                            Transaksi dengan keputusan sah REFUND dari komplain (UC-18) atau pembatalan pasca-bayar (UC-28). Kewajiban KEEP akibat kesalahan Penjual dibebankan ke Penjual dan tidak memotong refund Pembeli.
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $refundCandidates->count() > 0 ? 'bg-indigo-100 text-indigo-800 border border-indigo-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $refundCandidates->count() }} Menunggu Refund
                </span>
            </div>

            @if ($refundCandidates->isEmpty())
                <div class="text-center py-8 text-slate-500">
                    <p class="text-xs font-semibold">Tidak ada transaksi dalam antrean pengembalian dana (refund) saat ini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">Transaksi</th>
                                <th class="py-2.5 px-3">Pembeli</th>
                                <th class="py-2.5 px-3">Sumber Keputusan Sah</th>
                                <th class="py-2.5 px-3">Beban KEEP Penjual</th>
                                <th class="py-2.5 px-3">Nominal Refund Pembeli</th>
                                <th class="py-2.5 px-3 text-right">Aksi Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($refundCandidates as $tx)
                                @php
                                    $rPreview = $refundPreviews[$tx->transaction_id] ?? [
                                        'source' => 'POST_PAYMENT_CANCELLATION',
                                        'gross_amount' => (float) ($tx->agreement?->buyer_total ?? 0),
                                        'refund_amount' => (float) ($tx->agreement?->buyer_total ?? 0),
                                        'responsible_party' => null,
                                        'seller_keep_amount' => 0.0,
                                    ];
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition" data-cy="refund-row-{{ $tx->transaction_id }}">
                                    <td class="py-3 px-3">
                                        <a href="{{ route('transactions.show', $tx) }}" class="font-mono font-bold text-indigo-600 hover:underline">
                                            #TX-{{ $tx->transaction_id }}
                                        </a>
                                        <div class="text-[11px] font-semibold text-slate-800 line-clamp-1">
                                            {{ $tx->agreement?->item_title ?? 'Produk Digital' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-900">
                                        {{ $tx->agreement?->buyer?->username ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" data-cy="refund-source-{{ $tx->transaction_id }}">
                                            {{ $rPreview['source'] === 'COMPLAINT_DECISION' ? 'Keputusan Komplain (UC-18)' : 'Pembatalan Pasca-Bayar (UC-28)' }}
                                        </span>
                                        @if ($rPreview['responsible_party'])
                                            <div class="text-[10px] text-slate-500 mt-0.5">
                                                Pihak Bertanggung Jawab: <strong>{{ $rPreview['responsible_party'] }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        @if ($rPreview['seller_keep_amount'] > 0)
                                            <span class="font-semibold text-rose-700">
                                                Rp {{ number_format($rPreview['seller_keep_amount'], 0, ',', '.') }} (Beban Seller)
                                            </span>
                                            <span class="block text-[10px] text-slate-400">Tidak memotong refund Buyer</span>
                                        @else
                                            <span class="text-slate-400">Rp 0</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 font-extrabold text-indigo-700 text-sm" data-cy="refund-amount-{{ $tx->transaction_id }}">
                                        Rp {{ number_format($rPreview['refund_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick="openRefundModal(
                                                {{ $tx->transaction_id }},
                                                '{{ addslashes($tx->agreement?->buyer?->username ?? '') }}',
                                                {{ $rPreview['refund_amount'] }}
                                            )"
                                            class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition"
                                            data-cy="btn-open-refund-modal-{{ $tx->transaction_id }}"
                                        >
                                            Catat Refund Simulatif
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SECTION 1: ANTREAN PENINJAUAN PEMBATALAN (UC-28 - Kompatibel MT-A06) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-sm">
                        ⏳
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Antrean Pengajuan Pembatalan Pasca-Bayar
                        </h2>
                        <p class="text-xs text-slate-500">
                            Transaksi berstatus PAID yang diajukan pembatalan sebelum tahap penyerahan (DELIVERED).
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $pendingCancellations->count() > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-600' }}">
                    {{ $pendingCancellations->count() }} Menunggu Tinjauan
                </span>
            </div>

            @if ($pendingCancellations->isEmpty())
                <div class="text-center py-8 text-slate-500">
                    <p class="text-xs font-semibold">Tidak ada pengajuan pembatalan yang sedang menunggu peninjauan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">ID Pembatalan</th>
                                <th class="py-2.5 px-3">Transaksi</th>
                                <th class="py-2.5 px-3">Pemohon</th>
                                <th class="py-2.5 px-3">Alasan Pengajuan</th>
                                <th class="py-2.5 px-3">Diajukan Pada</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pendingCancellations as $canc)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        #CAN-{{ $canc->cancellation_id }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('transactions.show', $canc->transaction) }}" class="font-bold text-indigo-600 hover:underline">
                                            #TX-{{ $canc->transaction_id }}
                                        </a>
                                        <div class="text-[10px] text-slate-500">
                                            {{ $canc->transaction->agreement?->item_title ?? 'Produk Digital' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-800">{{ $canc->requestedBy?->username }}</span>
                                        <span class="block text-[10px] text-slate-400">
                                            {{ $canc->requested_by === $canc->transaction->agreement?->buyer_id ? 'Pembeli' : 'Penjual' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 max-w-xs">
                                        <p class="line-clamp-2 text-slate-700 italic">
                                            "{{ $canc->reason }}"
                                        </p>
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                                        {{ $canc->created_at?->translatedFormat('d M Y, H:i') }} WIB
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            onclick="openReviewModal({{ $canc->cancellation_id }}, {{ $canc->transaction_id }}, '{{ addslashes($canc->requestedBy?->username ?? '') }}', '{{ addslashes($canc->reason ?? '') }}')"
                                            class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition"
                                        >
                                            Tinjau Keputusan
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SECTION 2: KEWAJIBAN BIAYA KEEP PENJUAL (UC-28B) -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4" data-cy="section-keep-obligations">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        ⚖️
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Daftar Kewajiban KEEP Penjual (Escrow Retention)
                        </h2>
                        <p class="text-xs text-slate-500">
                            Biaya pemeliharaan rekening escrow yang dibebankan kepada penjual akibat pembatalan sepihak / kelalaian.
                        </p>
                    </div>
                </div>
            </div>

            @if ($keepObligations->isEmpty())
                <div class="text-center py-8 text-slate-500">
                    <p class="text-xs">Belum ada kewajiban KEEP yang tercatat pada sistem.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">ID KEEP</th>
                                <th class="py-2.5 px-3">Penjual (Seller)</th>
                                <th class="py-2.5 px-3">Transaksi Asal</th>
                                <th class="py-2.5 px-3">Channel & Aturan Hitung</th>
                                <th class="py-2.5 px-3">Nominal Tagihan</th>
                                <th class="py-2.5 px-3">Sisa Tagihan</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($keepObligations as $keep)
                                <tr class="hover:bg-slate-50/50 transition" data-cy="keep-row-{{ $keep->keep_obligation_id }}">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        #KEEP-{{ $keep->keep_obligation_id }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="{{ route('sellers.keep-ledger', $keep->seller_id) }}" class="font-bold text-indigo-600 hover:underline">
                                            {{ $keep->seller?->username }}
                                        </a>
                                        <span class="block text-[10px] text-slate-400">ID: {{ $keep->seller_id }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if ($keep->cancellation?->transaction)
                                            <a href="{{ route('transactions.show', $keep->cancellation->transaction) }}" class="font-bold text-indigo-600 hover:underline">
                                                #TX-{{ $keep->cancellation->transaction_id }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-800 uppercase">{{ $keep->payment_channel_snapshot }}</span>
                                        <span class="block text-[10px] text-slate-500">
                                            {{ $keep->calculation_rule === 'BANK_VA_FLAT' ? 'Flat Rp 4.000 (Bank/VA)' : '3x Fee Gateway (Rp ' . number_format($keep->fee_basis_amount, 0, ',', '.') . ')' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-900">
                                        Rp {{ number_format($keep->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 font-extrabold {{ $keep->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}" data-cy="keep-remaining-{{ $keep->keep_obligation_id }}">
                                        Rp {{ number_format($keep->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $keep->status === 'OPEN' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}" data-cy="keep-status-{{ $keep->keep_obligation_id }}">
                                            {{ $keep->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    {{ $keepObligations->links() }}
                </div>
            @endif
        </div>

        <!-- SECTION 3: REKAPITULASI TRANSAKSI TERBARU -->
        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">
                    Seluruh Transaksi Sistem
                </h2>
                <span class="text-xs text-slate-500">
                    Total: {{ $transactions->total() }} Transaksi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3">ID</th>
                            <th class="py-2.5 px-3">Item Kesepakatan</th>
                            <th class="py-2.5 px-3">Pembeli</th>
                            <th class="py-2.5 px-3">Penjual</th>
                            <th class="py-2.5 px-3">Nilai Transaksi</th>
                            <th class="py-2.5 px-3">Status Escrow</th>
                            <th class="py-2.5 px-3 text-right">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transactions as $tx)
                            <tr class="hover:bg-slate-50/50 transition" data-cy="tx-row-{{ $tx->transaction_id }}">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                    #TX-{{ $tx->transaction_id }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800 line-clamp-1">
                                        {{ $tx->agreement?->item_title ?? 'Produk Digital' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $tx->source_type->label() }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-700">
                                    {{ $tx->agreement?->buyer?->username ?? '-' }}
                                </td>
                                <td class="py-3 px-3 text-slate-700">
                                    {{ $tx->agreement?->seller?->username ?? '-' }}
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900">
                                    Rp {{ number_format($tx->agreement?->buyer_total ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $tx->status->badgeClass() }}" data-cy="tx-status-{{ $tx->transaction_id }}">
                                        {{ $tx->status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('transactions.show', $tx) }}" class="glass-btn-secondary text-xs py-1 px-2.5">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        </div>

    </div>
</div>

<!-- MODAL 1: CATAT PAYOUT SIMULATIF / PERCOBAAN GAGAL (UC-19, UC-29, UC-29B) -->
<div id="modal-record-payout" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4" data-cy="modal-record-payout">
    <div class="glass-card w-full max-w-lg p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">
                    Pencatatan Payout Simulatif Penjual
                </h3>
                <p id="payout-modal-subtitle" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" onclick="closePayoutModal()" class="text-slate-400 hover:text-slate-600 transition">
                ✕
            </button>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
            <div class="flex justify-between">
                <span class="text-slate-500">Tujuan Rekening Aktif Seller:</span>
                <span id="payout-modal-destination" class="font-semibold text-slate-800 text-right"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Hak Kotor (`seller_net`):</span>
                <span id="payout-modal-gross" class="font-semibold text-slate-900"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Potongan KEEP OPEN (Transaksi Lain):</span>
                <span id="payout-modal-keep" class="font-semibold text-rose-600"></span>
            </div>
            <div class="flex justify-between pt-1.5 border-t border-slate-200 font-bold">
                <span class="text-slate-900">Nominal Bersih Payout Diterima Seller:</span>
                <span id="payout-modal-net" class="text-emerald-700"></span>
            </div>
        </div>

        <form id="form-record-payout" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Hasil Eksekusi Payout Simulatif</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="execution_outcome" value="SUCCESS" checked onchange="togglePayoutOutcome('SUCCESS')" data-cy="radio-outcome-success">
                        <span class="text-xs font-bold text-emerald-700">Berhasil (SUCCESS)</span>
                    </label>
                    <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="execution_outcome" value="FAILED" onchange="togglePayoutOutcome('FAILED')" data-cy="radio-outcome-failed">
                        <span class="text-xs font-bold text-rose-700">Gagal (FAILED)</span>
                    </label>
                    <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="execution_outcome" value="UNKNOWN" onchange="togglePayoutOutcome('UNKNOWN')" data-cy="radio-outcome-unknown">
                        <span class="text-xs font-bold text-amber-700">Belum Pasti (UNKNOWN)</span>
                    </label>
                </div>
            </div>

            <!-- Bagian Input Keberhasilan (SUCCESS) -->
            <div id="payout-success-fields" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Referensi Simulasi Transfer <span class="text-rose-600">*</span></label>
                    <input
                        type="text"
                        name="reference_no"
                        id="payout-reference-no"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="Contoh: SIM-PAYOUT-20261029-001"
                        data-cy="input-payout-reference"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Penyelesaian (Opsional)</label>
                    <textarea
                        name="note"
                        rows="2"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="Catatan pencairan dana simulatif..."
                        data-cy="input-payout-note"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Bukti Simulasi (JPG/PNG/WEBP/PDF maks. 5MB)</label>
                    <input
                        type="file"
                        name="evidence_file"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        class="text-xs text-slate-600 w-full"
                        data-cy="input-payout-evidence"
                    >
                </div>
            </div>

            <!-- Bagian Input Kegagalan / Ketidakpastian (FAILED / UNKNOWN) -->
            <div id="payout-failure-fields" class="hidden space-y-3 p-3 rounded-xl bg-rose-50/50 border border-rose-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Penyebab <span class="text-rose-600">*</span></label>
                    <select name="failure_category" class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200" data-cy="select-failure-category">
                        <option value="SYSTEM">Gangguan Sistem / Saluran Transfer</option>
                        <option value="SELLER_DESTINATION">Tujuan Rekening Seller Ditolak / Tidak Valid</option>
                        <option value="GATEWAY_UNKNOWN">Status Mutasi Belum Pasti (Menunggu Rekonsiliasi)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sebab Gagal / Belum Pasti <span class="text-rose-600">*</span></label>
                    <textarea
                        name="failure_reason"
                        rows="2"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="Jelaskan penyebab kegagalan atau ketidakpastian payout..."
                        data-cy="input-failure-reason"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tindak Lanjut Operasional</label>
                    <input
                        type="text"
                        name="follow_up_action"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="Contoh: Meminta seller perbarui rekening / jadwalkan retry..."
                        data-cy="input-follow-up-action"
                    >
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer pt-1">
                    <input type="checkbox" name="announce_disruption" value="1" data-cy="checkbox-announce-disruption">
                    <span>Kirim juga pengumuman gangguan sistem payout ke pengguna terdampak</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closePayoutModal()" class="glass-btn-secondary text-xs py-2 px-4">
                    Batal
                </button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-5 font-bold" data-cy="btn-submit-payout">
                    Simpan Pencatatan Payout
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: CATAT REFUND SIMULATIF BUYER (UC-20) -->
<div id="modal-record-refund" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4" data-cy="modal-record-refund">
    <div class="glass-card w-full max-w-lg p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">
                    Pencatatan Refund Simulatif Pembeli (UC-20)
                </h3>
                <p id="refund-modal-subtitle" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" onclick="closeRefundModal()" class="text-slate-400 hover:text-slate-600 transition">
                ✕
            </button>
        </div>

        <div class="p-3 rounded-xl bg-indigo-50/60 border border-indigo-200 text-xs flex justify-between items-center font-bold">
            <span class="text-slate-800">Nominal Hak Refund Pembeli:</span>
            <span id="refund-modal-amount" class="text-indigo-700 text-sm"></span>
        </div>

        <form id="form-record-refund" method="POST" action="" enctype="multipart/form-data" class="space-y-3">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Metode Tujuan <span class="text-rose-600">*</span></label>
                    <select name="destination_type" class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200" data-cy="select-refund-type">
                        <option value="BANK">Transfer Bank (BANK)</option>
                        <option value="EWALLET">Dompet Digital (EWALLET)</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Provider Bank / E-Wallet</label>
                    <input
                        type="text"
                        name="destination_provider"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="BCA / Mandiri / GoPay / OVO"
                        data-cy="input-refund-provider"
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Rekening / Tujuan</label>
                    <input
                        type="text"
                        name="destination_number"
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="1234567890"
                        data-cy="input-refund-number"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemilik Tujuan <span class="text-rose-600">*</span></label>
                    <input
                        type="text"
                        name="destination_holder"
                        id="refund-destination-holder"
                        required
                        class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                        placeholder="Nama lengkap pemilik rekening"
                        data-cy="input-refund-holder"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Referensi Simulasi Refund <span class="text-rose-600">*</span></label>
                <input
                    type="text"
                    name="reference_no"
                    required
                    class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                    placeholder="Contoh: SIM-REFUND-20261029-001"
                    data-cy="input-refund-reference"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Refund (Opsional)</label>
                <textarea
                    name="note"
                    rows="2"
                    class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200"
                    placeholder="Catatan pengembalian dana simulatif kepada pembeli..."
                    data-cy="input-refund-note"
                ></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Bukti Simulasi Refund (JPG/PNG/WEBP/PDF maks. 5MB)</label>
                <input
                    type="file"
                    name="evidence_file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    class="text-xs text-slate-600 w-full"
                    data-cy="input-refund-evidence"
                >
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRefundModal()" class="glass-btn-secondary text-xs py-2 px-4">
                    Batal
                </button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-5 font-bold" data-cy="btn-submit-refund">
                    Simpan Refund & Tandai REFUNDED
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: PENGUMUMAN GANGGUAN SISTEM PAYOUT LUAS (UC-29) -->
<div id="modal-announce-disruption" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4" data-cy="modal-announce-disruption">
    <div class="glass-card w-full max-w-md p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-extrabold text-slate-900">
                Pengumuman Gangguan Sistem Payout
            </h3>
            <button type="button" onclick="document.getElementById('modal-announce-disruption')?.classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                ✕
            </button>
        </div>

        <form action="{{ route('admin.settlements.announce-disruption') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pesan Pengumuman Gangguan <span class="text-rose-600">*</span></label>
                <textarea
                    name="message"
                    rows="3"
                    required
                    maxlength="250"
                    class="glass-input text-xs w-full p-3 rounded-xl border border-slate-200"
                    placeholder="Contoh: Saluran pencairan bank sedang mengalami pemeliharaan terjadwal. Proses payout manual akan diulang pada settlement yang sama."
                    data-cy="input-disruption-message"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-announce-disruption')?.classList.add('hidden')" class="glass-btn-secondary text-xs py-2 px-4">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs" data-cy="btn-submit-disruption">
                    Kirim Pengumuman
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REVIEW PEMBATALAN OLEH ADMIN (Kompatibel MT-A06) -->
<div id="modal-review-cancellation" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">
                    Keputusan Peninjauan Pembatalan
                </h3>
                <p id="modal-review-subtitle" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 transition">
                ✕
            </button>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
            <span class="block text-[10px] uppercase font-bold text-slate-400">Alasan dari Pemohon (<span id="modal-review-requester"></span>):</span>
            <p id="modal-review-reason" class="mt-0.5 italic font-medium text-slate-800"></p>
        </div>

        <form id="form-review-cancellation" method="POST" action="" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Keputusan Admin</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="decision" value="APPROVED" checked onchange="toggleResponsibleParty(true)">
                        <span class="text-xs font-bold text-slate-800">Setujui & Batalkan</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="decision" value="REJECTED" onchange="toggleResponsibleParty(false)">
                        <span class="text-xs font-bold text-slate-800">Tolak Pembatalan</span>
                    </label>
                </div>
            </div>

            <div id="section-responsible-party" class="space-y-1.5">
                <label for="responsible_party" class="block text-xs font-bold text-slate-700">
                    Pihak Bertanggung Jawab <span class="text-rose-600">*</span>
                </label>
                <select name="responsible_party" id="responsible_party" class="glass-input text-xs w-full p-2.5 rounded-xl border border-slate-200">
                    <option value="SELLER">Penjual (Seller) - Berlakukan Kewajiban KEEP</option>
                    <option value="BUYER">Pembeli (Buyer) - Tanpa Kewajiban KEEP</option>
                    <option value="PLATFORM">Sistem / Platform</option>
                    <option value="UNDETERMINED">Tidak Dapat Ditentukan</option>
                </select>
            </div>

            <div class="space-y-1">
                <label for="decision_note" class="block text-xs font-bold text-slate-700">
                    Catatan Keputusan / Alasan Peninjauan
                </label>
                <textarea
                    name="decision_note"
                    id="decision_note"
                    rows="3"
                    class="glass-input text-xs w-full p-3 rounded-xl border border-slate-200"
                    placeholder="Tuliskan catatan investigasi atau alasan keputusan ini..."
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeReviewModal()" class="glass-btn-secondary text-xs py-2 px-4">
                    Tutup
                </button>
                <button type="submit" class="glass-btn-primary text-xs py-2 px-5 font-bold">
                    Simpan Keputusan Final
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function formatRupiah(num) {
        return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    }

    function openPayoutModal(txId, sellerName, gross, keepDeducted, netAmount, hasDestination, destSummary) {
        const modal = document.getElementById('modal-record-payout');
        const form = document.getElementById('form-record-payout');
        if (!modal || !form) return;

        form.action = `/admin/transactions/${txId}/settlement/payout`;
        document.getElementById('payout-modal-subtitle').innerText = `Transaksi #TX-${txId} • Penjual: ${sellerName}`;
        document.getElementById('payout-modal-destination').innerText = destSummary;
        document.getElementById('payout-modal-gross').innerText = formatRupiah(gross);
        document.getElementById('payout-modal-keep').innerText = '- ' + formatRupiah(keepDeducted);
        document.getElementById('payout-modal-net').innerText = formatRupiah(netAmount);

        togglePayoutOutcome('SUCCESS');
        modal.classList.remove('hidden');
    }

    function closePayoutModal() {
        document.getElementById('modal-record-payout')?.classList.add('hidden');
    }

    function togglePayoutOutcome(outcome) {
        const successFields = document.getElementById('payout-success-fields');
        const failureFields = document.getElementById('payout-failure-fields');
        const refInput = document.getElementById('payout-reference-no');

        if (outcome === 'SUCCESS') {
            successFields?.classList.remove('hidden');
            failureFields?.classList.add('hidden');
            if (refInput) refInput.required = true;
        } else {
            successFields?.classList.add('hidden');
            failureFields?.classList.remove('hidden');
            if (refInput) refInput.required = false;
        }
    }

    function openRefundModal(txId, buyerName, refundAmount) {
        const modal = document.getElementById('modal-record-refund');
        const form = document.getElementById('form-record-refund');
        if (!modal || !form) return;

        form.action = `/admin/transactions/${txId}/settlement/refund`;
        document.getElementById('refund-modal-subtitle').innerText = `Transaksi #TX-${txId} • Pembeli: ${buyerName}`;
        document.getElementById('refund-modal-amount').innerText = formatRupiah(refundAmount);
        document.getElementById('refund-destination-holder').value = buyerName;

        modal.classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('modal-record-refund')?.classList.add('hidden');
    }

    function openReviewModal(cancId, txId, requester, reason) {
        const modal = document.getElementById('modal-review-cancellation');
        const form = document.getElementById('form-review-cancellation');
        if (!modal || !form) return;

        form.action = `/admin/cancellations/${cancId}/review`;
        document.getElementById('modal-review-subtitle').innerText = `Pembatalan #CAN-${cancId} untuk Transaksi #TX-${txId}`;
        document.getElementById('modal-review-requester').innerText = requester;
        document.getElementById('modal-review-reason').innerText = `"${reason}"`;

        modal.classList.remove('hidden');
    }

    function closeReviewModal() {
        document.getElementById('modal-review-cancellation')?.classList.add('hidden');
    }

    function toggleResponsibleParty(isApproved) {
        const section = document.getElementById('section-responsible-party');
        const select = document.getElementById('responsible_party');
        if (!section || !select) return;

        if (isApproved) {
            section.classList.remove('hidden');
            select.required = true;
        } else {
            section.classList.add('hidden');
            select.required = false;
        }
    }
</script>
@endsection
