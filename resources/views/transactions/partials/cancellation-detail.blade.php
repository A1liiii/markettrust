@php
    $cancellation = $transaction->cancellations->sortByDesc('cancellation_id')->first();
    $txStatusStr = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->value : (string) $transaction->status;
    $isCancelled = $txStatusStr === 'CANCELLED';
    $canViewKeepDetails = ($isSeller ?? false) || ($isAdmin ?? false);
@endphp

@if ($cancellation || $isCancelled)
    @php
        $stageLabel = match($cancellation?->stage) {
            'BEFORE_PAYMENT' => 'Sebelum Pembayaran (Pra-Bayar)',
            'AFTER_PAYMENT_BEFORE_DELIVERY' => 'Setelah Pembayaran (Pasca-Bayar)',
            default => 'Tahap Transaksi',
        };

        $partyLabel = match($cancellation?->responsible_party) {
            'SELLER' => 'Penjual (Seller)',
            'BUYER' => 'Pembeli (Buyer)',
            'PLATFORM' => 'Kendala Sistem / Platform',
            'UNDETERMINED' => 'Tidak Dapat Ditentukan (Mediasi Bersama)',
            default => 'Belum Ditentukan',
        };

        $partyBadgeColor = match($cancellation?->responsible_party) {
            'SELLER' => 'bg-rose-100 text-rose-900 border-rose-300',
            'BUYER' => 'bg-amber-100 text-amber-900 border-amber-300',
            'PLATFORM' => 'bg-purple-100 text-purple-900 border-purple-300',
            default => 'bg-slate-100 text-slate-800 border-slate-300',
        };

        $keepObligation = $cancellation?->keepObligation;
    @endphp

    <div class="glass-card p-5 sm:p-6 border-rose-200/80 bg-gradient-to-r from-rose-500/5 via-slate-50/40 to-white/90 shadow-sm space-y-4" data-cy="cancellation-detail-card">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-rose-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Catatan Pembatalan &amp; Pertanggungjawaban</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider {{ $isCancelled ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-amber-100 text-amber-800 border-amber-200' }}" data-cy="cancellation-status-badge">
                            {{ $isCancelled ? 'Transaksi Dibatalkan' : ($cancellation?->status ?? 'Pengajuan') }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        {{ $stageLabel }} &bull; Diajukan oleh @<span>{{ $cancellation?->requestedBy?->username ?? 'Pengguna' }}</span>
                    </p>
                </div>
            </div>

            @if ($cancellation?->responsible_party)
                <div class="text-left sm:text-right shrink-0 p-2.5 rounded-xl bg-white/80 border border-slate-200">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Bertanggung Jawab</span>
                    <span class="px-2 py-0.5 rounded text-xs font-black border uppercase tracking-wider inline-block mt-0.5 {{ $partyBadgeColor }}" data-cy="responsible-party-badge">
                        {{ $partyLabel }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Alasan & Keputusan Peninjauan Admin -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3.5 rounded-xl bg-white/80 border border-slate-200/80 space-y-1">
                <span class="block text-[10px] uppercase font-bold text-slate-400">Alasan Pembatalan:</span>
                <p class="text-xs text-slate-800 leading-relaxed font-medium" data-cy="cancellation-reason">
                    {{ $cancellation?->reason ?: 'Tidak ada alasan khusus yang dicantumkan.' }}
                </p>
            </div>

            <div class="p-3.5 rounded-xl bg-white/80 border border-slate-200/80 space-y-1">
                <span class="block text-[10px] uppercase font-bold text-slate-400">Keputusan & Catatan Admin:</span>
                <p class="text-xs text-slate-800 leading-relaxed font-medium" data-cy="cancellation-decision-note">
                    {{ $cancellation?->decision_note ?: 'Pembatalan disetujui sesuai regulasi operasional transaksi MarketTrust.' }}
                </p>
                @if ($cancellation?->reviewed_at)
                    <div class="text-[10px] text-slate-400 mt-1">
                        Ditinjau oleh @<span>{{ $cancellation->reviewedBy?->username ?? 'Admin' }}</span> pada {{ $cancellation->reviewed_at->translatedFormat('d F Y, H:i') }} WIB
                    </div>
                @endif
            </div>
        </div>

        <!-- Rincian Kewajiban KEEP Penjual (Hanya Tampil untuk Penjual & Admin) -->
        @if ($canViewKeepDetails && $keepObligation)
            <div class="p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-amber-50/40 to-white/90 border border-amber-200/80 space-y-3" data-cy="seller-keep-obligation-card">
                <div class="flex items-center justify-between pb-2 border-b border-amber-200/60">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-amber-950">
                            Beban Kewajiban KEEP Penjual (Ganti Rugi Pembatalan Sepihak)
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-black border uppercase tracking-wider {{ $keepObligation->isOpen() ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-emerald-100 text-emerald-900 border-emerald-300' }}" data-cy="keep-status-badge">
                            {{ $keepObligation->isOpen() ? 'Belum Lunas (OPEN)' : 'Lunas (SETTLED)' }}
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-500 italic">
                        (Privat: Khusus Penjual & Admin)
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Aturan Perhitungan</span>
                        <span class="font-bold text-slate-800">
                            {{ $keepObligation->calculationRuleLabel() }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Nominal Asal Beban</span>
                        <span class="font-extrabold text-slate-900" data-cy="keep-total-amount">
                            Rp {{ number_format((float) $keepObligation->amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Telah Dipotong</span>
                        <span class="font-extrabold text-emerald-700" data-cy="keep-deducted-amount">
                            Rp {{ number_format((float) $keepObligation->deductedAmount(), 0, ',', '.') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Sisa Kewajiban KEEP</span>
                        <span class="font-black text-rose-700" data-cy="keep-remaining-amount">
                            Rp {{ number_format((float) $keepObligation->remaining_amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if ($keepObligation->deductions && $keepObligation->deductions->isNotEmpty())
                    <div class="pt-2 border-t border-amber-200/50 space-y-1">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Riwayat Pemotongan pada Payout Lain:</span>
                        <div class="space-y-1">
                            @foreach ($keepObligation->deductions as $deduction)
                                <div class="text-[11px] text-slate-700 flex items-center justify-between bg-white/70 p-2 rounded-lg border border-amber-100">
                                    <span>Pemotongan dari Settlement Payout #{{ $deduction->settlement_id }}</span>
                                    <span class="font-bold text-rose-600">- Rp {{ number_format((float) $deduction->deducted_amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
