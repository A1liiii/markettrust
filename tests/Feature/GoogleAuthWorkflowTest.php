<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to mock Socialite Google driver.
     */
    protected function mockGoogleUser(string $id, string $email, string $name, ?string $avatar = null): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);
        $socialiteUser->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    /**
     * Test existing Google user logs in directly.
     */
    public function test_existing_google_user_logs_in_directly(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google_sub_12345',
            'username' => 'google_player',
            'email' => 'google@example.com',
            'account_status' => 'ACTIVE',
        ]);

        $this->mockGoogleUser('google_sub_12345', 'google@example.com', 'Google Player');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test suspended Google user cannot log in.
     */
    public function test_suspended_google_user_cannot_log_in(): void
    {
        User::factory()->create([
            'google_id' => 'google_sub_99999',
            'username' => 'suspended_google_user',
            'account_status' => 'SUSPENDED',
        ]);

        $this->mockGoogleUser('google_sub_99999', 'suspended@example.com', 'Suspended User');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * Test first-time Google user is redirected to choose username.
     */
    public function test_new_google_user_is_redirected_to_choose_username(): void
    {
        $this->mockGoogleUser('new_google_id_777', 'fresh@example.com', 'Fresh User', 'https://avatar.url/img.png');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('auth.google.username'));
        $response->assertSessionHas('pending_google_registration');
        $this->assertGuest();
    }

    /**
     * Test completing Google registration with valid chosen username.
     */
    public function test_new_google_user_can_complete_registration(): void
    {
        $sessionData = [
            'google_id' => 'new_google_id_777',
            'email' => 'fresh@example.com',
            'avatar_url' => 'https://avatar.url/img.png',
            'name' => 'Fresh User',
            'suggested_username' => 'fresh_user',
        ];

        $response = $this->withSession(['pending_google_registration' => $sessionData])
            ->post('/auth/google/username', [
                'username' => 'fresh_trader',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'fresh_trader',
            'email' => 'fresh@example.com',
            'google_id' => 'new_google_id_777',
            'avatar_url' => 'https://avatar.url/img.png',
            'password_hash' => null,
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
    }

    /**
     * Test Google email matching local user prompts account linking.
     */
    public function test_google_login_with_existing_local_email_prompts_account_linking(): void
    {
        $localUser = User::factory()->create([
            'email' => 'existing@example.com',
            'username' => 'local_veteran',
            'password_hash' => Hash::make('my_local_pass'),
            'google_id' => null,
        ]);

        $this->mockGoogleUser('new_google_link_id', 'existing@example.com', 'Existing Person');

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('auth.google.link'));
        $response->assertSessionHas('pending_google_link');
        $this->assertGuest();
    }

    /**
     * Test user can link Google account with valid local password.
     */
    public function test_user_can_link_google_account_with_correct_password(): void
    {
        $localUser = User::factory()->create([
            'email' => 'existing@example.com',
            'username' => 'local_veteran',
            'password_hash' => Hash::make('my_local_pass'),
            'google_id' => null,
        ]);

        $sessionData = [
            'google_id' => 'new_google_link_id',
            'email' => 'existing@example.com',
            'avatar_url' => 'https://avatar.url/new_img.png',
            'user_id' => $localUser->user_id,
            'username' => $localUser->username,
        ];

        $response = $this->withSession(['pending_google_link' => $sessionData])
            ->post('/auth/google/link', [
                'password' => 'my_local_pass',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($localUser);

        $this->assertDatabaseHas('users', [
            'user_id' => $localUser->user_id,
            'google_id' => 'new_google_link_id',
        ]);
    }
}
