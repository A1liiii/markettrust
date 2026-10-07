<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthRouteTest extends TestCase
{
    /**
     * Test the landing page returns HTTP 200.
     */
    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('MarketTrust');
        $response->assertSee('Marketplace & Forum Digital', false);
        $response->assertSee('glass-card', false);
        $response->assertSee('glass-nav', false);
    }

    /**
     * Test the login page can be rendered.
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
        $response->assertSee('Masuk dengan Google');
        $response->assertSee('glass-panel', false);
        $response->assertSee('glass-input', false);
    }

    /**
     * Test the register page can be rendered.
     */
    public function test_register_page_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Buat Akun MarketTrust');
        $response->assertSee('Username Publik');
        $response->assertSee('glass-panel', false);
        $response->assertSee('glass-btn-primary', false);
    }

    /**
     * Test Google choose username page can be rendered.
     */
    public function test_google_choose_username_page_can_be_rendered(): void
    {
        $response = $this->withSession([
            'pending_google_registration' => [
                'google_id' => '123456',
                'email' => 'user@google.com',
                'name' => 'Google User',
                'avatar_url' => 'https://avatar.url',
                'suggested_username' => 'google_user',
            ],
        ])->get('/auth/google/username');

        $response->assertStatus(200);
        $response->assertSee('Satu Langkah Lagi!');
        $response->assertSee('user@google.com');
        $response->assertSee('glass-panel', false);
    }

    /**
     * Test Google link account page can be rendered.
     */
    public function test_google_link_account_page_can_be_rendered(): void
    {
        $response = $this->withSession([
            'pending_google_link' => [
                'google_id' => '654321',
                'email' => 'existing@google.com',
                'user_id' => 999,
                'username' => 'existing_user',
            ],
        ])->get('/auth/google/link');

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Kepemilikan Akun');
        $response->assertSee('existing@google.com');
        $response->assertSee('existing_user');
        $response->assertSee('glass-alert-warning', false);
    }

    /**
     * Test Google redirect without credentials gives proper error.
     */
    public function test_google_redirect_handles_unconfigured_credentials(): void
    {
        // When google client_id or secret is empty, it redirects back to login with error
        config([
            'services.google.client_id' => '',
            'services.google.client_secret' => '',
        ]);

        $response = $this->get('/auth/google');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['login']);
    }
}
