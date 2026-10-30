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

    /**
     * Reconcile the current gateway payment state before cancellation or expiry (MT-A06 & UC-13C).
     *
     * @return array{
     *     state: 'unpaid'|'paid'|'held_for_reconciliation'|'uncertain',
     *     can_cancel_or_expire: bool,
     *     gateway_status: string|null,
     *     message: string
     * }
     */
    public function reconcileGatewayStatus(Transaction $transaction): array;

    /**
     * Expire an overdue WAITING_PAYMENT transaction after reconciling with the payment gateway (UC-13C).
     *
     * @return array{status: string, message: string}
     */
    public function expireOverduePayment(Transaction $transaction): array;

    /**
     * Lazily evaluate payment deadline and gateway state when a single transaction is accessed.
     */
    public function evaluateLazyPaymentDeadline(Transaction $transaction): Transaction;

    /**
     * Lazily evaluate overdue WAITING_PAYMENT transactions for a user (or globally for Admin).
     */
    public function evaluateOverduePaymentsForUser(?User $user = null, int $limit = 10): int;
}
