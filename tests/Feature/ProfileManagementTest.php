<?php

namespace Tests\Feature;

use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest cannot view profile page.
     */
    public function test_guest_cannot_view_profile_page(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    /**
     * Test authenticated user can view their profile page.
     */
    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'username' => 'alimirza',
            'email' => 'ali@example.com',
            'account_status' => 'ACTIVE',
            'account_type' => 'USER',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('alimirza');
        $response->assertSee('ali@example.com');
        $response->assertSee('ACTIVE');
        $response->assertSee('glass-panel', false);
    }

    /**
     * Test authenticated user can view edit profile page.
     */
    public function test_authenticated_user_can_view_edit_profile_page(): void
    {
        $user = User::factory()->create([
            'username' => 'alimirza',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get('/profile/edit');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Profil & Keamanan', false);
        $response->assertSee('Pilih File Avatar Baru');
    }

    /**
     * Test user can upload a new avatar and file is stored.
     */
    public function test_user_can_upload_new_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'alimirza',
            'avatar_url' => null,
            'account_status' => 'ACTIVE',
        ]);

        $file = UploadedFile::fake()->image('my_avatar.jpg', 200, 200);

        $response = $this->actingAs($user)->put('/profile', [
            'avatar' => $file,
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->avatar_url);

        // Check file exists in public storage
        $storedPath = str_replace(asset('storage').'/', '', $user->avatar_url);
        $storedPath = str_replace('/storage/', '', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
    }

    /**
     * Test uploaded avatar is accessible via HTTP fallback route.
     */
    public function test_uploaded_avatar_is_accessible_via_http(): void
    {
        // Place real sample file in storage/app/public/avatars
        $testFile = UploadedFile::fake()->image('test_serve.png', 100, 100);
        $path = $testFile->store('avatars', 'public');

        $response = $this->get('/storage/'.$path);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');

        // Cleanup
        Storage::disk('public')->delete($path);
    }

    /**
     * Test updating avatar deletes previous local avatar file from disk.
     */
    public function test_updating_avatar_deletes_previous_local_avatar_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'avatar_url' => null,
            'account_status' => 'ACTIVE',
        ]);

        // Upload first avatar
        $firstFile = UploadedFile::fake()->image('avatar_first.jpg', 150, 150);
        $this->actingAs($user)->put('/profile', ['avatar' => $firstFile]);

        $user->refresh();
        $firstStored = str_replace(asset('storage').'/', '', $user->avatar_url);
        $firstStored = str_replace('/storage/', '', $firstStored);
        Storage::disk('public')->assertExists($firstStored);

        // Upload second avatar
        $secondFile = UploadedFile::fake()->image('avatar_second.png', 150, 150);
        $this->actingAs($user)->put('/profile', ['avatar' => $secondFile]);

        $user->refresh();
        $secondStored = str_replace(asset('storage').'/', '', $user->avatar_url);
        $secondStored = str_replace('/storage/', '', $secondStored);

        // Old file must be deleted, new file must exist
        Storage::disk('public')->assertMissing($firstStored);
        Storage::disk('public')->assertExists($secondStored);
    }

    /**
     * Test Google external avatar URL is preserved without modification.
     */
    public function test_google_external_avatar_url_is_preserved(): void
    {
        $googleUrl = 'https://lh3.googleusercontent.com/a/sample_avatar=s96-c';
        $user = User::factory()->create([
            'avatar_url' => $googleUrl,
            'account_status' => 'ACTIVE',
        ]);

        $this->assertEquals($googleUrl, $user->avatar_url);
    }

    /**
     * Test profile view renders representative metrics: reputation, transactions, listings, forum.
     */
    public function test_profile_view_renders_representative_metrics(): void
    {
        $user = User::factory()->create([
            'username' => 'trader_pro',
            'email' => 'pro@example.com',
            'account_status' => 'ACTIVE',
            'account_type' => 'USER',
        ]);

        // 1. Reputation event (+10 points)
        ReputationEvent::create([
            'user_id' => $user->user_id,
            'event_type' => 'TX_COMPLETED',
            'points_delta' => 10,
            'new_status' => 'LEGIT',
            'reason' => 'Transaksi diselesaikan tepat waktu',
            'event_key' => 'tx_completed_sample_999',
        ]);

        // 2. Listing
        $category = ProductCategory::create([
            'category_name' => 'Voucher',
            'description' => 'Kategori Voucher',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Steam Wallet',
            'description' => 'Voucher Steam Wallet',
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'IDR 50.000',
            'is_active' => true,
        ]);
        Listing::create([
            'seller_id' => $user->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Steam Wallet 50K Murah',
            'description' => 'Fast delivery via chat',
            'price' => 50000,
            'delivery_method' => 'DM Voucher Code',
            'estimated_delivery_value' => 5,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        // 3. Forum post
        ForumPost::create([
            'author_id' => $user->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Tips Aman Bertransaksi Akun',
            'content' => 'Gunakan rekening bersama escrow MarketTrust',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('trader_pro');
        $response->assertSee('Reputasi & Poin', false);
        $response->assertSee('+10');
        $response->assertSee('Terverifikasi (LEGIT)');
        $response->assertSee('Transaksi Escrow');
        $response->assertSee('Marketplace');
        $response->assertSee('Forum Komunitas');
        $response->assertSee('1'); // 1 listing & 1 postingan
    }

    /**
     * Test user cannot upload invalid avatar file (e.g. text file).
     */
    public function test_user_cannot_upload_invalid_avatar(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->put('/profile', [
            'avatar' => $invalidFile,
        ]);

        $response->assertSessionHasErrors(['avatar']);
    }

    /**
     * Test local user can update password with valid current password.
     */
    public function test_local_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('old_secret123'),
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'current_password' => 'old_secret123',
            'password' => 'new_secret123',
            'password_confirmation' => 'new_secret123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('new_secret123', $user->password_hash));
    }

    /**
     * Test local user cannot update password with incorrect current password.
     */
    public function test_local_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('correct_password'),
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'current_password' => 'wrong_password',
            'password' => 'new_secret123',
            'password_confirmation' => 'new_secret123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    /**
     * Test Google user without password can set initial local password.
     */
    public function test_google_user_without_password_can_set_initial_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => null,
            'google_id' => 'google_sub_123',
            'account_status' => 'ACTIVE',
        ]);

        $this->assertFalse($user->hasPassword());

        $response = $this->actingAs($user)->put('/profile', [
            'password' => 'initial_password123',
            'password_confirmation' => 'initial_password123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->hasPassword());
        $this->assertTrue(Hash::check('initial_password123', $user->password_hash));
    }

    /**
     * Test suspended user is rejected and logged out by active_account middleware.
     */
    public function test_suspended_user_is_logged_out_by_active_account_middleware(): void
    {
        $suspendedUser = User::factory()->create([
            'account_status' => 'SUSPENDED',
        ]);

        $response = $this->actingAs($suspendedUser)->get('/profile');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * Test admin middleware restricts non-admin users.
     */
    public function test_admin_middleware_restricts_non_admin_users(): void
    {
        Route::get('/test-admin-gate', function () {
            return 'admin-content';
        })->middleware(['auth', 'active_account', 'admin']);

        $regularUser = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $adminUser = User::factory()->create([
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        // Regular user blocked with 403
        $responseRegular = $this->actingAs($regularUser)->get('/test-admin-gate');
        $responseRegular->assertStatus(403);

        // Admin allowed
        $responseAdmin = $this->actingAs($adminUser)->get('/test-admin-gate');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('admin-content');
    }

    /**
     * Test unreviewed account with zero points displays Belum Ditinjau and 0 Poin.
     */
    public function test_unreviewed_account_with_zero_points_shows_unreviewed_status(): void
    {
        $user = User::factory()->create([
            'username' => 'newbie_trader',
            'account_status' => 'ACTIVE',
        ]);

        $this->assertEquals(0, $user->reputation_points);
        $this->assertEquals('UNREVIEWED', $user->reputation_status);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('0');
        $response->assertSee('Poin');
        $response->assertSee('Belum Ditinjau');
        $response->assertDontSee('Terverifikasi (LEGIT)');
        $response->assertSee('Ketentuan Transaksi dan Layanan');
        $response->assertSee('Midtrans Sandbox');
    }

    /**
     * Test user can update their username successfully.
     */
    public function test_user_can_update_username_successfully(): void
    {
        $user = User::factory()->create([
            'username' => 'lama_user',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'username' => 'baru_user123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('baru_user123', $user->username);
    }

    /**
     * Test user cannot update username to one that already exists.
     */
    public function test_user_cannot_update_username_to_duplicate(): void
    {
        User::factory()->create(['username' => 'existing_user']);

        $user = User::factory()->create([
            'username' => 'my_current_name',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'username' => 'existing_user',
        ]);

        $response->assertSessionHasErrors(['username']);
        $user->refresh();
        $this->assertEquals('my_current_name', $user->username);
    }

    /**
     * Test user cannot update username with invalid format.
     */
    public function test_user_cannot_update_username_with_invalid_format(): void
    {
        $user = User::factory()->create([
            'username' => 'valid_user',
            'account_status' => 'ACTIVE',
        ]);

        // Terlalu pendek (< 3 karakter)
        $responseShort = $this->actingAs($user)->put('/profile', ['username' => 'ab']);
        $responseShort->assertSessionHasErrors(['username']);

        // Mengandung spasi atau karakter ilegal selain a-z0-9_
        $responseInvalid = $this->actingAs($user)->put('/profile', ['username' => 'invalid name!']);
        $responseInvalid->assertSessionHasErrors(['username']);

        $user->refresh();
        $this->assertEquals('valid_user', $user->username);
    }
}
