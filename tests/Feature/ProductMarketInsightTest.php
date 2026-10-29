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
use Illuminate\Support\Facades\DB;
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
        $this->assertEquals('30d', $stats['period']);
        $this->assertEquals('30 Hari Terakhir', $stats['period_label']);
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
     * Uji pemfilteran periode waktu (7d, 30d, 90d, all):
     * Memastikan transaksi yang masuk hitungan hanya yang berada dalam rentang tanggal periode yang dipilih.
     */
    public function test_period_filtering_calculates_average_price_accurately(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Periode',
            'description' => 'Desc',
            'price' => 100000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        // Transaksi 1: 5 hari lalu (harga 100.000) -> masuk 7d, 30d, 90d, all
        $agr1 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item 1',
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
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);
        $tx1 = Transaction::create([
            'agreement_id' => $agr1->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->subDays(5),
        ]);
        DB::table('transactions')->where('transaction_id', $tx1->transaction_id)->update([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Transaksi 2: 20 hari lalu (harga 200.000) -> tidak masuk 7d, masuk 30d, 90d, all
        $agr2 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item 2',
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
        $tx2 = Transaction::create([
            'agreement_id' => $agr2->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->subDays(20),
        ]);
        DB::table('transactions')->where('transaction_id', $tx2->transaction_id)->update([
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        // Transaksi 3: 60 hari lalu (harga 300.000) -> tidak masuk 7d & 30d, masuk 90d & all
        $agr3 = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item 3',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 300000,
            'fee_amount' => 10000,
            'fee_type' => 'EXCLUDE',
            'buyer_total' => 310000,
            'seller_net' => 300000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED,
        ]);
        $tx3 = Transaction::create([
            'agreement_id' => $agr3->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->subDays(60),
        ]);
        DB::table('transactions')->where('transaction_id', $tx3->transaction_id)->update([
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);

        // 1. Uji Periode 7 Hari: hanya transaksi 1
        $stats7d = $this->marketplaceService->getProductMarketStats($variant->variant_id, '7d');
        $this->assertEquals(1, $stats7d['sample_count']);
        $this->assertEquals(100000.00, $stats7d['avg_price']);
        $this->assertEquals('7d', $stats7d['period']);
        $this->assertEquals('7 Hari Terakhir', $stats7d['period_label']);

        // 2. Uji Periode 30 Hari: transaksi 1 dan 2 (rata-rata 150.000)
        $stats30d = $this->marketplaceService->getProductMarketStats($variant->variant_id, '30d');
        $this->assertEquals(2, $stats30d['sample_count']);
        $this->assertEquals(150000.00, $stats30d['avg_price']);
        $this->assertEquals('30d', $stats30d['period']);
        $this->assertEquals('30 Hari Terakhir', $stats30d['period_label']);

        // 3. Uji Periode 90 Hari: transaksi 1, 2, dan 3 (rata-rata 200.000)
        $stats90d = $this->marketplaceService->getProductMarketStats($variant->variant_id, '90d');
        $this->assertEquals(3, $stats90d['sample_count']);
        $this->assertEquals(200000.00, $stats90d['avg_price']);
        $this->assertEquals('90d', $stats90d['period']);
        $this->assertEquals('90 Hari Terakhir', $stats90d['period_label']);

        // 4. Uji Periode Semua Waktu (all)
        $statsAll = $this->marketplaceService->getProductMarketStats($variant->variant_id, 'all');
        $this->assertEquals(3, $statsAll['sample_count']);
        $this->assertEquals(200000.00, $statsAll['avg_price']);
        $this->assertEquals('all', $statsAll['period']);
        $this->assertEquals('Semua Waktu', $statsAll['period_label']);
    }

    /**
     * Parameter periode yang tidak valid atau kosong otomatis fallback ke default 30d.
     */
    public function test_fallback_to_default_30_days_on_invalid_period_param(): void
    {
        $variant = $this->createVariant();

        $statsInvalid = $this->marketplaceService->getProductMarketStats($variant->variant_id, 'sembarang');
        $this->assertEquals('30d', $statsInvalid['period']);
        $this->assertEquals('30 Hari Terakhir', $statsInvalid['period_label']);
        $this->assertEquals(30, $statsInvalid['period_days']);

        $statsNull = $this->marketplaceService->getProductMarketStats($variant->variant_id, null);
        $this->assertEquals('30d', $statsNull['period']);
        $this->assertEquals('30 Hari Terakhir', $statsNull['period_label']);
    }

    /**
     * Memastikan varian lain terisolasi dan tidak ikut tercampur.
     */
    public function test_variant_isolation_in_market_stats(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variantA = $this->createVariant();
        $variantB = $this->createVariant();

        $listingA = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variantA->variant_id,
            'title' => 'Item Variant A',
            'description' => 'Desc',
            'price' => 50000,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $agrA = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variantA->variant_id,
            'item_title' => 'Item Variant A',
            'item_description' => 'Desc',
            'quantity' => 1,
            'base_price' => 50000,
            'fee_amount' => 2000,
            'fee_type' => 'EXCLUDE',
            'buyer_total' => 52000,
            'seller_net' => 50000,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED,
        ]);
        Transaction::create([
            'agreement_id' => $agrA->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listingA->listing_id,
            'status' => TransactionStatus::COMPLETED,
            'payment_due_at' => now()->addHour(),
        ]);

        // Cek statistik untuk Variant B (harus tetap kosong)
        $statsB = $this->marketplaceService->getProductMarketStats($variantB->variant_id);
        $this->assertEquals(0, $statsB['sample_count']);
        $this->assertFalse($statsB['has_price_data']);
        $this->assertNull($statsB['avg_price']);
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
     * Halaman detail listing menampilkan widget informasi pasar, filter periode, dan label eksplisit (UC05).
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

        // 1. Akses default tanpa parameter (30d)
        $response = $this->get('/marketplace/'.$listing->listing_id);

        $response->assertOk();
        $response->assertSee('Informasi Pasar Produk');
        $response->assertSee('Demand (Permintaan)');
        $response->assertSee('Supply (Penawaran)');
        $response->assertSee('Periode: 30 Hari Terakhir');
        $response->assertSee('7 Hari');
        $response->assertSee('30 Hari');
        $response->assertSee('90 Hari');
        $response->assertSee('Semua');
        $response->assertSee('Data harga belum tersedia');

        // 2. Akses dengan filter periode 7d
        $response7d = $this->get('/marketplace/'.$listing->listing_id.'?period=7d');
        $response7d->assertOk();
        $response7d->assertSee('Periode: 7 Hari Terakhir');

        // 3. Akses dengan parameter tidak valid (harus fallback aman ke 30d)
        $responseInvalid = $this->get('/marketplace/'.$listing->listing_id.'?period=invalid123');
        $responseInvalid->assertOk();
        $responseInvalid->assertSee('Periode: 30 Hari Terakhir');
    }
}
