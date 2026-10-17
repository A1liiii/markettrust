<?php

namespace Tests\Feature;

use App\Contracts\Services\MarketplaceServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMarketInsightTest extends TestCase
{
    use RefreshDatabase;

    protected MarketplaceServiceInterface $marketplaceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketplaceService = app(MarketplaceServiceInterface::class);
    }

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Kategori Akun '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Valorant '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Radiant Account '.uniqid(),
            'is_active' => true,
        ]);
    }

    /**
     * Uji kalkulasi demand & supply:
     * - Demand = postingan forum WTB yang aktif
     * - Supply = listing marketplace aktif + postingan forum WTS yang aktif
     */
    public function test_demand_and_supply_calculation(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();

        // Listing aktif pada varian
        Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Aktif 1',
            'description' => 'Desc',
            'price' => 100000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
        Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Aktif 2',
            'description' => 'Desc',
            'price' => 120000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
        // Listing DRAFT (tidak boleh dihitung ke supply)
        Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Draft',
            'description' => 'Desc',
            'price' => 90000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'DRAFT',
        ]);

        // Postingan Forum WTS aktif pada varian ini (tambah ke supply)
        ForumPost::create([
            'author_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'category_code' => 'WTS',
            'title' => 'WTS Radiant Skin',
            'content' => 'Desc',
            'price_offer' => 150000,
            'status' => 'ACTIVE',
        ]);

        // Postingan Forum WTB aktif pada varian ini (tambah ke demand)
        ForumPost::create([
            'author_id' => $buyer->user_id,
            'variant_id' => $variant->variant_id,
            'category_code' => 'WTB',
            'title' => 'WTB Radiant Skin Budget 120k',
            'content' => 'Desc',
            'price_offer' => 120000,
            'status' => 'ACTIVE',
        ]);

        // Postingan Forum WTB CLOSED/RESOLVED (tidak boleh dihitung)
        ForumPost::create([
            'author_id' => $buyer->user_id,
            'variant_id' => $variant->variant_id,
            'category_code' => 'WTB',
            'title' => 'WTB Sudah Dapat',
            'content' => 'Desc',
            'price_offer' => 100000,
            'status' => 'CLOSED',
        ]);

        $stats = $this->marketplaceService->getProductMarketStats($variant->variant_id);

        $this->assertEquals(1, $stats['demand']);
        $this->assertEquals(3, $stats['supply']); // 2 listings + 1 WTS
        $this->assertEquals(2, $stats['active_listings_count']);
        $this->assertEquals(1, $stats['wts_count']);
    }

    /**
     * Rata-rata harga pasar dihitung murni dari transaksi yang telah COMPLETED.
     * Transaksi WAITING_PAYMENT, PAID, CANCELLED, atau REFUNDED tidak diikutsertakan.
     */
    public function test_average_price_calculated_only_from_completed_transactions(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();

        $listing1 = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing 1',
            'description' => 'Desc',
            'price' => 100000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $listing2 = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing 2',
            'description' => 'Desc',
            'price' => 200000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        // Transaksi 1: COMPLETED (harga 100.000)
        $agr1 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Listing 1',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 100000,
            'fee_amount' => 5000,
            'fee_type' => 'EXCLUDE',
            'buyer_total' => 105000,
            'seller_net' => 100000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED,
        ]);
        Transaction::create([
            'agreement_id' => $agr1->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing1->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->addHour(),
        ]);

        // Transaksi 2: COMPLETED (harga 200.000)
        $agr2 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Listing 2',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 200000,
            'fee_amount' => 8000,
            'fee_type' => 'EXCLUDE',
            'buyer_total' => 208000,
            'seller_net' => 200000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED,
        ]);
        Transaction::create([
            'agreement_id' => $agr2->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing2->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->addHour(),
        ]);

        // Transaksi 3: CANCELLED (harga 500.000 - tidak boleh masuk hitungan rata-rata)
        $agr3 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Listing 3 Cancelled',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 500000,
            'fee_amount' => 15000,
            'fee_type' => 'EXCLUDE',
            'buyer_total' => 515000,
            'seller_net' => 500000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::CANCELLED,
        ]);
        Transaction::create([
            'agreement_id' => $agr3->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing1->listing_id,
            'status' => TransactionStatus::CANCELLED,
            'payment_due_at' => now()->addHour(),
        ]);

        $stats = $this->marketplaceService->getProductMarketStats($variant->variant_id);

        $this->assertEquals(2, $stats['sample_count']);
        $this->assertTrue($stats['has_price_data']);
        // (100.000 + 200.000) / 2 = 150.000
        $this->assertEquals(150000.00, $stats['avg_price']);
    }

    /**
     * Jika belum ada transaksi yang selesai, nilai avg_price adalah null.
     */
    public function test_average_price_is_null_when_no_completed_transactions_exist(): void
    {
        $variant = $this->createVariant();

        $stats = $this->marketplaceService->getProductMarketStats($variant->variant_id);

        $this->assertEquals(0, $stats['sample_count']);
        $this->assertFalse($stats['has_price_data']);
        $this->assertNull($stats['avg_price']);
    }

    /**
     * Halaman detail listing menampilkan badge statistik pasar secara akurat (UC05).
     */
    public function test_market_stats_displayed_on_listing_detail_page(): void
    {
        $seller = User::factory()->create();
        $variant = $this->createVariant();
        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing dengan Statistik Pasar',
            'description' => 'Desc',
            'price' => 100000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $response = $this->get('/marketplace/'.$listing->listing_id);

        $response->assertOk();
        $response->assertSee('Informasi Pasar Produk');
        $response->assertSee('Demand (Permintaan)');
        $response->assertSee('Supply (Penawaran)');
        $response->assertSee('Data harga belum tersedia');
    }
}
