<?php

namespace App\DTOs;

use App\Enums\PayoutDestinationType;

class PayoutDestinationDTO
{
    public readonly PayoutDestinationType $destinationType;

    public function __construct(
        PayoutDestinationType|string $destinationType,
        public readonly string $destinationHolder,
        public readonly ?string $destinationProvider = null,
        public readonly ?string $destinationNumber = null,
        public readonly ?string $destinationQrisImageUrl = null
    ) {
        $this->destinationType = $destinationType instanceof PayoutDestinationType
            ? $destinationType
            : PayoutDestinationType::from(strtoupper($destinationType));
    }

    public function toArray(): array
    {
        return [
            'destination_type' => $this->destinationType->value,
            'destination_provider' => $this->destinationProvider,
            'destination_number' => $this->destinationNumber,
            'destination_holder' => $this->destinationHolder,
            'destination_qris_image_url' => $this->destinationQrisImageUrl,
        ];
    }
}
