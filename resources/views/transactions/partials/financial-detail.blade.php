@php
    $sourceType = $transaction->source_type instanceof \App\Enums\TransactionSourceType
        ? $transaction->source_type->value
        : (string) $transaction->source_type;

    $feeType = $agreement->fee_type instanceof \App\Enums\FeeType
        ? $agreement->fee_type->value
        : strtoupper((string) ($agreement->fee_type ?? 'EXCLUDE'));

    $isMarketplace = $sourceType === 'MARKETPLACE';
    $isIncludeFee = ! $isMarketplace && $feeType === 'INCLUDE';

    $canViewSellerBreakdown = ($isSeller ?? false) || ($isAdmin ?? false);

    $payment = $transaction->payment;
    $hasVerifiedGatewayFee = $payment !== null && $payment->hasVerifiedGatewayFee();

    $cancellations = $transaction->relationLoaded('cancellations')
        ? $transaction->cancellations
        : $transaction->cancellations()->with('keepObligation.deductions.settlement')->get();

    $cancellationWithKeep = $cancellations
        ->sortByDesc('cancellation_id')
        ->first(fn ($c) => $c->keepObligation !== null);

    $transactionKeepObligation = $cancellationWithKeep?->keepObligation;

    $approvedSellerCancellationWithoutKeep = $cancellations
        ->sortByDesc('cancellation_id')
        ->first(fn ($c) => $c->status === 'APPROVED'
            && $c->stage === 'AFTER_PAYMENT_BEFORE_DELIVERY'
            && $c->responsible_party === 'SELLER'
            && $c->keepObligation === null);

    $settlement = $transaction->settlement;
    $latestAttempt = $settlement?->relationLoaded('attempts')
        ? $settlement->attempts->first()
        : null;

    $otherSellerKeepObligations = $canViewSellerBreakdown
        ? ($sellerKeepObligations ?? collect())->filter(
            fn ($ko) => (int) $ko->keep_obligation_id !== (int) ($transactionKeepObligation?->keep_obligation_id ?? 0)
        )->values()
        : collect();
@endphp

