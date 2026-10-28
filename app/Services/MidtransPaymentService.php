<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\NotificationEventType;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransPaymentService implements PaymentServiceInterface
{
    protected NotificationServiceInterface $notificationService;

    public function __construct(
        protected TransactionServiceInterface $transactionService,
        ?NotificationServiceInterface $notificationService = null
    ) {
        $this->notificationService = $notificationService ?? app(NotificationServiceInterface::class);
    }

    /**
     * Configure Midtrans global parameters before calling the API.
     */
    protected function configureMidtrans(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('services.midtrans.is_3ds', true);
        Config::$curlOptions = [
            CURLOPT_HTTPHEADER => [],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ];
    }

    /**
     * Request a Snap transaction from Midtrans API.
     *
     * @param  array<string, mixed>  $params
     */
    protected function requestSnapTransaction(array $params): object
    {
        $this->configureMidtrans();

        return Snap::createTransaction($params);
    }

    /**
     * Generate or retrieve a valid Snap payment token for the given transaction.
     *
     * @return array{token: string, redirect_url: string, order_id: string}
     */
    public function getOrCreateSnapToken(Transaction $transaction, User $buyer): array
    {
        $agreement = $transaction->agreement;

        if (! $agreement || (int) $agreement->buyer_id !== (int) $buyer->user_id) {
            throw new InvalidArgumentException('Hanya pembeli yang bersangkutan yang dapat melakukan pembayaran untuk transaksi ini.');
        }

        if (in_array($transaction->status, [TransactionStatus::WAITING_PAYMENT, TransactionStatus::CANCELLED], true)
            && $transaction->payment_due_at
            && $transaction->payment_due_at->isPast()
        ) {
            try {
                $this->expireOverduePayment($transaction);
            } catch (Exception) {
                // Tetap tolak pembuatan token meskipun rekonsiliasi eksternal terkendala
            }

            throw new InvalidArgumentException('Batas waktu pembayaran untuk transaksi ini telah kedaluwarsa.');
        }

        if ($transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            throw new InvalidArgumentException('Transaksi ini tidak sedang dalam status menunggu pembayaran.');
        }

        $payment = Payment::firstOrNew(['transaction_id' => $transaction->transaction_id]);

        if ($payment->exists && $payment->status === 'PAID') {
            throw new InvalidArgumentException('Transaksi ini sudah terbayar.');
        }

        if ($payment->exists && $payment->status === 'PENDING' && ! empty($payment->latest_payload['snap_token'])) {
            return [
                'token' => (string) $payment->latest_payload['snap_token'],
                'redirect_url' => (string) ($payment->latest_payload['redirect_url'] ?? ''),
                'order_id' => (string) $payment->gateway_order_id,
            ];
        }

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = (int) round((float) $agreement->buyer_total);
        $basePrice = (int) round((float) $agreement->base_price);
        $feeAmount = max(0, $grossAmount - $basePrice);

        $itemDetails = [
            [
                'id' => 'ITEM-'.($agreement->variant_id ?? $transaction->transaction_id),
                'price' => $basePrice,
                'quantity' => 1,
                'name' => mb_substr($agreement->item_title, 0, 50),
            ],
        ];

        if ($feeAmount > 0) {
            $itemDetails[] = [
                'id' => 'PLATFORM-FEE',
                'price' => $feeAmount,
                'quantity' => 1,
                'name' => 'Biaya Layanan Platform',
            ];
        }

        $customerDetails = [
            'first_name' => (string) ($buyer->name ?: $buyer->username),
            'email' => (string) $buyer->email,
            'phone' => (string) ($buyer->phone_number ?: '08123456789'),
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ];

        try {
            $snapResponse = $this->requestSnapTransaction($params);
        } catch (Exception $e) {
            throw new DomainException('Gagal menghubungi gateway Midtrans: '.$e->getMessage(), previous: $e);
        }

        $token = (string) ($snapResponse->token ?? '');
        $redirectUrl = (string) ($snapResponse->redirect_url ?? '');

        if (empty($token)) {
            throw new DomainException('Midtrans tidak mengembalikan Snap Token yang valid.');
        }

        $payment->fill([
            'gateway_order_id' => $orderId,
            'amount' => $agreement->buyer_total,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
            'latest_payload' => [
                'snap_token' => $token,
                'redirect_url' => $redirectUrl,
            ],
            'expires_at' => $transaction->payment_due_at,
        ]);
        $payment->save();

        return [
            'token' => $token,
            'redirect_url' => $redirectUrl,
            'order_id' => $orderId,
        ];
    }

    /**
     * Process an incoming HTTP notification webhook from Midtrans.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string}
     */
    public function handleNotification(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($signatureKey)) {
            throw new InvalidArgumentException('Payload notifikasi Midtrans tidak lengkap.');
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        if (! hash_equals($expectedSignature, $signatureKey)) {
            throw new DomainException('Verifikasi signature Midtrans tidak valid.');
        }

        $payment = Payment::where('gateway_order_id', $orderId)->first();
        if (! $payment && preg_match('/^TRX-(\d+)/', $orderId, $matches)) {
            $payment = Payment::where('transaction_id', (int) $matches[1])->first();
        }

        if (! $payment) {
            throw new DomainException("Pembayaran dengan order ID {$orderId} tidak ditemukan.");
        }

        $transaction = $payment->transaction;
        if (! $transaction) {
            throw new DomainException("Data transaksi untuk pembayaran #{$payment->payment_id} tidak ditemukan.");
        }

        $expectedAmount = (float) ($payment->amount ?: $transaction->agreement?->buyer_total ?? 0);
        if (! is_numeric($grossAmount) || abs((float) $grossAmount - $expectedAmount) >= 1.0) {
            throw new DomainException('Nominal gross_amount pada notifikasi Midtrans tidak sesuai dengan nominal tagihan transaksi.');
        }

        // Idempotency check: jika sudah PAID, jangan lakukan duplikasi status/riwayat atau menggeser paid_at/deadline
        if ($payment->status === 'PAID' && $transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            if ($transaction->status === TransactionStatus::CANCELLED && $this->hasLatePaymentFlag($transaction)) {
                return [
                    'status' => 'held_for_reconciliation',
                    'message' => 'Pembayaran diterima melewati batas waktu atau pada transaksi yang telah dibatalkan. Ditahan untuk rekonsiliasi Admin.',
                ];
            }

            return [
                'status' => 'already_paid',
                'message' => 'Pembayaran telah diproses sebelumnya.',
            ];
        }

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? 'midtrans');
        $gatewayTxId = (string) ($payload['transaction_id'] ?? '');

        $isSettled = ($transactionStatus === 'settlement')
            || ($transactionStatus === 'capture' && ($fraudStatus === 'accept' || empty($fraudStatus)));

        if ($isSettled) {
            $alreadyPaid = false;
            $heldForReconciliation = false;

            DB::transaction(function () use (
                $payment,
                $transaction,
                $payload,
                $paymentType,
                $gatewayTxId,
                $transactionStatus,
                &$alreadyPaid,
                &$heldForReconciliation
            ) {
                $lockedPayment = Payment::whereKey($payment->payment_id)->lockForUpdate()->first() ?? $payment;
                $lockedTransaction = Transaction::whereKey($transaction->transaction_id)->lockForUpdate()->first() ?? $transaction;

                if ($lockedPayment->status === 'PAID' && $lockedTransaction->status !== TransactionStatus::WAITING_PAYMENT) {
                    if ($lockedTransaction->status === TransactionStatus::CANCELLED && $this->hasLatePaymentFlag($lockedTransaction)) {
                        $heldForReconciliation = true;
                    } else {
                        $alreadyPaid = true;
                    }

                    return;
                }

                $now = now();
                $paidAt = $lockedPayment->paid_at ? $lockedPayment->paid_at->copy() : $now->copy();
                $verifiedGatewayFee = $this->extractVerifiedGatewayFeeAmount($payload);
                $channelUpper = strtoupper($paymentType);

                $isCancelledOrTerminal = in_array($lockedTransaction->status, [
                    TransactionStatus::CANCELLED,
                    TransactionStatus::REFUNDED,
                ], true);

                $isPastPaymentDue = $lockedTransaction->status === TransactionStatus::WAITING_PAYMENT
                    && $lockedTransaction->payment_due_at !== null
                    && $now->greaterThan($lockedTransaction->payment_due_at);

                // UC-13B: Callback berhasil yang datang terlambat (setelah payment_due_at atau saat sudah CANCELLED)
                // ditahan untuk rekonsiliasi Admin dan tidak otomatis membuka tahap penyerahan.
                if ($isCancelledOrTerminal || $isPastPaymentDue) {
                    $heldForReconciliation = true;

                    $lockedPayment->update([
                        'status' => 'PAID',
                        'paid_at' => $paidAt,
                        'payment_channel' => $paymentType,
                        'gateway_transaction_id' => $gatewayTxId ?: $lockedPayment->gateway_transaction_id,
                        'gateway_status' => $transactionStatus,
                        'gateway_fee_amount' => $verifiedGatewayFee ?? $lockedPayment->gateway_fee_amount,
                        'latest_payload' => $payload,
                        'last_notification_at' => $now,
                    ]);

                    if ($lockedTransaction->status === TransactionStatus::WAITING_PAYMENT) {
                        $this->transactionService->transitionStatus(
                            $lockedTransaction,
                            TransactionStatus::CANCELLED,
                            null,
                            "Pembayaran Midtrans ({$channelUpper}) diterima melewati batas waktu pembayaran. Ditahan untuk rekonsiliasi Admin."
                        );
                    } elseif (! $this->hasLatePaymentFlag($lockedTransaction)) {
                        TransactionStatusHistory::create([
                            'transaction_id' => $lockedTransaction->transaction_id,
                            'from_status' => TransactionStatus::CANCELLED->value,
                            'to_status' => TransactionStatus::CANCELLED->value,
                            'changed_by' => null,
                            'note' => "Callback pembayaran berhasil Midtrans ({$channelUpper}) diterima setelah transaksi berstatus CANCELLED. Ditahan untuk rekonsiliasi Admin.",
                            'changed_at' => $now,
                        ]);
                    }

                    if (! $this->hasLatePaymentFlag($lockedTransaction)) {
                        TransactionDeadlineEvent::create([
                            'transaction_id' => $lockedTransaction->transaction_id,
                            'event_type' => 'LATE_PAYMENT_CALLBACK',
                            'previous_deadline' => $lockedTransaction->payment_due_at,
                            'new_deadline' => null,
                            'extension_minutes' => null,
                            'reason' => "Callback pembayaran berhasil ({$channelUpper}) diterima melewati batas waktu pembayaran atau saat transaksi telah dibatalkan. Ditahan untuk rekonsiliasi Admin.",
                            'granted_by' => null,
                            'is_flagged_for_admin' => true,
                            'created_at' => $now,
                        ]);
                    }

                    $this->dispatchLatePaymentReconciliationNotifications($lockedTransaction);

                    return;
                }

                $lockedPayment->update([
                    'status' => 'PAID',
                    'paid_at' => $paidAt,
                    'payment_channel' => $paymentType,
                    'gateway_transaction_id' => $gatewayTxId ?: $lockedPayment->gateway_transaction_id,
                    'gateway_status' => $transactionStatus,
                    'gateway_fee_amount' => $verifiedGatewayFee ?? $lockedPayment->gateway_fee_amount,
                    'latest_payload' => $payload,
                    'last_notification_at' => $now,
                ]);

                $lockedTransaction->delivery_due_at = $lockedTransaction->delivery_due_at
                    ? $lockedTransaction->delivery_due_at->copy()
                    : $paidAt->copy()->addHour();
                $lockedTransaction->save();

                $this->transactionService->transitionStatus(
                    $lockedTransaction,
                    TransactionStatus::PAID,
                    null,
                    "Dana escrow berhasil diamankan via Midtrans ({$channelUpper})"
                );

                $this->dispatchPaymentSuccessNotifications($lockedTransaction);
            });

            if ($heldForReconciliation) {
                return [
                    'status' => 'held_for_reconciliation',
                    'message' => 'Pembayaran diterima melewati batas waktu atau pada transaksi yang telah dibatalkan. Ditahan untuk rekonsiliasi Admin.',
                ];
            }

            if ($alreadyPaid) {
                return [
                    'status' => 'already_paid',
                    'message' => 'Pembayaran telah diproses sebelumnya.',
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Pembayaran terverifikasi dan transaksi beralih ke status PAID.',
            ];
        }

        if ($transactionStatus === 'pending') {
            DB::transaction(function () use ($payment, $payload, $paymentType, $gatewayTxId) {
                $lockedPayment = Payment::whereKey($payment->payment_id)->lockForUpdate()->first() ?? $payment;

                if ($lockedPayment->status === 'PAID') {
                    return;
                }

                $lockedPayment->update([
                    'gateway_status' => 'pending',
                    'payment_channel' => $paymentType ?: $lockedPayment->payment_channel,
                    'gateway_transaction_id' => $gatewayTxId ?: $lockedPayment->gateway_transaction_id,
                    'latest_payload' => $payload,
                    'last_notification_at' => now(),
                ]);
            });

            return [
                'status' => 'pending',
                'message' => 'Pembayaran sedang menunggu penyelesaian oleh pembeli.',
            ];
        }

        if (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            $newPaymentStatus = match ($transactionStatus) {
                'deny' => 'FAILED',
                'expire' => 'EXPIRED',
                'cancel' => 'CANCELLED',
                default => 'FAILED',
            };

            $alreadyPaid = false;

            DB::transaction(function () use ($payment, $transaction, $payload, $transactionStatus, $newPaymentStatus, &$alreadyPaid) {
                $lockedPayment = Payment::whereKey($payment->payment_id)->lockForUpdate()->first() ?? $payment;
                $lockedTransaction = Transaction::whereKey($transaction->transaction_id)->lockForUpdate()->first() ?? $transaction;

                if ($lockedPayment->status === 'PAID') {
                    $alreadyPaid = true;

                    return;
                }

                $now = now();

                $lockedPayment->update([
                    'status' => $newPaymentStatus,
                    'gateway_status' => $transactionStatus,
                    'latest_payload' => $payload,
                    'last_notification_at' => $now,
                ]);

                if ($lockedTransaction->status === TransactionStatus::WAITING_PAYMENT) {
                    $this->transactionService->transitionStatus(
                        $lockedTransaction,
                        TransactionStatus::CANCELLED,
                        null,
                        "Transaksi dibatalkan karena pembayaran status: {$transactionStatus} pada Midtrans."
                    );

                    if ($transactionStatus === 'expire') {
                        $this->dispatchPaymentExpiredNotifications($lockedTransaction);
                    } else {
                        $this->dispatchTransactionCancelledNotifications(
                            $lockedTransaction,
                            "Pembayaran {$transactionStatus} pada Midtrans"
                        );
                    }
                }
            });

            if ($alreadyPaid) {
                return [
                    'status' => 'already_paid',
                    'message' => 'Pembayaran telah diproses sebelumnya.',
                ];
            }

            return [
                'status' => strtolower($newPaymentStatus),
                'message' => "Status pembayaran dicatat sebagai {$newPaymentStatus}.",
            ];
        }

        $payment->update([
            'gateway_status' => $transactionStatus,
            'latest_payload' => $payload,
            'last_notification_at' => now(),
        ]);

        return [
            'status' => 'recorded',
            'message' => "Status {$transactionStatus} berhasil dicatat.",
        ];
    }

    /**
     * Extract actual gateway fee amount only when an explicit, verifiable fee field is present in the Midtrans payload.
     *
     * Note (UC-13 Step 5 & UC-28B): Standard Midtrans Snap/Core HTTP notifications in Sandbox only include
     * `gross_amount` and do not expose actual merchant discount rate (MDR) / gateway fee fields.
     * When the actual fee field is absent from the payload, this method returns null (never guessing tariffs or 0.00)
     * so downstream KEEP calculations know the gateway fee is still unverified and awaits Admin verification.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractVerifiedGatewayFeeAmount(array $payload): ?float
    {
        foreach (['gateway_fee_amount', 'merchant_fee', 'mdr_fee'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '' && is_numeric($payload[$field])) {
                $fee = (float) $payload[$field];

                if ($fee >= 0) {
                    return round($fee, 2);
                }
            }
        }

        return null;
    }

    /**
     * Determine whether a transaction has already been flagged for a late payment callback.
     */
    protected function hasLatePaymentFlag(Transaction $transaction): bool
    {
        return $transaction->deadlineEvents()
            ->where('event_type', 'LATE_PAYMENT_CALLBACK')
            ->exists();
    }

    /**
     * Dispatch payment success notifications to both Buyer and Seller (idempotent one-time per transaction).
     */
    protected function dispatchPaymentSuccessNotifications(Transaction $transaction): void
    {
        $agreement = $transaction->agreement()->first();
        if (! $agreement) {
            return;
        }

        $itemTitle = (string) ($agreement->item_title ?: 'Produk Digital');

        if ($agreement->buyer_id) {
            $this->notificationService->notifyPaymentSuccess(
                (int) $agreement->buyer_id,
                (int) $transaction->transaction_id,
                $itemTitle
            );
        }

        if ($agreement->seller_id) {
            $this->notificationService->notifyPaymentSuccess(
                (int) $agreement->seller_id,
                (int) $transaction->transaction_id,
                $itemTitle
            );
        }
    }

    /**
     * Dispatch payment expired notifications to both Buyer and Seller (idempotent one-time per transaction).
     */
    protected function dispatchPaymentExpiredNotifications(Transaction $transaction): void
    {
        $agreement = $transaction->agreement()->first();
        if (! $agreement) {
            return;
        }

        $itemTitle = (string) ($agreement->item_title ?: 'Produk Digital');

        if ($agreement->buyer_id) {
            $this->notificationService->notifyPaymentExpired(
                (int) $agreement->buyer_id,
                (int) $transaction->transaction_id,
                $itemTitle
            );
        }

        if ($agreement->seller_id) {
            $this->notificationService->notifyPaymentExpired(
                (int) $agreement->seller_id,
                (int) $transaction->transaction_id,
                $itemTitle
            );
        }
    }

    /**
     * Dispatch transaction cancelled notifications to both Buyer and Seller (idempotent one-time per transaction).
     */
    protected function dispatchTransactionCancelledNotifications(Transaction $transaction, ?string $reason = null): void
    {
        $agreement = $transaction->agreement()->first();
        if (! $agreement) {
            return;
        }

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
    }

    /**
     * Dispatch notifications to Admins and Buyer when a late settlement callback is held for reconciliation.
     */
    protected function dispatchLatePaymentReconciliationNotifications(Transaction $transaction): void
    {
        $agreement = $transaction->agreement()->first();
        $itemTitle = mb_substr((string) ($agreement?->item_title ?: 'Produk Digital'), 0, 40);
        $txId = (int) $transaction->transaction_id;

        if ($agreement?->buyer_id) {
            $buyerMessage = "Pembayaran transaksi #{$txId} ('{$itemTitle}') diterima melewati batas waktu/setelah dibatalkan dan sedang ditahan untuk rekonsiliasi Admin.";
            $this->notificationService->notify(
                (int) $agreement->buyer_id,
                $txId,
                NotificationEventType::TRANSACTION_CANCELLED,
                $buyerMessage,
                null,
                true
            );
        }

        $adminMessage = "Rekonsiliasi Admin diperlukan: Pembayaran transaksi #{$txId} ('{$itemTitle}') masuk melewati batas waktu/setelah CANCELLED.";
        $admins = User::where('account_type', 'ADMIN')->get();

        foreach ($admins as $admin) {
            $this->notificationService->notify(
                (int) $admin->user_id,
                $txId,
                NotificationEventType::TRANSACTION_CANCELLED,
                $adminMessage,
                null,
                true
            );
        }
    }

    /**
     * Query Midtrans API directly for transaction status.
     */
    protected function queryMidtransTransactionStatus(string $orderId): object
    {
        $this->configureMidtrans();

        return \Midtrans\Transaction::status($orderId);
    }

    /**
     * Normalize a status response object returned by Midtrans Status API into a notification-compatible payload.
     *
     * @return array<string, mixed>|null
     */
    protected function normalizeStatusApiPayload(object $statusObj, Payment $payment): ?array
    {
        $payload = json_decode(json_encode($statusObj), true);
        if (! is_array($payload)) {
            return null;
        }

        $orderId = (string) ($payload['order_id'] ?? $payment->gateway_order_id);
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? number_format((float) $payment->amount, 2, '.', ''));

        $payload['order_id'] = $orderId;
        $payload['gross_amount'] = $grossAmount;

        if (empty($payload['signature_key']) && ! empty($orderId) && ! empty($statusCode) && ! empty($grossAmount)) {
            $serverKey = (string) config('services.midtrans.server_key');
            $payload['signature_key'] = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        }

        return $payload;
    }

    /**
     * Query Midtrans API directly for the current transaction status and sync the database.
     *
     * @return array{status: string, message: string}
     */
    public function syncPaymentStatus(Transaction $transaction): array
    {
        $transaction->refresh();

        if ($transaction->status === TransactionStatus::WAITING_PAYMENT
            && $transaction->payment_due_at !== null
            && $transaction->payment_due_at->isPast()
        ) {
            return $this->expireOverduePayment($transaction);
        }

        if ($transaction->status === TransactionStatus::CANCELLED) {
            if ($this->hasLatePaymentFlag($transaction)) {
                return [
                    'status' => 'held_for_reconciliation',
                    'message' => 'Pembayaran diterima melewati batas waktu atau pada transaksi yang telah dibatalkan. Ditahan untuk rekonsiliasi Admin.',
                ];
            }

            return [
                'status' => 'expired',
                'message' => 'Batas waktu pembayaran berakhir. Status transaksi telah dibatalkan (CANCELLED).',
            ];
        }

        $payment = $transaction->payment;
        if (! $payment || empty($payment->gateway_order_id)) {
            return [
                'status' => 'not_found',
                'message' => 'Belum ada data pembayaran gateway untuk transaksi ini.',
            ];
        }

        if ($payment->status === 'PAID' && $transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            if ($transaction->status === TransactionStatus::CANCELLED && $this->hasLatePaymentFlag($transaction)) {
                return [
                    'status' => 'held_for_reconciliation',
                    'message' => 'Pembayaran diterima melewati batas waktu atau pada transaksi yang telah dibatalkan. Ditahan untuk rekonsiliasi Admin.',
                ];
            }

            return [
                'status' => 'already_paid',
                'message' => 'Pembayaran telah diverifikasi sebelumnya.',
            ];
        }

        try {
            $statusObj = $this->queryMidtransTransactionStatus($payment->gateway_order_id);
            $payload = $this->normalizeStatusApiPayload($statusObj, $payment);

            if (! is_array($payload)) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal membaca respons status dari gateway Midtrans.',
                ];
            }

            return $this->handleNotification($payload);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Status belum tersedia di Midtrans: '.$e->getMessage(),
            ];
        }
    }

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
    public function reconcileGatewayStatus(Transaction $transaction): array
    {
        $transaction->refresh();
        $payment = $transaction->payment()->first();

        if ($payment && $payment->status === 'PAID') {
            if ($transaction->status === TransactionStatus::CANCELLED) {
                return [
                    'state' => 'held_for_reconciliation',
                    'can_cancel_or_expire' => false,
                    'gateway_status' => $payment->gateway_status,
                    'message' => 'Pembayaran telah tercatat pada transaksi yang dibatalkan dan sedang ditahan untuk rekonsiliasi Admin.',
                ];
            }

            return [
                'state' => 'paid',
                'can_cancel_or_expire' => false,
                'gateway_status' => $payment->gateway_status,
                'message' => 'Pembayaran telah diverifikasi sebagai PAID.',
            ];
        }

        if (! $payment || empty($payment->gateway_order_id)) {
            return [
                'state' => 'unpaid',
                'can_cancel_or_expire' => true,
                'gateway_status' => null,
                'message' => 'Belum ada order pembayaran pada gateway Midtrans.',
            ];
        }

        try {
            $statusObj = $this->queryMidtransTransactionStatus($payment->gateway_order_id);
        } catch (Exception $e) {
            if ((int) $e->getCode() === 404 || str_contains($e->getMessage(), '404')) {
                return [
                    'state' => 'unpaid',
                    'can_cancel_or_expire' => true,
                    'gateway_status' => 'not_found',
                    'message' => 'Transaksi belum terdaftar atau belum dibayar pada gateway Midtrans (404).',
                ];
            }

            return [
                'state' => 'uncertain',
                'can_cancel_or_expire' => false,
                'gateway_status' => null,
                'message' => 'Status gateway Midtrans belum dapat dipastikan: '.$e->getMessage(),
            ];
        }

        $payload = $this->normalizeStatusApiPayload($statusObj, $payment);
        if (! is_array($payload)) {
            return [
                'state' => 'uncertain',
                'can_cancel_or_expire' => false,
                'gateway_status' => null,
                'message' => 'Gagal membaca respons status dari gateway Midtrans.',
            ];
        }

        $statusCode = (string) ($payload['status_code'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        if ($statusCode === '404') {
            return [
                'state' => 'unpaid',
                'can_cancel_or_expire' => true,
                'gateway_status' => 'not_found',
                'message' => 'Transaksi belum terdaftar atau belum dibayar pada gateway Midtrans (404).',
            ];
        }

        if (empty($transactionStatus)) {
            return [
                'state' => 'uncertain',
                'can_cancel_or_expire' => false,
                'gateway_status' => null,
                'message' => 'Respons status dari gateway Midtrans tidak memuat transaction_status yang pasti.',
            ];
        }

        $isSettled = ($transactionStatus === 'settlement')
            || ($transactionStatus === 'capture' && ($fraudStatus === 'accept' || empty($fraudStatus)));

        if ($isSettled) {
            try {
                $notifResult = $this->handleNotification($payload);
            } catch (Exception $e) {
                return [
                    'state' => 'uncertain',
                    'can_cancel_or_expire' => false,
                    'gateway_status' => $transactionStatus,
                    'message' => 'Gagal memverifikasi penyelesaian pembayaran dari gateway: '.$e->getMessage(),
                ];
            }

            if (($notifResult['status'] ?? '') === 'held_for_reconciliation') {
                return [
                    'state' => 'held_for_reconciliation',
                    'can_cancel_or_expire' => false,
                    'gateway_status' => $transactionStatus,
                    'message' => $notifResult['message'],
                ];
            }

            return [
                'state' => 'paid',
                'can_cancel_or_expire' => false,
                'gateway_status' => $transactionStatus,
                'message' => $notifResult['message'],
            ];
        }

        if (in_array($transactionStatus, ['pending', 'expire', 'cancel', 'deny'], true)) {
            $payment->update([
                'gateway_status' => $transactionStatus,
                'latest_payload' => $payload,
                'last_notification_at' => now(),
            ]);

            return [
                'state' => 'unpaid',
                'can_cancel_or_expire' => true,
                'gateway_status' => $transactionStatus,
                'message' => "Transaksi belum memiliki pembayaran sah pada gateway Midtrans (status: {$transactionStatus}).",
            ];
        }

        return [
            'state' => 'uncertain',
            'can_cancel_or_expire' => false,
            'gateway_status' => $transactionStatus,
            'message' => "Status gateway Midtrans ({$transactionStatus}) belum dapat dipastikan.",
        ];
    }

    /**
     * Expire an overdue WAITING_PAYMENT transaction after reconciling with the payment gateway (UC-13C).
     *
     * @return array{status: string, message: string}
     */
    public function expireOverduePayment(Transaction $transaction): array
    {
        if ($transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            return [
                'status' => 'skipped',
                'message' => 'Transaksi tidak sedang dalam status WAITING_PAYMENT.',
            ];
        }

        if ($transaction->payment_due_at === null || $transaction->payment_due_at->isFuture()) {
            return [
                'status' => 'not_due',
                'message' => 'Batas waktu pembayaran belum terlewati.',
            ];
        }

        $reconciliation = $this->reconcileGatewayStatus($transaction);

        if ($reconciliation['state'] === 'uncertain') {
            return [
                'status' => 'uncertain',
                'message' => $reconciliation['message'],
            ];
        }

        if ($reconciliation['state'] === 'paid') {
            return [
                'status' => 'paid',
                'message' => $reconciliation['message'],
            ];
        }

        if ($reconciliation['state'] === 'held_for_reconciliation') {
            return [
                'status' => 'held_for_reconciliation',
                'message' => $reconciliation['message'],
            ];
        }

        $outcome = 'expired';

        DB::transaction(function () use ($transaction, $reconciliation, &$outcome) {
            $lockedTransaction = Transaction::whereKey($transaction->transaction_id)->lockForUpdate()->first() ?? $transaction;
            $lockedPayment = $lockedTransaction->payment()->lockForUpdate()->first();

            if ($lockedPayment && $lockedPayment->status === 'PAID') {
                $outcome = $lockedTransaction->status === TransactionStatus::CANCELLED
                    ? 'held_for_reconciliation'
                    : 'paid';

                return;
            }

            if ($lockedTransaction->status !== TransactionStatus::WAITING_PAYMENT) {
                $outcome = $lockedTransaction->status === TransactionStatus::CANCELLED
                    ? 'expired'
                    : 'skipped';

                return;
            }

            $now = now();

            if ($lockedPayment) {
                $lockedPayment->update([
                    'status' => 'EXPIRED',
                    'gateway_status' => $reconciliation['gateway_status'] ?: ($lockedPayment->gateway_status ?: 'expire'),
                    'last_notification_at' => $now,
                ]);
            }

            $this->transactionService->transitionStatus(
                $lockedTransaction,
                TransactionStatus::CANCELLED,
                null,
                'Batas waktu pembayaran 1 jam telah berakhir tanpa pembayaran sah setelah rekonsiliasi gateway.'
            );

            $this->dispatchPaymentExpiredNotifications($lockedTransaction);
        });

        return match ($outcome) {
            'paid' => [
                'status' => 'paid',
                'message' => 'Pembayaran telah diverifikasi sebagai PAID.',
            ],
            'held_for_reconciliation' => [
                'status' => 'held_for_reconciliation',
                'message' => 'Pembayaran diterima melewati batas waktu dan ditahan untuk rekonsiliasi Admin.',
            ],
            'skipped' => [
                'status' => 'skipped',
                'message' => 'Transaksi sudah tidak lagi berstatus WAITING_PAYMENT.',
            ],
            default => [
                'status' => 'expired',
                'message' => 'Batas waktu pembayaran berakhir. Status transaksi diubah menjadi CANCELLED dan pembayaran EXPIRED.',
            ],
        };
    }

    /**
     * Lazily evaluate payment deadline and gateway state when a single transaction is accessed.
     */
    public function evaluateLazyPaymentDeadline(Transaction $transaction): Transaction
    {
        if ($transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            return $transaction;
        }

        if ($transaction->payment_due_at !== null && $transaction->payment_due_at->isPast()) {
            $this->expireOverduePayment($transaction);

            return $transaction->refresh();
        }

        if ($transaction->payment?->gateway_order_id) {
            $this->syncPaymentStatus($transaction);

            return $transaction->refresh();
        }

        return $transaction;
    }

    /**
     * Lazily evaluate overdue WAITING_PAYMENT transactions for a user (or globally for Admin).
     */
    public function evaluateOverduePaymentsForUser(?User $user = null, int $limit = 10): int
    {
        $query = Transaction::query()
            ->whereIn('status', [TransactionStatus::WAITING_PAYMENT, 'WAITING_PAYMENT'])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', now())
            ->with(['payment', 'agreement']);

        if ($user && ! $user->isAdmin()) {
            $query->whereHas('agreement', function ($q) use ($user): void {
                $q->where('buyer_id', $user->user_id)
                    ->orWhere('seller_id', $user->user_id);
            });
        }

        $overdueTransactions = $query->limit($limit)->get();
        $processed = 0;

        foreach ($overdueTransactions as $overdueTransaction) {
            try {
                $this->expireOverduePayment($overdueTransaction);
                $processed++;
            } catch (Exception) {
                // Jangan gagalkan request pengguna jika rekonsiliasi gateway sedang terkendala
            }
        }

        return $processed;
    }
}
