<?php

namespace App\Contracts\Services;

use App\Models\KeepObligation;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Support\Collection;

interface CancellationServiceInterface
{
    /**
     * Ajukan atau proses pembatalan transaksi (sebelum pembayaran atau setelah pembayaran sebelum serah terima).
     */
    public function requestCancellation(Transaction $transaction, User $requester, ?string $reason = null): TransactionCancellation;

    /**
     * Tinjau pengajuan pembatalan oleh admin (setujui atau tolak).
     */
    public function reviewCancellation(
        TransactionCancellation $cancellation,
        User $admin,
        string $decision,
        ?string $responsibleParty = null,
        ?string $decisionNote = null
    ): TransactionCancellation;

    /**
     * Hitung atau sinkronisasikan kewajiban KEEP jika pembatalan disetujui dengan seller bertanggung jawab.
     */
    public function syncKeepObligation(TransactionCancellation $cancellation): ?KeepObligation;

    /**
     * Ambil seluruh kewajiban KEEP yang masih berstatus OPEN untuk seller tertentu.
     *
     * @return Collection<int, KeepObligation>
     */
    public function getRemainingKeepObligations(User|int $seller): Collection;

    /**
     * Hitung total nominal kewajiban KEEP yang masih tertunggak untuk seller.
     */
    public function getTotalRemainingKeep(User|int $seller): float;

    /**
     * Ambil keputusan pembatalan yang disetujui untuk persiapan refund.
     */
    public function getApprovedRefundDecision(Transaction|int $transaction): ?TransactionCancellation;
}

