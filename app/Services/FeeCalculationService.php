<?php

namespace App\Services;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\FeeType;

class FeeCalculationService implements FeeCalculationServiceInterface
{
    /**
     * Calculate platform fee based on tiered rules in SKPL 2.5.2.
     */
    public function calculate(float $basePrice, FeeType|string $feeType): FeeCalculationResultDTO
    {
        $feeTypeEnum = $feeType instanceof FeeType ? $feeType : FeeType::from(strtoupper($feeType));
        $feeAmount = $this->calculateFeeAmount($basePrice);

        if ($feeTypeEnum === FeeType::EXCLUDE) {
            $buyerTotal = $basePrice + $feeAmount;
            $sellerNet = $basePrice;
        } else { // INCLUDE
            $buyerTotal = $basePrice;
            $sellerNet = max(0.0, $basePrice - $feeAmount);
        }

        return new FeeCalculationResultDTO(
            basePrice: $basePrice,
            feeType: $feeTypeEnum,
            feeAmount: $feeAmount,
            buyerTotal: $buyerTotal,
            sellerNet: $sellerNet
        );
    }

    /**
     * Tiered formula from SKPL Bab 2.5.2:
     * - Rp5.000 - Rp10.000: 10% (flat) -> Rp500 - Rp1.000
     * - Rp10.001 - Rp50.000: 10% -> 5% (turun) -> Rp1.000 - Rp2.500
     * - Rp50.001 - Rp100.000: 5% (flat) -> Rp2.500 - Rp5.000
     * - Rp100.001 - Rp499.000: 5% -> 1,4% (turun) -> Rp5.000 - Rp7.000
     * - Rp499.001 - Rp1.000.000: 1,4% -> 1% (turun) -> Rp7.000 - Rp10.000
     * - > Rp1.000.000 s/d < Rp10.000.000: Rp10.000
     * - Mulai Rp10.000.000: fee naik Rp10.000 setiap kelipatan Rp10.000.000
     */
    public function calculateFeeAmount(float $price): float
    {
        if ($price < 5000) {
            return 500.0;
        }

        if ($price <= 10000) {
            return round($price * 0.10, 2);
        }

        if ($price <= 50000) {
            // Gradual reduction: 10% at 10.000 down to 5% at 50.000
            // Nominal fee: Rp1.000 at 10k -> Rp2.500 at 50k
            $ratio = ($price - 10000) / (50000 - 10000);
            $fee = 1000 + ($ratio * (2500 - 1000));
            return round($fee, 2);
        }

        if ($price <= 100000) {
            return round($price * 0.05, 2);
        }

        if ($price <= 499000) {
            // Gradual reduction: 5% at 100.000 down to 1.4% at 499.000
            // Nominal fee: Rp5.000 at 100k -> Rp7.000 at 499k
            $ratio = ($price - 100000) / (499000 - 100000);
            $fee = 5000 + ($ratio * (7000 - 5000));
            return round($fee, 2);
        }

        if ($price <= 1000000) {
            // Gradual reduction: 1.4% at 499.000 down to 1% at 1.000.000
            // Nominal fee: Rp7.000 at 499k -> Rp10.000 at 1jt
            $ratio = ($price - 499000) / (1000000 - 499000);
            $fee = 7000 + ($ratio * (10000 - 7000));
            return round($fee, 2);
        }

        if ($price < 10000000) {
            return 10000.0;
        }

        // Mulai Rp10.000.000: Rp20.000, Rp20.000.000: Rp30.000, dst.
        $multiplier = (int) floor($price / 10000000);
        return (float) ($multiplier + 1) * 10000.0;
    }
}
