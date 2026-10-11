<?php

namespace App\Contracts\Services;

use App\DTOs\FeeCalculationResultDTO;
use App\Enums\FeeType;

interface FeeCalculationServiceInterface
{
    /**
     * Calculate platform fee based on tiered rules in SKPL 2.5.2.
     */
    public function calculate(float $basePrice, FeeType|string $feeType): FeeCalculationResultDTO;
}
