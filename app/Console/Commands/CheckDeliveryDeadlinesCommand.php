<?php

namespace App\Console\Commands;

use App\Contracts\Services\NotificationServiceInterface;
use App\Enums\NotificationEventType;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\User;
use Illuminate\Console\Command;

class CheckDeliveryDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check-delivery-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa batas tenggat penyerahan transaksi PAID dan kirim peringatan keterlambatan (UC-14B)';

    /**
     * Execute the console command.
     */
    public function handle(NotificationServiceInterface $notificationService): int
    {
        $now = now();

        $overdueTransactions = Transaction::query()
            ->whereIn('status', [TransactionStatus::PAID, 'PAID'])
            ->whereNull('delivered_at')
            ->whereNotNull('delivery_due_at')
            ->where('delivery_due_at', '<=', $now)
            ->with(['agreement.seller', 'agreement.buyer'])
            ->get();

        if ($overdueTransactions->isEmpty()) {
            $this->info('Tidak ada transaksi PAID yang melewati batas tenggat penyerahan.');

            return self::SUCCESS;
        }

        $admins = User::where('account_type', 'ADMIN')->get();
        $processedCount = 0;

        foreach ($overdueTransactions as $transaction) {
            $itemTitle = $transaction->agreement?->item_title ?? 'Produk Digital';
            $sellerId = $transaction->agreement?->seller_id;

            if ($sellerId) {
                // Peringatan ke Penjual (idempotent, 1 kali per transaksi)
                $notificationService->notifySellerDeliveryWarning(
                    $sellerId,
                    $transaction->transaction_id,
                    $itemTitle,
                    0
                );
            }

            // Peringatan ke Admin (idempotent, 1 kali per transaksi)
            $adminMessage = "Peringatan Admin: Batas waktu penyerahan transaksi #{$transaction->transaction_id} ('".mb_substr($itemTitle, 0, 40)."') telah terlewati tanpa penyerahan.";
            foreach ($admins as $admin) {
                $notificationService->notify(
                    $admin->user_id,
                    $transaction->transaction_id,
                    NotificationEventType::SELLER_DELIVERY_WARNING,
                    $adminMessage,
                    null,
                    true
                );
            }

            // Catat event warning jika belum ada
            $alreadyWarned = $transaction->deadlineEvents()
                ->where('event_type', 'DELIVERY_WARNING')
                ->exists();

            if (! $alreadyWarned) {
                TransactionDeadlineEvent::create([
                    'transaction_id' => $transaction->transaction_id,
                    'event_type' => 'DELIVERY_WARNING',
                    'previous_deadline' => $transaction->delivery_due_at,
                    'new_deadline' => null,
                    'extension_minutes' => null,
                    'reason' => 'Peringatan batas waktu penyerahan dikirim ke Penjual dan Admin.',
                    'granted_by' => null,
                    'is_flagged_for_admin' => false,
                    'created_at' => $now,
                ]);
            }

            // Periksa jika pernah ada perpanjangan waktu dan perpanjangan tersebut kini telah berakhir
            $hasExtension = $transaction->deadlineEvents()
                ->where('event_type', 'DELIVERY_EXTENSION')
                ->exists();

            $alreadyFlagged = $transaction->deadlineEvents()
                ->where('event_type', 'DELIVERY_OVERDUE')
                ->exists();

            if ($hasExtension && ! $alreadyFlagged) {
                TransactionDeadlineEvent::create([
                    'transaction_id' => $transaction->transaction_id,
                    'event_type' => 'DELIVERY_OVERDUE',
                    'previous_deadline' => $transaction->delivery_due_at,
                    'new_deadline' => null,
                    'extension_minutes' => null,
                    'reason' => 'Tambahan waktu penyerahan telah berakhir tanpa penyerahan resmi dari Penjual.',
                    'granted_by' => null,
                    'is_flagged_for_admin' => true,
                    'created_at' => $now,
                ]);
            }

            $processedCount++;
        }

        $this->info("Berhasil memproses {$processedCount} transaksi keterlambatan penyerahan.");

        return self::SUCCESS;
    }
}
