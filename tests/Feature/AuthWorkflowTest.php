<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration.
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'username' => 'budi_santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'budi_santoso',
            'email' => 'budi@example.com',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
    }

    /**
     * Test registration fails with invalid username.
     */
    public function test_registration_fails_with_invalid_username(): void
    {
        // Too short
        $response = $this->post('/register', [
            'username' => 'ab',
            'email' => 'ab@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['username']);

        // Invalid characters (dashes or symbols)
        $response2 = $this->post('/register', [
            'username' => 'user-name!@',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response2->assertSessionHasErrors(['username']);
    }

    /**
     * Test user can login with email.
     */
    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'username' => 'john_doe',
            'password_hash' => Hash::make('secret123'),
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'login' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user can login with username.
     */
    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'username' => 'jane_doe',
            'password_hash' => Hash::make('secret123'),
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'login' => 'jane_doe',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test suspended user cannot login.
     */
    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'username' => 'bad_user',
            'password_hash' => Hash::make('secret123'),
            'account_status' => 'SUSPENDED',
        ]);

        $response = $this->post('/login', [
            'login' => 'bad_user',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'account_status' => 'ACTIVE',
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
