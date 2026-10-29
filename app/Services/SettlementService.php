<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\Contracts\Services\SettlementServiceInterface;
use App\DTOs\PayoutDestinationDTO;
use App\Enums\NotificationEventType;
use App\Enums\PayoutDestinationType;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\EvidenceFile;
use App\Models\KeepDeduction;
use App\Models\KeepObligation;
use App\Models\Settlement;
use App\Models\SettlementAttempt;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettlementService implements SettlementServiceInterface
{
    public function __construct(
        protected PayoutDestinationServiceInterface $payoutDestinationService,
        protected NotificationServiceInterface $notificationService
    ) {}

    /**
     * Hitung pratinjau nominal payout penjual, alokasi potongan KEEP OPEN dari transaksi asal lain, dan kelengkapan tujuan.
     *
     * @return array{
     *     gross_amount: float,
     *     keep_deducted_amount: float,
     *     net_amount: float,
     *     total_open_keep_before: float,
     *     remaining_keep_after: float,
     *     allocations: array<int, array<string, mixed>>,
     *     has_valid_destination: bool,
     *     destination: array<string, mixed>|null
     * }
     */
    public function calculatePayoutPreview(Transaction $transaction): array
    {
        $transaction->loadMissing(['agreement.seller.activePayoutAccount']);
        $agreement = $transaction->agreement;

        if (! $agreement) {
            throw new InvalidArgumentException('Transaksi tidak memiliki kesepakatan (agreement) yang valid.');
        }

        $grossAmount = round(max(0.0, (float) $agreement->seller_net), 2);
        $seller = $agreement->seller;

        $openObligations = $this->getEligibleOpenKeepObligationsForPayout(
            (int) $agreement->seller_id,
            (int) $transaction->transaction_id
        );

        $allocationResult = $this->buildKeepAllocations($grossAmount, $openObligations);

        $destinationDto = $seller ? $this->payoutDestinationService->getDestinationForUser($seller) : null;
        $hasValidDestination = $destinationDto !== null && $this->payoutDestinationService->validateDestination($destinationDto);

        return [
            'gross_amount' => $grossAmount,
            'keep_deducted_amount' => $allocationResult['keep_deducted_amount'],
            'net_amount' => $allocationResult['net_amount'],
            'total_open_keep_before' => $allocationResult['total_open_keep_before'],
            'remaining_keep_after' => $allocationResult['remaining_keep_after'],
            'allocations' => $allocationResult['allocations'],
            'has_valid_destination' => $hasValidDestination,
            'destination' => $destinationDto ? $this->destinationDtoToSnapshot($destinationDto) : null,
        ];
    }

    /**
     * Siapkan atau sinkronkan ulang tujuan payout aktif Seller ke record settlement yang belum COMPLETED.
     */
    public function prepareOrSyncPayoutSettlement(Transaction $transaction, ?User $actor = null): Settlement
    {
        if ($actor !== null && ! $actor->isAdmin()) {
            throw new AuthorizationException('Hanya Administrator yang berwenang menyiapkan atau menyinkronkan settlement payout.');
        }

        return DB::transaction(function () use ($transaction, $actor) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->with(['agreement.seller.activePayoutAccount', 'complaint', 'settlement'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransactionEligibleForPayout($lockedTx);

            $seller = $lockedTx->agreement->seller;
            $destinationDto = $seller ? $this->payoutDestinationService->getDestinationForUser($seller) : null;

            if (! $destinationDto || ! $this->payoutDestinationService->validateDestination($destinationDto)) {
                throw new InvalidArgumentException('Proses payout ditahan karena tujuan rekening payout aktif Penjual belum tersedia atau belum lengkap.');
            }

            $snapshot = $this->destinationDtoToSnapshot($destinationDto);
            $preview = $this->calculatePayoutPreview($lockedTx);

            /** @var Settlement|null $existing */
            $existing = Settlement::where('transaction_id', $lockedTx->transaction_id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->isCompleted()) {
                throw new InvalidArgumentException('Settlement untuk transaksi ini sudah berstatus COMPLETED dan terkunci.');
            }

            $complaintId = $lockedTx->complaint && strtoupper((string) $lockedTx->complaint->decision) === 'PAYOUT'
                ? $lockedTx->complaint->complaint_id
                : null;

            if ($existing) {
                $oldValue = $existing->only([
                    'destination_type',
                    'destination_provider',
                    'destination_number',
                    'destination_holder',
                    'destination_qris_image_url',
                    'status',
                ]);

                $existing->update(array_merge($snapshot, [
                    'settlement_type' => Settlement::TYPE_PAYOUT,
                    'complaint_id' => $existing->complaint_id ?? $complaintId,
                    'gross_amount' => $preview['gross_amount'],
                    'keep_deducted_amount' => $preview['keep_deducted_amount'],
                    'amount' => $preview['net_amount'],
                    'status' => Settlement::STATUS_READY,
                    'payout_due_at' => $existing->payout_due_at ?? now()->addHour(),
                ]));

                // Tandai percobaan gagal sebelumnya yang meminta update tujuan bahwa tujuan sudah diperbarui
                SettlementAttempt::where('settlement_id', $existing->settlement_id)
                    ->where('requires_destination_update', true)
                    ->update(['requires_destination_update' => false]);

                if ($actor) {
                    AuditLog::create([
                        'actor_id' => $actor->user_id,
                        'action' => 'SYNC_PAYOUT_DESTINATION',
                        'entity_type' => 'settlement',
                        'entity_id' => $existing->settlement_id,
                        'old_value' => $oldValue,
                        'new_value' => $snapshot,
                        'created_at' => now(),
                    ]);
                }

                return $existing->fresh(['attempts', 'keepDeductions']);
            }

            return Settlement::create(array_merge($snapshot, [
                'transaction_id' => $lockedTx->transaction_id,
                'complaint_id' => $complaintId,
                'settlement_type' => Settlement::TYPE_PAYOUT,
                'gross_amount' => $preview['gross_amount'],
                'keep_deducted_amount' => $preview['keep_deducted_amount'],
                'amount' => $preview['net_amount'],
                'status' => Settlement::STATUS_READY,
                'payout_due_at' => now()->addHour(),
            ]));
        });
    }

    /**
     * Catat penyelesaian payout simulatif yang berhasil secara atomik beserta pemotongan KEEP.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordSimulatedPayout(
        Transaction $transaction,
        User $admin,
        array $data,
        ?UploadedFile $evidenceFile = null
    ): Settlement {
        $this->assertAdminActor($admin);

        $referenceNo = trim((string) ($data['reference_no'] ?? ''));
        if ($referenceNo === '') {
            throw new InvalidArgumentException('Nomor referensi bukti pencairan (reference_no) wajib diisi.');
        }

        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        return DB::transaction(function () use ($transaction, $admin, $data, $referenceNo, $note, $evidenceFile) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->with(['agreement.seller.activePayoutAccount', 'complaint'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransactionEligibleForPayout($lockedTx);

            /** @var Settlement|null $existingSettlement */
            $existingSettlement = Settlement::where('transaction_id', $lockedTx->transaction_id)
                ->lockForUpdate()
                ->first();

            if ($existingSettlement && $existingSettlement->isCompleted()) {
                throw new InvalidArgumentException('Settlement untuk transaksi ini sudah berstatus COMPLETED dan tidak dapat diubah atau digandakan.');
            }

            $seller = $lockedTx->agreement->seller;
            $destinationDto = $seller ? $this->payoutDestinationService->getDestinationForUser($seller) : null;

            if (! $destinationDto || ! $this->payoutDestinationService->validateDestination($destinationDto)) {
                throw new InvalidArgumentException('Proses payout ditahan karena tujuan rekening payout aktif Penjual belum tersedia atau belum lengkap.');
            }

            $destinationSnapshot = $this->destinationDtoToSnapshot($destinationDto);
            $grossAmount = round(max(0.0, (float) $lockedTx->agreement->seller_net), 2);

            // Kunci seluruh kewajiban KEEP OPEN milik Seller dari transaksi asal lain secara FIFO
            $lockedObligations = $this->getEligibleOpenKeepObligationsForPayout(
                (int) $lockedTx->agreement->seller_id,
                (int) $lockedTx->transaction_id,
                true
            );

            $allocationResult = $this->buildKeepAllocations($grossAmount, $lockedObligations);
            $keepDeductedAmount = $allocationResult['keep_deducted_amount'];
            $netPayoutAmount = $allocationResult['net_amount'];

            // Validasi jika Admin mengirimkan nominal eksplisit pada request (TC-19-04)
            if (array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== '') {
                $submittedAmount = round((float) $data['amount'], 2);
                if (abs($submittedAmount - $netPayoutAmount) > 0.01) {
                    throw new InvalidArgumentException(sprintf(
                        'Nominal payout yang diajukan (Rp %s) tidak sesuai dengan perhitungan hak bersih setelah potongan KEEP (Rp %s).',
                        number_format($submittedAmount, 0, ',', '.'),
                        number_format($netPayoutAmount, 0, ',', '.')
                    ));
                }
            }

            $now = now();
            $complaintId = $lockedTx->complaint && strtoupper((string) $lockedTx->complaint->decision) === 'PAYOUT'
                ? $lockedTx->complaint->complaint_id
                : null;

            if ($existingSettlement) {
                $existingSettlement->update(array_merge($destinationSnapshot, [
                    'complaint_id' => $existingSettlement->complaint_id ?? $complaintId,
                    'settlement_type' => Settlement::TYPE_PAYOUT,
                    'gross_amount' => $grossAmount,
                    'keep_deducted_amount' => $keepDeductedAmount,
                    'amount' => $netPayoutAmount,
                    'status' => Settlement::STATUS_COMPLETED,
                    'payout_due_at' => $existingSettlement->payout_due_at ?? $now->copy()->addHour(),
                    'recorded_by' => $admin->user_id,
                    'reference_no' => $referenceNo,
                    'note' => $note,
                    'completed_at' => $now,
                ]));
                $settlement = $existingSettlement;
            } else {
                $settlement = Settlement::create(array_merge($destinationSnapshot, [
                    'transaction_id' => $lockedTx->transaction_id,
                    'complaint_id' => $complaintId,
                    'settlement_type' => Settlement::TYPE_PAYOUT,
                    'gross_amount' => $grossAmount,
                    'keep_deducted_amount' => $keepDeductedAmount,
                    'amount' => $netPayoutAmount,
                    'status' => Settlement::STATUS_COMPLETED,
                    'payout_due_at' => $now->copy()->addHour(),
                    'recorded_by' => $admin->user_id,
                    'reference_no' => $referenceNo,
                    'note' => $note,
                    'completed_at' => $now,
                ]));
            }

            // Simpan berkas bukti simulasi ke evidence_files.settlement_id
            if ($evidenceFile) {
                $this->storeSettlementEvidence($settlement, $admin, $evidenceFile, $now);
            }

            // Finalkan pemotongan KEEP secara atomik
            foreach ($allocationResult['allocations'] as $alloc) {
                /** @var KeepObligation|null $obligation */
                $obligation = $lockedObligations->firstWhere('keep_obligation_id', $alloc['keep_obligation_id']);
                if (! $obligation || $alloc['deducted_amount'] <= 0) {
                    continue;
                }

                KeepDeduction::updateOrCreate(
                    [
                        'keep_obligation_id' => $obligation->keep_obligation_id,
                        'settlement_id' => $settlement->settlement_id,
                    ],
                    [
                        'amount' => $alloc['deducted_amount'],
                        'deducted_at' => $now,
                    ]
                );

                $newRemaining = max(0.0, round((float) $obligation->remaining_amount - (float) $alloc['deducted_amount'], 2));
                $obligation->update([
                    'remaining_amount' => $newRemaining,
                    'status' => $newRemaining <= 0
                        ? KeepObligation::STATUS_SETTLED
                        : KeepObligation::STATUS_OPEN,
                ]);
            }

            // Catat percobaan berhasil di settlement_attempts
            $nextAttemptNo = ((int) $settlement->attempts()->max('attempt_no')) + 1;
            SettlementAttempt::create(array_merge($destinationSnapshot, [
                'settlement_id' => $settlement->settlement_id,
                'attempt_no' => $nextAttemptNo,
                'status' => SettlementAttempt::STATUS_SUCCESS,
                'failure_category' => SettlementAttempt::CATEGORY_NONE,
                'amount_attempted' => $netPayoutAmount,
                'reference_no' => $referenceNo,
                'failure_reason' => null,
                'follow_up_action' => $note ?: 'Payout simulatif berhasil dicatat dan diverifikasi oleh Admin.',
                'requires_destination_update' => false,
                'is_system_disruption_announced' => false,
                'recorded_by' => $admin->user_id,
                'attempted_at' => $now,
            ]));

            // Bersihkan flag requires_destination_update pada attempt sebelumnya jika ada
            SettlementAttempt::where('settlement_id', $settlement->settlement_id)
                ->where('requires_destination_update', true)
                ->update(['requires_destination_update' => false]);

            // Transisikan status transaksi ke COMPLETED secara atomik
            $fromStatus = $lockedTx->status instanceof TransactionStatus ? $lockedTx->status->value : (string) $lockedTx->status;
            $lockedTx->update([
                'status' => TransactionStatus::COMPLETED->value,
                'closed_at' => $now,
            ]);

            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => $fromStatus,
                'to_status' => TransactionStatus::COMPLETED->value,
                'changed_by' => $admin->user_id,
                'note' => sprintf(
                    'Payout simulatif selesai dicatat (Gross: Rp %s, Potongan KEEP: Rp %s, Payout Bersih: Rp %s, Ref: %s).',
                    number_format($grossAmount, 0, ',', '.'),
                    number_format($keepDeductedAmount, 0, ',', '.'),
                    number_format($netPayoutAmount, 0, ',', '.'),
                    $referenceNo
                ),
                'changed_at' => $now,
            ]);

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'RECORD_PAYOUT_COMPLETED',
                'entity_type' => 'settlement',
                'entity_id' => $settlement->settlement_id,
                'old_value' => ['transaction_status' => $fromStatus],
                'new_value' => [
                    'transaction_id' => $lockedTx->transaction_id,
                    'settlement_type' => Settlement::TYPE_PAYOUT,
                    'gross_amount' => $grossAmount,
                    'keep_deducted_amount' => $keepDeductedAmount,
                    'amount' => $netPayoutAmount,
                    'reference_no' => $referenceNo,
                    'status' => Settlement::STATUS_COMPLETED,
                ],
                'created_at' => $now,
            ]);

            // Notifikasi ke Seller dan Buyer
            $itemTitle = (string) ($lockedTx->agreement->item_title ?? 'Produk Digital');
            $shortTitle = mb_substr($itemTitle, 0, 40);

            $this->notificationService->notify(
                (int) $lockedTx->agreement->seller_id,
                (int) $lockedTx->transaction_id,
                NotificationEventType::PAYOUT_READY,
                sprintf(
                    "Payout transaksi #%d ('%s') sebesar Rp %s telah berhasil dicatat (Ref: %s).",
                    $lockedTx->transaction_id,
                    $shortTitle,
                    number_format($netPayoutAmount, 0, ',', '.'),
                    $referenceNo
                ),
                1,
                false
            );

            $this->notificationService->notify(
                (int) $lockedTx->agreement->buyer_id,
                (int) $lockedTx->transaction_id,
                NotificationEventType::PAYOUT_READY,
                sprintf("Transaksi #%d ('%s') telah selesai sepenuhnya (COMPLETED).", $lockedTx->transaction_id, $shortTitle),
                1,
                false
            );

            return $settlement->fresh(['evidenceFiles', 'keepDeductions.keepObligation', 'attempts']);
        });
    }

    /**
     * Catat percobaan payout yang gagal (FAILED) atau belum pasti (UNKNOWN) pada settlement_attempts
     * tanpa memfinalkan potongan KEEP, tanpa menyelesaikan transaksi, dan tanpa membuat KEEP baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordFailedOrUnknownPayoutAttempt(
        Transaction $transaction,
        User $admin,
        array $data
    ): SettlementAttempt {
        $this->assertAdminActor($admin);

        $attemptStatus = strtoupper(trim((string) ($data['status'] ?? SettlementAttempt::STATUS_FAILED)));
        if (! in_array($attemptStatus, [SettlementAttempt::STATUS_FAILED, SettlementAttempt::STATUS_UNKNOWN], true)) {
            throw new InvalidArgumentException('Status percobaan bermasalah harus berupa FAILED atau UNKNOWN.');
        }

        $failureReason = trim((string) ($data['failure_reason'] ?? ''));
        if ($failureReason === '') {
            throw new InvalidArgumentException('Penyebab kegagalan atau ketidakpastian payout wajib diisi.');
        }

        $failureCategory = strtoupper(trim((string) ($data['failure_category'] ?? SettlementAttempt::CATEGORY_SYSTEM)));
        if (! in_array($failureCategory, [
            SettlementAttempt::CATEGORY_SYSTEM,
            SettlementAttempt::CATEGORY_SELLER_DESTINATION,
            SettlementAttempt::CATEGORY_GATEWAY_UNKNOWN,
            SettlementAttempt::CATEGORY_NONE,
        ], true)) {
            $failureCategory = SettlementAttempt::CATEGORY_SYSTEM;
        }

        $followUpAction = trim((string) ($data['follow_up_action'] ?? ''));
        if ($followUpAction === '') {
            $followUpAction = match ($failureCategory) {
                SettlementAttempt::CATEGORY_SELLER_DESTINATION => 'Meminta Penjual memperbaiki rekening payout aktif (UC-26) dan melakukan validasi ulang sebelum retry.',
                SettlementAttempt::CATEGORY_GATEWAY_UNKNOWN => 'Menahan eksekusi ulang sampai mutasi/status kanal terverifikasi agar tidak terjadi pencairan ganda.',
                default => 'Menjadwalkan percobaan ulang (retry) pada settlement yang sama setelah gangguan sistem teratasi.',
            };
        }

        $referenceNo = isset($data['reference_no']) && trim((string) $data['reference_no']) !== ''
            ? trim((string) $data['reference_no'])
            : null;
        $announceDisruption = ! empty($data['announce_disruption']);

        return DB::transaction(function () use (
            $transaction,
            $admin,
            $attemptStatus,
            $failureCategory,
            $failureReason,
            $followUpAction,
            $referenceNo,
            $announceDisruption
        ) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->with(['agreement.seller.activePayoutAccount', 'complaint'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransactionEligibleForPayout($lockedTx);

            /** @var Settlement|null $settlement */
            $settlement = Settlement::where('transaction_id', $lockedTx->transaction_id)
                ->lockForUpdate()
                ->first();

            if ($settlement && $settlement->isCompleted()) {
                throw new InvalidArgumentException('Settlement sudah berstatus COMPLETED sehingga tidak dapat dicatat sebagai gagal.');
            }

            $seller = $lockedTx->agreement->seller;
            $destinationDto = $seller ? $this->payoutDestinationService->getDestinationForUser($seller) : null;
            $destinationSnapshot = $destinationDto
                ? $this->destinationDtoToSnapshot($destinationDto)
                : [
                    'destination_type' => $settlement?->destination_type ?? 'BANK',
                    'destination_provider' => $settlement?->destination_provider,
                    'destination_number' => $settlement?->destination_number,
                    'destination_holder' => $settlement?->destination_holder ?? ($seller?->username ?? 'Seller'),
                    'destination_qris_image_url' => $settlement?->destination_qris_image_url,
                ];

            $preview = $this->calculatePayoutPreview($lockedTx);
            $complaintId = $lockedTx->complaint && strtoupper((string) $lockedTx->complaint->decision) === 'PAYOUT'
                ? $lockedTx->complaint->complaint_id
                : null;
            $now = now();

            if (! $settlement) {
                $settlement = Settlement::create(array_merge($destinationSnapshot, [
                    'transaction_id' => $lockedTx->transaction_id,
                    'complaint_id' => $complaintId,
                    'settlement_type' => Settlement::TYPE_PAYOUT,
                    'gross_amount' => $preview['gross_amount'],
                    'keep_deducted_amount' => $preview['keep_deducted_amount'],
                    'amount' => $preview['net_amount'],
                    'status' => Settlement::STATUS_PROCESSING,
                    'payout_due_at' => $now->copy()->addHour(),
                    'recorded_by' => $admin->user_id,
                    'note' => 'Percobaan payout tercatat '.$attemptStatus.': '.$failureReason,
                ]));
            } else {
                $settlement->update(array_merge($destinationSnapshot, [
                    'complaint_id' => $settlement->complaint_id ?? $complaintId,
                    'gross_amount' => $preview['gross_amount'],
                    'keep_deducted_amount' => $preview['keep_deducted_amount'],
                    'amount' => $preview['net_amount'],
                    'status' => Settlement::STATUS_PROCESSING,
                    'recorded_by' => $admin->user_id,
                    'note' => 'Percobaan payout tercatat '.$attemptStatus.': '.$failureReason,
                ]));
            }

            $nextAttemptNo = ((int) $settlement->attempts()->max('attempt_no')) + 1;
            $requiresDestinationUpdate = $failureCategory === SettlementAttempt::CATEGORY_SELLER_DESTINATION;

            $attempt = SettlementAttempt::create(array_merge($destinationSnapshot, [
                'settlement_id' => $settlement->settlement_id,
                'attempt_no' => $nextAttemptNo,
                'status' => $attemptStatus,
                'failure_category' => $failureCategory,
                'amount_attempted' => $preview['net_amount'],
                'reference_no' => $referenceNo,
                'failure_reason' => $failureReason,
                'follow_up_action' => $followUpAction,
                'requires_destination_update' => $requiresDestinationUpdate,
                'is_system_disruption_announced' => $announceDisruption,
                'recorded_by' => $admin->user_id,
                'attempted_at' => $now,
            ]));

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'RECORD_PAYOUT_ATTEMPT_'.$attemptStatus,
                'entity_type' => 'settlement_attempt',
                'entity_id' => $attempt->attempt_id,
                'old_value' => null,
                'new_value' => [
                    'settlement_id' => $settlement->settlement_id,
                    'transaction_id' => $lockedTx->transaction_id,
                    'attempt_no' => $nextAttemptNo,
                    'status' => $attemptStatus,
                    'failure_category' => $failureCategory,
                    'failure_reason' => $failureReason,
                    'follow_up_action' => $followUpAction,
                    'reference_no' => $referenceNo,
                ],
                'created_at' => $now,
            ]);

            $itemTitle = (string) ($lockedTx->agreement->item_title ?? 'Produk Digital');
            $shortTitle = mb_substr($itemTitle, 0, 40);

            if ($requiresDestinationUpdate && $lockedTx->agreement->seller_id) {
                $this->notificationService->notify(
                    (int) $lockedTx->agreement->seller_id,
                    (int) $lockedTx->transaction_id,
                    NotificationEventType::PAYOUT_READY,
                    "Payout transaksi #{$lockedTx->transaction_id} ('{$shortTitle}') tertahan karena tujuan rekening tidak valid: {$failureReason}. Silakan perbarui Rekening Payout Anda.",
                    1,
                    false
                );
            } else {
                $this->notificationService->notify(
                    (int) $lockedTx->agreement->seller_id,
                    (int) $lockedTx->transaction_id,
                    NotificationEventType::PAYOUT_READY,
                    "Percobaan payout transaksi #{$lockedTx->transaction_id} ('{$shortTitle}') berstatus {$attemptStatus}. Tindak lanjut: {$followUpAction}",
                    1,
                    false
                );
            }

            if ($announceDisruption) {
                $this->announceSystemDisruption(
                    $admin,
                    "Pengumuman Sistem: Terjadi kendala saluran pencairan payout ({$failureReason}). Tim Admin sedang menindaklanjuti pada settlement yang sama.",
                    $lockedTx
                );
            }

            return $attempt;
        });
    }

    /**
     * Kirim pengumuman gangguan sistem payout berdampak luas ke pengguna terkait.
     */
    public function announceSystemDisruption(
        User $admin,
        string $message,
        ?Transaction $relatedTransaction = null
    ): int {
        $this->assertAdminActor($admin);

        $trimmed = trim($message);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Isi pesan pengumuman gangguan sistem wajib diisi.');
        }

        return DB::transaction(function () use ($admin, $trimmed, $relatedTransaction) {
            // Kirim ke seluruh Seller dan Buyer pada transaksi yang sedang menunggu/memproses settlement (CONFIRMED / DISPUTED)
            // atau seluruh pengguna aktif jika tidak ada antrean spesifik
            $pendingTransactions = Transaction::query()
                ->whereIn('status', [TransactionStatus::CONFIRMED->value, TransactionStatus::DISPUTED->value, 'CONFIRMED', 'DISPUTED'])
                ->with('agreement')
                ->get();

            $recipientIds = collect();
            foreach ($pendingTransactions as $tx) {
                if ($tx->agreement?->seller_id) {
                    $recipientIds->push((int) $tx->agreement->seller_id);
                }
                if ($tx->agreement?->buyer_id) {
                    $recipientIds->push((int) $tx->agreement->buyer_id);
                }
            }

            if ($relatedTransaction?->agreement) {
                $recipientIds->push((int) $relatedTransaction->agreement->seller_id);
                $recipientIds->push((int) $relatedTransaction->agreement->buyer_id);
            }

            $recipientIds = $recipientIds->filter()->unique()->values();

            if ($recipientIds->isEmpty()) {
                $recipientIds = User::query()
                    ->where('account_status', 'ACTIVE')
                    ->where('account_type', 'USER')
                    ->pluck('user_id');
            }

            $count = 0;
            foreach ($recipientIds as $userId) {
                $this->notificationService->notify(
                    (int) $userId,
                    $relatedTransaction?->transaction_id,
                    NotificationEventType::PAYOUT_READY,
                    $trimmed,
                    1,
                    false
                );
                $count++;
            }

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'ANNOUNCE_SETTLEMENT_SYSTEM_DISRUPTION',
                'entity_type' => 'settlement',
                'entity_id' => $relatedTransaction?->settlement?->settlement_id ?? ($relatedTransaction?->transaction_id ?? 0),
                'old_value' => null,
                'new_value' => [
                    'message' => $trimmed,
                    'recipients_count' => $count,
                    'related_transaction_id' => $relatedTransaction?->transaction_id,
                ],
                'created_at' => now(),
            ]);

            return $count;
        });
    }

    /**
     * Hitung pratinjau hak pengembalian dana (refund) pembeli berdasarkan keputusan sah komplain atau pembatalan.
     *
     * @return array{
     *     is_eligible: bool,
     *     source: string|null,
     *     gross_amount: float,
     *     refund_amount: float,
     *     responsible_party: string|null,
     *     seller_keep_amount: float
     * }
     */
    public function calculateRefundPreview(Transaction $transaction): array
    {
        $transaction->loadMissing(['agreement', 'complaint', 'cancellations.keepObligation', 'settlement']);
        $agreement = $transaction->agreement;

        if (! $agreement) {
            throw new InvalidArgumentException('Transaksi tidak memiliki kesepakatan (agreement) yang valid.');
        }

        $grossAmount = round((float) $agreement->buyer_total, 2);
        $complaint = $transaction->complaint;
        $approvedCancel = $transaction->approvedPostPaymentCancellation();

        $source = null;
        $responsibleParty = null;
        $sellerKeepAmount = 0.0;

        if ($complaint && strtoupper((string) $complaint->status) === 'RESOLVED' && strtoupper((string) $complaint->decision) === 'REFUND') {
            $source = 'COMPLAINT_DECISION';
            $responsibleParty = 'SELLER';
        } elseif ($approvedCancel) {
            $source = 'POST_PAYMENT_CANCELLATION';
            $responsibleParty = $approvedCancel->responsible_party;
            $sellerKeepAmount = (float) ($approvedCancel->keepObligation?->amount ?? 0.0);
        }

        // Sesuai SKPL terbaru & UC-20: KEEP akibat kesalahan Seller menjadi kewajiban Seller di keep_obligations
        // dan TIDAK memotong refund Buyer.
        $refundAmount = max(0.0, $grossAmount);

        return [
            'is_eligible' => $transaction->isEligibleForRefund(),
            'source' => $source,
            'gross_amount' => $grossAmount,
            'refund_amount' => $refundAmount,
            'responsible_party' => $responsibleParty,
            'seller_keep_amount' => $sellerKeepAmount,
        ];
    }

    /**
     * Catat penyelesaian refund simulatif pembeli secara atomik dari keputusan sah komplain atau pembatalan.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordSimulatedRefund(
        Transaction $transaction,
        User $admin,
        array $data,
        ?UploadedFile $evidenceFile = null
    ): Settlement {
        $this->assertAdminActor($admin);

        $destinationType = strtoupper(trim((string) ($data['destination_type'] ?? '')));
        if (! in_array($destinationType, ['BANK', 'EWALLET', 'QRIS'], true)) {
            throw new InvalidArgumentException('Metode tujuan refund (destination_type) harus berupa BANK, EWALLET, atau QRIS.');
        }

        $destinationHolder = trim((string) ($data['destination_holder'] ?? ''));
        $destinationProvider = isset($data['destination_provider']) ? trim((string) $data['destination_provider']) : null;
        $destinationNumber = isset($data['destination_number']) ? trim((string) $data['destination_number']) : null;
        $destinationQrisUrl = isset($data['destination_qris_image_url']) ? trim((string) $data['destination_qris_image_url']) : null;

        $destinationDto = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::from($destinationType),
            destinationHolder: $destinationHolder,
            destinationProvider: $destinationProvider ?: null,
            destinationNumber: $destinationNumber ?: null,
            destinationQrisImageUrl: $destinationQrisUrl ?: null
        );

        if (! $this->payoutDestinationService->validateDestination($destinationDto)) {
            throw new InvalidArgumentException('Data tujuan refund Pembeli belum lengkap (nama pemilik dan nomor tujuan/provider wajib diisi).');
        }

        $referenceNo = trim((string) ($data['reference_no'] ?? ''));
        if ($referenceNo === '') {
            throw new InvalidArgumentException('Nomor referensi bukti refund (reference_no) wajib diisi.');
        }

        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        return DB::transaction(function () use ($transaction, $admin, $data, $destinationDto, $referenceNo, $note, $evidenceFile) {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::where('transaction_id', $transaction->transaction_id)
                ->with(['agreement', 'complaint', 'cancellations.keepObligation'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransactionEligibleForRefund($lockedTx);

            /** @var Settlement|null $existingSettlement */
            $existingSettlement = Settlement::where('transaction_id', $lockedTx->transaction_id)
                ->lockForUpdate()
                ->first();

            if ($existingSettlement && $existingSettlement->isCompleted()) {
                throw new InvalidArgumentException('Settlement untuk transaksi ini sudah berstatus COMPLETED dan tidak dapat digandakan.');
            }

            $grossAmount = round((float) $lockedTx->agreement->buyer_total, 2);
            if ($grossAmount <= 0) {
                throw new InvalidArgumentException('Nominal dasar refund tidak valid atau bernilai nol/negatif.');
            }

            $expectedRefundAmount = $grossAmount;

            if (array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== '') {
                $submittedAmount = round((float) $data['amount'], 2);
                if ($submittedAmount <= 0) {
                    throw new InvalidArgumentException('Nominal refund tidak boleh bernilai nol atau negatif.');
                }

                if (abs($submittedAmount - $expectedRefundAmount) > 0.01) {
                    throw new InvalidArgumentException(sprintf(
                        'Nominal refund yang diajukan (Rp %s) tidak sesuai dengan ketetapan hak refund Pembeli (Rp %s).',
                        number_format($submittedAmount, 0, ',', '.'),
                        number_format($expectedRefundAmount, 0, ',', '.')
                    ));
                }
            }

            $destinationSnapshot = $this->destinationDtoToSnapshot($destinationDto);
            $complaintId = $lockedTx->complaint && strtoupper((string) $lockedTx->complaint->decision) === 'REFUND'
                ? $lockedTx->complaint->complaint_id
                : null;
            $now = now();

            if ($existingSettlement) {
                $existingSettlement->update(array_merge($destinationSnapshot, [
                    'complaint_id' => $existingSettlement->complaint_id ?? $complaintId,
                    'settlement_type' => Settlement::TYPE_REFUND,
                    'gross_amount' => $grossAmount,
                    'keep_deducted_amount' => 0,
                    'amount' => $expectedRefundAmount,
                    'status' => Settlement::STATUS_COMPLETED,
                    'recorded_by' => $admin->user_id,
                    'reference_no' => $referenceNo,
                    'note' => $note,
                    'completed_at' => $now,
                ]));
                $settlement = $existingSettlement;
            } else {
                $settlement = Settlement::create(array_merge($destinationSnapshot, [
                    'transaction_id' => $lockedTx->transaction_id,
                    'complaint_id' => $complaintId,
                    'settlement_type' => Settlement::TYPE_REFUND,
                    'gross_amount' => $grossAmount,
                    'keep_deducted_amount' => 0,
                    'amount' => $expectedRefundAmount,
                    'status' => Settlement::STATUS_COMPLETED,
                    'recorded_by' => $admin->user_id,
                    'reference_no' => $referenceNo,
                    'note' => $note,
                    'completed_at' => $now,
                ]));
            }

            if ($evidenceFile) {
                $this->storeSettlementEvidence($settlement, $admin, $evidenceFile, $now);
            }

            $fromStatus = $lockedTx->status instanceof TransactionStatus ? $lockedTx->status->value : (string) $lockedTx->status;
            $lockedTx->update([
                'status' => TransactionStatus::REFUNDED->value,
                'closed_at' => $now,
            ]);

            TransactionStatusHistory::create([
                'transaction_id' => $lockedTx->transaction_id,
                'from_status' => $fromStatus,
                'to_status' => TransactionStatus::REFUNDED->value,
                'changed_by' => $admin->user_id,
                'note' => sprintf(
                    'Refund simulatif kepada Pembeli berhasil dicatat sebesar Rp %s (Ref: %s).',
                    number_format($expectedRefundAmount, 0, ',', '.'),
                    $referenceNo
                ),
                'changed_at' => $now,
            ]);

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'RECORD_REFUND_COMPLETED',
                'entity_type' => 'settlement',
                'entity_id' => $settlement->settlement_id,
                'old_value' => ['transaction_status' => $fromStatus],
                'new_value' => [
                    'transaction_id' => $lockedTx->transaction_id,
                    'settlement_type' => Settlement::TYPE_REFUND,
                    'gross_amount' => $grossAmount,
                    'amount' => $expectedRefundAmount,
                    'destination_type' => $destinationSnapshot['destination_type'],
                    'destination_provider' => $destinationSnapshot['destination_provider'],
                    'destination_number' => $destinationSnapshot['destination_number'],
                    'destination_holder' => $destinationSnapshot['destination_holder'],
                    'reference_no' => $referenceNo,
                    'status' => Settlement::STATUS_COMPLETED,
                ],
                'created_at' => $now,
            ]);

            $itemTitle = (string) ($lockedTx->agreement->item_title ?? 'Produk Digital');
            $this->notificationService->notifyRefundProcessed(
                (int) $lockedTx->agreement->buyer_id,
                (int) $lockedTx->transaction_id,
                $itemTitle
            );

            $this->notificationService->notify(
                (int) $lockedTx->agreement->seller_id,
                (int) $lockedTx->transaction_id,
                NotificationEventType::REFUND_PROCESSED,
                sprintf("Pengembalian dana (refund) transaksi #%d ('%s') kepada Pembeli telah selesai dicatat.", $lockedTx->transaction_id, mb_substr($itemTitle, 0, 40)),
                1,
                false
            );

            return $settlement->fresh(['evidenceFiles']);
        });
    }

    /**
     * Ambil daftar KeepObligation berstatus OPEN milik Seller yang berasal dari transaksi asal lain.
     *
     * @return Collection<int, KeepObligation>
     */
    protected function getEligibleOpenKeepObligationsForPayout(
        int $sellerId,
        int $currentTransactionId,
        bool $lockForUpdate = false
    ): Collection {
        $query = KeepObligation::query()
            ->where('seller_id', $sellerId)
            ->where('status', KeepObligation::STATUS_OPEN)
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($currentTransactionId) {
                $q->whereDoesntHave('cancellation', function ($sub) use ($currentTransactionId) {
                    $sub->where('transaction_id', $currentTransactionId);
                });
            })
            ->with('cancellation')
            ->orderBy('keep_obligation_id', 'asc');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * Hitung alokasi pemotongan KEEP secara FIFO sampai maksimal gross_amount (amount tidak pernah negatif).
     *
     * @param  Collection<int, KeepObligation>  $openObligations
     * @return array{
     *     keep_deducted_amount: float,
     *     net_amount: float,
     *     total_open_keep_before: float,
     *     remaining_keep_after: float,
     *     allocations: array<int, array<string, mixed>>
     * }
     */
    protected function buildKeepAllocations(float $grossAmount, Collection $openObligations): array
    {
        $totalOpenBefore = round((float) $openObligations->sum('remaining_amount'), 2);
        $availableGross = max(0.0, $grossAmount);
        $totalDeducted = 0.0;
        $allocations = [];

        foreach ($openObligations as $obligation) {
            $remainingBefore = round((float) $obligation->remaining_amount, 2);
            if ($remainingBefore <= 0 || $availableGross <= 0) {
                continue;
            }

            $deduct = round(min($remainingBefore, $availableGross), 2);
            $remainingAfter = max(0.0, round($remainingBefore - $deduct, 2));

            $allocations[] = [
                'keep_obligation_id' => (int) $obligation->keep_obligation_id,
                'origin_transaction_id' => $obligation->cancellation?->transaction_id
                    ? (int) $obligation->cancellation->transaction_id
                    : null,
                'obligation_amount' => round((float) $obligation->amount, 2),
                'remaining_before' => $remainingBefore,
                'deducted_amount' => $deduct,
                'remaining_after' => $remainingAfter,
                'will_settle' => $remainingAfter <= 0,
            ];

            $totalDeducted = round($totalDeducted + $deduct, 2);
            $availableGross = max(0.0, round($availableGross - $deduct, 2));
        }

        $netAmount = max(0.0, round($grossAmount - $totalDeducted, 2));
        $remainingKeepAfter = max(0.0, round($totalOpenBefore - $totalDeducted, 2));

        return [
            'keep_deducted_amount' => $totalDeducted,
            'net_amount' => $netAmount,
            'total_open_keep_before' => $totalOpenBefore,
            'remaining_keep_after' => $remainingKeepAfter,
            'allocations' => $allocations,
        ];
    }

    /**
     * Pastikan aktor adalah Admin aktif.
     */
    protected function assertAdminActor(User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException('Hanya Administrator aktif yang berwenang mencatat penyelesaian finansial (settlement).');
        }
    }

    /**
     * Validasi kelayakan transaksi untuk diproses Payout.
     */
    protected function assertTransactionEligibleForPayout(Transaction $transaction): void
    {
        $statusStr = $transaction->status instanceof TransactionStatus ? $transaction->status->value : (string) $transaction->status;

        if (in_array($statusStr, [TransactionStatus::COMPLETED->value, TransactionStatus::REFUNDED->value], true)) {
            throw new InvalidArgumentException("Transaksi sudah berstatus final ({$statusStr}) dan tidak dapat diproses payout ulang.");
        }

        if ($transaction->hasActiveComplaint()) {
            throw new InvalidArgumentException('Proses payout ditahan karena terdapat komplain aktif yang belum diputuskan.');
        }

        $complaint = $transaction->complaint;
        if ($complaint && strtoupper((string) $complaint->status) === 'RESOLVED' && strtoupper((string) $complaint->decision) === 'REFUND') {
            throw new InvalidArgumentException('Transaksi ini telah diputuskan untuk REFUND kepada Pembeli sehingga tidak dapat diproses sebagai PAYOUT.');
        }

        $isConfirmed = $statusStr === TransactionStatus::CONFIRMED->value;
        $isResolvedForPayout = $complaint !== null
            && strtoupper((string) $complaint->status) === 'RESOLVED'
            && strtoupper((string) $complaint->decision) === 'PAYOUT';

        if (! $isConfirmed && ! $isResolvedForPayout) {
            throw new InvalidArgumentException("Transaksi belum memenuhi syarat payout (status saat ini: {$statusStr}). Payout hanya diizinkan untuk transaksi CONFIRMED atau keputusan komplain PAYOUT.");
        }
    }

    /**
     * Validasi kelayakan transaksi untuk diproses Refund.
     */
    protected function assertTransactionEligibleForRefund(Transaction $transaction): void
    {
        $statusStr = $transaction->status instanceof TransactionStatus ? $transaction->status->value : (string) $transaction->status;

        if (in_array($statusStr, [TransactionStatus::COMPLETED->value, TransactionStatus::REFUNDED->value], true)) {
            throw new InvalidArgumentException("Transaksi sudah berstatus final ({$statusStr}) dan tidak dapat diproses refund.");
        }

        if ($statusStr === TransactionStatus::WAITING_PAYMENT->value) {
            throw new InvalidArgumentException('Transaksi belum dibayar (WAITING_PAYMENT) sehingga tidak dapat diproses refund.');
        }

        if ($transaction->hasActiveComplaint()) {
            throw new InvalidArgumentException('Proses refund ditahan karena masih terdapat komplain aktif yang belum diputuskan.');
        }

        $complaint = $transaction->complaint;
        if ($complaint && strtoupper((string) $complaint->status) === 'RESOLVED' && strtoupper((string) $complaint->decision) === 'PAYOUT') {
            throw new InvalidArgumentException('Komplain transaksi ini telah diputuskan untuk PAYOUT ke Penjual, bukan REFUND.');
        }

        $hasComplaintRefundDecision = $complaint !== null
            && strtoupper((string) $complaint->status) === 'RESOLVED'
            && strtoupper((string) $complaint->decision) === 'REFUND';

        $approvedPostPaymentCancellation = $transaction->approvedPostPaymentCancellation();

        if (! $hasComplaintRefundDecision && ! $approvedPostPaymentCancellation) {
            throw new InvalidArgumentException('Transaksi tidak memiliki keputusan sah untuk refund (memerlukan keputusan komplain REFUND atau pembatalan pasca-bayar berstatus APPROVED).');
        }
    }

    /**
     * Konversi PayoutDestinationDTO menjadi array snapshot untuk tabel settlements.
     *
     * @return array<string, mixed>
     */
    protected function destinationDtoToSnapshot(PayoutDestinationDTO $dto): array
    {
        return [
            'destination_type' => $dto->destinationType->value,
            'destination_provider' => $dto->destinationProvider,
            'destination_number' => $dto->destinationNumber,
            'destination_holder' => $dto->destinationHolder,
            'destination_qris_image_url' => $dto->destinationQrisImageUrl,
        ];
    }

    /**
     * Simpan berkas bukti simulasi ke tabel evidence_files dengan settlement_id.
     */
    protected function storeSettlementEvidence(
        Settlement $settlement,
        User $admin,
        UploadedFile $file,
        mixed $now
    ): EvidenceFile {
        $path = $file->store('evidence/settlements', 'public');

        return EvidenceFile::create([
            'uploaded_by' => $admin->user_id,
            'settlement_id' => $settlement->settlement_id,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => $path,
            'mime_type' => $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream'),
            'uploaded_at' => $now,
        ]);
    }
}
