<?php

namespace App\DTO;

class FeeCalculationResult
{
    /**
     * Data Transfer Object for Fee Calculation.
     */
    public function __construct(
        public readonly float $basePrice,
        public readonly string $feeType, // 'EXCLUDE' or 'INCLUDE'
        public readonly float $feePercent,
        public readonly float $flatFee,
        public readonly float $feeAmount,
        public readonly float $buyerTotal,
        public readonly float $sellerNet,
        public readonly ?int $feeConfigId = null,
    ) {}

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice,
            'fee_type' => $this->feeType,
            'fee_percent' => $this->feePercent,
            'flat_fee' => $this->flatFee,
            'fee_amount' => $this->feeAmount,
            'buyer_total' => $this->buyerTotal,
            'seller_net' => $this->sellerNet,
            'fee_config_id' => $this->feeConfigId,
        ];
    }
}
