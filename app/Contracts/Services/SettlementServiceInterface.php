<?php

namespace App\Contracts\Services;

use App\Models\Settlement;
use App\Models\SettlementAttempt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface SettlementServiceInterface
{
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
    public function calculatePayoutPreview(Transaction $transaction): array;

    /**
     * Siapkan atau sinkronkan ulang tujuan payout aktif Seller ke record settlement yang belum COMPLETED.
     */
    public function prepareOrSyncPayoutSettlement(Transaction $transaction, ?User $actor = null): Settlement;

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
    ): Settlement;

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
    ): SettlementAttempt;

    /**
     * Kirim pengumuman gangguan sistem payout berdampak luas ke pengguna terkait.
     */
    public function announceSystemDisruption(
        User $admin,
        string $message,
        ?Transaction $relatedTransaction = null
    ): int;

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
    public function calculateRefundPreview(Transaction $transaction): array;

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
    ): Settlement;
}
