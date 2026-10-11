<?php

namespace App\DTOs;

use App\Enums\FeeType;

class FeeCalculationResultDTO
{
    public readonly FeeType $feeType;

    public function __construct(
        public readonly float $basePrice,
        FeeType|string $feeType,
        public readonly float $feeAmount,
        public readonly float $buyerTotal,
        public readonly float $sellerNet,
        public readonly ?int $feeConfigId = null,
        public readonly float $percentageRate = 0.05,
        public readonly float $minFeeAmount = 1000.00
    ) {
        $this->feeType = $feeType instanceof FeeType ? $feeType : FeeType::from(strtoupper($feeType));
    }

    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice,
            'fee_type' => $this->feeType->value,
            'fee_amount' => $this->feeAmount,
            'buyer_total' => $this->buyerTotal,
            'seller_net' => $this->sellerNet,
            'fee_config_id' => $this->feeConfigId,
            'percentage_rate' => $this->percentageRate,
            'min_fee_amount' => $this->minFeeAmount,
        ];
    }
}
