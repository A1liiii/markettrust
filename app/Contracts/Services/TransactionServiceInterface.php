<?php

namespace App\Contracts\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;

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
}
