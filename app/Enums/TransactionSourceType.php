<?php

namespace App\Enums;

enum TransactionSourceType: string
{
    case MARKETPLACE = 'MARKETPLACE';
    case FORUM = 'FORUM';

    public function label(): string
    {
        return match ($this) {
            self::MARKETPLACE => 'Marketplace',
            self::FORUM => 'Forum Diskusi',
        };
    }
}
