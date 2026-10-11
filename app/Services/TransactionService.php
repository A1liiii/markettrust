<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Perform atomic status transition and record transaction_status_histories.
     */
    public function transitionStatus(Transaction $transaction, TransactionStatus|string $toStatus, ?User $actor = null, ?string $note = null): bool
    {
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
}
