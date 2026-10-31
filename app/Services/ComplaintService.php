<?php

namespace App\Services;

use App\Contracts\Services\ComplaintServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Complaint;
use App\Models\ComplaintResponse;
use App\Models\EvidenceFile;
use App\Models\SellerPayoutAccount;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ComplaintService implements ComplaintServiceInterface
{
    public function __construct(
        protected NotificationServiceInterface $notificationService
    ) {}

    /**
     * Buyer submits an official complaint with mandatory evidence (UC-16 / KF-06).
     */
    public function fileComplaint(
        Transaction $transaction,
        User $buyer,
        string $reason,
        UploadedFile $evidenceFile
    ): Complaint {
        return DB::transaction(function () use ($transaction, $buyer, $reason, $evidenceFile): Complaint {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $agreement = $lockedTx->agreement;
            if (! $agreement || (int) $agreement->buyer_id !== (int) $buyer->user_id) {
                throw new AuthorizationException('Hanya pembeli transaksi ini yang berhak mengajukan komplain.');
            }

            $txStatus = $lockedTx->status instanceof TransactionStatus
                ? $lockedTx->status->value
                : (string) $lockedTx->status;

            if ($txStatus !== TransactionStatus::DELIVERED->value) {
                throw new InvalidArgumentException("Komplain hanya dapat diajukan saat pesanan berstatus DELIVERED (status saat ini: {$txStatus}).");
            }

            if ($lockedTx->complaint()->exists()) {
                throw new InvalidArgumentException('Transaksi ini sudah memiliki komplain terdaftar. Maksimal 1 komplain per transaksi.');
            }

            $now = now();

            if ($lockedTx->confirmation_due_at !== null) {
                $hasGracePeriod = $lockedTx->deadlineEvents()
                    ->where('event_type', 'CONFIRMATION_GRACE_PERIOD')
                    ->exists();

                $effectiveDeadline = $lockedTx->confirmation_due_at;
                if (! $hasGracePeriod && $now->greaterThan($lockedTx->confirmation_due_at) && $now->lte($lockedTx->confirmation_due_at->copy()->addMinutes(30))) {
                    $effectiveDeadline = $lockedTx->confirmation_due_at->copy()->addMinutes(30);
                }

                if ($now->greaterThan($effectiveDeadline)) {
                    throw new InvalidArgumentException('Batas waktu pengajuan komplain (termasuk masa tenggang konfirmasi) telah berakhir.');
                }
            }

            $path = $evidenceFile->store('evidence/complaints', 'public');

            $complaint = Complaint::create([
                'transaction_id' => $lockedTx->transaction_id,
                'raised_by' => $buyer->user_id,
                'reason' => trim($reason),
                'status' => 'OPEN',
            ]);

            EvidenceFile::create([
                'uploaded_by' => $buyer->user_id,
                'complaint_id' => $complaint->complaint_id,
                'file_name' => $evidenceFile->getClientOriginalName(),
                'file_url' => $path,
                'mime_type' => $evidenceFile->getClientMimeType() ?: ($evidenceFile->getMimeType() ?: 'application/octet-stream'),
                'uploaded_at' => $now,
            ]);

            $lockedTx->update([
                'status' => TransactionStatus::DISPUTED->value,
                'confirmation_due_at' => null,
            ]);

            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => TransactionStatus::DELIVERED->value,
                'to_status' => TransactionStatus::DISPUTED->value,
                'changed_by' => $buyer->user_id,
                'note' => 'Pembeli mengajukan komplain resmi: '.trim($reason),
                'created_at' => $now,
            ]);

            TransactionDeadlineEvent::create([
                'transaction_id' => $lockedTx->transaction_id,
                'event_type' => 'SELLER_COMPLAINT_RESPONSE_DUE',
                'previous_deadline' => null,
                'new_deadline' => $now->copy()->addHours(24),
                'extension_minutes' => 1440,
                'reason' => 'Tenggat 24 jam bagi Penjual untuk memberikan tanggapan/klarifikasi atas komplain Pembeli.',
                'granted_by' => $buyer->user_id,
                'is_flagged_for_admin' => false,
                'created_at' => $now,
            ]);

            // Notifikasi ke Penjual
            $this->notificationService->notify(
                $agreement->seller_id,
                $lockedTx->transaction_id,
                'COMPLAINT_DEADLINE_WARNING',
                "Pembeli mengajukan komplain atas pesanan [{$agreement->item_title}]. Anda memiliki waktu 24 jam untuk memberikan tanggapan resmi."
            );

            // Notifikasi ke seluruh Admin
            $admins = User::where('account_type', 'ADMIN')->get();
            foreach ($admins as $admin) {
                $this->notificationService->notify(
                    $admin->user_id,
                    $lockedTx->transaction_id,
                    'DISPUTE_FILED',
                    "Sengketa baru diajukan untuk transaksi #{$lockedTx->transaction_id} [{$agreement->item_title}]."
                );
            }

            return $complaint;
        });
    }

    /**
     * Seller submits a response/clarification with optional evidence within 24 hours (UC-17 / KF-07).
     */
    public function respondToComplaint(
        Complaint $complaint,
        User $seller,
        string $responseText,
        ?UploadedFile $evidenceFile = null
    ): ComplaintResponse {
        return DB::transaction(function () use ($complaint, $seller, $responseText, $evidenceFile): ComplaintResponse {
            /** @var Complaint $lockedComplaint */
            $lockedComplaint = Complaint::where('complaint_id', $complaint->complaint_id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $lockedComplaint->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            $agreement = $lockedTx->agreement;
            if (! $agreement || (int) $agreement->seller_id !== (int) $seller->user_id) {
                throw new AuthorizationException('Hanya penjual transaksi ini yang berhak memberikan tanggapan komplain.');
            }

            if ($lockedComplaint->isResolved()) {
                throw new InvalidArgumentException('Komplain ini telah selesai dan tidak dapat ditanggapi lagi.');
            }

            $now = now();

            $response = ComplaintResponse::create([
                'complaint_id' => $lockedComplaint->complaint_id,
                'responder_id' => $seller->user_id,
                'response_text' => trim($responseText),
                'created_at' => $now,
            ]);

            if ($evidenceFile) {
                $path = $evidenceFile->store('evidence/responses', 'public');
                EvidenceFile::create([
                    'uploaded_by' => $seller->user_id,
                    'response_id' => $response->response_id,
                    'file_name' => $evidenceFile->getClientOriginalName(),
                    'file_url' => $path,
                    'mime_type' => $evidenceFile->getClientMimeType() ?: ($evidenceFile->getMimeType() ?: 'application/octet-stream'),
                    'uploaded_at' => $now,
                ]);
            }

            // Tanggapan sah pertama memulai tenggat admin 24 jam (AC #3)
            $sellerResponsesCount = ComplaintResponse::where('complaint_id', $lockedComplaint->complaint_id)
                ->where('responder_id', $seller->user_id)
                ->count();

            if ($sellerResponsesCount === 1) {
                TransactionDeadlineEvent::create([
                    'transaction_id' => $lockedTx->transaction_id,
                    'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
                    'previous_deadline' => null,
                    'new_deadline' => $now->copy()->addHours(24),
                    'extension_minutes' => 1440,
                    'reason' => 'Penjual telah memberikan tanggapan resmi. Tenggat waktu 24 jam bagi Admin untuk meninjau dan memutuskan sengketa.',
                    'granted_by' => $seller->user_id,
                    'is_flagged_for_admin' => false,
                    'created_at' => $now,
                ]);
            }

            // Notifikasi ke Pembeli
            $this->notificationService->notify(
                $agreement->buyer_id,
                $lockedTx->transaction_id,
                'COMPLAINT_RESPONDED',
                "Penjual telah memberikan tanggapan resmi atas komplain transaksi [{$agreement->item_title}]."
            );

            // Notifikasi ke Admin
            $admins = User::where('account_type', 'ADMIN')->get();
            foreach ($admins as $admin) {
                $this->notificationService->notify(
                    $admin->user_id,
                    $lockedTx->transaction_id,
                    'SELLER_DISPUTE_RESPONDED',
                    "Penjual telah menanggapi sengketa transaksi #{$lockedTx->transaction_id}. Tenggat keputusan Admin 24 jam telah dimulai."
                );
            }

            return $response;
        });
    }

    /**
     * Admin requests additional evidence and extends deadline (UC-18 / KF-09).
     */
    public function requestAdditionalEvidence(
        Complaint $complaint,
        User $admin,
        string $reason,
        int $extensionHours = 24
    ): Complaint {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang berwenang meminta bukti tambahan.');
        }

        if ($complaint->isResolved()) {
            throw new InvalidArgumentException('Sengketa ini telah selesai diputuskan dan tidak dapat ditinjau ulang.');
        }

        $now = now();

        $complaint->update([
            'status' => 'REVIEWING',
        ]);

        $prevDeadline = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
            ->where('event_type', 'ADMIN_DISPUTE_DECISION_DUE')
            ->latest('deadline_event_id')
            ->value('new_deadline');

        $newDeadline = $now->copy()->addHours($extensionHours);

        TransactionDeadlineEvent::create([
            'transaction_id' => $complaint->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
            'previous_deadline' => $prevDeadline,
            'new_deadline' => $newDeadline,
            'extension_minutes' => $extensionHours * 60,
            'reason' => 'Admin meminta bukti/klarifikasi tambahan: '.trim($reason),
            'granted_by' => $admin->user_id,
            'is_flagged_for_admin' => false,
            'created_at' => $now,
        ]);

        $agreement = $complaint->transaction?->agreement;
        if ($agreement) {
            $msg = "Admin meminta informasi/bukti tambahan terkait sengketa [{$agreement->item_title}]: ".trim($reason);
            $this->notificationService->notify(
                $agreement->buyer_id,
                $complaint->transaction_id,
                'DISPUTE_EVIDENCE_REQUESTED',
                $msg
            );
            $this->notificationService->notify(
                $agreement->seller_id,
                $complaint->transaction_id,
                'DISPUTE_EVIDENCE_REQUESTED',
                $msg
            );
        }

        return $complaint;
    }

    /**
     * Admin makes final dispute resolution decision (PAYOUT or REFUND) (UC-18 / KF-10).
     * Prepares exactly one Settlement in PLANNED status (Contract MT-A07).
     */
    public function resolveComplaint(
        Complaint $complaint,
        User $admin,
        string $decision,
        string $decisionReason
    ): Complaint {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang berwenang memutuskan sengketa.');
        }

        $decision = strtoupper(trim($decision));
        if (! in_array($decision, ['PAYOUT', 'REFUND'], true)) {
            throw new InvalidArgumentException("Keputusan sengketa harus 'PAYOUT' atau 'REFUND'.");
        }

        return DB::transaction(function () use ($complaint, $admin, $decision, $decisionReason): Complaint {
            /** @var Complaint $lockedComplaint */
            $lockedComplaint = Complaint::where('complaint_id', $complaint->complaint_id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $lockedComplaint->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedComplaint->isResolved()) {
                throw new InvalidArgumentException('Sengketa ini sudah diputuskan sebelumnya.');
            }

            if ($lockedComplaint->settlement()->exists() || $lockedTx->settlement()->exists()) {
                throw new LogicException('Settlement untuk sengketa transaksi ini sudah pernah dibuat.');
            }

            $now = now();
            $agreement = $lockedTx->agreement;

            $lockedComplaint->update([
                'status' => 'RESOLVED',
                'decision' => $decision,
                'decision_reason' => trim($decisionReason),
                'decided_by' => $admin->user_id,
                'decided_at' => $now,
            ]);

            if ($decision === 'PAYOUT') {
                $lockedTx->update([
                    'status' => TransactionStatus::CONFIRMED->value,
                    'confirmed_at' => $now,
                ]);

                TransactionStatusHistory::create([
                    'transaction_id' => $lockedTx->transaction_id,
                    'from_status' => TransactionStatus::DISPUTED->value,
                    'to_status' => TransactionStatus::CONFIRMED->value,
                    'changed_by' => $admin->user_id,
                    'note' => 'Keputusan sengketa Admin: PAYOUT ke Penjual. Alasan: '.trim($decisionReason),
                    'created_at' => $now,
                ]);

                $seller = $agreement->seller;
                $payoutAccount = SellerPayoutAccount::where('seller_id', $seller->user_id)
                    ->where('is_active', true)
                    ->first();

                $snapshot = $payoutAccount ? $payoutAccount->toSettlementSnapshot() : [
                    'destination_type' => 'BANK',
                    'destination_provider' => 'MANUAL_TRANSFER',
                    'destination_number' => '-',
                    'destination_holder' => $seller->name ?? ($seller->username ?? 'Penjual'),
                    'destination_qris_image_url' => null,
                ];

                Settlement::create([
                    'transaction_id' => $lockedTx->transaction_id,
                    'complaint_id' => $lockedComplaint->complaint_id,
                    'settlement_type' => 'PAYOUT',
                    'destination_type' => $snapshot['destination_type'],
                    'destination_provider' => $snapshot['destination_provider'],
                    'destination_number' => $snapshot['destination_number'],
                    'destination_holder' => $snapshot['destination_holder'],
                    'destination_qris_image_url' => $snapshot['destination_qris_image_url'],
                    'amount' => $agreement->seller_net,
                    'gross_amount' => $agreement->base_price,
                    'keep_deducted_amount' => 0,
                    'status' => 'PLANNED', // Sesuai kontrak MT-A07: hanya PLANNED, belum selesai
                    'recorded_by' => $admin->user_id,
                    'note' => trim($decisionReason),
                    'payout_due_at' => $now->copy()->addHour(),
                ]);
            } else { // REFUND
                $lockedTx->update([
                    'status' => TransactionStatus::REFUNDED->value,
                    'closed_at' => $now,
                ]);

                TransactionStatusHistory::create([
                    'transaction_id' => $lockedTx->transaction_id,
                    'from_status' => TransactionStatus::DISPUTED->value,
                    'to_status' => TransactionStatus::REFUNDED->value,
                    'changed_by' => $admin->user_id,
                    'note' => 'Keputusan sengketa Admin: REFUND ke Pembeli. Alasan: '.trim($decisionReason),
                    'created_at' => $now,
                ]);

                $buyer = $agreement->buyer;
                Settlement::create([
                    'transaction_id' => $lockedTx->transaction_id,
                    'complaint_id' => $lockedComplaint->complaint_id,
                    'settlement_type' => 'REFUND',
                    'destination_type' => 'BANK',
                    'destination_provider' => 'MIDTRANS_REFUND',
                    'destination_number' => '-',
                    'destination_holder' => $buyer->name ?? ($buyer->username ?? 'Pembeli'),
                    'destination_qris_image_url' => null,
                    'amount' => $agreement->buyer_total,
                    'gross_amount' => $agreement->buyer_total,
                    'keep_deducted_amount' => 0,
                    'status' => 'PLANNED', // Sesuai kontrak MT-A07: hanya PLANNED, belum selesai
                    'recorded_by' => $admin->user_id,
                    'note' => trim($decisionReason),
                    'payout_due_at' => $now->copy()->addHour(),
                ]);
            }

            if ($agreement) {
                $this->notificationService->notifyDisputeDecided(
                    $agreement->buyer_id,
                    $lockedTx->transaction_id,
                    $agreement->item_title,
                    $decision
                );
                $this->notificationService->notifyDisputeDecided(
                    $agreement->seller_id,
                    $lockedTx->transaction_id,
                    $agreement->item_title,
                    $decision
                );
            }

            return $lockedComplaint;
        });
    }
}
