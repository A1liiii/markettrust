<?php

namespace Tests\Feature\MarketTrust;

use App\Contracts\Services\MarketplaceServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
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

class MarketplaceStatisticsScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected ProductCategory $categoryA;

    protected ProductCategory $categoryB;

    protected Product $productCanva;

    protected Product $productNetflix;

    protected ProductVariant $variantCanva1M;

    protected ProductVariant $variantCanva1Y;

    protected ProductVariant $variantNetflix1M;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'username' => 'aliseller',
            'account_status' => 'ACTIVE',
        ]);

        $this->buyer = User::factory()->create([
            'username' => 'alibuyer',
            'account_status' => 'ACTIVE',
        ]);

        $this->categoryA = ProductCategory::create([
            'category_name' => 'Desain & Produktivitas',
            'description' => 'Aplikasi desain dan kerja',
            'is_active' => true,
        ]);

        $this->categoryB = ProductCategory::create([
            'category_name' => 'Hiburan & Streaming',
            'description' => 'Layanan hiburan video/musik',
            'is_active' => true,
        ]);

        $this->productCanva = Product::create([
            'category_id' => $this->categoryA->category_id,
            'product_name' => 'Canva Pro Enterprise',
            'description' => 'Langganan Canva Pro',
            'is_active' => true,
        ]);

        $this->productNetflix = Product::create([
            'category_id' => $this->categoryB->category_id,
            'product_name' => 'Netflix Premium UHD',
            'description' => 'Langganan Netflix 4K',
            'is_active' => true,
        ]);

        $this->variantCanva1M = ProductVariant::create([
            'product_id' => $this->productCanva->product_id,
            'variant_name' => '1 Bulan Private',
            'is_active' => true,
        ]);

        $this->variantCanva1Y = ProductVariant::create([
            'product_id' => $this->productCanva->product_id,
            'variant_name' => '1 Tahun Private',
            'is_active' => true,
        ]);

        $this->variantNetflix1M = ProductVariant::create([
            'product_id' => $this->productNetflix->product_id,
            'variant_name' => '1 Bulan 1 Profil',
            'is_active' => true,
        ]);
    }

    /**
     * TC-04-01: Pencarian dan Filter Listing Marketplace (Kata Kunci, Kategori, Produk, Varian, Urut Harga).
     */
    public function test_tc_04_01_search_and_filter_marketplace_listings(): void
    {
        Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variantCanva1M->variant_id,
            'title' => 'Akun Canva 1 Bulan Murah Bergaransi',
            'description' => 'Akun private full garansi 30 hari',
            'price' => 20000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variantCanva1Y->variant_id,
            'title' => 'Akun Canva 1 Tahun Private Eksklusif',
            'description' => 'Akun resmi 1 tahun tanpa drop',
            'price' => 150000.00,
            'delivery_method' => 'DIRECT_CHAT',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);

        Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variantNetflix1M->variant_id,
            'title' => 'Akun Netflix Premium 1 Bulan',
            'description' => 'UHD 4K 1 profil anti hold',
            'price' => 45000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        // Listing berstatus CLOSED tidak boleh muncul di katalog publik
        Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variantCanva1M->variant_id,
            'title' => 'Listing Canva Lama Sudah Tutup',
            'description' => 'Stok habis',
            'price' => 10000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'CLOSED',
        ]);

        // Subtest A: Pencarian kata kunci "Canva"
        $responseSearch = $this->get(route('marketplace.index', ['q' => 'Canva']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Akun Canva 1 Bulan Murah Bergaransi');
        $responseSearch->assertSee('Akun Canva 1 Tahun Private Eksklusif');
        $responseSearch->assertDontSee('Akun Netflix Premium 1 Bulan');
        $responseSearch->assertDontSee('Listing Canva Lama Sudah Tutup');

        // Subtest B: Filter Kategori (Category B -> hanya Netflix)
        $responseCategory = $this->get(route('marketplace.index', ['category_id' => $this->categoryB->category_id]));
        $responseCategory->assertOk();
        $responseCategory->assertSee('Akun Netflix Premium 1 Bulan');
        $responseCategory->assertDontSee('Akun Canva 1 Bulan Murah Bergaransi');

        // Subtest C: Filter Varian Spesifik (Canva 1 Tahun)
        $responseVariant = $this->get(route('marketplace.index', ['variant_id' => $this->variantCanva1Y->variant_id]));
        $responseVariant->assertOk();
        $responseVariant->assertSee('Akun Canva 1 Tahun Private Eksklusif');
        $responseVariant->assertDontSee('Akun Canva 1 Bulan Murah Bergaransi');

        // Subtest D: Pengurutan Harga Termurah (price_asc)
        $responseAsc = $this->get(route('marketplace.index', ['sort' => 'price_asc']));
        $responseAsc->assertOk();
        $listingsAsc = $responseAsc->viewData('listings');
        $this->assertEquals(20000.00, (float) $listingsAsc->first()->price);

        // Subtest E: Pengurutan Harga Termahal (price_desc)
        $responseDesc = $this->get(route('marketplace.index', ['sort' => 'price_desc']));
        $responseDesc->assertOk();
        $listingsDesc = $responseDesc->viewData('listings');
        $this->assertEquals(150000.00, (float) $listingsDesc->first()->price);
    }

    /**
     * TC-04-02: Filter Periode Statistik Pasar pada Detail Produk (UC-05).
     * Memastikan perhitungan hanya dari transaksi COMPLETED pada varian terkait dan sesuai periode (7d, 30d, 90d, all).
     */
    public function test_tc_04_02_product_market_stats_calculation_and_periods(): void
    {
        $listing = Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variantCanva1M->variant_id,
            'title' => 'Canva Pro 1 Bulan Stok Siap',
            'description' => 'Fast delivery garansi 30 hari',
            'price' => 25000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        // Buat post WTB dan WTS aktif pada varian yang sama untuk menguji demand & supply
        ForumPost::create([
            'author_id' => $this->buyer->user_id,
            'variant_id' => $this->variantCanva1M->variant_id,
            'category_code' => 'WTB',
            'title' => 'WTB Canva Pro 1 Bulan Butuh Cepat',
            'content' => 'Cari harga di bawah 25rb',
            'expected_price' => 22000.00,
            'status' => 'ACTIVE',
        ]);

        ForumPost::create([
            'author_id' => $this->seller->user_id,
            'variant_id' => $this->variantCanva1M->variant_id,
            'category_code' => 'WTS',
            'title' => 'WTS Canva Pro 1 Bulan Legal',
            'content' => 'Ready stok banyak',
            'expected_price' => 25000.00,
            'status' => 'ACTIVE',
        ]);

        // 1. Transaksi COMPLETED 3 hari lalu pada varian Canva 1M (base_price = 20.000)
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            20000.00,
            TransactionStatus::COMPLETED,
            now()->subDays(3)
        );

        // 2. Transaksi COMPLETED 20 hari lalu pada varian Canva 1M (base_price = 30.000)
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            30000.00,
            TransactionStatus::COMPLETED,
            now()->subDays(20)
        );

        // 3. Transaksi COMPLETED 60 hari lalu pada varian Canva 1M (base_price = 40.000)
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            40000.00,
            TransactionStatus::COMPLETED,
            now()->subDays(60)
        );

        // 4. Transaksi COMPLETED 120 hari lalu pada varian Canva 1M (base_price = 50.000)
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            50000.00,
            TransactionStatus::COMPLETED,
            now()->subDays(120)
        );

        // 5. Transaksi CANCELLED & PAID 2 hari lalu (TIDAK BOLEH masuk hitungan statistik!)
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            99000.00,
            TransactionStatus::CANCELLED,
            now()->subDays(2)
        );
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1M->variant_id,
            88000.00,
            TransactionStatus::PAID,
            now()->subDays(1)
        );

        // 6. Transaksi COMPLETED pada varian LAIN (Canva 1 Tahun) -> TIDAK BOLEH mencemari statistik varian Canva 1M!
        $this->createTransactionWithCreatedAt(
            $this->variantCanva1Y->variant_id,
            150000.00,
            TransactionStatus::COMPLETED,
            now()->subDays(2)
        );

        // Subtest A: Periode 7 Hari Terakhir (7d) -> hanya trx 3 hari lalu (20.000), sample = 1
        $response7d = $this->get(route('marketplace.show', ['listing' => $listing->listing_id, 'period' => '7d']));
        $response7d->assertOk();
        $stats7d = $response7d->viewData('marketStats');
        $this->assertSame('7d', $stats7d['period']);
        $this->assertSame('7 Hari Terakhir', $stats7d['period_label']);
        $this->assertSame(1, $stats7d['sample_count']);
        $this->assertEquals(20000.00, $stats7d['avg_price']);
        $this->assertSame(1, $stats7d['demand']);
        $this->assertSame(2, $stats7d['supply']); // 1 active listing + 1 active WTS post

        // Subtest B: Periode 30 Hari Terakhir (30d) -> trx 3 hari & 20 hari lalu ((20.000 + 30.000)/2 = 25.000), sample = 2
        $response30d = $this->get(route('marketplace.show', ['listing' => $listing->listing_id, 'period' => '30d']));
        $response30d->assertOk();
        $stats30d = $response30d->viewData('marketStats');
        $this->assertSame('30d', $stats30d['period']);
        $this->assertSame('30 Hari Terakhir', $stats30d['period_label']);
        $this->assertSame(2, $stats30d['sample_count']);
        $this->assertEquals(25000.00, $stats30d['avg_price']);

        // Subtest C: Periode 90 Hari Terakhir (90d - Catatan Gap Notion: diimplementasikan di service & UI)
        // Mencakup trx 3, 20, dan 60 hari lalu ((20.000 + 30.000 + 40.000)/3 = 30.000), sample = 3
        $response90d = $this->get(route('marketplace.show', ['listing' => $listing->listing_id, 'period' => '90d']));
        $response90d->assertOk();
        $stats90d = $response90d->viewData('marketStats');
        $this->assertSame('90d', $stats90d['period']);
        $this->assertSame('90 Hari Terakhir', $stats90d['period_label']);
        $this->assertSame(3, $stats90d['sample_count']);
        $this->assertEquals(30000.00, $stats90d['avg_price']);

        // Subtest D: Periode Semua Waktu (all) -> mencakup trx 3, 20, 60, 120 hari lalu ((20k+30k+40k+50k)/4 = 35.000), sample = 4
        $responseAll = $this->get(route('marketplace.show', ['listing' => $listing->listing_id, 'period' => 'all']));
        $responseAll->assertOk();
        $statsAll = $responseAll->viewData('marketStats');
        $this->assertSame('all', $statsAll['period']);
        $this->assertSame('Semua Waktu', $statsAll['period_label']);
        $this->assertSame(4, $statsAll['sample_count']);
        $this->assertEquals(35000.00, $statsAll['avg_price']);

        // Subtest E: Fallback jika parameter period tidak dikenali -> kembali ke default 30d
        $marketplaceService = app(MarketplaceServiceInterface::class);
        $statsFallback = $marketplaceService->getProductMarketStats($this->variantCanva1M->variant_id, 'invalid_period');
        $this->assertSame('30d', $statsFallback['period']);
        $this->assertSame(30, $statsFallback['period_days']);
    }

    protected function createTransactionWithCreatedAt(
        int $variantId,
        float $basePrice,
        TransactionStatus $status,
        \DateTimeInterface $createdAt
    ): Transaction {
        $agreement = Agreement::create([
            'created_by' => $this->buyer->user_id,
            'buyer_id' => $this->buyer->user_id,
            'seller_id' => $this->seller->user_id,
            'variant_id' => $variantId,
            'item_title' => 'Item Uji Statistik',
            'item_description' => 'Deskripsi uji statistik pasar',
            'quantity' => 1,
            'base_price' => $basePrice,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 1000.00,
            'buyer_total' => $basePrice + 1000.00,
            'seller_net' => $basePrice,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 15,
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED->value,
            'buyer_accepted_at' => $createdAt,
            'seller_accepted_at' => $createdAt,
            'finalized_at' => $createdAt,
        ]);

        $transaction = Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => 'MARKETPLACE',
            'status' => $status->value,
            'payment_due_at' => now()->addHour(),
        ]);

        DB::table('transactions')
            ->where('transaction_id', $transaction->transaction_id)
            ->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        return $transaction->fresh();
    }
}
