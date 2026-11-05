<?php

namespace Tests\Feature\MarketTrust;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleOAuthProfileScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockGoogleUser(string $id, string $email, string $name, ?string $avatar = null): void
    {
        $googleUser = Mockery::mock('Laravel\Socialite\Two\User');
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getNickname')->andReturn(null);
        $googleUser->shouldReceive('getAvatar')->andReturn($avatar ?? 'https://lh3.googleusercontent.com/a/default');

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /**
     * TC-02-01 (Turunan): Login Google Berhasil untuk Akun Pengguna yang Sudah Terhubung
     */
    public function test_tc_02_01_google_login_success_for_existing_user(): void
    {
        $user = User::create([
            'username' => 'google_linked_user',
            'email' => 'google_user@markettrust.local',
            'google_id' => 'gid_123456789',
            'password_hash' => Hash::make('SecretPass123!'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $this->mockGoogleUser('gid_123456789', 'google_user@markettrust.local', 'Google Linked User');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * TC-02-02 (Turunan): Login Google Berhasil untuk Akun Admin Mengarahkan ke Dashboard Admin
     */
    public function test_tc_02_02_google_login_admin_redirects_to_admin_dashboard(): void
    {
        $admin = User::create([
            'username' => 'google_admin',
            'email' => 'admin_oauth@markettrust.local',
            'google_id' => 'gid_admin_999',
            'password_hash' => Hash::make('AdminPass123!'),
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        $this->mockGoogleUser('gid_admin_999', 'admin_oauth@markettrust.local', 'Google Admin');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * TC-02-03 (Turunan): Penolakan Login Google untuk Akun Berstatus SUSPENDED
     */
    public function test_tc_02_03_google_login_rejected_for_suspended_account(): void
    {
        User::create([
            'username' => 'suspended_oauth_user',
            'email' => 'suspended_oauth@markettrust.local',
            'google_id' => 'gid_suspended_403',
            'password_hash' => Hash::make('Pass123!'),
            'account_type' => 'USER',
            'account_status' => 'SUSPENDED',
        ]);

        $this->mockGoogleUser('gid_suspended_403', 'suspended_oauth@markettrust.local', 'Suspended User');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * TC-02-04 (Turunan): Penanganan Email Google yang Sudah Terdaftar di Akun Lokal
     */
    public function test_tc_02_04_google_login_with_existing_email_triggers_account_linking(): void
    {
        $localUser = User::create([
            'username' => 'local_acc_owner',
            'email' => 'shared_email@markettrust.local',
            'password_hash' => Hash::make('LocalPassword123!'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $this->mockGoogleUser('gid_new_for_email', 'shared_email@markettrust.local', 'Shared Email User');

        $callbackResponse = $this->get(route('auth.google.callback'));
        $callbackResponse->assertRedirect(route('auth.google.link'));
        $this->assertTrue(session()->has('pending_google_link'));

        // Selesaikan penautan akun via POST
        $linkResponse = $this->post(route('auth.google.link.store'), [
            'password' => 'LocalPassword123!',
        ]);

        $linkResponse->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($localUser);
        $this->assertEquals('gid_new_for_email', $localUser->fresh()->google_id);
    }

    /**
     * TC-02-05 (Turunan): Registrasi Google Pengguna Baru Diarahkan Memilih Username
     */
    public function test_tc_02_05_google_registration_flow_for_new_user(): void
    {
        $this->mockGoogleUser('gid_brand_new', 'brandnew@markettrust.local', 'Brand New User');

        $callbackResponse = $this->get(route('auth.google.callback'));
        $callbackResponse->assertRedirect(route('auth.google.username'));
        $this->assertTrue(session()->has('pending_google_registration'));

        // Submit form pemilihan username
        $completeResponse = $this->post(route('auth.google.username.store'), [
            'username' => 'brand_new_registered',
        ]);

        $completeResponse->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'brand_new_registered',
            'email' => 'brandnew@markettrust.local',
            'google_id' => 'gid_brand_new',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-02-06 (Turunan): Pembaruan Profil Pengguna
     */
    public function test_tc_02_06_profile_update_successful(): void
    {
        $user = User::create([
            'username' => 'profile_edit_user',
            'email' => 'profile_edit@markettrust.local',
            'password_hash' => Hash::make('OldPassword123!'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'username' => 'profile_updated_user',
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertDatabaseHas('users', [
            'user_id' => $user->user_id,
            'username' => 'profile_updated_user',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password_hash));
    }
}
