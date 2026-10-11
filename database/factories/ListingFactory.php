<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'variant_id' => ProductVariant::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(10000, 500000),
            'delivery_method' => 'Kredensial Akun via Catatan Terenkripsi',
            'estimated_delivery_value' => fake()->randomElement([15, 30, 60, 2, 24]),
            'estimated_delivery_unit' => fake()->randomElement(['MINUTE', 'HOUR', 'DAY']),
            'status' => 'ACTIVE',
        ];
    }

    /**
     * Indicate that the listing is in DRAFT status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'DRAFT',
        ]);
    }

    /**
     * Indicate that the listing is in ACTIVE status.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Indicate that the listing is in CLOSED status.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'CLOSED',
        ]);
    }
}
