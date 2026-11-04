<?php

namespace Tests\Unit\MarketTrust;

use App\Models\AccountReport;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModerationRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji konstanta status dan sumber pada model AccountReport.
     */
    public function test_account_report_status_constants(): void
    {
        $this->assertSame('OPEN', AccountReport::STATUS_OPEN);
        $this->assertSame('REVIEWING', AccountReport::STATUS_REVIEWING);
        $this->assertSame('RESOLVED', AccountReport::STATUS_RESOLVED);
        $this->assertSame('DISMISSED', AccountReport::STATUS_DISMISSED);

        $this->assertSame('USER', AccountReport::SOURCE_USER);
        $this->assertSame('ADMIN', AccountReport::SOURCE_ADMIN);
        $this->assertSame('SYSTEM', AccountReport::SOURCE_SYSTEM);
    }

    /**
     * Uji helper status method pada instance model AccountReport.
     */
    public function test_account_report_helper_status_methods(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();

        $report = new AccountReport([
            'reporter_id' => $reporter->user_id,
            'reported_user_id' => $reported->user_id,
            'reason' => 'Pengujian status method',
            'status' => AccountReport::STATUS_OPEN,
        ]);

        $this->assertTrue($report->isOpen());
        $this->assertFalse($report->isReviewing());
        $this->assertFalse($report->isResolved());
        $this->assertFalse($report->isDismissed());

        $report->status = AccountReport::STATUS_REVIEWING;
        $this->assertTrue($report->isReviewing());

        $report->status = AccountReport::STATUS_RESOLVED;
        $this->assertTrue($report->isResolved());

        $report->status = AccountReport::STATUS_DISMISSED;
        $this->assertTrue($report->isDismissed());
    }

    /**
     * Uji konstanta event reputasi dan helper generateReportEventKey untuk idempotensi.
     */
    public function test_reputation_event_constants_and_key_generation(): void
    {
        $this->assertSame('ADMIN_MODERATION', ReputationEvent::EVENT_ADMIN_MODERATION);
        $this->assertSame('TRANSACTION_COMPLETED', ReputationEvent::EVENT_TRANSACTION_COMPLETED);

        $key = ReputationEvent::generateReportEventKey(105);
        $this->assertSame('account_report:105:resolution', $key);

        $keyString = ReputationEvent::generateReportEventKey('999');
        $this->assertSame('account_report:999:resolution', $keyString);
    }
}
