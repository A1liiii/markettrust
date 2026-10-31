<?php

namespace App\Contracts\Services;

use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface AccountModerationServiceInterface
{
    /**
     * Buat laporan akun baru oleh pengguna terdaftar (UC-30).
     * Mencegah pelaporan diri sendiri dan laporan aktif identik.
     *
     * @param  array{reported_user_id: int, transaction_id?: int|null, reason: string}  $data
     */
    public function createReport(User $reporter, array $data, ?UploadedFile $evidence = null): AccountReport;

    /**
     * Tandai laporan sedang dalam peninjauan / investigasi mendalam admin (UC-24).
     */
    public function reviewReport(AccountReport $report, User $admin, string $note): AccountReport;

    /**
     * Tolak/tutup laporan yang tidak terbukti tanpa memberikan sanksi bagi terlapor (UC-24).
     */
    public function dismissReport(AccountReport $report, User $admin, string $note): AccountReport;

    /**
     * Putuskan laporan akun dengan sanksi moderasi reputasi dan/atau penangguhan akun (UC-24).
     * Pencatatan event reputasi bersifat idempoten dengan event_key unik.
     *
     * @param  array{review_note: string, account_status?: string|null, new_reputation_status?: string|null, points_delta?: int}  $decisionData
     */
    public function resolveReport(AccountReport $report, User $admin, array $decisionData): AccountReport;

    /**
     * Ambil ringkasan rekam jejak pengguna untuk kebutuhan peninjauan moderasi:
     * snapshot pembatalan transaksi, kewajiban KEEP, dan riwayat reputasi.
     *
     * @return array{user: User, cancellations: Collection, keep_obligations: Collection, reputation_events: Collection, active_reports_count: int}
     */
    public function getUserModerationContext(User|int $user): array;
}
