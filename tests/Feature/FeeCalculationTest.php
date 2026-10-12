<?php

namespace Tests\Feature;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class FeeCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected FeeCalculationServiceInterface $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeService = app(FeeCalculationServiceInterface::class);
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
     * Uji perbandingan mode EXCLUDE vs INCLUDE pada forum/DM.
     */
    public function test_fee_modes_exclude_and_include(): void
    {
        // EXCLUDE: Buyer menanggung fee
        $resultEx = $this->feeService->calculate(50000, 'EXCLUDE');
        $this->assertEquals(50000, $resultEx->basePrice);
        $this->assertEquals(FeeType::EXCLUDE, $resultEx->feeType);
        $this->assertEquals(2500, $resultEx->feeAmount);
        $this->assertEquals(52500, $resultEx->buyerTotal);
        $this->assertEquals(50000, $resultEx->sellerNet);

        // INCLUDE: Seller menanggung fee
        $resultInc = $this->feeService->calculate(50000, 'INCLUDE');
        $this->assertEquals(50000, $resultInc->basePrice);
        $this->assertEquals(FeeType::INCLUDE, $resultInc->feeType);
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

        $this->assertEquals(FeeType::EXCLUDE, $result->feeType);
        $this->assertEquals(100000, $result->basePrice);
        $this->assertEquals(5000, $result->feeAmount);
        $this->assertEquals(105000, $result->buyerTotal);
        $this->assertEquals(100000, $result->sellerNet);
    }

    /**
     * Uji method applyToAgreement mengunci nilai snapshot finansial tanpa fee_config_id.
     */
    public function test_apply_to_agreement_locks_snapshot_values_without_fee_config(): void
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

        $this->feeService->applyToAgreement($agreement, TransactionSourceType::MARKETPLACE);
        $agreement->save();

        $this->assertNotNull($agreement->agreement_id);
        $this->assertEquals(5000, $agreement->fee_amount);
        $this->assertEquals(105000, $agreement->buyer_total);
        $this->assertEquals(100000, $agreement->seller_net);

        // Kolom fee_config_id telah dihapus dari tabel agreements
        $this->assertArrayNotHasKey('fee_config_id', $agreement->getAttributes());
    }

    /**
     * Uji snapshot immutability: agreement yang sudah FINALIZED dan lengkap tidak boleh dihitung ulang.
     */
    public function test_finalized_agreement_snapshot_is_not_recalculated(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create([
            'category_name' => 'Game Items',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Steam Wallet',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Voucher 100K',
            'is_active' => true,
        ]);

        $agreement = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Voucher Game',
            'item_description' => 'Kode dikirim via chat',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 5000,
            'buyer_total' => 105000,
            'seller_net' => 100000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        // Simulasikan percobaan menghitung ulang dengan mengubah harga di memori
        $agreement->base_price = 200000;
        $this->feeService->applyToAgreement($agreement, TransactionSourceType::MARKETPLACE);

        // Nilai snapshot fee tetap Rp5.000 dan tidak berubah
        $this->assertEquals(5000, $agreement->fee_amount);
        $this->assertEquals(105000, $agreement->buyer_total);
        $this->assertEquals(100000, $agreement->seller_net);
    }

    /**
     * Uji bahwa agreement FINALIZED dengan snapshot tidak lengkap melempar LogicException.
     */
    public function test_finalized_agreement_with_incomplete_snapshot_throws_logic_exception(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Game', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'MLBB', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'V1', 'is_active' => true]);

        $agreement = new Agreement([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item Test',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => null, // snapshot finansial belum lengkap!
            'buyer_total' => null,
            'seller_net' => null,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Kesepakatan sudah berstatus FINALIZED tetapi snapshot finansial tidak lengkap.');

        $this->feeService->applyToAgreement($agreement, TransactionSourceType::FORUM);
    }

    /**
     * Uji bahwa pemanggilan applyToAgreement tanpa source dan tanpa relasi tepercaya melempar InvalidArgumentException.
     */
    public function test_apply_to_agreement_without_source_and_unresolvable_relation_throws_exception(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Game', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'MLBB', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'V1', 'is_active' => true]);

        $agreement = new Agreement([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item Standalone',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 50000,
            'fee_type' => 'EXCLUDE',
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => 'DRAFT',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sumber transaksi wajib ditentukan ('MARKETPLACE' atau 'FORUM') untuk menerapkan fee pada agreement.");

        $this->feeService->applyToAgreement($agreement);
    }

    /**
     * Uji bahwa applyToAgreement mendeteksi sumber MARKETPLACE dari relasi conversation yang memiliki listing_id.
     */
    public function test_apply_to_agreement_resolves_trusted_source_from_conversation_listing(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Game', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'MLBB', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'V1', 'is_active' => true]);

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Uji Auto Detect',
            'description' => 'Desc',
            'price' => 50000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $agreement = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item dari Listing',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 50000,
            'fee_type' => 'INCLUDE', // Klien awalnya set INCLUDE
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => 'DRAFT',
        ]);

        // Hubungkan dengan percakapan listing
        $conversation = Conversation::create([
            'participant_one_id' => min($buyer->user_id, $seller->user_id),
            'participant_two_id' => max($buyer->user_id, $seller->user_id),
            'listing_id' => $listing->listing_id,
            'agreement_id' => $agreement->agreement_id,
            'created_at' => now(),
        ]);

        $agreement->setRelation('conversation', $conversation);

        // Panggil tanpa parameter $source -> harus auto-detect MARKETPLACE dan paksa EXCLUDE
        $this->feeService->applyToAgreement($agreement);

        $this->assertEquals(FeeType::EXCLUDE, $agreement->fee_type);
        $this->assertEquals(2500, $agreement->fee_amount);
        $this->assertEquals(52500, $agreement->buyer_total);
        $this->assertEquals(50000, $agreement->seller_net);
    }

    /**
     * Uji bahwa applyToAgreement pada sumber MARKETPLACE selalu memaksa EXCLUDE walaupun agreement diset INCLUDE.
     */
    public function test_apply_to_agreement_forces_exclude_on_marketplace_even_if_include_set(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Game', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'MLBB', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'V1', 'is_active' => true]);

        $agreement = new Agreement([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item Marketplace',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => FeeType::INCLUDE->value, // Coba paksa INCLUDE
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => 'DRAFT',
        ]);

        $this->feeService->applyToAgreement($agreement, 'MARKETPLACE');

        // Wajib dipaksa EXCLUDE di sisi server
        $this->assertEquals(FeeType::EXCLUDE, $agreement->fee_type);
        $this->assertEquals(5000, $agreement->fee_amount);
        $this->assertEquals(105000, $agreement->buyer_total);
        $this->assertEquals(100000, $agreement->seller_net);
    }

    /**
     * Uji AgreementService::createFromListing memaksa EXCLUDE dan menolak harga di bawah minimum.
     */
    public function test_agreement_service_create_from_listing_enforces_rules(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Voucher', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Voucher', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'Default', 'is_active' => true]);

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Produk Uji',
            'description' => 'Deskripsi listing produk',
            'price' => 50000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $agreementService = app(AgreementServiceInterface::class);
        $agreement = $agreementService->createFromListing($listing, $buyer, $seller);

        $this->assertEquals(FeeType::EXCLUDE, $agreement->fee_type);
        $this->assertEquals(2500.00, (float) $agreement->fee_amount);
        $this->assertEquals(52500.00, (float) $agreement->buyer_total);
        $this->assertEquals(50000.00, (float) $agreement->seller_net);

        // Uji penolakan harga di bawah Rp5.000 pada listing
        $cheapListing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Murah',
            'description' => 'Deskripsi listing murah',
            'price' => 3000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('di bawah batas minimum yang diizinkan (Rp5.000)');
        $agreementService->createFromListing($cheapListing, $buyer, $seller);
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

    /**
     * Test endpoint JSON POST /fee/preview memaksa EXCLUDE saat source=MARKETPLACE
     * meskipun request mengirimkan fee_type=INCLUDE.
     */
    public function test_fee_preview_endpoint_forces_exclude_for_marketplace(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        $response = $this->actingAs($user)->postJson('/fee/preview', [
            'base_price' => 100000,
            'fee_type' => 'INCLUDE', // Klien mencoba minta INCLUDE
            'source' => 'MARKETPLACE', // Tetapi jalur marketplace
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'base_price' => 100000,
                'fee_type' => 'EXCLUDE', // Server-side memaksa EXCLUDE
                'fee_amount' => 5000,
                'buyer_total' => 105000,
                'seller_net' => 100000,
            ],
        ]);
    }

    /**
     * Uji bahwa agreement yang terkait listing marketplace dengan fee_type=INCLUDE dan $source='FORUM'
     * wajib ditolak dengan exception, bukan menghasilkan snapshot INCLUDE.
     */
    public function test_marketplace_agreement_with_explicit_forum_source_is_rejected(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $category = ProductCategory::create(['category_name' => 'Game', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'MLBB', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'variant_name' => 'V1', 'is_active' => true]);

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Uji Konflik',
            'description' => 'Desc',
            'price' => 100000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $agreement = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item dari Listing',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_type' => 'INCLUDE', // Sengaja diisi INCLUDE
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'status' => 'DRAFT',
        ]);

        // Hubungkan agreement dengan conversation yang memiliki listing_id
        $conversation = Conversation::create([
            'participant_one_id' => min($buyer->user_id, $seller->user_id),
            'participant_two_id' => max($buyer->user_id, $seller->user_id),
            'listing_id' => $listing->listing_id,
            'agreement_id' => $agreement->agreement_id,
            'created_at' => now(),
        ]);

        $agreement->setRelation('conversation', $conversation);

        // Percobaan menerapkan $source = 'FORUM' harus ditolak karena bertentangan dengan relasi listing marketplace
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sumber transaksi eksplisit ('FORUM') bertentangan dengan sumber relasi tersimpan ('MARKETPLACE').");

        $this->feeService->applyToAgreement($agreement, 'FORUM');
    }
}
