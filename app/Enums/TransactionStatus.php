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
            self::PAID => 'Sudah Dibayar (Menunggu Kirim)',
            self::DELIVERED => 'Item Diserahkan (Menunggu Konfirmasi)',
            self::CONFIRMED => 'Dikonfirmasi Pembeli',
            self::DISPUTED => 'Dalam Sengketa',
            self::COMPLETED => 'Pesanan Selesai',
            self::REFUNDED => 'Dana Dikembalikan (Refund)',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::WAITING_PAYMENT => 'bg-amber-100 text-amber-800 border-amber-200',
            self::PAID => 'bg-blue-100 text-blue-800 border-blue-200',
            self::DELIVERED => 'bg-purple-100 text-purple-800 border-purple-200',
            self::CONFIRMED, self::COMPLETED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::DISPUTED => 'bg-orange-100 text-orange-800 border-orange-200',
            self::REFUNDED, self::CANCELLED => 'bg-rose-100 text-rose-800 border-rose-200',
        };
    }

    public function stepIndex(): int
    {
        return match ($this) {
            self::WAITING_PAYMENT => 1,
            self::PAID => 2,
            self::DELIVERED => 3,
            self::CONFIRMED, self::COMPLETED => 4,
            default => 0,
        };
    }
}
