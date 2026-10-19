<?php

namespace App\Contracts\Services;

use App\Models\Transaction;
use App\Models\User;

interface PaymentServiceInterface
{
    /**
     * Generate or retrieve a valid Snap payment token for the given transaction.
     *
     * @return array{token: string, redirect_url: string, order_id: string}
     */
    public function getOrCreateSnapToken(Transaction $transaction, User $buyer): array;

    /**
     * Process an incoming HTTP notification webhook from Midtrans.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string}
     */
    public function handleNotification(array $payload): array;

    /**
     * Query Midtrans API directly for the current transaction status and sync the database.
     *
     * @return array{status: string, message: string}
     */
    public function syncPaymentStatus(Transaction $transaction): array;
}
