<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\NotificationEventType;
use App\Enums\TransactionStatus;
use App\Models\EvidenceFile;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        protected NotificationServiceInterface $notificationService,
        protected PayoutDestinationServiceInterface $payoutDestinationService
    ) {}

    /**
     * Perform atomic status transition and record transaction_status_histories.
     */
    public function transitionStatus(
        Transaction $transaction,
        TransactionStatus|string $toStatus,
        ?User $actor = null,
        ?string $note = null
    ): bool {
        $toStatusStr = $toStatus instanceof TransactionStatus ? $toStatus->value : $toStatus;
        $fromStatusStr = $transaction->status instanceof TransactionStatus ? $transaction->status->value : $transaction->status;

        if ($fromStatusStr === $toStatusStr) {
            return false;
        }

        return DB::transaction(function () use ($transaction, $fromStatusStr, $toStatusStr, $actor, $note) {
            $now = now();
            $transaction->update(['status' => $toStatusStr]);

            TransactionStatusHistory::create([
                'transaction_id' => $transaction->transaction_id,
                'from_status' => $fromStatusStr,
                'to_status' => $toStatusStr,
                'changed_by' => $actor?->user_id,
                'note' => $note ?? 'Perubahan status transaksi ke '.$toStatusStr,
                'changed_at' => $now,
            ]);

            return true;
        });
    }

    /**
     * Submit official delivery results and evidence by Seller.
     */
    public function deliverOrder(
        Transaction $transaction,
        User $seller,
        string $deliveryNote,
        ?string $resultLink = null,
        ?UploadedFile $evidenceFile = null
    ): Transaction {
        if ($transaction->agreement->seller_id !== $seller->user_id) {
            throw new AuthorizationException('Hanya Penjual terkait yang berhak menyerahkan hasil transaksi.');
        }

        return DB::transaction(function () use ($transaction, $seller, $deliveryNote, $resultLink, $evidenceFile) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $statusStr = $lockedTx->status instanceof TransactionStatus
                ? $lockedTx->status->value
                : (string) $lockedTx->status;

            // Idempotency: jika sudah berstatus DELIVERED dan delivered_at sudah ada
            if ($statusStr === TransactionStatus::DELIVERED->value && $lockedTx->delivered_at !== null) {
                return $lockedTx;
            }

            if ($statusStr !== TransactionStatus::PAID->value) {
                throw new InvalidArgumentException("Transaksi tidak dapat diserahkan karena status saat ini adalah [{$statusStr}].");
            }

            $now = now();
            $confirmationDue = $now->copy()->addHour();

            // Simpan bukti penyerahan resmi jika dilampirkan
            if ($evidenceFile) {
                $path = $evidenceFile->store('evidence/delivery', 'public');
                EvidenceFile::create([
                    'uploaded_by' => $seller->user_id,
                    'delivery_transaction_id' => $lockedTx->transaction_id,
                    'file_name' => $evidenceFile->getClientOriginalName(),
                    'file_url' => $path,
                    'mime_type' => $evidenceFile->getClientMimeType() ?: ($evidenceFile->getMimeType() ?: 'application/octet-stream'),
                    'uploaded_at' => $now,
                ]);
            }

            $lockedTx->update([
                'status' => TransactionStatus::DELIVERED->value,
                'delivered_at' => $now,
                'confirmation_due_at' => $confirmationDue,
                'delivery_note' => $deliveryNote,
                'result_link' => $resultLink,
            ]);

            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => TransactionStatus::PAID->value,
                'to_status' => TransactionStatus::DELIVERED->value,
                'changed_by' => $seller->user_id,
                'note' => 'Penjual telah menyerahkan hasil resmi transaksi.',
                'changed_at' => $now,
            ]);

            // Dispatch notifikasi ke Pembeli
            $buyerId = $lockedTx->agreement->buyer_id;
            $itemTitle = $lockedTx->agreement->item_title ?? 'Produk Digital';
            $this->notificationService->notifyItemDelivered($buyerId, $lockedTx->transaction_id, $itemTitle);

            return $lockedTx;
        });
    }

    /**
     * Record delivery deadline extension.
     */
    public function extendDeliveryDeadline(
        Transaction $transaction,
        int $extensionMinutes,
        string $reason,
        ?User $actor = null
    ): TransactionDeadlineEvent {
        return DB::transaction(function () use ($transaction, $extensionMinutes, $reason, $actor) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousDeadline = $lockedTx->delivery_due_at ?: now();
            $newDeadline = $previousDeadline->copy()->addMinutes($extensionMinutes);

            $lockedTx->update([
                'delivery_due_at' => $newDeadline,
            ]);

            return TransactionDeadlineEvent::create([
                'transaction_id' => $lockedTx->transaction_id,
                'event_type' => 'DELIVERY_EXTENSION',
                'previous_deadline' => $previousDeadline,
                'new_deadline' => $newDeadline,
                'extension_minutes' => $extensionMinutes,
                'reason' => $reason,
                'granted_by' => $actor?->user_id,
                'is_flagged_for_admin' => false,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Manual confirmation of delivery by Buyer.
     */
    public function confirmOrder(Transaction $transaction, User $buyer): Transaction
    {
        if ($transaction->agreement->buyer_id !== $buyer->user_id) {
            throw new AuthorizationException('Hanya Pembeli terkait yang berhak mengonfirmasi penerimaan pesanan.');
        }

        return DB::transaction(function () use ($transaction, $buyer) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $statusStr = $lockedTx->status instanceof TransactionStatus
                ? $lockedTx->status->value
                : (string) $lockedTx->status;

            // Idempotency: jika sudah berstatus CONFIRMED atau COMPLETED
            if (in_array($statusStr, [TransactionStatus::CONFIRMED->value, TransactionStatus::COMPLETED->value]) && $lockedTx->confirmed_at !== null) {
                return $lockedTx;
            }

            if ($statusStr !== TransactionStatus::DELIVERED->value) {
                throw new InvalidArgumentException("Transaksi tidak dapat dikonfirmasi karena status saat ini adalah [{$statusStr}].");
            }

            // Guard komplain aktif: komplain menahan konfirmasi & payout
            if ($lockedTx->hasActiveComplaint()) {
                throw new InvalidArgumentException('Transaksi tidak dapat dikonfirmasi karena terdapat komplain aktif.');
            }

            $now = now();

            $lockedTx->update([
                'status' => TransactionStatus::CONFIRMED->value,
                'confirmed_at' => $now,
            ]);

            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => TransactionStatus::DELIVERED->value,
                'to_status' => TransactionStatus::CONFIRMED->value,
                'changed_by' => $buyer->user_id,
                'note' => 'Pembeli mengonfirmasi penerimaan hasil pesanan.',
                'changed_at' => $now,
            ]);

            // Siapkan antrean payout manual sesuai kontrak settlement (tanpa transfer otomatis & tanpa settlement ganda)
            $this->queuePayoutSettlement($lockedTx, $now);

            // Notifikasi ke Seller & Admin
            $sellerId = $lockedTx->agreement->seller_id;
            $itemTitle = $lockedTx->agreement->item_title ?? 'Produk Digital';
            $shortTitle = mb_substr($itemTitle, 0, 40);

            $this->notificationService->notifyPayoutReady($sellerId, $lockedTx->transaction_id, $itemTitle);

            $admins = User::where('account_type', 'ADMIN')->get();
            foreach ($admins as $admin) {
                $this->notificationService->notify(
                    $admin->user_id,
                    $lockedTx->transaction_id,
                    NotificationEventType::PAYOUT_READY,
                    "Pesanan #{$lockedTx->transaction_id} ('{$shortTitle}') telah dikonfirmasi Pembeli. Antrean payout siap diproses manual.",
                    null,
                    true
                );
            }

            return $lockedTx;
        });
    }

    /**
     * Automatic system confirmation after grace period expiry.
     */
    public function autoConfirmOrder(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $statusStr = $lockedTx->status instanceof TransactionStatus
                ? $lockedTx->status->value
                : (string) $lockedTx->status;

            if (in_array($statusStr, [TransactionStatus::CONFIRMED->value, TransactionStatus::COMPLETED->value]) && $lockedTx->confirmed_at !== null) {
                return $lockedTx;
            }

            if ($statusStr !== TransactionStatus::DELIVERED->value) {
                return $lockedTx;
            }

            // Guard komplain aktif: komplain menahan auto-confirm & payout
            if ($lockedTx->hasActiveComplaint()) {
                return $lockedTx;
            }

            $now = now();

            $lockedTx->update([
                'status' => TransactionStatus::CONFIRMED->value,
                'confirmed_at' => $now,
            ]);

            // KNF-03: changed_by = null menandai konfirmasi otomatis sistem (bukan manual buyer)
            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => TransactionStatus::DELIVERED->value,
                'to_status' => TransactionStatus::CONFIRMED->value,
                'changed_by' => null,
                'note' => 'Konfirmasi otomatis oleh sistem (Auto-Confirm) setelah batas masa tenggang berakhir tanpa komplain.',
                'changed_at' => $now,
            ]);

            // Siapkan antrean payout manual sesuai kontrak settlement
            $this->queuePayoutSettlement($lockedTx, $now);

            // Catat event auto-confirmed di deadline events
            TransactionDeadlineEvent::create([
                'transaction_id' => $lockedTx->transaction_id,
                'event_type' => 'AUTO_CONFIRMED',
                'previous_deadline' => $lockedTx->confirmation_due_at,
                'new_deadline' => null,
                'extension_minutes' => null,
                'reason' => 'Pesanan disetujui otomatis oleh sistem karena batas waktu konfirmasi telah habis.',
                'granted_by' => null,
                'is_flagged_for_admin' => false,
                'created_at' => $now,
            ]);

            // Notifikasi ke Seller, Buyer, dan Admin
            $sellerId = $lockedTx->agreement->seller_id;
            $buyerId = $lockedTx->agreement->buyer_id;
            $itemTitle = $lockedTx->agreement->item_title ?? 'Produk Digital';
            $shortTitle = mb_substr($itemTitle, 0, 40);

            $this->notificationService->notifyPayoutReady($sellerId, $lockedTx->transaction_id, $itemTitle);

            $this->notificationService->notify(
                $buyerId,
                $lockedTx->transaction_id,
                NotificationEventType::PAYOUT_READY,
                "Pesanan #{$lockedTx->transaction_id} ('{$shortTitle}') telah disetujui otomatis oleh sistem karena batas waktu konfirmasi telah habis.",
                null,
                true
            );

            $admins = User::where('account_type', 'ADMIN')->get();
            foreach ($admins as $admin) {
                $this->notificationService->notify(
                    $admin->user_id,
                    $lockedTx->transaction_id,
                    NotificationEventType::PAYOUT_READY,
                    "Pesanan #{$lockedTx->transaction_id} ('{$shortTitle}') disetujui otomatis oleh sistem (Auto-Confirm). Antrean payout siap diproses manual.",
                    null,
                    true
                );
            }

            return $lockedTx;
        });
    }

    /**
     * Siapkan record antrean payout manual pada tabel settlements tanpa transfer otomatis & tanpa duplikasi.
     */
    protected function queuePayoutSettlement(Transaction $lockedTx, CarbonInterface $now): Settlement
    {
        $payoutDueAt = $now->copy()->addHour();
        $existing = Settlement::where('transaction_id', $lockedTx->transaction_id)->first();

        if ($existing) {
            if (! $existing->payout_due_at) {
                $existing->update(['payout_due_at' => $payoutDueAt]);
            }

            return $existing;
        }

        $seller = $lockedTx->agreement->seller;
        $destination = $this->payoutDestinationService->getDestinationForUser($seller);
        $sellerNet = $lockedTx->agreement->seller_net;

        return Settlement::create([
            'transaction_id' => $lockedTx->transaction_id,
            'settlement_type' => 'PAYOUT',
            'destination_type' => $destination?->destinationType->value ?? 'BANK',
            'destination_provider' => $destination?->destinationProvider,
            'destination_number' => $destination?->destinationNumber,
            'destination_holder' => $destination?->destinationHolder ?? ($seller->name ?? $seller->username),
            'destination_qris_image_url' => $destination?->destinationQrisImageUrl,
            'amount' => $sellerNet,
            'gross_amount' => $sellerNet,
            'keep_deducted_amount' => 0,
            'status' => 'PLANNED',
            'payout_due_at' => $payoutDueAt,
        ]);
    }
}
