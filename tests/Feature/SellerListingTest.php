<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerListingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu tanpa autentikasi dialihkan ke halaman login.
     */
    public function test_guest_cannot_access_seller_listings(): void
    {
        $response = $this->get('/seller/listings');

        $response->assertRedirect('/login');
    }

    /**
     * Seller hanya dapat melihat penawaran listing miliknya sendiri di halaman dashboard seller.
     */
    public function test_seller_can_view_only_their_own_listings(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();

        $listingA = Listing::factory()->create([
            'seller_id' => $sellerA->user_id,
            'title' => 'Listing Spesial Seller A',
        ]);

        $listingB = Listing::factory()->create([
            'seller_id' => $sellerB->user_id,
            'title' => 'Listing Rahasia Seller B',
        ]);

        $response = $this->actingAs($sellerA)->get('/seller/listings');

        $response->assertOk();
        $response->assertSee('Listing Spesial Seller A');
        $response->assertDontSee('Listing Rahasia Seller B');
    }

    /**
     * Seller dapat membuat listing baru dengan data yang valid.
     */
    public function test_seller_can_create_listing_with_valid_data(): void
    {
        $seller = User::factory()->create();
        $category = ProductCategory::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Glory 100 Star Full Skin',
            'description' => 'Login via Moonton all unbind, data aman garansi seumur hidup.',
            'price' => 250000,
            'delivery_method' => 'Kredensial Akun via Catatan Terenkripsi',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Glory 100 Star Full Skin',
            'price' => 250000,
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Pembuatan listing dengan varian nonaktif ditolak oleh validasi.
     */
    public function test_creating_listing_with_inactive_variant_is_rejected(): void
    {
        $seller = User::factory()->create();
        $inactiveVariant = ProductVariant::factory()->inactive()->create();

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $inactiveVariant->variant_id,
            'title' => 'Penawaran Varian Tidak Aktif',
            'description' => 'Deskripsi listing',
            'price' => 50000,
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors('variant_id');
    }

    /**
     * Pembuatan listing dengan harga di bawah batas minimum (Rp 1.000) ditolak.
     */
    public function test_creating_listing_with_price_below_minimum_is_rejected(): void
    {
        $seller = User::factory()->create();
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Harga Murah Tidak Wajar',
            'description' => 'Deskripsi listing',
            'price' => 500, // Di bawah min 1000
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors('price');
    }

    /**
     * Pembuatan listing dengan estimasi tidak valid ditolak.
     */
    public function test_creating_listing_with_invalid_delivery_values_is_rejected(): void
    {
        $seller = User::factory()->create();
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Durasi Salah',
            'description' => 'Deskripsi listing',
            'price' => 20000,
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 0, // Invalid min 1
            'estimated_delivery_unit' => 'MONTH', // Invalid enum
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors(['estimated_delivery_value', 'estimated_delivery_unit']);
    }

    /**
     * Seller dapat memperbarui listing miliknya sendiri.
     */
    public function test_seller_can_update_their_own_listing(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'price' => 100000,
            'title' => 'Judul Sebelum Update',
        ]);

        $response = $this->actingAs($seller)->put("/seller/listings/{$listing->listing_id}", [
            'variant_id' => $listing->variant_id,
            'title' => 'Judul Sukses Diperbarui',
            'description' => $listing->description,
            'price' => 175000,
            'delivery_method' => $listing->delivery_method,
            'estimated_delivery_value' => 2,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'listing_id' => $listing->listing_id,
            'title' => 'Judul Sukses Diperbarui',
            'price' => 175000,
            'estimated_delivery_value' => 2,
            'estimated_delivery_unit' => 'HOUR',
        ]);
    }

    /**
     * Pengguna lain tidak dapat mengedit atau memperbarui listing milik seller lain (403 Forbidden).
     */
    public function test_seller_cannot_update_another_sellers_listing(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();

        $listingA = Listing::factory()->create([
            'seller_id' => $sellerA->user_id,
        ]);

        // Seller B mencoba membuka form edit listing A
        $responseEdit = $this->actingAs($sellerB)->get("/seller/listings/{$listingA->listing_id}/edit");
        $responseEdit->assertForbidden();

        // Seller B mencoba request PUT ke listing A
        $responsePut = $this->actingAs($sellerB)->put("/seller/listings/{$listingA->listing_id}", [
            'variant_id' => $listingA->variant_id,
            'title' => 'Upaya Pembajakan Listing',
            'description' => 'Deskripsi dibajak',
            'price' => 50000,
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);
        $responsePut->assertForbidden();
    }

    /**
     * Seller dapat men-toggle status listing antara AKTIF dan DITUTUP.
     */
    public function test_seller_can_toggle_listing_status(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'status' => 'ACTIVE',
        ]);

        // Toggle dari ACTIVE -> CLOSED
        $response1 = $this->actingAs($seller)->patch("/seller/listings/{$listing->listing_id}/toggle-status");
        $response1->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'listing_id' => $listing->listing_id,
            'status' => 'CLOSED',
        ]);

        // Toggle dari CLOSED -> ACTIVE
        $response2 = $this->actingAs($seller)->patch("/seller/listings/{$listing->listing_id}/toggle-status");
        $response2->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'listing_id' => $listing->listing_id,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Akun yang berstatus SUSPENDED tidak dapat membuat atau mengedit listing.
     */
    public function test_suspended_user_cannot_create_or_update_listing(): void
    {
        $suspendedUser = User::factory()->suspended()->create();
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        // Coba akses form create
        $responseCreate = $this->actingAs($suspendedUser)->get('/seller/listings/create');
        $responseCreate->assertRedirect(route('login'));
        $responseCreate->assertSessionHasErrors('login');
        $this->assertGuest();

        // Coba kirim POST store
        $responseStore = $this->actingAs($suspendedUser)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Akun Suspended',
            'description' => 'Deskripsi',
            'price' => 50000,
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);
        $responseStore->assertRedirect(route('login'));
        $responseStore->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->assertDatabaseMissing('listings', [
            'title' => 'Listing Akun Suspended',
        ]);
    }

    /**
     * Navbar tidak menampilkan tombol 'Mulai Berjualan' maupun menu 'Listing' jika user belum memiliki listing.
     * Tombol 'Ingin berjualan?' ditampilkan pada section Marketplace di homepage.
     */
    public function test_navbar_hides_listing_and_mulai_berjualan_when_user_has_no_listings(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Mulai Berjualan');
        $response->assertDontSee('href="'.route('seller.listings.index').'"', false);
        $response->assertSee('Ingin berjualan?');
    }

    /**
     * Navbar menampilkan menu 'Listing' setelah user memiliki minimal 1 listing penawaran.
     */
    public function test_navbar_displays_listing_menu_when_user_has_at_least_one_listing(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        Listing::factory()->create([
            'seller_id' => $user->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Perdana User',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('href="'.route('seller.listings.index').'"', false);
        $response->assertDontSee('Mulai Berjualan');
        $response->assertSee('Ingin berjualan?');
    }

    /**
     * TC-LS-04: Format harga string dengan 'Rp' dan titik ribuan disanitasi otomatis menjadi numerik murni.
     */
    public function test_creating_listing_with_formatted_rupiah_price_string_is_sanitized_and_stored_correctly(): void
    {
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Terformat Rupiah',
            'description' => 'Deskripsi akun dengan format rupiah',
            'price' => 'Rp 150.000',
            'delivery_method' => 'Kredensial Akun via Catatan Terenkripsi',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect(route('seller.listings.index'));
        $this->assertDatabaseHas('listings', [
            'seller_id' => $seller->user_id,
            'title' => 'Akun Terformat Rupiah',
            'price' => 150000.00,
        ]);
    }

    /**
     * TC-LS-05 & TC-LS-04: Pembuatan listing dengan harga di bawah Rp 5.000 ditolak.
     */
    public function test_creating_listing_with_price_below_5000_is_rejected(): void
    {
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($seller)->post('/seller/listings', [
            'variant_id' => $variant->variant_id,
            'title' => 'Listing Murah di Bawah Batas Fee Platform',
            'description' => 'Deskripsi listing',
            'price' => 4500, // Di bawah batas platform 5000
            'delivery_method' => 'Notes',
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'HOUR',
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors('price');
        $this->assertDatabaseMissing('listings', [
            'title' => 'Listing Murah di Bawah Batas Fee Platform',
        ]);
    }
}
