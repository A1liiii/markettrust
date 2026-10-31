@php
    $txStatus = $transaction->status instanceof \App\Enums\TransactionStatus ? $transaction->status->value : (string) $transaction->status;
    $canRequestCancel = in_array($txStatus, ['WAITING_PAYMENT', 'PAID'], true) && !($hasPendingCancellation ?? false);
    $isWaitingPayment = $txStatus === 'WAITING_PAYMENT';
    $isPaid = $txStatus === 'PAID';
@endphp

<!-- Banner Status Pengajuan Pembatalan Sedang Berjalan -->
@if ($hasPendingCancellation ?? false)
    <div class="glass-card p-5 border-amber-200/80 bg-gradient-to-r from-amber-500/10 via-amber-50/50 to-white/70 shadow-sm" data-cy="pending-cancellation-banner">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                        Pengajuan Pembatalan Sedang Ditinjau Admin
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-800">
                        Dalam Antrean Tinjauan
                    </span>
                </div>
                <p class="text-xs text-slate-700">
                    Pengajuan pembatalan telah masuk ke antrean investigasi Administrator. Selama proses ini berlangsung, transaksi tetap tertahan dan proses refund akan ditetapkan setelah keputusan final keluar.
                </p>
                @if ($latestCancellation?->reason)
                    <div class="mt-2 p-2.5 rounded-lg bg-white/80 border border-amber-200 text-xs text-slate-800">
                        <strong class="text-amber-950 font-semibold">Alasan yang Diajukan:</strong>
                        <p class="mt-0.5 italic text-slate-600">"{{ $latestCancellation->reason }}"</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

