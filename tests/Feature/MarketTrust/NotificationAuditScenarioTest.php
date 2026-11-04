<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\NotificationEventType;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAuditScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-15-01 — Lonceng Notifikasi Navbar dan Halaman Notifikasi Pengguna
     * Uji pemuatan daftar notifikasi pada halaman /notifications,
     * penghitungan badge unread, dan filtering tab (all, unread, read).
     */
    public function test_tc_15_01_lonceng_notifikasi_navbar_dan_halaman_notifikasi(): void
    {
        $user = User::factory()->create();

        // Buat 2 notifikasi belum dibaca dan 1 notifikasi sudah dibaca
        $notifUnread1 = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::TRANSACTION_CREATED->value,
            'message' => 'Pesanan baru #MT-101 telah dibentuk',
            'read_at' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        $notifUnread2 = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::AGREEMENT_PROPOSED->value,
            'message' => 'Usulan kesepakatan baru dari penjual',
            'read_at' => null,
            'created_at' => now()->subMinutes(5),
        ]);

        $notifRead = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::PAYMENT_SUCCESS->value,
            'message' => 'Pembayaran pesanan #MT-100 sukses',
            'read_at' => now()->subHours(2),
            'created_at' => now()->subHours(3),
        ]);

        // 1. Akses halaman notifikasi filter 'all'
        $responseAll = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'all']));
        $responseAll->assertOk();
        $responseAll->assertViewHas('unreadCount', 2);
        $responseAll->assertViewHas('totalCount', 3);
        $responseAll->assertViewHas('readCount', 1);

        // 2. Akses halaman notifikasi filter 'unread'
        $responseUnread = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'unread']));
        $responseUnread->assertOk();
        $notifications = $responseUnread->viewData('notifications');
        $this->assertCount(2, $notifications);

        // 3. Akses halaman notifikasi filter 'read'
        $responseRead = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'read']));
        $responseRead->assertOk();
        $notificationsRead = $responseRead->viewData('notifications');
        $this->assertCount(1, $notificationsRead);
    }

    /**
     * TC-15-02 — Fitur Tandai Semua Notifikasi Sudah Dibaca
     * Uji fungsi mark single read, mark-all-read via POST mereset unreadCount,
     * serta proteksi otorisasi kepemilikan notifikasi (403 saat membaca notifikasi user lain).
     */
    public function test_tc_15_02_fitur_tandai_semua_notifikasi_sudah_dibaca(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notif1 = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::AGREEMENT_PROPOSED->value,
            'message' => 'Usulan kesepakatan transaksi #1',
            'read_at' => null,
            'created_at' => now()->subMinutes(20),
        ]);

        $notif2 = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::AGREEMENT_REVISED->value,
            'message' => 'Revisi kesepakatan transaksi #1',
            'read_at' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        // 1. Akses notifikasi milik pihak lain -> ditolak 403 Forbidden
        $forbiddenRes = $this->actingAs($otherUser)
            ->get(route('notifications.read', $notif1->notification_id));
        $forbiddenRes->assertForbidden();

        // 2. Tandai satu notifikasi sebagai dibaca via JSON
        $singleReadRes = $this->actingAs($user)
            ->postJson(route('notifications.read', $notif1->notification_id));
        $singleReadRes->assertOk();
        $singleReadRes->assertJsonPath('success', true);

        $notif1->refresh();
        $this->assertNotNull($notif1->read_at);
        $this->assertEquals(1, $user->notifications()->unread()->count());

        // 3. Tandai semua dibaca via mark-all-read
        $markAllRes = $this->actingAs($user)
            ->post(route('notifications.mark-all-read'));
        $markAllRes->assertRedirect();

        $this->assertEquals(0, $user->notifications()->unread()->count());
        $notif2->refresh();
        $this->assertNotNull($notif2->read_at);
    }

    /**
     * TC-15-03 — Audit Trail Admin untuk Tindakan Kritis Sistem
     * Uji modul audit log hanya dapat diakses oleh Admin, pencatatan otomatis tindakan kritis,
     * dan kemampuan filter audit berdasarkan action.
     */
    public function test_tc_15_03_audit_trail_admin_untuk_tindakan_kritis_sistem(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        // Buat data audit log kritis
        AuditLog::create([
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_RESOLVED',
            'entity_type' => 'account_reports',
            'entity_id' => 12,
            'old_value' => ['status' => 'OPEN'],
            'new_value' => ['status' => 'RESOLVED'],
            'created_at' => now()->subHour(),
        ]);

        AuditLog::create([
            'actor_id' => $admin->user_id,
            'action' => 'PAYOUT_TRANSFERRED',
            'entity_type' => 'settlements',
            'entity_id' => 88,
            'old_value' => ['status' => 'PENDING'],
            'new_value' => ['status' => 'TRANSFERRED'],
            'created_at' => now()->subMinutes(30),
        ]);

        // 1. Pengguna non-admin ditolak (403 atau redirect dengan otorisasi admin)
        $unauthorizedRes = $this->actingAs($user)->get(route('admin.audit-logs.index'));
        $this->assertTrue(
            $unauthorizedRes->status() === 403 || $unauthorizedRes->isRedirect(),
            'Pengguna non-admin tidak boleh mengakses admin audit logs.'
        );

        // 2. Admin dapat mengakses halaman audit logs
        $adminRes = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $adminRes->assertOk();
        $adminRes->assertViewHas('auditLogs');

        // 3. Filter audit berdasarkan action
        $filterRes = $this->actingAs($admin)->get(route('admin.audit-logs.index', [
            'action' => 'ACCOUNT_REPORT_RESOLVED',
        ]));
        $filterRes->assertOk();
        $logs = $filterRes->viewData('auditLogs');
        $this->assertCount(1, $logs);
        $this->assertSame('ACCOUNT_REPORT_RESOLVED', $logs->first()->action);
    }
}
