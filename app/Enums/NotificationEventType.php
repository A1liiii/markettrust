<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case AGREEMENT_PROPOSED = 'AGREEMENT_PROPOSED';
    case AGREEMENT_REVISED = 'AGREEMENT_REVISED';
    case TRANSACTION_CREATED = 'TRANSACTION_CREATED';
    case PAYMENT_SUCCESS = 'PAYMENT_SUCCESS';
    case PAYMENT_EXPIRED = 'PAYMENT_EXPIRED';
    case SELLER_DELIVERY_WARNING = 'SELLER_DELIVERY_WARNING';
    case BUYER_GRACE_WARNING = 'BUYER_GRACE_WARNING';
    case COMPLAINT_DEADLINE_WARNING = 'COMPLAINT_DEADLINE_WARNING';
    case DISPUTE_DECIDED = 'DISPUTE_DECIDED';
    case TRANSACTION_CANCELLED = 'TRANSACTION_CANCELLED';
    case TRANSACTION_KEEP = 'TRANSACTION_KEEP';
    case PAYOUT_READY = 'PAYOUT_READY';
    case REFUND_PROCESSED = 'REFUND_PROCESSED';

    /**
     * Dapatkan label ramah pengguna dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::AGREEMENT_PROPOSED => 'Usulan Kesepakatan',
            self::AGREEMENT_REVISED => 'Revisi Kesepakatan',
            self::TRANSACTION_CREATED => 'Pesanan Baru',
            self::PAYMENT_SUCCESS => 'Pembayaran Berhasil',
            self::PAYMENT_EXPIRED => 'Pembayaran Kedaluwarsa',
            self::SELLER_DELIVERY_WARNING => 'Peringatan Penyerahan',
            self::BUYER_GRACE_WARNING => 'Masa Tenggang Konfirmasi',
            self::COMPLAINT_DEADLINE_WARNING => 'Tenggat Komplain',
            self::DISPUTE_DECIDED => 'Keputusan Sengketa',
            self::TRANSACTION_CANCELLED => 'Transaksi Dibatalkan',
            self::TRANSACTION_KEEP => 'Keputusan KEEP',
            self::PAYOUT_READY => 'Siap Payout Penjual',
            self::REFUND_PROCESSED => 'Refund Diproses',
        };
    }

    /**
     * Class CSS badge untuk indikator visual di antarmuka.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::AGREEMENT_PROPOSED, self::AGREEMENT_REVISED => 'bg-indigo-100 text-indigo-700 border-indigo-200',
            self::TRANSACTION_CREATED => 'bg-sky-100 text-sky-700 border-sky-200',
            self::PAYMENT_SUCCESS, self::PAYOUT_READY => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::SELLER_DELIVERY_WARNING, self::BUYER_GRACE_WARNING, self::COMPLAINT_DEADLINE_WARNING => 'bg-amber-100 text-amber-800 border-amber-200',
            self::PAYMENT_EXPIRED, self::TRANSACTION_CANCELLED => 'bg-rose-100 text-rose-800 border-rose-200',
            self::DISPUTE_DECIDED, self::TRANSACTION_KEEP => 'bg-purple-100 text-purple-800 border-purple-200',
            self::REFUND_PROCESSED => 'bg-teal-100 text-teal-800 border-teal-200',
        };
    }

    /**
     * Tentukan apakah event ini merupakan notifikasi berstatus one-time per transaksi
     * yang tidak boleh berganda saat callback/worker retry.
     */
    public function isOneTime(): bool
    {
        return match ($this) {
            self::SELLER_DELIVERY_WARNING,
            self::BUYER_GRACE_WARNING,
            self::COMPLAINT_DEADLINE_WARNING,
            self::PAYMENT_SUCCESS,
            self::PAYMENT_EXPIRED,
            self::PAYOUT_READY,
            self::REFUND_PROCESSED,
            self::DISPUTE_DECIDED,
            self::TRANSACTION_CANCELLED,
            self::TRANSACTION_KEEP,
            self::TRANSACTION_CREATED => true,
            default => false,
        };
    }
}
