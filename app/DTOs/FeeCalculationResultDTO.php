<?php

namespace App\DTOs;

use App\Enums\FeeType;

class FeeCalculationResultDTO
{
    public readonly FeeType $feeType;

    public readonly float $feePercent;

    public readonly float $flatFee;

    public function __construct(
        public readonly float $basePrice,
        FeeType|string $feeType,
        public readonly float $feeAmount,
        public readonly float $buyerTotal,
        public readonly float $sellerNet,
        ?float $feePercent = null,
        ?float $flatFee = null
    ) {
        $this->feeType = $feeType instanceof FeeType ? $feeType : FeeType::from(strtoupper($feeType));
        $this->feePercent = $feePercent ?? ($this->basePrice > 0 ? round(($this->feeAmount / $this->basePrice) * 100, 2) : 0.0);
        $this->flatFee = $flatFee ?? ($this->basePrice > 1000000 ? 10000.0 : 0.0);
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice,
            'fee_type' => $this->feeType->value,
            'fee_percent' => $this->feePercent,
            'flat_fee' => $this->flatFee,
            'fee_amount' => $this->feeAmount,
            'buyer_total' => $this->buyerTotal,
            'seller_net' => $this->sellerNet,
        ];
    }
}
