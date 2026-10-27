<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Enums\NotificationEventType;
use App\Models\Notification;

class NotificationService implements NotificationServiceInterface
{
    /**
     * Dispatch notification with deduplication and idempotency against retries.
     */
    public function notify(
        int $recipientUserId,
        ?int $transactionId,
        NotificationEventType|string $eventType,
        string $message,
        ?int $deduplicationWindowMinutes = 5,
        bool $forceOneTime = false
    ): ?Notification {
        $eventTypeStr = $eventType instanceof NotificationEventType ? $eventType->value : (string) $eventType;
        $eventTypeEnum = $eventType instanceof NotificationEventType ? $eventType : NotificationEventType::tryFrom($eventTypeStr);

        $isOneTime = $forceOneTime || ($eventTypeEnum && $eventTypeEnum->isOneTime()) || $deduplicationWindowMinutes === null;

        $query = Notification::where('user_id', $recipientUserId)
            ->where('event_type', $eventTypeStr);

        if ($transactionId !== null) {
            $query->where('transaction_id', $transactionId);
        } else {
            $query->whereNull('transaction_id');
        }

        if (! $isOneTime && $deduplicationWindowMinutes !== null && $deduplicationWindowMinutes > 0) {
            $query->where('created_at', '>=', now()->subMinutes($deduplicationWindowMinutes));
        }

        $existing = $query->latest('notification_id')->first();

        if ($existing) {
            return $existing;
        }

        return Notification::create([
            'user_id' => $recipientUserId,
            'transaction_id' => $transactionId,
            'event_type' => $eventTypeStr,
            'message' => mb_substr($message, 0, 255),
            'read_at' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Notifikasi usulan atau revisi kesepakatan (proposal/revision).
     */
    public function notifyProposal(
        int $recipientUserId,
        ?int $transactionId,
        string $itemTitle,
        string $proposerUsername,
        bool $isRevision = false
    ): ?Notification {
        $eventType = $isRevision
            ? NotificationEventType::AGREEMENT_REVISED
            : NotificationEventType::AGREEMENT_PROPOSED;

        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = $isRevision
            ? "Usulan kesepakatan untuk '{$shortTitle}' telah direvisi oleh @{$proposerUsername}."
            : "Usulan kesepakatan baru untuk '{$shortTitle}' diajukan oleh @{$proposerUsername}.";

        return $this->notify($recipientUserId, $transactionId, $eventType, $message, 5, false);
    }

    /**
     * Notifikasi pembayaran berhasil diverifikasi Midtrans escrow.
     */
    public function notifyPaymentSuccess(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Pembayaran transaksi #{$transactionId} ('{$shortTitle}') berhasil diverifikasi. Dana escrow diamankan.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::PAYMENT_SUCCESS, $message, null, true);
    }

    /**
     * Notifikasi batas waktu pembayaran habis (expiry).
     */
    public function notifyPaymentExpired(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Batas waktu pembayaran transaksi #{$transactionId} ('{$shortTitle}') telah habis. Transaksi dibatalkan.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::PAYMENT_EXPIRED, $message, null, true);
    }

    /**
     * Peringatan penjual atas tenggat penyerahan item yang mendekat/habis.
     */
    public function notifySellerDeliveryWarning(
        int $sellerId,
        int $transactionId,
        string $itemTitle,
        int $remainingMinutes = 15
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Peringatan: Batas waktu penyerahan item transaksi #{$transactionId} ('{$shortTitle}') tersisa {$remainingMinutes} menit.";

        return $this->notify($sellerId, $transactionId, NotificationEventType::SELLER_DELIVERY_WARNING, $message, null, true);
    }

    /**
     * Notifikasi perpanjangan 30 menit masa tenggang konfirmasi buyer sebelum auto-confirm.
     */
    public function notifyBuyerGracePeriod(
        int $buyerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Waktu konfirmasi transaksi #{$transactionId} ('{$shortTitle}') diperpanjang 30 menit sebelum disetujui otomatis.";

        return $this->notify($buyerId, $transactionId, NotificationEventType::BUYER_GRACE_WARNING, $message, null, true);
    }

    /**
     * Peringatan tenggat pengajuan atau tanggapan komplain/sengketa.
     */
    public function notifyComplaintDeadline(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        int $remainingMinutes = 15
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Peringatan: Batas waktu komplain/tanggapan untuk transaksi #{$transactionId} ('{$shortTitle}') tersisa {$remainingMinutes} menit.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::COMPLAINT_DEADLINE_WARNING, $message, null, true);
    }

    /**
     * Notifikasi penetapan keputusan resolusi sengketa oleh Admin.
     */
    public function notifyDisputeDecided(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        string $decision
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $shortDecision = mb_substr($decision, 0, 40);
        $message = "Sengketa transaksi #{$transactionId} ('{$shortTitle}') telah diputuskan oleh Admin: {$shortDecision}.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::DISPUTE_DECIDED, $message, null, true);
    }

    /**
     * Notifikasi pembatalan transaksi.
     */
    public function notifyTransactionCancelled(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle,
        ?string $reason = null
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $reasonSuffix = $reason ? ' ('.mb_substr($reason, 0, 50).')' : '';
        $message = "Transaksi #{$transactionId} ('{$shortTitle}') telah dibatalkan{$reasonSuffix}.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::TRANSACTION_CANCELLED, $message, null, true);
    }

    /**
     * Notifikasi keputusan KEEP atas dana escrow atau transaksi.
     */
    public function notifyTransactionKeep(
        int $recipientUserId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Keputusan KEEP ditetapkan untuk transaksi #{$transactionId} ('{$shortTitle}'). Dana escrow dialokasikan.";

        return $this->notify($recipientUserId, $transactionId, NotificationEventType::TRANSACTION_KEEP, $message, null, true);
    }

    /**
     * Notifikasi transaksi dikonfirmasi dan dana siap diproses payout manual ke penjual.
     */
    public function notifyPayoutReady(
        int $sellerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Transaksi #{$transactionId} ('{$shortTitle}') selesai. Dana siap untuk penyiapan payout manual.";

        return $this->notify($sellerId, $transactionId, NotificationEventType::PAYOUT_READY, $message, null, true);
    }

    /**
     * Notifikasi proses pengembalian dana (refund) manual/disetujui kepada pembeli.
     */
    public function notifyRefundProcessed(
        int $buyerId,
        int $transactionId,
        string $itemTitle
    ): ?Notification {
        $shortTitle = mb_substr($itemTitle, 0, 40);
        $message = "Pengembalian dana (refund) transaksi #{$transactionId} ('{$shortTitle}') telah diproses/disetujui.";

        return $this->notify($buyerId, $transactionId, NotificationEventType::REFUND_PROCESSED, $message, null, true);
    }
}
