<?php

namespace App\Services;

use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\DTOs\PayoutDestinationDTO;
use App\Enums\PayoutDestinationType;
use App\Models\User;

class PayoutDestinationService implements PayoutDestinationServiceInterface
{
    /**
     * Retrieve payout destination details for a user.
     */
    public function getDestinationForUser(User $user): ?PayoutDestinationDTO
    {
        // For MVP, if user has seller account details or settlements recorded
        $latestSettlement = $user->sellerAgreements()
            ->whereHas('transactions.settlement')
            ->with('transactions.settlement')
            ->first()
            ?->transactions
            ?->first()
            ?->settlement;

        if ($latestSettlement && $latestSettlement->destination_holder) {
            return new PayoutDestinationDTO(
                destinationType: $latestSettlement->destination_type,
                destinationHolder: $latestSettlement->destination_holder,
                destinationProvider: $latestSettlement->destination_provider,
                destinationNumber: $latestSettlement->destination_number,
                destinationQrisImageUrl: $latestSettlement->destination_qris_image_url
            );
        }

        return null;
    }

    /**
     * Validate payout destination data.
     */
    public function validateDestination(PayoutDestinationDTO $destination): bool
    {
        if (empty(trim($destination->destinationHolder))) {
            return false;
        }

        return match ($destination->destinationType) {
            PayoutDestinationType::BANK => ! empty($destination->destinationProvider) && ! empty($destination->destinationNumber),
            PayoutDestinationType::EWALLET => ! empty($destination->destinationProvider) && ! empty($destination->destinationNumber),
            PayoutDestinationType::QRIS => ! empty($destination->destinationQrisImageUrl) || ! empty($destination->destinationNumber),
        };
    }
}
