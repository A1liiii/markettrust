<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\EvidenceFile;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        protected NotificationServiceInterface $notificationService
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
}