<div class="space-y-2" data-cy="financial-detail-section">
    <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">
        {{ $isMarketplace ? 'Rincian Pembayaran' : 'Nilai Finansial Kesepakatan' }}
    </span>

    @if (! $canViewSellerBreakdown)
        {{-- Tampilan Ringkas Buyer: hanya Harga Produk & Total Pembayaran (buyer_total) --}}
        <div class="bg-white/70 rounded-xl p-4 border border-slate-200/80 space-y-2 text-xs sm:text-sm" data-cy="financial-buyer-card">
            <div class="flex justify-between text-slate-600">
                <span>Harga Produk:</span>
                <span class="font-semibold text-slate-900" data-cy="buyer-product-price">
                    Rp {{ number_format((float) $agreement->buyer_total, 0, ',', '.') }}
                </span>
            </div>

            <div class="border-t border-slate-200 pt-2 flex justify-between font-bold">
                <span class="text-slate-900">Total Pembayaran:</span>
                <span class="text-indigo-700" data-cy="snapshot-buyer-total">
                    Rp {{ number_format((float) $agreement->buyer_total, 0, ',', '.') }}
                </span>
            </div>

            @if ($settlement && strtoupper((string) $settlement->settlement_type) === 'REFUND' && $settlement->status === 'COMPLETED')
                <div class="border-t border-slate-200 pt-2 flex justify-between font-bold text-emerald-700" data-cy="buyer-refund-completed">
                    <span>Refund Diterima ({{ $settlement->reference_no }}):</span>
                    <span>Rp {{ number_format((float) $settlement->amount, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>
    @else
        {{-- Tampilan Ringkas Seller & Admin: 1 kartu bersih untuk fee platform, gateway, KEEP & Settlement --}}
        <div class="bg-white/70 rounded-xl p-4 border border-slate-200/80 space-y-2.5 text-xs sm:text-sm" data-cy="financial-platform-card">
            <div class="flex justify-between items-center text-slate-600">
                <span>Harga Dasar Produk:</span>
                <span class="font-semibold text-slate-900" data-cy="snapshot-base-price">
                    Rp {{ number_format((float) $agreement->base_price, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex justify-between items-center text-slate-600">
                <span class="inline-flex flex-wrap items-center gap-1.5">
                    <span>Biaya Layanan Platform:</span>
                    <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $isIncludeFee ? 'bg-amber-100 text-amber-800' : 'bg-indigo-50 text-indigo-700' }}"
                        data-cy="snapshot-fee-mode"
                    >
                        {{ $isMarketplace ? 'EXCLUDE (Tetap)' : ($isIncludeFee ? 'INCLUDE' : 'EXCLUDE') }}
                    </span>
                </span>
                <span class="font-semibold {{ $isIncludeFee ? 'text-amber-700' : 'text-indigo-600' }}" data-cy="snapshot-fee-amount">
                    {{ $isIncludeFee ? '- ' : '+ ' }}Rp {{ number_format((float) $agreement->fee_amount, 0, ',', '.') }}
                </span>
            </div>

            <div class="border-t border-slate-200 pt-2 flex justify-between items-center font-bold">
                <span class="text-slate-900">Total Pembayaran Pembeli:</span>
                <span class="text-indigo-700" data-cy="snapshot-buyer-total">
                    Rp {{ number_format((float) $agreement->buyer_total, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex justify-between items-center font-bold">
                <span class="text-slate-700">Pencairan Hak Bersih Penjual:</span>
                <span class="text-emerald-600" data-cy="snapshot-seller-net">
                    Rp {{ number_format((float) $agreement->seller_net, 0, ',', '.') }}
                </span>
            </div>

            {{-- Biaya Gateway Aktual & Kewajiban KEEP / Settlement --}}
            <div class="border-t border-slate-200/80 pt-2.5 space-y-2" data-cy="financial-gateway-section">
                <div class="flex justify-between items-center text-slate-600">
                    <span>
                        Biaya Gateway{{ $payment && ! empty($payment->payment_channel) ? ' (' . strtoupper($payment->payment_channel) . ')' : '' }}:
                    </span>
                    @if ($hasVerifiedGatewayFee)
                        <span class="font-semibold text-slate-800" data-cy="gateway-fee-amount">
                            Rp {{ number_format((float) $payment->gateway_fee_amount, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="text-amber-700 font-medium italic" data-cy="gateway-fee-unverified">
                            Menunggu verifikasi
                        </span>
                    @endif
                </div>

                <div class="space-y-1.5" data-cy="financial-keep-section">
                    @if ($transactionKeepObligation)
                        @php
                            $keepIsSettled = $transactionKeepObligation->status === 'SETTLED' || (float) $transactionKeepObligation->remaining_amount <= 0;
                            $keepIsPartial = ! $keepIsSettled && $transactionKeepObligation->isPartiallyDeducted();
                        @endphp
                        <div class="flex flex-wrap justify-between items-center gap-2 text-slate-600" data-cy="transaction-keep-obligation-card">
                            <span class="inline-flex items-center gap-1.5">
                                <span>Kewajiban KEEP ({{ $transactionKeepObligation->calculationRuleLabel() }}):</span>
                                <span
                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $keepIsSettled ? 'bg-emerald-100 text-emerald-800' : ($keepIsPartial ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}"
                                    data-cy="keep-obligation-status"
                                >
                                    {{ $keepIsSettled ? 'SETTLED' : ($keepIsPartial ? 'OPEN (Parsial)' : 'OPEN') }}
                                </span>
                            </span>
                            <span class="font-semibold text-rose-700">
                                <span data-cy="keep-total-amount">Rp {{ number_format((float) $transactionKeepObligation->amount, 0, ',', '.') }}</span>
                                <span class="text-slate-400 font-normal">|</span>
                                <span class="text-slate-600 font-normal">Sisa:</span>
                                <span data-cy="keep-remaining-amount">Rp {{ number_format((float) $transactionKeepObligation->remaining_amount, 0, ',', '.') }}</span>
                            </span>
                        </div>
                    @elseif ($approvedSellerCancellationWithoutKeep)
                        <div class="flex justify-between items-center text-slate-600" data-cy="keep-awaiting-gateway-fee">
                            <span>Kewajiban KEEP:</span>
                            <span class="text-amber-700 font-medium italic" data-cy="keep-obligation-status">
                                Menunggu verifikasi biaya gateway
                            </span>
                        </div>
                    @elseif (! $settlement && $otherSellerKeepObligations->isEmpty())
                        <div class="flex justify-between items-center text-slate-500" data-cy="keep-status-none">
                            <span>Potongan / Kewajiban KEEP:</span>
                            <span class="font-medium text-slate-600" data-cy="keep-obligation-status">Tidak ada</span>
                        </div>
                    @endif

                    @if ($settlement)
                        @php
                            $settlementType = strtoupper((string) $settlement->settlement_type);
                            $grossAmount = $settlement->gross_amount !== null
                                ? (float) $settlement->gross_amount
                                : (float) $agreement->seller_net;
                            $keepDeductedAmount = (float) ($settlement->keep_deducted_amount ?? 0);
                            $finalSettlementAmount = (float) $settlement->amount;
                        @endphp
                        <div class="pt-1.5 border-t border-slate-100 space-y-1.5" data-cy="financial-settlement-card">
                            @if ($settlementType === 'PAYOUT')
                                <div class="flex justify-between items-center text-slate-600">
                                    <span>Hak Kotor Settlement:</span>
                                    <span class="font-semibold text-slate-900" data-cy="settlement-gross-amount">
                                        Rp {{ number_format($grossAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-slate-600">
                                    <span>Potongan KEEP pada Payout:</span>
                                    <span class="font-semibold {{ $keepDeductedAmount > 0 ? 'text-rose-600' : 'text-slate-700' }}" data-cy="settlement-keep-deducted">
                                        - Rp {{ number_format($keepDeductedAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center font-bold text-slate-900">
                                    <span>Payout Bersih Diterima ({{ $settlement->status }}):</span>
                                    <span class="text-emerald-700" data-cy="settlement-net-amount">
                                        Rp {{ number_format($finalSettlementAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <div class="flex justify-between items-center font-bold text-slate-900">
                                    <span>Nominal Refund Pembeli ({{ $settlement->status }}):</span>
                                    <span class="text-indigo-700" data-cy="settlement-refund-amount">
                                        Rp {{ number_format($finalSettlementAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif

                            @if ($latestAttempt && in_array($latestAttempt->status, ['FAILED', 'UNKNOWN'], true))
                                <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-xs space-y-1" data-cy="settlement-attempt-warning">
                                    <div class="font-bold text-rose-800">
                                        Percobaan Payout #{{ $latestAttempt->attempt_no }} ({{ $latestAttempt->status }}): {{ $latestAttempt->failure_reason }}
                                    </div>
                                    <div class="text-[11px] text-slate-600">
                                        Tindak lanjut: {{ $latestAttempt->follow_up_action }}
                                    </div>
                                    @if ($latestAttempt->requires_destination_update && ($isSeller ?? false))
                                        <a href="{{ route('seller.payout-accounts.index') }}" class="inline-block font-bold text-indigo-600 hover:underline pt-0.5" data-cy="link-fix-payout-account">
                                            Perbarui Rekening Payout Anda &rarr;
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($otherSellerKeepObligations->isNotEmpty())
                        @php
                            $totalOpenKeepOther = (float) $otherSellerKeepObligations
                                ->where('status', 'OPEN')
                                ->sum('remaining_amount');
                        @endphp
                        <div class="flex justify-between items-center text-slate-600 pt-1 border-t border-slate-100" data-cy="seller-keep-ledger-summary">
                            <span>Sisa KEEP Aktif (Transaksi Lain):</span>
                            <span class="font-semibold {{ $totalOpenKeepOther > 0 ? 'text-rose-700' : 'text-emerald-700' }}" data-cy="seller-other-open-keep-total">
                                Rp {{ number_format($totalOpenKeepOther, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
