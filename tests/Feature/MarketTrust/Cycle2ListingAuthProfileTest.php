<?php

namespace Tests\Feature\MarketTrust;

use App\Models\Listing;
use App\Models\ProductVariant;
use App\Models\ReputationEvent;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian Terpadu Siklus 2 (Testing Cycle 2) - Bagian Fadhil Syahidan.
 * Cakupan:
 * 1. Kelola Listing (pembuatan dengan/tanpa foto, validasi, fallback storage).
 * 2. Login & Registrasi (layout 1 layar desktop bebas scroll, autentikasi).
 * 3. Profil Seller (penghapusan tombol etalase & kartu KEEP, pemformatan 1 baris).
 */
class Cycle2ListingAuthProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-C2-LS-01: Seller dapat membuat listing marketplace tanpa melampirkan foto.
     */
    public function test_c2_ls_01_seller_can_create_listing_without_photos(): void
    {
        $seller = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $payload = [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mobile Legends Mythic Murni',
            'description' => 'Akun pribadi tangan pertama aman terpercaya.',
            'price' => 125000,
            'delivery_method' => 'Data Kredensial via Catatan Aman',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
            'images' => [],
        ];

        $response = $this->actingAs($seller)->post(route('seller.listings.store'), $payload);

        $response->assertRedirect(route('seller.listings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('listings', [
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mobile Legends Mythic Murni',
            'price' => 125000,
            'status' => 'ACTIVE',
        ]);

        $listing = Listing::where('title', 'Akun Mobile Legends Mythic Murni')->firstOrFail();
        $this->assertEmpty($listing->all_image_urls);
        $this->assertNull($listing->cover_image_url);
    }

    /**
     * TC-C2-LS-02: Seller dapat membuat listing dengan foto dan otomatis ditangani fallback storage.
     */
    public function test_c2_ls_02_seller_can_create_listing_with_photos_using_storage_fallback(): void
    {
        Storage::fake('public');
        config([
            'services.supabase.url' => null,
            'services.supabase.key' => null,
        ]);
        app()->instance(SupabaseStorageService::class, new SupabaseStorageService);

        $seller = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $photo = UploadedFile::fake()->image('mlbb_profile.png', 800, 600);

        $payload = [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Epic Glory Full Skin Limited',
            'description' => 'Skin collector lengkap siap pakai.',
            'price' => 350000,
            'delivery_method' => 'Data Kredensial via Catatan Aman',
            'estimated_delivery_value' => 20,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
            'images' => [$photo],
        ];

        $response = $this->actingAs($seller)->post(route('seller.listings.store'), $payload);

        $response->assertRedirect(route('seller.listings.index'));
        $response->assertSessionHas('success');

        $listing = Listing::where('title', 'Akun Epic Glory Full Skin Limited')->firstOrFail();
        $this->assertCount(1, $listing->all_image_urls);
        $this->assertNotNull($listing->cover_image_url);

        $storedPath = str_replace('/storage/', '', $listing->image_urls[0]);
        Storage::disk('public')->assertExists($storedPath);
    }

    /**
     * TC-C2-LS-03: Validasi form menolak harga di bawah batas minimum dan file bukan gambar.
     */
    public function test_c2_ls_03_listing_validation_rules_reject_invalid_payloads(): void
    {
        $seller = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        // Harga di bawah Rp 5.000
        $responsePrice = $this->actingAs($seller)->post(route('seller.listings.store'), [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Murah Banget',
            'description' => 'Deskripsi valid',
            'price' => 2000,
            'delivery_method' => 'Data Kredensial via Catatan Aman',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
        $responsePrice->assertSessionHasErrors('price');

        // File bukan gambar (PDF)
        $invalidFile = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $responseFile = $this->actingAs($seller)->post(route('seller.listings.store'), [
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Lampiran PDF',
            'description' => 'Deskripsi valid',
            'price' => 50000,
            'delivery_method' => 'Data Kredensial via Catatan Aman',
            'estimated_delivery_value' => 10,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
            'images' => [$invalidFile],
        ]);
        $responseFile->assertSessionHasErrors('images.0');
    }

    /**
     * TC-C2-LS-04: Pengguna tamu (guest) tidak dapat mengakses pembuatan listing.
     */
    public function test_c2_ls_04_guest_cannot_access_listing_creation(): void
    {
        $responseCreate = $this->get(route('seller.listings.create'));
        $responseCreate->assertRedirect(route('login'));

        $responseStore = $this->post(route('seller.listings.store'), [
            'title' => 'Listing Guest Ilegal',
        ]);
        $responseStore->assertRedirect(route('login'));
    }

    /**
     * TC-C2-AUTH-01: Halaman login memuat layout desktop compact bebas vertical scroll.
     */
    public function test_c2_auth_01_login_view_renders_compact_desktop_layout(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('min-h-[calc(100vh-4.5rem)]', false);
        $response->assertSee('Masuk ke MarketTrust');
        $response->assertSee('data-cy="login-form"', false);
    }

    /**
     * TC-C2-AUTH-02: Halaman registrasi memuat form 2-kolom responsif untuk desktop.
     */
    public function test_c2_auth_02_register_view_renders_compact_grid_layout(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('grid grid-cols-1 sm:grid-cols-2 gap-3', false);
        $response->assertSee('Buat Akun MarketTrust');
        $response->assertSee('data-cy="register-form"', false);
    }

    /**
     * TC-C2-AUTH-03: Proses autentikasi login dengan kredensial valid dan penolakan password salah.
     */
    public function test_c2_auth_03_login_authentication_success_and_invalid_credential_rejection(): void
    {
        $user = User::factory()->create([
            'email' => 'fadhil_cycle2@markettrust.local',
            'password_hash' => Hash::make('PasswordAman123!'),
            'account_status' => 'ACTIVE',
        ]);

        // Gagal login: password salah
        $resFailed = $this->post(route('login'), [
            'login' => 'fadhil_cycle2@markettrust.local',
            'password' => 'PasswordSalahTotal',
        ]);
        $resFailed->assertSessionHasErrors();
        $this->assertGuest();

        // Berhasil login: password benar
        $resSuccess = $this->post(route('login'), [
            'login' => 'fadhil_cycle2@markettrust.local',
            'password' => 'PasswordAman123!',
        ]);
        $resSuccess->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * TC-C2-AUTH-04: Registrasi akun baru berhasil menyimpan data pengguna aktif.
     */
    public function test_c2_auth_04_user_registration_creates_active_account(): void
    {
        $payload = [
            'username' => 'fadhil_member_new',
            'email' => 'new_member@markettrust.local',
            'password' => 'KombinasiKuat#2026',
            'password_confirmation' => 'KombinasiKuat#2026',
        ];

        $response = $this->post(route('register'), $payload);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'username' => 'fadhil_member_new',
            'email' => 'new_member@markettrust.local',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-C2-PROF-01: Profil seller tidak menampilkan tombol etalase produk dan kartu KEEP.
     */
    public function test_c2_prof_01_profile_page_removes_etalase_button_and_keep_section(): void
    {
        $viewer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create([
            'username' => 'seller_legit_cycle2',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($viewer)->get(route('profile.show', $seller->user_id));

        $response->assertOk();
        $response->assertDontSee('Lihat Etalase Produk');
        $response->assertDontSee('Kewajiban Penggantian Fee (KEEP)');
        $response->assertSee('Catatan Kepatuhan Transaksi');
        $response->assertSee('Tinjauan Pembatalan Pasca-Bayar');
    }

    /**
     * TC-C2-PROF-02: Indikator legitimasi dan total akumulasi skor reputasi terformat satu baris rapi.
     */
    public function test_c2_prof_02_reputation_indicators_and_scores_render_one_line(): void
    {
        $seller = User::factory()->create([
            'username' => 'seller_reputation_test',
            'account_status' => 'ACTIVE',
        ]);

        ReputationEvent::create([
            'user_id' => $seller->user_id,
            'event_type' => ReputationEvent::EVENT_ADMIN_MODERATION,
            'points_delta' => -15,
            'previous_status' => null,
            'new_status' => 'PROBLEMATIC',
            'reason' => 'Sanksi moderasi sengketa',
            'event_key' => 'cycle2:test:event:1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($seller)->get(route('profile.show', $seller->user_id));

        $response->assertOk();
        $response->assertSee('Panduan Indikator Reputasi & Legitimasi', false);
        $response->assertSee('PROBLEMATIC');
        $response->assertSee('Total Akumulasi:');
        $response->assertSee('whitespace-nowrap', false);
    }
}
