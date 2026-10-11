<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case WAITING_PAYMENT = 'WAITING_PAYMENT';
    case PAID = 'PAID';
    case DELIVERED = 'DELIVERED';
    case CONFIRMED = 'CONFIRMED';
    case DISPUTED = 'DISPUTED';
    case COMPLETED = 'COMPLETED';
    case REFUNDED = 'REFUNDED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::WAITING_PAYMENT => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar (Proses Penyerahan)',
            self::DELIVERED => 'Sudah Diserahkan (Menunggu Konfirmasi)',
            self::CONFIRMED => 'Telah Dikonfirmasi Buyer',
            self::DISPUTED => 'Dalam Komplain / Sengketa',
            self::COMPLETED => 'Selesai (Pencairan dana ke Seller)',
            self::REFUNDED => 'Dikembalikan ke Buyer (Refund)',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
