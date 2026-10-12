<?php

namespace App\Services;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\FeeCalculationServiceInterface;
use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Models\Agreement;
use App\Models\Listing;
use App\Models\User;
use InvalidArgumentException;

class AgreementService implements AgreementServiceInterface
{
    public function __construct(
        protected FeeCalculationServiceInterface $feeCalculationService
    ) {}

    /**
     * Create an agreement from a marketplace listing.
     * Aturan bisnis marketplace: harga listing tetap, fee wajib ditambahkan ke Buyer (EXCLUDE),
     * Seller menerima harga listing penuh.
     *
     * @throws InvalidArgumentException
     */
    public function createFromListing(Listing $listing, User $buyer, User $seller): Agreement
    {
        $basePrice = (float) $listing->price;

        if ($basePrice < 5000) {
            throw new InvalidArgumentException(sprintf(
                'Harga dasar transaksi (Rp%s) di bawah batas minimum yang diizinkan (Rp5.000).',
                number_format($basePrice, 0, ',', '.')
            ));
        }

        $feeCalculation = $this->feeCalculationService->calculateForMarketplace($basePrice);

        return Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $listing->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => $basePrice,
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
        return $this->feeCalculationService->calculate($basePrice, $feeType);
    }
}
