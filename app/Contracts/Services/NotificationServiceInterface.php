<?php

namespace App\Contracts\Services;

use App\Enums\NotificationEventType;
use App\Models\Notification;

interface NotificationServiceInterface
{
    /**
     * Dispatch notification with deduplication.
     */
    public function notify(
        int $recipientUserId,
        ?int $transactionId,
        NotificationEventType|string $eventType,
        string $message,
        ?int $deduplicationWindowMinutes = 5,
        bool $forceOneTime = false
    ): ?Notification;

    /**
     * Notifikasi usulan atau revisi kesepakatan (proposal/revision).
     */
    public function notifyProposal(
        int $recipientUserId,
        ?int $transactionId,
        string $itemTitle,
        string $proposerUsername,
        bool $isRevision = false
    ): ?Notification;

    /**
     * Notifikasi pembayaran berhasil diverifikasi Midtrans escrow.
     */
    public function notifyPaymentSuccess(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Notifikasi batas waktu pembayaran habis (expiry).
     */
    public function notifyPaymentExpired(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Peringatan penjual atas tenggat penyerahan item yang mendekat/habis.
     */
    public function notifySellerDeliveryWarning(
        int $sellerId,
        int $transactionId,
        string $itemTitle,
        int $remainingMinutes = 15
    ): ?Notification;

    /**
     * Notifikasi perpanjangan 30 menit masa tenggang konfirmasi buyer sebelum auto-confirm.
     */
    public function notifyBuyerGracePeriod(
        int $buyerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Peringatan tenggat pengajuan atau tanggapan komplain/sengketa.
     */
    public function notifyComplaintDeadline(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        int $remainingMinutes = 15
    ): ?Notification;

    /**
     * Notifikasi penetapan keputusan resolusi sengketa oleh Admin.
     */
    public function notifyDisputeDecided(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        string $decision
    ): ?Notification;

    /**
     * Notifikasi pembatalan transaksi.
     */
    public function notifyTransactionCancelled(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        ?string $reason = null
    ): ?Notification;

    /**
     * Notifikasi keputusan KEEP atas dana escrow atau transaksi.
     */
    public function notifyTransactionKeep(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Notifikasi transaksi dikonfirmasi dan dana siap diproses payout manual ke penjual.
     */
    public function notifyPayoutReady(
        int $sellerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Notifikasi proses pengembalian dana (refund) manual/disetujui kepada pembeli.
     */
    public function notifyRefundProcessed(
        int $buyerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;

    /**
     * Notifikasi penyerahan hasil pesanan oleh penjual kepada pembeli.
     */
    public function notifyItemDelivered(
        int $buyerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification;
}
