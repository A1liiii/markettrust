<?php

namespace Tests\Unit\MarketTrust;

use App\Enums\NotificationEventType;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAuditRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji enum NotificationEventType beserta helper label dan badge class.
     */
    public function test_notification_event_type_enum(): void
    {
        $this->assertSame('AGREEMENT_PROPOSED', NotificationEventType::AGREEMENT_PROPOSED->value);
        $this->assertSame('AGREEMENT_REVISED', NotificationEventType::AGREEMENT_REVISED->value);
        $this->assertSame('TRANSACTION_CREATED', NotificationEventType::TRANSACTION_CREATED->value);

        $this->assertSame('Usulan Kesepakatan', NotificationEventType::AGREEMENT_PROPOSED->label());
        $this->assertSame('Revisi Kesepakatan', NotificationEventType::AGREEMENT_REVISED->label());
        $this->assertSame('Pesanan Baru', NotificationEventType::TRANSACTION_CREATED->label());
    }

    /**
     * Uji scope unread dan read serta helper method isRead pada model Notification.
     */
    public function test_notification_scope_unread_and_read(): void
    {
        $user = User::factory()->create();

        $unreadNotif = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::TRANSACTION_CREATED->value,
            'message' => 'Pesanan baru telah dibuat #101',
            'read_at' => null,
            'created_at' => now(),
        ]);

        $readNotif = Notification::create([
            'user_id' => $user->user_id,
            'event_type' => NotificationEventType::PAYMENT_SUCCESS->value,
            'message' => 'Pembayaran pesanan #100 berhasil diverifikasi',
            'read_at' => now(),
            'created_at' => now()->subHour(),
        ]);

        $this->assertFalse($unreadNotif->isRead());
        $this->assertTrue($readNotif->isRead());

        $this->assertCount(1, $user->notifications()->unread()->get());
        $this->assertCount(1, $user->notifications()->read()->get());
    }

    /**
     * Uji casting json array dan relasi actor pada model AuditLog.
     */
    public function test_audit_log_casts_json_values(): void
    {
        $admin = User::factory()->admin()->create();

        $log = AuditLog::create([
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_RESOLVED',
            'entity_type' => 'account_reports',
            'entity_id' => 99,
            'old_value' => ['status' => 'OPEN', 'points' => 100],
            'new_value' => ['status' => 'RESOLVED', 'points' => 90],
            'created_at' => now(),
        ]);

        $this->assertIsArray($log->old_value);
        $this->assertIsArray($log->new_value);
        $this->assertSame('OPEN', $log->old_value['status']);
        $this->assertSame('RESOLVED', $log->new_value['status']);
        $this->assertTrue($log->actor->is($admin));
    }
}
