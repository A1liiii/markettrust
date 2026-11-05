<?php

namespace Tests\Feature\MarketTrust;

use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian skenario integrasi HTTP & Database untuk TS-04: Katalog Admin & Listing.
 * Mencakup TC-03-01, TC-03-02, dan TC-03-03.
 */
class CatalogListingScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-03-01: Admin dapat menambah kategori, produk, dan varian baru secara berjenjang.
     */
    public function test_tc_03_01_admin_can_create_category_product_and_variant(): void
    {
        $admin = User::factory()->admin()->create(['account_status' => 'ACTIVE']);

        // 1. Tambah Kategori
        $responseCategory = $this->actingAs($admin)->post('/admin/catalog/categories', [
            'category_name' => 'Akun Game Digital',
            'description' => 'Kategori akun game resmi',
            'is_active' => true,
        ]);
        $responseCategory->assertRedirect(route('admin.catalog.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('product_categories', [
            'category_name' => 'Akun Game Digital',
            'is_active' => true,
        ]);

        $category = ProductCategory::where('category_name', 'Akun Game Digital')->firstOrFail();

        // 2. Tambah Produk Master
        $responseProduct = $this->actingAs($admin)->post('/admin/catalog/products', [
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends Bang Bang',
            'description' => 'Akun MLBB berbagai tier',
            'is_active' => true,
        ]);
        $responseProduct->assertRedirect(route('admin.catalog.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends Bang Bang',
            'is_active' => true,
        ]);

        $product = Product::where('product_name', 'Mobile Legends Bang Bang')->firstOrFail();

        // 3. Tambah Varian Spesifikasi
        $responseVariant = $this->actingAs($admin)->post('/admin/catalog/variants', [
            'product_id' => $product->product_id,
            'variant_name' => 'Mythic Glory 100 Stars',
            'is_active' => true,
        ]);
        $responseVariant->assertRedirect(route('admin.catalog.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->product_id,
            'variant_name' => 'Mythic Glory 100 Stars',
            'is_active' => true,
        ]);
    }

    /**
     * TC-03-01: Akses non-admin ditolak saat mencoba menambah data katalog.
     */
    public function test_tc_03_01_non_admin_cannot_manage_catalog(): void
    {
        $user = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);

        $resIndex = $this->actingAs($user)->get('/admin/catalog');
        $resIndex->assertForbidden();

        $resPost = $this->actingAs($user)->post('/admin/catalog/categories', [
            'category_name' => 'Kategori Ilegal',
        ]);
        $resPost->assertForbidden();
    }

    /**
     * TC-03-01: Validasi menolak nama kategori duplikat dan varian duplikat.
     */
    public function test_tc_03_01_duplicate_category_and_variant_rejected(): void
    {
        $admin = User::factory()->admin()->create(['account_status' => 'ACTIVE']);
        $cat = ProductCategory::factory()->create(['category_name' => 'Voucher Game']);
        $prod = Product::factory()->create(['category_id' => $cat->category_id, 'product_name' => 'Produk A']);
        ProductVariant::factory()->create(['product_id' => $prod->product_id, 'variant_name' => 'Varian Duplikat']);

        // Coba tambah kategori dengan nama sama
        $resCat = $this->actingAs($admin)->post('/admin/catalog/categories', [
            'category_name' => 'Voucher Game',
        ]);
        $resCat->assertSessionHasErrors('category_name');

        // Coba tambah varian dengan nama sama pada produk yang sama
        $resVar = $this->actingAs($admin)->post('/admin/catalog/variants', [
            'product_id' => $prod->product_id,
            'variant_name' => 'Varian Duplikat',
        ]);
        $resVar->assertSessionHasErrors('variant_name');
    }

    /**
     * TC-03-02: Seller dapat membuat listing marketplace baru dengan status ACTIVE.
     */
    public function test_tc_03_02_seller_can_create_active_marketplace_listing(): void
    {
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $payload = [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Glory Siap Turnamen',
            'description' => 'Akun tangan pertama, all unbind, garansi penuh.',
            'price' => 150000,
            'delivery_method' => 'Data Kredensial via Catatan Aman',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ];

        $response = $this->actingAs($seller)->post(route('seller.listings.store'), $payload);

        $response->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Glory Siap Turnamen',
            'price' => 150000,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-03-02: Seller dapat membuat listing berstatus DRAFT (catatan gap: alur lanjutan draft belum dirinci dokumen).
     */
    public function test_tc_03_02_seller_can_create_draft_listing_with_specification_gap_recorded(): void
    {
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $payload = [
            'variant_id' => $variant->variant_id,
            'title' => 'Draft Penawaran Lisensi Game',
            'description' => 'Deskripsi penawaran yang masih dalam bentuk draft.',
            'price' => 75000,
            'delivery_method' => 'Email Pengiriman',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'DRAFT',
        ];

        $response = $this->actingAs($seller)->post(route('seller.listings.store'), $payload);

        $response->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'seller_id' => $seller->user_id,
            'title' => 'Draft Penawaran Lisensi Game',
            'status' => 'DRAFT',
        ]);
    }

    /**
     * TC-03-02: Pembuatan listing ditolak jika harga di bawah Rp 5.000 atau varian nonaktif.
     */
    public function test_tc_03_02_listing_with_invalid_price_or_inactive_variant_rejected(): void
    {
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $activeVariant = ProductVariant::factory()->create(['is_active' => true]);
        $inactiveVariant = ProductVariant::factory()->create(['is_active' => false]);

        // Harga di bawah Rp 5.000
        $resLowPrice = $this->actingAs($seller)->post(route('seller.listings.store'), [
            'variant_id' => $activeVariant->variant_id,
            'title' => 'Listing Harga Murah Tidak Valid',
            'description' => 'Deskripsi',
            'price' => 3000,
            'delivery_method' => 'Instant',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);
        $resLowPrice->assertSessionHasErrors('price');

        // Varian nonaktif
        $resInactiveVar = $this->actingAs($seller)->post(route('seller.listings.store'), [
            'variant_id' => $inactiveVariant->variant_id,
            'title' => 'Listing Varian Nonaktif',
            'description' => 'Deskripsi',
            'price' => 50000,
            'delivery_method' => 'Instant',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);
        $resInactiveVar->assertSessionHasErrors('variant_id');
    }

    /**
     * TC-03-03: Toggle status listing seller antara ACTIVE dan CLOSED.
     */
    public function test_tc_03_03_seller_can_toggle_listing_status_between_active_and_closed(): void
    {
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $listing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'status' => 'ACTIVE',
        ]);

        // 1. Toggle ACTIVE -> CLOSED
        $resToggle1 = $this->actingAs($seller)->patch(route('seller.listings.toggle-status', $listing));
        $resToggle1->assertRedirect(route('seller.listings.index'));
        $listing->refresh();
        $this->assertSame('CLOSED', $listing->status);

        // 2. Toggle CLOSED -> ACTIVE
        $resToggle2 = $this->actingAs($seller)->patch(route('seller.listings.toggle-status', $listing));
        $resToggle2->assertRedirect(route('seller.listings.index'));
        $listing->refresh();
        $this->assertSame('ACTIVE', $listing->status);
    }

    /**
     * TC-03-03: Seller lain dilarang men-toggle status listing bukan miliknya.
     */
    public function test_tc_03_03_other_seller_cannot_toggle_listing(): void
    {
        $owner = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $listing = Listing::factory()->create([
            'seller_id' => $owner->user_id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($stranger)->patch(route('seller.listings.toggle-status', $listing));
        $response->assertForbidden();

        $listing->refresh();
        $this->assertSame('ACTIVE', $listing->status);
    }

    /**
     * TC-03-03: Listing nonaktif (CLOSED) tidak boleh muncul pada pencarian marketplace publik aktif.
     */
    public function test_tc_03_03_closed_listing_is_hidden_from_active_marketplace_queries(): void
    {
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $activeListing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'title' => 'Akun Aktif Terlihat',
            'status' => 'ACTIVE',
        ]);
        $closedListing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'title' => 'Akun Ditutup Sembunyi',
            'status' => 'CLOSED',
        ]);

        $response = $this->get('/marketplace?q=Akun');
        $response->assertOk();
        $response->assertSee('Akun Aktif Terlihat');
        $response->assertDontSee('Akun Ditutup Sembunyi');
    }
}
