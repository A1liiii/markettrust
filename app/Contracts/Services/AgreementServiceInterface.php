<?php

namespace App\Contracts\Services;

use App\DTOs\AgreementSnapshotDTO;
use App\Models\Agreement;
use App\Models\Listing;
use App\Models\User;

interface AgreementServiceInterface
{
    /**
     * Create an agreement from a marketplace listing.
     */
    public function createFromListing(Listing $listing, User $buyer, User $seller): Agreement;

    /**
     * Create an immutable snapshot DTO from an agreement.
     */
    public function createSnapshot(Agreement $agreement): AgreementSnapshotDTO;
}
