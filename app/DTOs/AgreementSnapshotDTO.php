<?php

namespace App\DTOs;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Models\Agreement;

class AgreementSnapshotDTO
{
    public function __construct(
        public readonly ?int $agreementId,
        public readonly int $createdBy,
        public readonly int $buyerId,
        public readonly int $sellerId,
        public readonly int $variantId,
        public readonly ?int $feeConfigId,
        public readonly string $itemTitle,
        public readonly string $itemDescription,
        public readonly int $quantity,
        public readonly float $basePrice,
        public readonly FeeType $feeType,
        public readonly float $feeAmount,
        public readonly float $buyerTotal,
        public readonly float $sellerNet,
        public readonly string $deliveryMethod,
        public readonly int $deliveryWithinMinutes,
        public readonly ?string $agreementNote,
        public readonly int $revisionNo,
        public readonly AgreementStatus $status,
        public readonly ?string $buyerAcceptedAt = null,
        public readonly ?string $sellerAcceptedAt = null,
        public readonly ?string $finalizedAt = null
    ) {}

    public static function fromModel(Agreement $agreement): self
    {
        return new self(
            agreementId: $agreement->agreement_id,
            createdBy: (int) $agreement->created_by,
            buyerId: (int) $agreement->buyer_id,
            sellerId: (int) $agreement->seller_id,
            variantId: (int) $agreement->variant_id,
            feeConfigId: $agreement->fee_config_id ? (int) $agreement->fee_config_id : null,
            itemTitle: (string) $agreement->item_title,
            itemDescription: (string) $agreement->item_description,
            quantity: (int) $agreement->quantity,
            basePrice: (float) $agreement->base_price,
            feeType: $agreement->fee_type instanceof FeeType ? $agreement->fee_type : FeeType::from($agreement->fee_type),
            feeAmount: (float) $agreement->fee_amount,
            buyerTotal: (float) $agreement->buyer_total,
            sellerNet: (float) $agreement->seller_net,
            deliveryMethod: (string) $agreement->delivery_method,
            deliveryWithinMinutes: (int) $agreement->delivery_within_minutes,
            agreementNote: $agreement->agreement_note,
            revisionNo: (int) $agreement->revision_no,
            status: $agreement->status instanceof AgreementStatus ? $agreement->status : AgreementStatus::from($agreement->status),
            buyerAcceptedAt: $agreement->buyer_accepted_at?->toIso8601String(),
            sellerAcceptedAt: $agreement->seller_accepted_at?->toIso8601String(),
            finalizedAt: $agreement->finalized_at?->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'agreement_id' => $this->agreementId,
            'created_by' => $this->createdBy,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'variant_id' => $this->variantId,
            'fee_config_id' => $this->feeConfigId,
            'item_title' => $this->itemTitle,
            'item_description' => $this->itemDescription,
            'quantity' => $this->quantity,
            'base_price' => $this->basePrice,
            'fee_type' => $this->feeType->value,
            'fee_amount' => $this->feeAmount,
            'buyer_total' => $this->buyerTotal,
            'seller_net' => $this->sellerNet,
            'delivery_method' => $this->deliveryMethod,
            'delivery_within_minutes' => $this->deliveryWithinMinutes,
            'agreement_note' => $this->agreementNote,
            'revision_no' => $this->revisionNo,
            'status' => $this->status->value,
            'buyer_accepted_at' => $this->buyerAcceptedAt,
            'seller_accepted_at' => $this->sellerAcceptedAt,
            'finalized_at' => $this->finalizedAt,
        ];
    }
}
