<?php

namespace App\Enums;

enum FeeType: string
{
    case INCLUDE = 'INCLUDE';
    case EXCLUDE = 'EXCLUDE';

    public function label(): string
    {
        return match ($this) {
            self::INCLUDE => 'Termasuk Harga',
            self::EXCLUDE => 'Biaya Layanan Platform',
        };
    }
}
