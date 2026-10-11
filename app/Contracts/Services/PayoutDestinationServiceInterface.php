<?php

namespace App\Contracts\Services;

use App\DTOs\PayoutDestinationDTO;
use App\Models\User;

interface PayoutDestinationServiceInterface
{
    /**
     * Retrieve payout destination details for a user.
     */
    public function getDestinationForUser(User $user): ?PayoutDestinationDTO;

    /**
     * Validate payout destination data.
     */
    public function validateDestination(PayoutDestinationDTO $destination): bool;
}
