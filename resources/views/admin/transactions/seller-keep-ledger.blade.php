@extends('layouts.app')

@section('title', 'Catatan Kewajiban KEEP Penjual - MarketTrust')

@section('content')
<div class="py-8 sm:py-12" data-cy="seller-keep-ledger-page">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                    Catatan Kewajiban KEEP: {{ $seller->username }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-600">
                    Rincian kewajiban KEEP akibat pembatalan pasca-bayar atas tanggung jawab Penjual beserta riwayat pemotongan payout.
                </p>
            </div>
            <div class="text-right">
                <span class="block text-[10px] uppercase font-bold text-slate-400">Total Sisa OPEN</span>
                <span class="text-lg font-extrabold {{ $totalOpenRemaining > 0 ? 'text-rose-600' : 'text-emerald-600' }}" data-cy="ledger-total-open">
                    Rp {{ number_format($totalOpenRemaining, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="glass-card p-6 border border-white/80 shadow-md space-y-4">
            @if ($obligations->isEmpty())
                <p class="text-xs text-slate-500 text-center py-6">Tidak ada kewajiban KEEP untuk akun ini.</p>
            @else
                <div class="space-y-3">
                    @foreach ($obligations as $keep)
                        <div class="p-4 rounded-xl bg-white/80 border border-slate-200 space-y-2 text-xs" data-cy="ledger-keep-item-{{ $keep->keep_obligation_id }}">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">
                                    #KEEP-{{ $keep->keep_obligation_id }}
                                    @if ($keep->cancellation?->transaction_id)
                                        (Transaksi Asal #TX-{{ $keep->cancellation->transaction_id }})
                                    @endif
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $keep->status === 'SETTLED' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ $keep->status }}
                                </span>
                            </div>

                            <div class="flex justify-between text-slate-600">
                                <span>Aturan Perhitungan ({{ strtoupper($keep->payment_channel_snapshot) }}):</span>
                                <span class="font-semibold text-slate-800">{{ $keep->calculationRuleLabel() }}</span>
                            </div>

                            <div class="flex justify-between text-slate-600">
                                <span>Total Kewajiban:</span>
                                <span class="font-bold text-slate-900">Rp {{ number_format((float) $keep->amount, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between font-bold">
                                <span class="text-slate-700">Sisa Kewajiban (`remaining_amount`):</span>
                                <span class="{{ $keep->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    Rp {{ number_format((float) $keep->remaining_amount, 0, ',', '.') }}
                                </span>
                            </div>

                            @if ($keep->deductions->isNotEmpty())
                                <div class="pt-2 border-t border-slate-100 space-y-1">
                                    <span class="block text-[10px] uppercase font-bold text-slate-400">Riwayat Pemotongan pada Payout:</span>
                                    @foreach ($keep->deductions as $deduction)
                                        <div class="flex justify-between text-[11px] text-slate-600">
                                            <span>
                                                Settlement #STL-{{ $deduction->settlement_id }}
                                                @if ($deduction->settlement?->transaction_id)
                                                    (Transaksi Payout #TX-{{ $deduction->settlement->transaction_id }})
                                                @endif
                                            </span>
                                            <span class="font-semibold text-emerald-700">
                                                - Rp {{ number_format((float) $deduction->amount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
