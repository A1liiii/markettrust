<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSearchAndListingTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(string $name = 'Game Accounts'): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => $name.' '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null, string $name = 'Mobile Legends'): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => $name.' '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null, string $name = 'Mythic Tier'): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => $name.' '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createListing(User $seller, ?ProductVariant $variant = null, array $attributes = []): Listing
    {
        $variant = $variant ?: $this->createVariant();

        return Listing::create(array_merge([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun MLBB Mythic Siap Tempur',
            'description' => 'Akun aman, data lengkap, garansi anti hackback.',
            'price' => 150000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ], $attributes));
    }

    /**
     * Tamu dapat mengakses marketplace dan melihat katalog listing aktif.
     */
    public function test_guest_can_view_marketplace_catalog_with_active_listings(): void
    {
        $seller = User::factory()->create();
        $listing = $this->createListing($seller, null, ['title' => 'Akun Sultan Mobile Legends']);

        $response = $this->get('/marketplace');

        $response->assertOk();
        $response->assertSee('Katalog Terverifikasi Escrow');
        $response->assertSee('Akun Sultan Mobile Legends');
    }

    /**
     * Listing dengan status DRAFT atau CLOSED tidak tampil pada katalog publik.
     */
    public function test_inactive_and_draft_listings_are_hidden_from_catalog(): void
    {
        $seller = User::factory()->create();
        $this->createListing($seller, null, ['title' => 'Listing Aktif Muncul', 'status' => 'ACTIVE']);
        $this->createListing($seller, null, ['title' => 'Listing Draft Rahasia', 'status' => 'DRAFT']);
        $this->createListing($seller, null, ['title' => 'Listing Closed Terjual', 'status' => 'CLOSED']);

        $response = $this->get('/marketplace');

        $response->assertOk();
        $response->assertSee('Listing Aktif Muncul');
        $response->assertDontSee('Listing Draft Rahasia');
        $response->assertDontSee('Listing Closed Terjual');
    }

    /**
     * Pencarian berbasis kata kunci (q) mencocokkan judul atau deskripsi listing.
     */
    public function test_can_search_listings_by_keyword(): void
    {
        $seller = User::factory()->create();
        $this->createListing($seller, null, [
            'title' => 'Voucher Game Genshin Impact Genesis',
            'description' => 'Topup cepat langsung masuk ID',
        ]);
        $this->createListing($seller, null, [
            'title' => 'Akun Valorant Radiant Full Skin',
            'description' => 'Email first hand, no cheat',
        ]);

        $response = $this->get('/marketplace?q=Genshin');

        $response->assertOk();
        $response->assertSee('Voucher Game Genshin Impact Genesis');
        $response->assertDontSee('Akun Valorant Radiant Full Skin');
    }

    /**
     * Filter bersarang berdasarkan category_id, product_id, dan variant_id.
     */
    public function test_can_filter_listings_by_category_product_and_variant(): void
    {
        $seller = User::factory()->create();

        $categoryA = $this->createCategory('Kategori A');
        $productA = $this->createProduct($categoryA, 'Produk A');
        $variantA = $this->createVariant($productA, 'Varian A');

        $categoryB = $this->createCategory('Kategori B');
        $productB = $this->createProduct($categoryB, 'Produk B');
        $variantB = $this->createVariant($productB, 'Varian B');

        $listingA = $this->createListing($seller, $variantA, ['title' => 'Barang Dari Kategori A']);
        $listingB = $this->createListing($seller, $variantB, ['title' => 'Barang Dari Kategori B']);

        // Filter kategori A
        $response = $this->get('/marketplace?category_id='.$categoryA->category_id);
        $response->assertOk();
        $response->assertSee('Barang Dari Kategori A');
        $response->assertDontSee('Barang Dari Kategori B');

        // Filter varian B
        $responseVariant = $this->get('/marketplace?variant_id='.$variantB->variant_id);
        $responseVariant->assertOk();
        $responseVariant->assertDontSee('Barang Dari Kategori A');
        $responseVariant->assertSee('Barang Dari Kategori B');
    }

    /**
     * Pengurutan harga (price_asc dan price_desc).
     */
    public function test_can_sort_listings_by_price(): void
    {
        $seller = User::factory()->create();
        $this->createListing($seller, null, ['title' => 'Barang Murah', 'price' => 20000]);
        $this->createListing($seller, null, ['title' => 'Barang Mahal', 'price' => 500000]);

        $responseAsc = $this->get('/marketplace?sort=price_asc');
        $responseAsc->assertOk();
        $responseAsc->assertSeeInOrder(['Barang Murah', 'Barang Mahal']);

        $responseDesc = $this->get('/marketplace?sort=price_desc');
        $responseDesc->assertOk();
        $responseDesc->assertSeeInOrder(['Barang Mahal', 'Barang Murah']);
    }

    /**
     * Tamu dapat melihat halaman detail publik untuk listing aktif.
     */
    public function test_guest_can_view_active_listing_detail(): void
    {
        $seller = User::factory()->create();
        $listing = $this->createListing($seller, null, [
            'title' => 'Akun Pubg Mobile Conqueror',
            'description' => 'Mantap pol siap turnamen',
        ]);

        $response = $this->get('/marketplace/'.$listing->listing_id);

        $response->assertOk();
        $response->assertSee('Akun Pubg Mobile Conqueror');
        $response->assertSee('Mantap pol siap turnamen');
        $response->assertSee('Masuk untuk Membeli');

        // Pembeli terautentikasi melihat tombol Beli Sekarang
        $buyer = User::factory()->create();
        $authResponse = $this->actingAs($buyer)->get('/marketplace/'.$listing->listing_id);
        $authResponse->assertOk();
        $authResponse->assertSee('Beli Sekarang');
    }

    /**
     * Akses ke listing yang DRAFT atau CLOSED menghasilkan 404 bagi publik.
     */
    public function test_viewing_non_active_listing_returns_404_for_guest_and_other_users(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $draftListing = $this->createListing($seller, null, ['status' => 'DRAFT']);
        $closedListing = $this->createListing($seller, null, ['status' => 'CLOSED']);

        // Tamu
        $this->get('/marketplace/'.$draftListing->listing_id)->assertNotFound();
        $this->get('/marketplace/'.$closedListing->listing_id)->assertNotFound();

        // User lain
        $this->actingAs($buyer)->get('/marketplace/'.$draftListing->listing_id)->assertNotFound();
        $this->actingAs($buyer)->get('/marketplace/'.$closedListing->listing_id)->assertNotFound();
    }

    /**
     * Pemilik listing (seller) dapat melihat preview listing miliknya meskipun berstatus DRAFT/CLOSED.
     */
    public function test_owner_seller_can_preview_their_own_draft_listing(): void
    {
        $seller = User::factory()->create();
        $otherSeller = User::factory()->create();
        $draftListing = $this->createListing($seller, null, [
            'title' => 'Draft Listing Eksklusif',
            'status' => 'DRAFT',
        ]);

        // Pemilik dapat akses via preview
        $response = $this->actingAs($seller)->get(route('seller.listings.preview', $draftListing));
        $response->assertOk();
        $response->assertSee('Mode Pratinjau Seller:');
        $response->assertSee('Draft Listing Eksklusif');

        // Seller lain dilarang (403)
        $this->actingAs($otherSeller)->get(route('seller.listings.preview', $draftListing))->assertForbidden();
    }
}
