<?php

namespace App\Services;

use App\Contracts\Services\CancellationServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\NotificationEventType;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\KeepObligation;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancellationService implements CancellationServiceInterface
{
    public function __construct(
        protected PaymentServiceInterface $paymentService,
        protected TransactionServiceInterface $transactionService,
        protected NotificationServiceInterface $notificationService
    ) {}

    /**
     * Ajukan atau proses pembatalan transaksi.
     */
    public function requestCancellation(Transaction $transaction, User $requester, ?string $reason = null): TransactionCancellation
    {
        $transaction->loadMissing(['agreement', 'payment']);
        $agreement = $transaction->agreement;

        if (! $agreement) {
            throw new InvalidArgumentException('Transaksi tidak memiliki kesepakatan yang valid.');
        }

        // Pastikan hanya buyer, seller bersangkutan, atau admin yang dapat mengajukan
        $isBuyer = (int) $agreement->buyer_id === (int) $requester->user_id;
        $isSeller = (int) $agreement->seller_id === (int) $requester->user_id;
        $isAdmin = $requester->isAdmin();

        if (! $isBuyer && ! $isSeller && ! $isAdmin) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk mengajukan pembatalan transaksi ini.');
        }

        $currentStatus = $transaction->status instanceof TransactionStatus ? $transaction->status->value : (string) $transaction->status;

        // Pembatalan hanya diperbolehkan sebelum DELIVERED
        if (in_array($currentStatus, ['DELIVERED', 'CONFIRMED', 'COMPLAINED', 'RESOLVED', 'CANCELLED', 'REFUNDED'], true)) {
            throw new InvalidArgumentException('Transaksi sudah berada pada tahap serah terima atau telah selesai/batal sehingga tidak dapat dibatalkan.');
        }

        // 1. Tahap SEBELUM PEMBAYARAN (WAITING_PAYMENT)
        if ($currentStatus === 'WAITING_PAYMENT') {
            // Lakukan rekonsiliasi gateway terlebih dahulu
            $reconcile = $this->paymentService->reconcileGatewayStatus($transaction);

            // Jika status gateway tidak pasti atau pembayaran sudah masuk, tolak pembatalan pra-bayar
            if (empty($reconcile['can_cancel_or_expire']) || $reconcile['can_cancel_or_expire'] !== true) {
                $msg = $reconcile['message'] ?? 'Status pembayaran terdeteksi aktif atau tidak dapat dibatalkan sebelum pembayaran.';
                throw new InvalidArgumentException('Pembatalan pra-bayar ditolak: '.$msg);
            }

            return DB::transaction(function () use ($transaction, $requester, $reason, $agreement) {
                $cancellation = TransactionCancellation::create([
                    'transaction_id' => $transaction->transaction_id,
                    'requested_by' => $requester->user_id,
                    'stage' => TransactionCancellation::STAGE_BEFORE_PAYMENT,
                    'reason' => $reason,
                    'status' => TransactionCancellation::STATUS_APPROVED,
                    'responsible_party' => null,
                    'reviewed_by' => null,
                    'decision_note' => 'Pembatalan otomatis pra-bayar setelah verifikasi gateway.',
                    'reviewed_at' => now(),
                ]);

                // Transisi status transaksi ke CANCELLED dan tutup akses bayar
                $this->transactionService->transitionStatus(
                    $transaction,
                    TransactionStatus::CANCELLED,
                    $requester,
                    'Transaksi dibatalkan sebelum pembayaran terverifikasi.'
                );

                $itemTitle = (string) ($agreement->item_title ?: 'Produk Digital');

                if ($agreement->buyer_id) {
                    $this->notificationService->notifyTransactionCancelled(
                        (int) $agreement->buyer_id,
                        (int) $transaction->transaction_id,
                        $itemTitle,
                        $reason
                    );
                }

                if ($agreement->seller_id) {
                    $this->notificationService->notifyTransactionCancelled(
                        (int) $agreement->seller_id,
                        (int) $transaction->transaction_id,
                        $itemTitle,
                        $reason
                    );
                }

                return $cancellation;
            });
        }

        // 2. Tahap SETELAH PEMBAYARAN SEBELUM SERAH TERIMA (PAID)
        if ($currentStatus === 'PAID') {
            $trimmedReason = trim((string) $reason);
            if ($trimmedReason === '') {
                throw new InvalidArgumentException('Alasan pembatalan wajib diisi untuk transaksi yang telah dibayar.');
            }

            if ($transaction->hasPendingCancellation()) {
                throw new InvalidArgumentException('Pengajuan pembatalan untuk transaksi ini sedang dalam proses peninjauan Admin.');
            }

            return TransactionCancellation::create([
                'transaction_id' => $transaction->transaction_id,
                'requested_by' => $requester->user_id,
                'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
                'reason' => $trimmedReason,
                'status' => TransactionCancellation::STATUS_REQUESTED,
                'responsible_party' => null,
                'reviewed_by' => null,
                'decision_note' => null,
                'reviewed_at' => null,
            ]);
        }

        throw new InvalidArgumentException("Transaksi dengan status {$currentStatus} tidak dapat diproses pembatalannya.");
    }

    /**
     * Tinjau pengajuan pembatalan oleh admin (setujui atau tolak).
     */
    public function reviewCancellation(
        TransactionCancellation $cancellation,
        User $admin,
        string $decision,
        ?string $responsibleParty = null,
        ?string $decisionNote = null
    ): TransactionCancellation {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya Administrator yang memiliki wewenang meninjau pembatalan transaksi.');
        }

        if (! $cancellation->isRequested()) {
            throw new InvalidArgumentException('Pengajuan pembatalan ini sudah pernah ditinjau sebelumnya.');
        }

        $transaction = $cancellation->transaction()->with(['agreement', 'payment'])->firstOrFail();
        $agreement = $transaction->agreement;
        $itemTitle = (string) ($agreement?->item_title ?: 'Produk Digital');

        $decisionUpper = strtoupper(trim($decision));
        if (! in_array($decisionUpper, [TransactionCancellation::STATUS_APPROVED, TransactionCancellation::STATUS_REJECTED], true)) {
            throw new InvalidArgumentException('Keputusan peninjauan harus berupa APPROVED atau REJECTED.');
        }

        return DB::transaction(function () use ($cancellation, $transaction, $agreement, $admin, $decisionUpper, $responsibleParty, $decisionNote, $itemTitle) {
            if ($decisionUpper === TransactionCancellation::STATUS_REJECTED) {
                $cancellation->update([
                    'status' => TransactionCancellation::STATUS_REJECTED,
                    'reviewed_by' => $admin->user_id,
                    'decision_note' => $decisionNote,
                    'reviewed_at' => now(),
                    'responsible_party' => null,
                ]);

                AuditLog::create([
                    'actor_id' => $admin->user_id,
                    'action' => 'TRANSACTION_CANCEL_REJECTED',
                    'entity_type' => 'transaction_cancellation',
                    'entity_id' => $cancellation->cancellation_id,
                    'old_value' => ['status' => TransactionCancellation::STATUS_REQUESTED],
                    'new_value' => [
                        'status' => TransactionCancellation::STATUS_REJECTED,
                        'decision_note' => $decisionNote,
                        'transaction_id' => $transaction->transaction_id,
                    ],
                    'created_at' => now(),
                ]);

                // Notifikasi ke pemohon bahwa pengajuan ditolak
                $this->notificationService->notify(
                    (int) $cancellation->requested_by,
                    (int) $transaction->transaction_id,
                    NotificationEventType::TRANSACTION_CANCELLED,
                    'Pengajuan pembatalan ditolak oleh Admin: '.($decisionNote ?: 'Pesanan tetap dilanjutkan.')
                );

                return $cancellation->fresh(['transaction', 'requestedBy', 'reviewedBy']);
            }

            // Keputusan APPROVED
            $validParties = [
                TransactionCancellation::PARTY_BUYER,
                TransactionCancellation::PARTY_SELLER,
                TransactionCancellation::PARTY_PLATFORM,
                TransactionCancellation::PARTY_UNDETERMINED,
            ];

            $partyUpper = strtoupper(trim((string) $responsibleParty));
            if (! in_array($partyUpper, $validParties, true)) {
                throw new InvalidArgumentException('Pihak yang bertanggung jawab harus ditentukan (BUYER, SELLER, PLATFORM, atau UNDETERMINED).');
            }

            $cancellation->update([
                'status' => TransactionCancellation::STATUS_APPROVED,
                'responsible_party' => $partyUpper,
                'reviewed_by' => $admin->user_id,
                'decision_note' => $decisionNote,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'TRANSACTION_CANCEL_APPROVED',
                'entity_type' => 'transaction_cancellation',
                'entity_id' => $cancellation->cancellation_id,
                'old_value' => ['status' => TransactionCancellation::STATUS_REQUESTED],
                'new_value' => [
                    'status' => TransactionCancellation::STATUS_APPROVED,
                    'responsible_party' => $partyUpper,
                    'decision_note' => $decisionNote,
                    'transaction_id' => $transaction->transaction_id,
                ],
                'created_at' => now(),
            ]);

            // Transaksi berstatus CANCELLED untuk menyiapkan proses pengembalian dana (refund)
            $this->transactionService->transitionStatus(
                $transaction,
                TransactionStatus::CANCELLED,
                $admin,
                'Pembatalan disetujui Admin. Menunggu proses pengembalian dana (refund).'
            );

            // Jika pihak bertanggung jawab adalah SELLER, hitung kewajiban KEEP
            if ($partyUpper === TransactionCancellation::PARTY_SELLER) {
                $this->syncKeepObligation($cancellation);

                if ($agreement?->seller_id) {
                    $this->notificationService->notifyTransactionKeep(
                        (int) $agreement->seller_id,
                        (int) $transaction->transaction_id,
                        $itemTitle
                    );
                }
            }

            // Notifikasi pembatalan ke buyer dan seller
            if ($agreement?->buyer_id) {
                $this->notificationService->notifyTransactionCancelled(
                    (int) $agreement->buyer_id,
                    (int) $transaction->transaction_id,
                    $itemTitle,
                    $decisionNote ?: $cancellation->reason
                );
            }

            if ($agreement?->seller_id) {
                $this->notificationService->notifyTransactionCancelled(
                    (int) $agreement->seller_id,
                    (int) $transaction->transaction_id,
                    $itemTitle,
                    $decisionNote ?: $cancellation->reason
                );
            }

            return $cancellation->fresh(['transaction', 'requestedBy', 'reviewedBy', 'keepObligation']);
        });
    }

    /**
     * Hitung atau sinkronisasikan kewajiban KEEP jika pembatalan disetujui dengan seller bertanggung jawab.
     */
    public function syncKeepObligation(TransactionCancellation $cancellation): ?KeepObligation
    {
        if ($cancellation->status !== TransactionCancellation::STATUS_APPROVED) {
            return null;
        }

        if ($cancellation->responsible_party !== TransactionCancellation::PARTY_SELLER) {
            return null;
        }

        // Idempotensi: jika kewajiban KEEP sudah pernah dicatat, kembalikan data existing
        $existing = KeepObligation::where('cancellation_id', $cancellation->cancellation_id)->first();
        if ($existing) {
            return $existing;
        }

        $transaction = $cancellation->transaction()->with(['agreement', 'payment'])->first();
        if (! $transaction || ! $transaction->agreement) {
            return null;
        }

        $sellerId = $transaction->agreement->seller_id;
        $payment = $transaction->payment;
        $channel = strtolower((string) ($payment?->payment_channel ?? 'bank_transfer'));

        // Deteksi apakah Bank / Virtual Account (VA)
        $isBankVa = str_contains($channel, 'va')
            || str_contains($channel, 'bank')
            || str_contains($channel, 'transfer')
            || str_contains($channel, 'echannel');

        if ($isBankVa) {
            $rule = KeepObligation::RULE_BANK_VA_FLAT;
            $feeBasis = 4000.00;
            $multiplier = 1;
            $amount = 4000.00;
        } else {
            // Channel non Bank/VA (e-wallet, QRIS, dsb.): 3x gateway fee
            // Jika fee gateway belum terverifikasi, tunda pencatatan KEEP (jangan asumsikan 0)
            if (! $payment || ! $payment->hasVerifiedGatewayFee() || $payment->gateway_fee_amount === null) {
                return null;
            }

            $rule = KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE;
            $feeBasis = (float) $payment->gateway_fee_amount;
            $multiplier = 3;
            $amount = round($feeBasis * 3, 2);
        }

        return KeepObligation::create([
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $sellerId,
            'payment_channel_snapshot' => $payment?->payment_channel ?? 'BANK_VA',
            'calculation_rule' => $rule,
            'fee_basis_amount' => $feeBasis,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'remaining_amount' => $amount,
            'status' => KeepObligation::STATUS_OPEN,
        ]);
    }

    /**
     * Ambil seluruh kewajiban KEEP yang masih berstatus OPEN untuk seller tertentu.
     *
     * @return Collection<int, KeepObligation>
     */
    public function getRemainingKeepObligations(User|int $seller): Collection
    {
        $sellerId = $seller instanceof User ? $seller->user_id : (int) $seller;

        return KeepObligation::query()
            ->where('seller_id', $sellerId)
            ->open()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Hitung total nominal kewajiban KEEP yang masih tertunggak untuk seller.
     */
    public function getTotalRemainingKeep(User|int $seller): float
    {
        $sellerId = $seller instanceof User ? $seller->user_id : (int) $seller;

        return (float) KeepObligation::query()
            ->where('seller_id', $sellerId)
            ->open()
            ->sum('remaining_amount');
    }

    /**
     * Ambil keputusan pembatalan yang disetujui untuk persiapan refund.
     */
    public function getApprovedRefundDecision(Transaction|int $transaction): ?TransactionCancellation
    {
        $transactionId = $transaction instanceof Transaction ? $transaction->transaction_id : (int) $transaction;

        return TransactionCancellation::query()
            ->where('transaction_id', $transactionId)
            ->where('status', TransactionCancellation::STATUS_APPROVED)
            ->latest('cancellation_id')
            ->first();
    }
}
