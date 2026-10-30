<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;

class TransactionCancellationPolicy
{
    /**
     * Otorisasi pengajuan pembatalan oleh buyer atau seller.
     */
    public function create(User $user, Transaction $transaction): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $transaction->loadMissing(['agreement']);
        $agreement = $transaction->agreement;

        if (! $agreement) {
            return false;
        }

        $isBuyer = (int) $agreement->buyer_id === (int) $user->user_id;
        $isSeller = (int) $agreement->seller_id === (int) $user->user_id;
        $isAdmin = $user->isAdmin();

        if (! $isBuyer && ! $isSeller && ! $isAdmin) {
            return false;
        }

        $statusStr = $transaction->status instanceof TransactionStatus ? $transaction->status->value : (string) $transaction->status;

        // Tidak boleh dibatalkan jika sudah DELIVERED, CONFIRMED, COMPLAINED, RESOLVED, CANCELLED, REFUNDED
        if (in_array($statusStr, ['DELIVERED', 'CONFIRMED', 'COMPLAINED', 'RESOLVED', 'CANCELLED', 'REFUNDED'], true)) {
            return false;
        }

        // Jika PAID, pastikan belum ada pengajuan pending
        if ($statusStr === 'PAID' && $transaction->hasPendingCancellation()) {
            return false;
        }

        return true;
    }

    /**
     * Otorisasi peninjauan pengajuan pembatalan oleh Admin.
     */
    public function review(User $user, TransactionCancellation $cancellation): bool
    {
        return $user->isActive() && $user->isAdmin() && $cancellation->isRequested();
    }
}

