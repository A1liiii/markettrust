<?php

namespace App\Services;

use App\Contracts\Services\AccountModerationServiceInterface;
use App\Models\AccountReport;
use App\Models\AuditLog;
use App\Models\EvidenceFile;
use App\Models\KeepObligation;
use App\Models\ReputationEvent;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AccountModerationService implements AccountModerationServiceInterface
{
    /**
     * Buat laporan akun baru oleh pengguna terdaftar (UC-30).
     * Mencegah pelaporan diri sendiri dan laporan aktif identik.
     * Tidak mengurangi poin atau memberikan sanksi pada terlapor.
     *
     * @param  array{reported_user_id: int, transaction_id?: int|null, reason: string}  $data
     */
    public function createReport(User $reporter, array $data, ?UploadedFile $evidence = null): AccountReport
    {
        $reportedUserId = (int) $data['reported_user_id'];

        // 1. Validasi larangan melaporkan akun diri sendiri
        if ((int) $reporter->user_id === $reportedUserId) {
            throw ValidationException::withMessages([
                'reported_user_id' => 'Anda tidak dapat melaporkan akun Anda sendiri.',
            ]);
        }

        // 2. Pastikan pengguna yang dilaporkan ada dan valid
        $reportedUser = User::find($reportedUserId);
        if (! $reportedUser) {
            throw ValidationException::withMessages([
                'reported_user_id' => 'Akun pengguna yang dilaporkan tidak ditemukan.',
            ]);
        }

        // 3. Cegah duplikasi laporan aktif yang identik (status OPEN atau REVIEWING oleh pelapor yang sama)
        $hasActiveReport = AccountReport::query()
            ->where('reporter_id', $reporter->user_id)
            ->where('reported_user_id', $reportedUserId)
            ->whereIn('status', [AccountReport::STATUS_OPEN, AccountReport::STATUS_REVIEWING])
            ->exists();

        if ($hasActiveReport) {
            throw ValidationException::withMessages([
                'reported_user_id' => 'Anda sudah memiliki laporan aktif yang sedang diproses untuk akun ini.',
            ]);
        }

        return DB::transaction(function () use ($reporter, $reportedUserId, $data, $evidence) {
            $report = AccountReport::create([
                'reported_user_id' => $reportedUserId,
                'reporter_id' => $reporter->user_id,
                'transaction_id' => ! empty($data['transaction_id']) ? (int) $data['transaction_id'] : null,
                'source' => AccountReport::SOURCE_USER,
                'reason' => trim($data['reason']),
                'status' => AccountReport::STATUS_OPEN,
                'reviewed_by' => null,
                'review_note' => null,
                'reviewed_at' => null,
            ]);

            // Simpan bukti pendukung jika dilampirkan
            if ($evidence) {
                $path = $evidence->store('evidence/reports', 'public');
                EvidenceFile::create([
                    'uploaded_by' => $reporter->user_id,
                    'report_id' => $report->report_id,
                    'file_name' => $evidence->getClientOriginalName(),
                    'file_url' => $path,
                    'mime_type' => $evidence->getClientMimeType() ?: ($evidence->getMimeType() ?: 'application/octet-stream'),
                    'uploaded_at' => now(),
                ]);
            }

            return $report;
        });
    }

    /**
     * Tandai laporan sedang dalam peninjauan / investigasi mendalam admin (UC-24).
     */
    public function reviewReport(AccountReport $report, User $admin, string $note): AccountReport
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang berwenang meninjau laporan akun.');
        }

        return DB::transaction(function () use ($report, $admin, $note) {
            $lockedReport = AccountReport::lockForUpdate()->findOrFail($report->report_id);

            $oldStatus = $lockedReport->status;
            $oldNote = $lockedReport->review_note;

            $now = now();
            $lockedReport->update([
                'status' => AccountReport::STATUS_REVIEWING,
                'reviewed_by' => $admin->user_id,
                'review_note' => trim($note),
                'reviewed_at' => $now,
            ]);

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'ACCOUNT_REPORT_REVIEWING',
                'entity_type' => 'account_reports',
                'entity_id' => $lockedReport->report_id,
                'old_value' => [
                    'status' => $oldStatus,
                    'review_note' => $oldNote,
                ],
                'new_value' => [
                    'status' => AccountReport::STATUS_REVIEWING,
                    'review_note' => trim($note),
                    'reviewed_by' => $admin->user_id,
                    'reviewed_at' => $now->toIso8601String(),
                ],
                'created_at' => $now,
            ]);

            return $lockedReport;
        });
    }

    /**
     * Tolak/tutup laporan yang tidak terbukti tanpa memberikan sanksi bagi terlapor (UC-24).
     */
    public function dismissReport(AccountReport $report, User $admin, string $note): AccountReport
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang berwenang menutup laporan akun.');
        }

        return DB::transaction(function () use ($report, $admin, $note) {
            $lockedReport = AccountReport::lockForUpdate()->findOrFail($report->report_id);

            $oldStatus = $lockedReport->status;
            $oldNote = $lockedReport->review_note;

            $now = now();
            $lockedReport->update([
                'status' => AccountReport::STATUS_DISMISSED,
                'reviewed_by' => $admin->user_id,
                'review_note' => trim($note),
                'reviewed_at' => $now,
            ]);

            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'ACCOUNT_REPORT_DISMISSED',
                'entity_type' => 'account_reports',
                'entity_id' => $lockedReport->report_id,
                'old_value' => [
                    'status' => $oldStatus,
                    'review_note' => $oldNote,
                ],
                'new_value' => [
                    'status' => AccountReport::STATUS_DISMISSED,
                    'review_note' => trim($note),
                    'reviewed_by' => $admin->user_id,
                    'reviewed_at' => $now->toIso8601String(),
                ],
                'created_at' => $now,
            ]);

            return $lockedReport;
        });
    }

    /**
     * Putuskan laporan akun dengan sanksi moderasi reputasi dan/atau penangguhan akun (UC-24).
     * Pencatatan event reputasi bersifat idempoten dengan event_key unik.
     *
     * @param  array{review_note: string, account_status?: string|null, new_reputation_status?: string|null, points_delta?: int}  $decisionData
     */
    public function resolveReport(AccountReport $report, User $admin, array $decisionData): AccountReport
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Hanya administrator yang berwenang menetapkan keputusan moderasi akun.');
        }

        return DB::transaction(function () use ($report, $admin, $decisionData) {
            $lockedReport = AccountReport::lockForUpdate()->findOrFail($report->report_id);
            $reportedUser = User::lockForUpdate()->findOrFail($lockedReport->reported_user_id);

            $oldReportStatus = $lockedReport->status;
            $oldAccountStatus = $reportedUser->account_status;
            $oldReputationStatus = $reportedUser->reputation_status;
            $oldReputationPoints = $reportedUser->reputation_points;

            $now = now();
            $reviewNote = trim($decisionData['review_note'] ?? '');
            if (empty($reviewNote)) {
                throw new InvalidArgumentException('Catatan tinjauan keputusan moderasi wajib diisi.');
            }

            // 1. Terapkan perubahan status akun jika ditentukan
            $targetAccountStatus = $decisionData['account_status'] ?? null;
            if ($targetAccountStatus && in_array($targetAccountStatus, ['ACTIVE', 'SUSPENDED'], true)) {
                $reportedUser->account_status = $targetAccountStatus;
                $reportedUser->save();
            }

            // 2. Terapkan perubahan reputasi secara idempoten
            $pointsDelta = isset($decisionData['points_delta']) ? (int) $decisionData['points_delta'] : 0;
            $newReputationStatus = $decisionData['new_reputation_status'] ?? null;
            if ($newReputationStatus && ! in_array($newReputationStatus, ['LEGIT', 'PROBLEMATIC'], true)) {
                $newReputationStatus = null;
            }

            $eventKey = ReputationEvent::generateReportEventKey($lockedReport->report_id);
            $existingEvent = ReputationEvent::where('event_key', $eventKey)->first();

            // Catat ReputationEvent hanya jika belum pernah dibuat dan ada perubahan poin / status reputasi
            if (! $existingEvent && ($pointsDelta !== 0 || $newReputationStatus !== null)) {
                $previousStatus = in_array($oldReputationStatus, ['LEGIT', 'PROBLEMATIC'], true)
                    ? $oldReputationStatus
                    : null;

                ReputationEvent::create([
                    'user_id' => $reportedUser->user_id,
                    'event_type' => ReputationEvent::EVENT_ADMIN_MODERATION,
                    'points_delta' => $pointsDelta,
                    'previous_status' => $previousStatus,
                    'new_status' => $newReputationStatus,
                    'transaction_id' => $lockedReport->transaction_id,
                    'report_id' => $lockedReport->report_id,
                    'actor_id' => $admin->user_id,
                    'reason' => $reviewNote,
                    'event_key' => $eventKey,
                    'created_at' => $now,
                ]);
            }

            // 3. Perbarui status laporan menjadi RESOLVED
            $lockedReport->update([
                'status' => AccountReport::STATUS_RESOLVED,
                'reviewed_by' => $admin->user_id,
                'review_note' => $reviewNote,
                'reviewed_at' => $now,
            ]);

            // 4. Catat jejak audit
            AuditLog::create([
                'actor_id' => $admin->user_id,
                'action' => 'ACCOUNT_REPORT_RESOLVED',
                'entity_type' => 'account_reports',
                'entity_id' => $lockedReport->report_id,
                'old_value' => [
                    'status' => $oldReportStatus,
                    'account_status' => $oldAccountStatus,
                    'reputation_status' => $oldReputationStatus,
                    'reputation_points' => $oldReputationPoints,
                ],
                'new_value' => [
                    'status' => AccountReport::STATUS_RESOLVED,
                    'account_status' => $reportedUser->account_status,
                    'reputation_status' => $reportedUser->reputation_status,
                    'reputation_points' => $reportedUser->reputation_points,
                    'review_note' => $reviewNote,
                    'points_delta' => $pointsDelta,
                    'new_reputation_status' => $newReputationStatus,
                    'event_key' => $eventKey,
                ],
                'created_at' => $now,
            ]);

            return $lockedReport;
        });
    }

    /**
     * Ambil ringkasan rekam jejak pengguna untuk kebutuhan peninjauan moderasi:
     * snapshot pembatalan transaksi, kewajiban KEEP, dan riwayat reputasi.
     *
     * @return array{user: User, cancellations: Collection, keep_obligations: Collection, reputation_events: Collection, active_reports_count: int}
     */
    public function getUserModerationContext(User|int $user): array
    {
        $targetUser = $user instanceof User ? $user : User::findOrFail($user);

        $cancellations = TransactionCancellation::query()
            ->where(function ($query) use ($targetUser) {
                $query->where('requested_by', $targetUser->user_id)
                    ->orWhere(function ($q) use ($targetUser) {
                        $q->where('responsible_party', 'SELLER')
                            ->whereHas('transaction.agreement', fn ($sub) => $sub->where('seller_id', $targetUser->user_id));
                    });
            })
            ->with(['transaction.agreement', 'requestedBy', 'reviewedBy'])
            ->latest('cancellation_id')
            ->limit(10)
            ->get();

        $keepObligations = KeepObligation::query()
            ->where('seller_id', $targetUser->user_id)
            ->with(['cancellation.transaction.agreement'])
            ->latest('keep_obligation_id')
            ->limit(10)
            ->get();

        $reputationEvents = ReputationEvent::query()
            ->where('user_id', $targetUser->user_id)
            ->with(['actor', 'transaction', 'report'])
            ->latest('reputation_event_id')
            ->limit(15)
            ->get();

        $activeReportsCount = AccountReport::query()
            ->where('reported_user_id', $targetUser->user_id)
            ->whereIn('status', [AccountReport::STATUS_OPEN, AccountReport::STATUS_REVIEWING])
            ->count();

        return [
            'user' => $targetUser,
            'cancellations' => $cancellations,
            'keep_obligations' => $keepObligations,
            'reputation_events' => $reputationEvents,
            'active_reports_count' => $activeReportsCount,
        ];
    }
}
