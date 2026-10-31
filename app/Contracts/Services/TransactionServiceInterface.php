<?php

namespace App\Contracts\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface TransactionServiceInterface
{
    /**
     * Perform atomic status transition and record to transaction_status_histories.
     */
    public function transitionStatus(
        Transaction $transaction,
        TransactionStatus|string $toStatus,
        ?User $actor = null,
        ?string $note = null
    ): bool;

    /**
     * Submit official delivery results and evidence by Seller.
     */
    public function deliverOrder(
        Transaction $transaction,
        User $seller,
        string $deliveryNote,
        ?string $resultLink = null,
        ?UploadedFile $evidenceFile = null
    ): Transaction;

    /**
     * Record delivery deadline extension.
     */
    public function extendDeliveryDeadline(
        Transaction $transaction,
        int $extensionMinutes,
        string $reason,
        ?User $actor = null
    ): TransactionDeadlineEvent;

    /**
     * Manual confirmation of delivery by Buyer.
     */
    public function confirmOrder(Transaction $transaction, User $buyer): Transaction;

    /**
     * Automatic system confirmation after grace period expiry.
     */
    public function autoConfirmOrder(Transaction $transaction): Transaction;
}
