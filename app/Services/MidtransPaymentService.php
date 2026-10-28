<?php

namespace App\Services;

use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransPaymentService implements PaymentServiceInterface
{
    public function __construct(
        protected TransactionServiceInterface $transactionService
    ) {}

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

        if ($transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            throw new InvalidArgumentException('Transaksi ini tidak sedang dalam status menunggu pembayaran.');
        }

        if ($transaction->payment_due_at && $transaction->payment_due_at->isPast()) {
            throw new InvalidArgumentException('Batas waktu pembayaran untuk transaksi ini telah kedaluwarsa.');
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

        // Idempotency check: jika sudah PAID, jangan lakukan duplikasi status/riwayat atau menggeser paid_at/deadline
        if ($payment->status === 'PAID' && $transaction->status !== TransactionStatus::WAITING_PAYMENT) {
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

            DB::transaction(function () use ($payment, $transaction, $payload, $paymentType, $gatewayTxId, $transactionStatus, &$alreadyPaid) {
                $lockedPayment = Payment::whereKey($payment->payment_id)->lockForUpdate()->first() ?? $payment;
                $lockedTransaction = Transaction::whereKey($transaction->transaction_id)->lockForUpdate()->first() ?? $transaction;

                if ($lockedPayment->status === 'PAID' && $lockedTransaction->status !== TransactionStatus::WAITING_PAYMENT) {
                    $alreadyPaid = true;

                    return;
                }

                $now = now();
                $paidAt = $lockedPayment->paid_at ? $lockedPayment->paid_at->copy() : $now->copy();

                $lockedPayment->update([
                    'status' => 'PAID',
                    'paid_at' => $paidAt,
                    'payment_channel' => $paymentType,
                    'gateway_transaction_id' => $gatewayTxId ?: $lockedPayment->gateway_transaction_id,
                    'gateway_status' => $transactionStatus,
                    'latest_payload' => $payload,
                    'last_notification_at' => $now,
                ]);

                $lockedTransaction->delivery_due_at = $lockedTransaction->delivery_due_at
                    ? $lockedTransaction->delivery_due_at->copy()
                    : $paidAt->copy()->addHour();
                $lockedTransaction->save();

                $channelUpper = strtoupper($paymentType);
                $this->transactionService->transitionStatus(
                    $lockedTransaction,
                    TransactionStatus::PAID,
                    null,
                    "Dana escrow berhasil diamankan via Midtrans ({$channelUpper})"
                );
            });

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
            $payment->update([
                'gateway_status' => 'pending',
                'payment_channel' => $paymentType ?: $payment->payment_channel,
                'gateway_transaction_id' => $gatewayTxId ?: $payment->gateway_transaction_id,
                'latest_payload' => $payload,
                'last_notification_at' => now(),
            ]);

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

            DB::transaction(function () use ($payment, $transaction, $payload, $transactionStatus, $newPaymentStatus) {
                $now = now();

                $payment->update([
                    'status' => $newPaymentStatus,
                    'gateway_status' => $transactionStatus,
                    'latest_payload' => $payload,
                    'last_notification_at' => $now,
                ]);

                if ($transaction->status === TransactionStatus::WAITING_PAYMENT) {
                    $this->transactionService->transitionStatus(
                        $transaction,
                        TransactionStatus::CANCELLED,
                        null,
                        "Transaksi dibatalkan karena pembayaran status: {$transactionStatus} pada Midtrans."
                    );
                }
            });

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
     * Query Midtrans API directly for transaction status.
     */
    protected function queryMidtransTransactionStatus(string $orderId): object
    {
        $this->configureMidtrans();

        return \Midtrans\Transaction::status($orderId);
    }

    /**
     * Query Midtrans API directly for the current transaction status and sync the database.
     *
     * @return array{status: string, message: string}
     */
    public function syncPaymentStatus(Transaction $transaction): array
    {
        $payment = $transaction->payment;
        if (! $payment || empty($payment->gateway_order_id)) {
            return [
                'status' => 'not_found',
                'message' => 'Belum ada data pembayaran gateway untuk transaksi ini.',
            ];
        }

        if ($payment->status === 'PAID' && $transaction->status !== TransactionStatus::WAITING_PAYMENT) {
            return [
                'status' => 'already_paid',
                'message' => 'Pembayaran telah diverifikasi sebelumnya.',
            ];
        }

        try {
            $statusObj = $this->queryMidtransTransactionStatus($payment->gateway_order_id);
            $payload = json_decode(json_encode($statusObj), true);

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
}
