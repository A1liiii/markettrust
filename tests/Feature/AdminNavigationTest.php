<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin dashboard menampilkan overview analitik dan sidebar kontrol panel lengkap.
     */
    public function test_admin_can_access_dashboard_overview_analytics_with_sidebar(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'superadmin',
            'email' => 'superadmin@markettrust.id',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Control Panel');
        $response->assertSee('Overview Analitik');

        // Metrik analitik utama
        $response->assertSee('Gross Escrow Volume');
        $response->assertSee('Pendapatan Fee Platform');
        $response->assertSee('Escrow Aktif (Berjalan)');
        $response->assertSee('Distribusi Status Escrow Transaksi');
        $response->assertSee('Transaksi Terbaru');
        $response->assertSee('Listing Terbaru');

        // Sidebar modul navigasi
        $response->assertSee('Katalog Digital');
        $response->assertSee('Transaksi & Escrow', false);
        $response->assertSee('Sengketa & Komplain', false);
        $response->assertSee('Pengguna & Akun', false);
        $response->assertSee('Audit Log');

        // Identitas admin di sidebar
        $response->assertSee('@superadmin');
        $response->assertSee('ADMIN');
        $response->assertSee('superadmin@markettrust.id');

        // Form & tombol logout di sidebar
        $response->assertSee(route('logout'));
        $response->assertSee('Logout');

        // Gear icon button untuk pengaturan akun (title / aria-label)
        $response->assertSee('Pengaturan Akun');

        // Footer bersih tanpa status database / escrow engine
        $response->assertDontSee('Database: PostgreSQL Supabase / SQLite');
        $response->assertDontSee('Escrow Engine: Active');

        // Kontrol interaktif sidebar smooth open/close (Desktop toggle & Mobile drawer panel)
        $response->assertSee('admin-desktop-toggle');
        $response->assertSee('admin-sidebar-collapse');
        $response->assertSee('admin-mobile-backdrop');
        $response->assertSee('admin-mobile-panel');

        // Text 'MarketTrust Control Engine v2.0' sudah dihapus dari navbar
        $response->assertDontSee('MarketTrust Control Engine v2.0');

        // Tidak boleh ada link publik user biasa
        $response->assertDontSee('#marketplace');
        $response->assertDontSee('Listing Saya');
        $response->assertDontSee('Mulai Berjualan');

        // Memastikan tidak ada class underline yang mengganggu
        $response->assertDontSee('underline underline-offset-4');
    }

    /**
     * Sidebar dan user card admin juga tampil konsisten pada halaman modul katalog digital.
     */
    public function test_admin_sees_sidebar_consistently_on_catalog_page(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'chief_admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.catalog.index'));

        $response->assertOk();
        $response->assertSee('Control Panel');
        $response->assertSee('Overview Analitik');
        $response->assertSee('Katalog Digital');
        $response->assertSee('@chief_admin');
        $response->assertSee('Logout');

        // Tidak boleh ada class underline
        $response->assertDontSee('underline underline-offset-4');
    }

    /**
     * Pengguna publik/biasa hanya melihat navbar publik dan tidak melihat modul atau sidebar admin.
     */
    public function test_regular_user_sees_public_navbar_without_admin_modules(): void
    {
        $user = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Digital Escrow');
        $response->assertDontSee('Control Panel');

        // Public items visible
        $response->assertSee('Marketplace');
        $response->assertSee('Forum');

        // Admin dashboard & links hidden from regular user
        $response->assertDontSee(route('admin.dashboard'));
        $response->assertDontSee(route('admin.catalog.index'));
        $response->assertDontSee(route('admin.transactions.index'));
        $response->assertDontSee(route('admin.disputes.index'));
        $response->assertDontSee(route('admin.users.index'));
        $response->assertDontSee(route('admin.audit-logs.index'));
        $response->assertDontSee('Overview Analitik');
    }

    /**
     * User biasa dilarang mengakses admin dashboard (403 Forbidden).
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['account_type' => 'USER']);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    /**
     * Tamu / guest dilarang mengakses admin dashboard dan dialihkan ke login.
     */
    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Admin placeholder routes dapat diakses dengan sukses oleh admin.
     */
    public function test_admin_can_access_all_admin_module_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $routes = [
            route('admin.transactions.index') => 'Pengawasan Transaksi',
            route('admin.disputes.index') => 'Penyelesaian Sengketa',
            route('admin.users.index') => 'Manajemen Pengguna',
            route('admin.audit-logs.index') => 'Audit Log',
        ];

        foreach ($routes as $url => $keyword) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $response->assertSee($keyword);
            $response->assertSee('Modul Tahap Lanjutan');
            $response->assertSee('Logout');
        }
    }

    /**
     * User biasa dilarang mengakses rute modul admin placeholder (403 Forbidden).
     */
    public function test_regular_user_cannot_access_admin_placeholder_routes(): void
    {
        $user = User::factory()->create(['account_type' => 'USER']);

        $routes = [
            route('admin.transactions.index'),
            route('admin.disputes.index'),
            route('admin.users.index'),
            route('admin.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertForbidden();
        }
    }

    /**
     * Admin dapat membuka platform publik (beranda) tanpa dialihkan paksa kembali ke dashboard.
     */
    public function test_admin_can_visit_public_home_platform_without_bounce(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Panel Admin');
        $response->assertSee('Marketplace');
    }

    /**
     * Admin saat login dialihkan ke admin dashboard analitik.
     */
    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin_super@example.com',
            'username' => 'admin_super',
            'password_hash' => bcrypt('adminpass123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'admin_super@example.com',
            'password' => 'adminpass123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * Dashboard admin merender 7 kartu ringkasan, antrean tindakan admin, pemisahan dana escrow, dan 5 chart analitik.
     */
    public function test_admin_dashboard_renders_complete_summary_cards_action_queue_and_charts(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();

        // 7 Kartu Ringkasan Status Transaksi
        $response->assertSee('Transaksi Aktif');
        $response->assertSee('Menunggu Bayar');
        $response->assertSee('Menunggu Serah');
        $response->assertSee('Menunggu Konfirmasi');
        $response->assertSee('Siap Payout');
        $response->assertSee('Komplain Aktif');
        $response->assertSee('Payout Gagal');

        // Antrean Tindakan Admin (Action Queue)
        $response->assertSee('Antrean Tindakan Admin (Action Queue)');

        // Pemisahan Dana Escrow & Fee Platform
        $response->assertSee('Kinerja Finansial & Pemisahan Dana', false);
        $response->assertSee('Prinsip Integritas Escrow MarketTrust');
        $response->assertSee('Payout Siap Transfer');

        // 5 Chart Canvas & Tombol Periode
        $response->assertSee('chart-transaction-trend');
        $response->assertSee('chart-status-donut');
        $response->assertSee('chart-performance-bar');
        $response->assertSee('chart-complaints-trend');
        $response->assertSee('chart-payouts-status');

        $response->assertSee('7 Hari');
        $response->assertSee('30 Hari');
        $response->assertSee('90 Hari');
        $response->assertSee('Semua');

        // Aktivitas Terkini
        $response->assertSee('Aktivitas Terkini (Live Stream)');
    }

    /**
     * Endpoint API JSON analitik mengembalikan struktur data lengkap 5 chart.
     */
    public function test_admin_can_fetch_analytics_chart_data_json(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.analytics', ['period' => '7d']));

        $response->assertOk();
        $response->assertJsonStructure([
            'period',
            'trend' => ['labels', 'transactions', 'gmv'],
            'statusComposition' => ['labels', 'data', 'total'],
            'performance' => ['labels', 'data'],
            'complaints' => ['labels', 'data'],
            'payouts' => ['labels', 'data'],
        ]);
        $response->assertJsonPath('period', '7d');
    }

    /**
     * Pengguna biasa dilarang mengakses endpoint analitik admin (403 Forbidden).
     */
    public function test_regular_user_cannot_access_analytics_endpoint(): void
    {
        $user = User::factory()->create(['account_type' => 'USER']);

        $response = $this->actingAs($user)->getJson(route('admin.dashboard.analytics'));

        $response->assertForbidden();
    }
}
