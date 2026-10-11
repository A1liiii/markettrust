<?php

namespace App\Services;

use App\Contracts\Services\AgreementServiceInterface;
use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Models\Agreement;
use App\Models\Listing;
use App\Models\User;

class AgreementService implements AgreementServiceInterface
{
    /**
     * Create an agreement from a marketplace listing.
     */
    public function createFromListing(Listing $listing, User $buyer, User $seller): Agreement
    {
        $feeCalculation = $this->calculateFee((float) $listing->price, 'EXCLUDE');

        return Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $listing->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => $listing->price,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => $feeCalculation->feeAmount,
            'buyer_total' => $feeCalculation->buyerTotal,
            'seller_net' => $feeCalculation->sellerNet,
            'delivery_method' => $listing->delivery_method,
            'delivery_within_minutes' => $listing->estimated_delivery_unit === 'HOUR'
                ? $listing->estimated_delivery_value * 60
                : $listing->estimated_delivery_value,
            'revision_no' => 1,
            'status' => AgreementStatus::DRAFT->value,
        ]);
    }

    /**
     * Create an immutable snapshot DTO from an agreement.
     */
    public function createSnapshot(Agreement $agreement): AgreementSnapshotDTO
    {
        return AgreementSnapshotDTO::fromModel($agreement);
    }

    /**
     * Calculate fee helper (kept for backwards compatibility).
     */
    public function calculateFee(float $basePrice, string $feeType = 'EXCLUDE'): FeeCalculationResultDTO
    {
        return app(FeeCalculationService::class)->calculate($basePrice, $feeType);
    }
}
