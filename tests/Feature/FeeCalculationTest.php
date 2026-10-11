<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\FeeConfig;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\FeeCalculatorService;
use Database\Seeders\FeeConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FeeCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected FeeCalculatorService $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeeConfigSeeder::class);
        $this->feeService = app(FeeCalculatorService::class);
    }

    /**
     * Uji penolakan untuk transaksi di bawah batas minimum Rp5.000.
     */
    public function test_rejection_for_amount_under_5000(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('di bawah batas minimum yang diizinkan (Rp5.000)');

        $this->feeService->calculate(4999, 'EXCLUDE');
    }

    /**
     * Tier 1: Rp5.000 - Rp10.000 -> 10%.
     */
    public function test_tier_1_fee_calculation(): void
    {
        // Rp5.000 -> 10% = Rp500
        $resultMin = $this->feeService->calculate(5000, 'EXCLUDE');
        $this->assertEquals(500, $resultMin->feeAmount);
        $this->assertEquals(5500, $resultMin->buyerTotal);
        $this->assertEquals(5000, $resultMin->sellerNet);

        // Rp7.500 -> 10% = Rp750
        $resultMid = $this->feeService->calculate(7500, 'EXCLUDE');
        $this->assertEquals(750, $resultMid->feeAmount);
        $this->assertEquals(8250, $resultMid->buyerTotal);

        // Rp10.000 -> 10% = Rp1.000
        $resultMax = $this->feeService->calculate(10000, 'EXCLUDE');
        $this->assertEquals(1000, $resultMax->feeAmount);
        $this->assertEquals(11000, $resultMax->buyerTotal);
    }

    /**
     * Tier 2: > Rp10.000 - Rp50.000 -> Interpolasi Linier Rp1.000 - Rp2.500.
     */
    public function test_tier_2_fee_calculation(): void
    {
        // Rp25.000 -> 1000 + (15000 / 40000) * 1500 = 1000 + 562.5 = 1563 (ceil)
        $result = $this->feeService->calculate(25000, 'EXCLUDE');
        $this->assertEquals(1563, $result->feeAmount);
        $this->assertEquals(26563, $result->buyerTotal);
        $this->assertEquals(25000, $result->sellerNet);

        // Rp50.000 -> Rp2.500
        $resultMax = $this->feeService->calculate(50000, 'EXCLUDE');
        $this->assertEquals(2500, $resultMax->feeAmount);
        $this->assertEquals(52500, $resultMax->buyerTotal);
    }

    /**
     * Tier 3: > Rp50.000 - Rp100.000 -> 5%.
     */
    public function test_tier_3_fee_calculation(): void
    {
        // Rp75.000 -> 5% = Rp3.750
        $result = $this->feeService->calculate(75000, 'EXCLUDE');
        $this->assertEquals(3750, $result->feeAmount);
        $this->assertEquals(78750, $result->buyerTotal);
        $this->assertEquals(75000, $result->sellerNet);

        // Rp100.000 -> 5% = Rp5.000
        $resultMax = $this->feeService->calculate(100000, 'EXCLUDE');
        $this->assertEquals(5000, $resultMax->feeAmount);
        $this->assertEquals(105000, $resultMax->buyerTotal);
    }

    /**
     * Tier 4: > Rp100.000 - Rp499.000 -> Interpolasi Linier Rp5.000 - Rp7.000.
     */
    public function test_tier_4_fee_calculation(): void
    {
        // Rp250.000 -> 5000 + (150000 / 399000) * 2000 = 5000 + 751.88 = 5752 (ceil)
        $result = $this->feeService->calculate(250000, 'EXCLUDE');
        $this->assertEquals(5752, $result->feeAmount);
        $this->assertEquals(255752, $result->buyerTotal);

        // Rp499.000 -> Rp7.000
        $resultMax = $this->feeService->calculate(499000, 'EXCLUDE');
        $this->assertEquals(7000, $resultMax->feeAmount);
        $this->assertEquals(506000, $resultMax->buyerTotal);
    }

    /**
     * Tier 5: > Rp499.000 - Rp1.000.000 -> Interpolasi Linier Rp7.000 - Rp10.000.
     */
    public function test_tier_5_fee_calculation(): void
    {
        // Rp750.000 -> 7000 + (251000 / 501000) * 3000 = 7000 + 1502.99 = 8503 (ceil)
        $result = $this->feeService->calculate(750000, 'EXCLUDE');
        $this->assertEquals(8503, $result->feeAmount);
        $this->assertEquals(758503, $result->buyerTotal);

        // Rp1.000.000 -> Rp10.000
        $resultMax = $this->feeService->calculate(1000000, 'EXCLUDE');
        $this->assertEquals(10000, $resultMax->feeAmount);
        $this->assertEquals(1010000, $resultMax->buyerTotal);
    }

    /**
     * Tier 6: Rp1.000.000 - < Rp10.000.000 -> Flat Rp10.000.
     */
    public function test_tier_6_fee_calculation(): void
    {
        // Rp1.500.000 -> Flat Rp10.000
        $result1 = $this->feeService->calculate(1500000, 'EXCLUDE');
        $this->assertEquals(10000, $result1->feeAmount);
        $this->assertEquals(1510000, $result1->buyerTotal);

        // Rp5.000.000 -> Flat Rp10.000
        $result2 = $this->feeService->calculate(5000000, 'EXCLUDE');
        $this->assertEquals(10000, $result2->feeAmount);
        $this->assertEquals(5010000, $result2->buyerTotal);

        // Rp9.999.999 -> Flat Rp10.000
        $result3 = $this->feeService->calculate(9999999, 'EXCLUDE');
        $this->assertEquals(10000, $result3->feeAmount);
        $this->assertEquals(10009999, $result3->buyerTotal);
    }

    /**
     * Tier 7: >= Rp10.000.000 -> Rp20.000 + Rp10.000 per kelipatan Rp10 juta.
     */
    public function test_tier_7_fee_calculation(): void
    {
        // Rp10.000.000 -> Rp20.000
        $result10m = $this->feeService->calculate(10000000, 'EXCLUDE');
        $this->assertEquals(20000, $result10m->feeAmount);
        $this->assertEquals(10020000, $result10m->buyerTotal);

        // Rp15.000.000 -> tetap 1 kelipatan 10jt = Rp20.000
        $result15m = $this->feeService->calculate(15000000, 'EXCLUDE');
        $this->assertEquals(20000, $result15m->feeAmount);

        // Rp20.000.000 -> 2 kelipatan 10jt = Rp30.000
        $result20m = $this->feeService->calculate(20000000, 'EXCLUDE');
        $this->assertEquals(30000, $result20m->feeAmount);
        $this->assertEquals(20030000, $result20m->buyerTotal);

        // Rp35.000.000 -> 3 kelipatan 10jt = Rp40.000
        $result35m = $this->feeService->calculate(35000000, 'EXCLUDE');
        $this->assertEquals(40000, $result35m->feeAmount);
    }

    /**
     * Uji perbandingan mode EXCLUDE vs INCLUDE.
     */
    public function test_fee_modes_exclude_and_include(): void
    {
        // EXCLUDE: Buyer menanggung fee
        $resultEx = $this->feeService->calculate(50000, 'EXCLUDE');
        $this->assertEquals(50000, $resultEx->basePrice);
        $this->assertEquals('EXCLUDE', $resultEx->feeType);
        $this->assertEquals(2500, $resultEx->feeAmount);
        $this->assertEquals(52500, $resultEx->buyerTotal);
        $this->assertEquals(50000, $resultEx->sellerNet);

        // INCLUDE: Seller menanggung fee
        $resultInc = $this->feeService->calculate(50000, 'INCLUDE');
        $this->assertEquals(50000, $resultInc->basePrice);
        $this->assertEquals('INCLUDE', $resultInc->feeType);
        $this->assertEquals(2500, $resultInc->feeAmount);
        $this->assertEquals(50000, $resultInc->buyerTotal);
        $this->assertEquals(47500, $resultInc->sellerNet);
    }

    /**
     * Uji helper calculateForMarketplace yang selalu menggunakan mode EXCLUDE.
     */
    public function test_calculate_for_marketplace_is_always_exclude(): void
    {
        $result = $this->feeService->calculateForMarketplace(100000);

        $this->assertEquals('EXCLUDE', $result->feeType);
        $this->assertEquals(100000, $result->basePrice);
        $this->assertEquals(5000, $result->feeAmount);
        $this->assertEquals(105000, $result->buyerTotal);
        $this->assertEquals(100000, $result->sellerNet);
    }

    /**
     * Uji method applyToAgreement mengunci nilai snapshot finansial tanpa referensi fee_config_id.
     */
    public function test_apply_to_agreement_locks_snapshot_values_with_null_fee_config_id(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create([
            'category_name' => 'Akun Game',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Mythic Account',
            'is_active' => true,
        ]);

        $agreement = new Agreement([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Akun Mythic MLBB Polos',
            'item_description' => 'Fast hand delivery via email change',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => 'EXCLUDE',
            'delivery_method' => 'Email Account Credentials',
            'delivery_within_minutes' => 60,
            'status' => 'DRAFT',
        ]);

        $this->feeService->applyToAgreement($agreement);
        $agreement->save();

        $this->assertNotNull($agreement->agreement_id);
        $this->assertEquals(5000, $agreement->fee_amount);
        $this->assertEquals(105000, $agreement->buyer_total);
        $this->assertEquals(100000, $agreement->seller_net);
        // fee_config_id harus null karena fee berasal dari aturan aplikasi, bukan fee_configs
        $this->assertNull($agreement->fee_config_id);
    }

    /**
     * Uji bahwa perhitungan fee tidak bergantung pada tabel fee_configs (bahkan jika tabel kosong).
     */
    public function test_fee_calculation_is_independent_from_fee_configs_table(): void
    {
        FeeConfig::query()->delete();
        $this->assertEquals(0, FeeConfig::count());

        $result = $this->feeService->calculate(50000, 'EXCLUDE');
        $this->assertEquals(2500, $result->feeAmount);
        $this->assertEquals(52500, $result->buyerTotal);
    }

    /**
     * Test admin dapat mengakses halaman konfigurasi fee dan mencatat riwayat legasi baru.
     */
    public function test_admin_can_access_fee_config_page_and_create_legacy_record(): void
    {
        $admin = User::factory()->admin()->create(['account_status' => 'ACTIVE']);

        // 1. Kunjungi halaman index admin fee config
        $response = $this->actingAs($admin)->get('/admin/fee-configs');
        $response->assertStatus(200);
        $response->assertSee('Struktur Tarif');
        $response->assertSee('Tier 1');
        $response->assertSee('Tier 7');
        $response->assertSee('Jalur Marketplace');
        $response->assertSee('Wajib EXCLUDE');

        // 2. Kirim penetapan konfigurasi legasi baru
        $postResponse = $this->actingAs($admin)->post('/admin/fee-configs', [
            'min_amount' => 15000,
            'max_amount' => 2000000,
            'fee_percent' => 4.5,
            'flat_fee' => 1500,
        ]);

        $postResponse->assertRedirect('/admin/fee-configs');
        $postResponse->assertSessionHas('success');

        $activeConfig = FeeConfig::where('is_active', true)->first();
        $this->assertNotNull($activeConfig);
        $this->assertEquals(15000, $activeConfig->min_amount);
        $this->assertEquals(2000000, $activeConfig->max_amount);
        $this->assertEquals(4.5, $activeConfig->fee_percent);
        $this->assertEquals(1500, $activeConfig->flat_fee);

        $archivedCount = FeeConfig::where('is_active', false)->count();
        $this->assertGreaterThanOrEqual(1, $archivedCount);
    }

    /**
     * Test pengguna reguler dan tamu tidak dapat mengakses admin fee config.
     */
    public function test_regular_user_and_guest_cannot_access_admin_fee_config(): void
    {
        $regularUser = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        // Tamu dialihkan ke login
        $this->get('/admin/fee-configs')->assertRedirect('/login');

        // User biasa ditolak dengan 403 Forbidden
        $this->actingAs($regularUser)->get('/admin/fee-configs')->assertStatus(403);
    }

    /**
     * Test endpoint JSON POST /fee/preview mengembalikan hasil kalkulasi fee akurat.
     */
    public function test_fee_preview_endpoint_returns_json_calculation(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        $response = $this->actingAs($user)->postJson('/fee/preview', [
            'base_price' => 50000,
            'fee_type' => 'EXCLUDE',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'base_price' => 50000,
                'fee_type' => 'EXCLUDE',
                'fee_amount' => 2500,
                'buyer_total' => 52500,
                'seller_net' => 50000,
            ],
        ]);

        // Uji jika harga di bawah Rp5.000
        $invalidResponse = $this->actingAs($user)->postJson('/fee/preview', [
            'base_price' => 4999,
            'fee_type' => 'EXCLUDE',
        ]);

        $invalidResponse->assertStatus(422);
        $invalidResponse->assertJson(['status' => 'error']);
    }
}
