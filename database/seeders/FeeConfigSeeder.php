<?php

namespace Database\Seeders;

use App\Models\FeeConfig;
use Illuminate\Database\Seeder;

class FeeConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FeeConfig::firstOrCreate(
            ['is_active' => true],
            [
                'min_amount' => 10000.00,
                'max_amount' => 1000000.00,
                'fee_percent' => 5.00,
                'flat_fee' => 1000.00,
                'is_active' => true,
                'effective_from' => now()->subDay(),
            ]
        );
    }
}
