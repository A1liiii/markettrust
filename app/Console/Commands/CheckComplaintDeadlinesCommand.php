<?php

namespace App\Console\Commands;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Complaint;
use App\Models\TransactionDeadlineEvent;
use App\Models\User;
use Illuminate\Console\Command;

class CheckComplaintDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check-complaint-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa batas waktu respons penjual (24 jam) dan tenggat keputusan sengketa admin (UC-17B & UC-18)';

    /**
     * Execute the console command.
     */
    public function handle(NotificationServiceInterface $notificationService): int
    {
        $now = now();
        $this->info("Memulai pemeriksaan batas waktu sengketa transaksi pada {$now->toDateTimeString()}...");

        $activeComplaints = Complaint::with(['transaction.agreement', 'responses'])
            ->whereIn('status', ['OPEN', 'REVIEWING'])
            ->get();

        $admins = User::where('account_type', 'ADMIN')->get();

        foreach ($activeComplaints as $complaint) {
            $sellerDeadline = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
                ->where('event_type', 'SELLER_COMPLAINT_RESPONSE_DUE')
                ->latest('deadline_event_id')
                ->first();

            $adminDecisionDue = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
                ->where('event_type', 'ADMIN_DISPUTE_DECISION_DUE')
                ->latest('deadline_event_id')
                ->first();

            // 1. Jika penjual belum merespons dan batas waktu 24 jam seller telah habis (AC #4)
            // Tanpa tanggapan seller, tenggat admin mulai dari akhir tenggat seller; tidak otomatis memenangkan buyer.
            if ($sellerDeadline && $sellerDeadline->new_deadline && $now->greaterThan($sellerDeadline->new_deadline)) {
                $hasSellerResponse = $complaint->responses()->exists();

                if (! $hasSellerResponse && ! $adminDecisionDue) {
                    $adminDeadline = $sellerDeadline->new_deadline->copy()->addHours(24);

                    TransactionDeadlineEvent::create([
                        'transaction_id' => $complaint->transaction_id,
                        'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
                        'previous_deadline' => $sellerDeadline->new_deadline,
                        'new_deadline' => $adminDeadline,
                        'extension_minutes' => 1440,
                        'reason' => 'Penjual tidak memberikan tanggapan resmi dalam 24 jam. Tenggat keputusan Admin 24 jam dimulai dari akhir tenggat Penjual.',
                        'granted_by' => null,
                        'is_flagged_for_admin' => true,
                        'created_at' => $now,
                    ]);

                    foreach ($admins as $admin) {
                        $notificationService->notify(
                            $admin->user_id,
                            $complaint->transaction_id,
                            'SELLER_COMPLAINT_TIMED_OUT',
                            "Batas waktu tanggapan Penjual untuk transaksi #{$complaint->transaction_id} telah habis tanpa respons. Sengketa dialihkan ke antrean keputusan Admin."
                        );
                    }

                    $this->warn("Tenggat seller berakhir tanpa tanggapan pada transaksi #{$complaint->transaction_id}. Memulai tenggat admin.");
                }
            }

            // 2. Periksa keterlambatan keputusan Admin jika telah melewati batas keputusan (AC #4)
            $latestAdminDeadline = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
                ->where('event_type', 'ADMIN_DISPUTE_DECISION_DUE')
                ->latest('deadline_event_id')
                ->first();

            if ($latestAdminDeadline && $latestAdminDeadline->new_deadline && $now->greaterThan($latestAdminDeadline->new_deadline)) {
                $alreadyFlaggedOverdue = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
                    ->where('event_type', 'ADMIN_DISPUTE_OVERDUE')
                    ->exists();

                if (! $alreadyFlaggedOverdue) {
                    TransactionDeadlineEvent::create([
                        'transaction_id' => $complaint->transaction_id,
                        'event_type' => 'ADMIN_DISPUTE_OVERDUE',
                        'previous_deadline' => $latestAdminDeadline->new_deadline,
                        'new_deadline' => null,
                        'extension_minutes' => null,
                        'reason' => 'Keputusan resolusi sengketa oleh Admin telah melewati batas waktu SLA 24 jam.',
                        'granted_by' => null,
                        'is_flagged_for_admin' => true,
                        'created_at' => $now,
                    ]);

                    foreach ($admins as $admin) {
                        $notificationService->notify(
                            $admin->user_id,
                            $complaint->transaction_id,
                            'ADMIN_DISPUTE_OVERDUE_WARNING',
                            "PERINGATAN: Sengketa transaksi #{$complaint->transaction_id} telah melewati batas waktu 24 jam keputusan Admin!"
                        );
                    }

                    $this->error("Peringatan keterlambatan admin dicatat untuk transaksi #{$complaint->transaction_id}.");
                }
            }
        }

        $this->info('Pemeriksaan batas waktu sengketa selesai.');

        return Command::SUCCESS;
    }
}
