<?php

namespace Tests\Feature\MarketTrust;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-01-01: Registrasi Akun Berhasil dengan Data Valid
     */
    public function test_tc_01_01_registration_successful_with_valid_data(): void
    {
        $payload = [
            'username' => 'qa_tester_01',
            'email' => 'qa_tester_01@markettrust.local',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post(route('register'), $payload);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'qa_tester_01',
            'email' => 'qa_tester_01@markettrust.local',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $user = User::where('username', 'qa_tester_01')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password_hash));
    }

    /**
     * TC-01-02: Registrasi Gagal Karena Validasi Input Tidak Sesuai (Duplikat Identitas)
     */
    public function test_tc_01_02_registration_fails_on_duplicate_identity(): void
    {
        User::create([
            'username' => 'existing_user',
            'email' => 'existing@markettrust.local',
            'password_hash' => Hash::make('Password123!'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        // Duplikat username
        $responseUsername = $this->post(route('register'), [
            'username' => 'existing_user',
            'email' => 'new_email@markettrust.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $responseUsername->assertSessionHasErrors('username');

        // Duplikat email
        $responseEmail = $this->post(route('register'), [
            'username' => 'another_user',
            'email' => 'existing@markettrust.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $responseEmail->assertSessionHasErrors('email');
    }

    /**
     * TC-01-03: Login Berhasil Menggunakan Username Maupun Email (Subtest Email & Subtest Username)
     */
    public function test_tc_01_03_login_successful_with_email_and_username(): void
    {
        $user = User::create([
            'username' => 'login_tester',
            'email' => 'login_tester@markettrust.local',
            'password_hash' => Hash::make('MySecretPassword123'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        // Subtest 1: Login menggunakan Email
        $responseEmail = $this->post(route('login'), [
            'login' => 'login_tester@markettrust.local',
            'password' => 'MySecretPassword123',
        ]);
        $responseEmail->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        Auth::logout();
        $this->assertGuest();

        // Subtest 2: Login menggunakan Username
        $responseUsername = $this->post(route('login'), [
            'login' => 'login_tester',
            'password' => 'MySecretPassword123',
        ]);
        $responseUsername->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * TC-01-04: Login Akun Admin Mengarahkan Langsung ke Dashboard Admin
     */
    public function test_tc_01_04_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::create([
            'username' => 'super_admin',
            'email' => 'admin@markettrust.local',
            'password_hash' => Hash::make('AdminPass123!'),
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->post(route('login'), [
            'login' => 'admin@markettrust.local',
            'password' => 'AdminPass123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * TC-01-05: Penolakan Login untuk Akun Berstatus SUSPENDED
     */
    public function test_tc_01_05_suspended_account_login_is_rejected(): void
    {
        User::create([
            'username' => 'banned_user',
            'email' => 'banned@markettrust.local',
            'password_hash' => Hash::make('BannedPass123!'),
            'account_type' => 'USER',
            'account_status' => 'SUSPENDED',
        ]);

        $response = $this->post(route('login'), [
            'login' => 'banned@markettrust.local',
            'password' => 'BannedPass123!',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * TC-01-06: Logout Sesi Pengguna
     */
    public function test_tc_01_06_user_logout_session_invalidation(): void
    {
        $user = User::create([
            'username' => 'user_logout',
            'email' => 'logout@markettrust.local',
            'password_hash' => Hash::make('Password123!'),
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
