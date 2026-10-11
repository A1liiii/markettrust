<?php

namespace App\Enums;

enum PayoutDestinationType: string
{
    case BANK = 'BANK';
    case EWALLET = 'EWALLET';
    case QRIS = 'QRIS';

    public function label(): string
    {
        return match ($this) {
            self::BANK => 'Rekening Bank',
            self::EWALLET => 'Dompet Digital (E-Wallet)',
            self::QRIS => 'QRIS Interaktif',
        };
    }
}
