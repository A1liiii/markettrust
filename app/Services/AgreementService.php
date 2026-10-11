<?php

namespace App\Services;

use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;

class AgreementService
{
    /**
     * Calculate fee: 5% from base price (min Rp 1.000, rounded up to full rupiah).
     */
    public function calculateFee(float $basePrice, string $feeType = 'EXCLUDE'): FeeCalculationResultDTO
    {
        $percentageRate = 0.05;
        $minFeeAmount = 1000.00;

        $rawFee = $basePrice * $percentageRate;
        $calculatedFee = max($rawFee, $minFeeAmount);
        $feeAmount = (float) ceil($calculatedFee);

        if (strtoupper($feeType) === 'EXCLUDE') {
            $buyerTotal = $basePrice + $feeAmount;
            $sellerNet = $basePrice;
        } else { // INCLUDE
            $buyerTotal = $basePrice;
            $sellerNet = max(0.00, $basePrice - $feeAmount);
        }

        return new FeeCalculationResultDTO(
            basePrice: $basePrice,
            feeType: $feeType,
            feeAmount: $feeAmount,
            buyerTotal: $buyerTotal,
            sellerNet: $sellerNet
        );
    }
}
