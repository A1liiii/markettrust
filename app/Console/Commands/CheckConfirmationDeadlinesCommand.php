<?php

namespace App\Console\Commands;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use Illuminate\Console\Command;

class CheckConfirmationDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check-confirmation-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa tenggat konfirmasi penerimaan Buyer, aktifkan masa tenggang 30 menit, dan eksekusi Auto-Confirm (UC-15B)';

    /**
     * Execute the console command.
     */
    public function handle(
        TransactionServiceInterface $transactionService,
        NotificationServiceInterface $notificationService
    ): int {
        $now = now();

        $overdueTransactions = Transaction::query()
            ->whereIn('status', [TransactionStatus::DELIVERED, 'DELIVERED'])
            ->whereNull('confirmed_at')
            ->whereNotNull('confirmation_due_at')
            ->where('confirmation_due_at', '<=', $now)
            ->with(['agreement.buyer', 'agreement.seller', 'deadlineEvents', 'complaint'])
            ->get();

        if ($overdueTransactions->isEmpty()) {
            $this->info('Tidak ada transaksi DELIVERED yang melewati batas tenggat konfirmasi.');

            return self::SUCCESS;
        }

        $processedCount = 0;

        foreach ($overdueTransactions as $transaction) {
            // Guard Komplain Aktif: komplain aktif menahan konfirmasi & antrean payout
            if ($transaction->hasActiveComplaint()) {
                $this->warn("Transaksi #{$transaction->transaction_id} dilewati karena memiliki komplain aktif.");

                continue;
            }

            $hasGracePeriod = $transaction->deadlineEvents()
                ->where('event_type', 'BUYER_GRACE_PERIOD')
                ->exists();

            if (! $hasGracePeriod) {
                // Fase 1: Batas 1 jam terlewati -> Berikan tambahan waktu 30 menit (grace deadline)
                $oldDeadline = $transaction->confirmation_due_at;
                $newDeadline = $oldDeadline->copy()->addMinutes(30);

                if ($newDeadline->lessThanOrEqualTo($now)) {
                    $newDeadline = $now->copy()->addMinutes(30);
                }

                $transaction->update([
                    'confirmation_due_at' => $newDeadline,
                ]);

                TransactionDeadlineEvent::create([
                    'transaction_id' => $transaction->transaction_id,
                    'event_type' => 'BUYER_GRACE_PERIOD',
                    'previous_deadline' => $oldDeadline,
                    'new_deadline' => $newDeadline,
                    'extension_minutes' => 30,
                    'reason' => 'Masa tenggang 30 menit diberikan kepada Pembeli sebelum konfirmasi otomatis oleh sistem.',
                    'granted_by' => null,
                    'is_flagged_for_admin' => false,
                    'created_at' => $now,
                ]);

                $buyerId = $transaction->agreement?->buyer_id;
                $itemTitle = $transaction->agreement?->item_title ?? 'Produk Digital';

                if ($buyerId) {
                    $notificationService->notifyBuyerGracePeriod($buyerId, $transaction->transaction_id, $itemTitle);
                }

                $this->info("Transaksi #{$transaction->transaction_id}: Masa tenggang 30 menit telah diberikan.");
            } else {
                // Fase 2: Masa tenggang 30 menit telah berakhir tanpa respons -> Jalankan Auto-Confirm
                $transactionService->autoConfirmOrder($transaction);
                $this->info("Transaksi #{$transaction->transaction_id}: Berhasil disetujui otomatis oleh sistem (Auto-Confirm).");
            }

            $processedCount++;
        }

        $this->info("Selesai memproses {$processedCount} transaksi tenggat konfirmasi.");

        return self::SUCCESS;
    }
}
