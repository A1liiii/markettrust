<?php

namespace App\Console\Commands;

use App\Contracts\Services\PaymentServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Console\Command;

class CheckPaymentDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check-payment-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rekonsiliasi dan batalkan otomatis transaksi WAITING_PAYMENT yang melewati batas waktu 1 jam (UC-13C)';

    /**
     * Execute the console command.
     */
    public function handle(PaymentServiceInterface $paymentService): int
    {
        $now = now();

        $overdueTransactions = Transaction::query()
            ->whereIn('status', [TransactionStatus::WAITING_PAYMENT, 'WAITING_PAYMENT'])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', $now)
            ->with(['payment', 'agreement'])
            ->get();

        if ($overdueTransactions->isEmpty()) {
            $this->info('Tidak ada transaksi WAITING_PAYMENT yang melewati batas waktu pembayaran.');

            return self::SUCCESS;
        }

        $expiredCount = 0;
        $paidCount = 0;
        $heldCount = 0;
        $uncertainCount = 0;

        foreach ($overdueTransactions as $transaction) {
            $result = $paymentService->expireOverduePayment($transaction);
            $status = $result['status'] ?? '';

            if ($status === 'expired') {
                $expiredCount++;
            } elseif ($status === 'paid') {
                $paidCount++;
            } elseif ($status === 'held_for_reconciliation') {
                $heldCount++;
            } elseif ($status === 'uncertain') {
                $uncertainCount++;
            }
        }

        $this->info(
            "Rekonsiliasi batas pembayaran selesai: {$expiredCount} expired/cancelled, "
            ."{$paidCount} paid, {$heldCount} held for reconciliation, {$uncertainCount} uncertain (ditunda)."
        );

        return self::SUCCESS;
    }
}