<!-- Banner Ringkasan Transaksi Dibatalkan -->
@if ($txStatus === 'CANCELLED' && $latestCancellation)
    <div class="glass-card p-5 border-rose-200/80 bg-gradient-to-r from-rose-500/10 via-rose-50/40 to-white/80 shadow-sm space-y-4" data-cy="cancelled-transaction-banner">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold shadow-xs shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-rose-900 uppercase tracking-wider">
                        Transaksi Resmi Dibatalkan
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                        {{ $latestCancellation->isBeforePayment() ? 'Pra-Pembayaran' : 'Pasca-Pembayaran' }}
                    </span>
                </div>
                <p class="text-xs text-slate-600">
                    @if ($latestCancellation->isBeforePayment())
                        Transaksi dibatalkan sebelum pembayaran escrow terverifikasi. Tidak ada penalti reputasi dan akses pembayaran telah ditutup.
                    @else
                        Pembatalan pasca-pembayaran telah disetujui Admin. Dana transaksi sedang dipersiapkan untuk pengembalian (refund) sesuai kebijakan escrow.
                    @endif
                </p>
            </div>
        </div>

        <!-- Rincian Putusan Admin & Tanggung Jawab -->
        @if ($latestCancellation->isAfterPaymentBeforeDelivery())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-rose-100 text-xs">
                <div class="p-3 rounded-lg bg-white/80 border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Pihak Bertanggung Jawab</span>
                    <span class="font-bold text-slate-800">
                        @switch($latestCancellation->responsible_party)
                            @case('BUYER')
                                Pembeli (Buyer)
                                @break
                            @case('SELLER')
                                <span class="text-rose-700">Penjual (Seller)</span>
                                @break
                            @case('PLATFORM')
                                Sistem / Platform
                                @break
                            @default
                                Tidak Ditentukan (Undetermined)
                        @endswitch
                    </span>
                </div>
                <div class="p-3 rounded-lg bg-white/80 border border-slate-200/80 space-y-1">
                    <span class="block text-[10px] uppercase font-bold text-slate-400">Ditinjau Pada</span>
                    <span class="font-bold text-slate-800">
                        {{ $latestCancellation->reviewed_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                    </span>
                </div>
            </div>

            @if ($latestCancellation->decision_note)
                <div class="p-3 rounded-lg bg-white/80 border border-rose-200 text-xs text-slate-800">
                    <span class="block text-[10px] uppercase font-bold text-rose-800">Catatan Administrator</span>
                    <p class="mt-0.5">{{ $latestCancellation->decision_note }}</p>
                </div>
            @endif

            <!-- Rincian Kewajiban KEEP jika Seller Bertanggung Jawab -->
            @if ($latestCancellation->keepObligation)
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-300/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-amber-800 font-bold text-xs uppercase tracking-wide">
                                ⚖️ Kewajiban KEEP Ditetapkan (Biaya Pemeliharaan Escrow)
                            </span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $latestCancellation->keepObligation->status === 'OPEN' ? 'bg-amber-200 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">
                            Status: {{ $latestCancellation->keepObligation->status }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-700">
                        Karena pembatalan pasca-bayar ditetapkan akibat tanggung jawab Penjual, maka diterbitkan kewajiban KEEP sebesar
                        <strong class="text-slate-900 font-black">Rp {{ number_format($latestCancellation->keepObligation->amount, 0, ',', '.') }}</strong>
                        (Aturan: <em>{{ $latestCancellation->keepObligation->calculation_rule === 'BANK_VA_FLAT' ? 'Flat Rp 4.000 (Bank/VA)' : '3x Fee Payment Gateway' }}</em>).
                        Kewajiban ini akan dipotongkan pada pencairan dana transaksi berikutnya.
                    </p>
                </div>
            @endif
        @endif
    </div>
@endif

<!-- Tombol Pemicu Modal Pembatalan (Jika Memenuhi Syarat) -->
@if ($canRequestCancel && ($isBuyer || $isSeller))
    <div class="flex justify-end pt-2">
        <button
            type="button"
            onclick="openCancelModal()"
            class="px-3.5 py-2 rounded-xl border border-rose-300 text-rose-700 bg-rose-50/70 hover:bg-rose-100 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs"
            data-cy="btn-open-cancel-modal"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span>{{ $isWaitingPayment ? 'Batalkan Pesanan Ini' : 'Ajukan Pembatalan Transaksi' }}</span>
        </button>
    </div>

    <!-- Modal Form Pembatalan -->
    <div id="modal-cancel-transaction" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="glass-card w-full max-w-lg p-6 bg-white/95 border border-white/80 shadow-2xl rounded-2xl space-y-5 animate-in fade-in duration-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">
                            {{ $isWaitingPayment ? 'Konfirmasi Pembatalan Pesanan' : 'Form Pengajuan Pembatalan Transaksi' }}
                        </h3>
                        <p class="text-xs text-slate-500">Transaksi #{{ $transaction->transaction_id }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeCancelModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Petunjuk & Regulasi SKPL -->
            @if ($isWaitingPayment)
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ketentuan Pembatalan Pra-Bayar:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-600 pl-1 text-[11px]">
                        <li>Sistem akan melakukan verifikasi ke payment gateway terlebih dahulu.</li>
                        <li>Akses pembayaran akan langsung ditutup dan status transaksi diubah menjadi <strong>CANCELLED</strong>.</li>
                        <li>Pembatalan sebelum pembayaran <strong>tidak</strong> mempengaruhi skor reputasi Anda.</li>
                    </ul>
                </div>
            @else
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200/80 text-xs text-rose-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Ketentuan Pembatalan Pasca-Bayar (Escrow):
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-600 pl-1 text-[11px]">
                        <li>Dana escrow pembeli telah tersimpan di rekening penampung.</li>
                        <li>Pengajuan ini akan masuk ke antrean peninjauan oleh <strong>Administrator</strong>.</li>
                        <li>Jika pembatalan disebabkan oleh <strong>Penjual</strong>, Penjual akan dikenakan kewajiban <strong>KEEP</strong> (Flat Rp 4.000 untuk Bank/VA atau 3x gateway fee) yang akan dipotong pada payout mendatang.</li>
                    </ul>
                </div>
            @endif

            <form action="{{ route('transactions.cancel', $transaction) }}" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-1">
                    <label for="cancel-reason" class="block text-xs font-bold text-slate-700">
                        Alasan Pembatalan {{ $isPaid ? '(Wajib Diisi)' : '(Opsional)' }}
                    </label>
                    <textarea
                        name="reason"
                        id="cancel-reason"
                        rows="3"
                        class="glass-input text-xs w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                        placeholder="{{ $isPaid ? 'Jelaskan alasan mengapa transaksi ini perlu dibatalkan...' : 'Tuliskan alasan pembatalan jika ada...' }}"
                        {{ $isPaid ? 'required minlength=5' : '' }}
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        onclick="closeCancelModal()"
                        class="glass-btn-secondary text-xs py-2 px-4"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/20 transition active:scale-95"
                    >
                        {{ $isWaitingPayment ? 'Ya, Batalkan Pesanan' : 'Kirim Pengajuan Pembatalan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCancelModal() {
            document.getElementById('modal-cancel-transaction')?.classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('modal-cancel-transaction')?.classList.add('hidden');
        }
    </script>
@endif

