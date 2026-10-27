<?php

namespace Database\Factories;

use App\Enums\PayoutDestinationType;
use App\Models\SellerPayoutAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerPayoutAccount>
 */
class SellerPayoutAccountFactory extends Factory
{
    protected $model = SellerPayoutAccount::class;

    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'destination_number' => fake()->numerify('##########'),
            'destination_holder' => fake()->name(),
            'destination_qris_image_url' => null,
            'is_active' => true,
        ];
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Pemilik Rekening BCA',
            'destination_qris_image_url' => null,
        ]);
    }

    public function ewallet(): static
    {
        return $this->state(fn () => [
            'destination_type' => PayoutDestinationType::EWALLET,
            'destination_provider' => 'GoPay',
            'destination_number' => '081234567890',
            'destination_holder' => 'Pemilik Akun GoPay',
            'destination_qris_image_url' => null,
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn () => [
            'destination_type' => PayoutDestinationType::QRIS,
            'destination_provider' => 'QRIS',
            'destination_number' => 'ID1020304050',
            'destination_holder' => 'Toko Digital Sejahtera',
            'destination_qris_image_url' => 'payout_qris/sample_qris.png',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
