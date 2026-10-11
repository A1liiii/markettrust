<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case DRAFT = 'DRAFT';
    case FINALIZED = 'FINALIZED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Usulan',
            self::FINALIZED => 'Disetujui (Final)',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
