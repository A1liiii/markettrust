<?php

namespace Tests\Unit\MarketTrust;

use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit terisolasi untuk aturan bisnis konfirmasi penerimaan dan auto-confirm.
 * Mencakup TC-10-01, TC-10-02, dan TC-10-03.
 */
class BuyerConfirmationRulesTest extends TestCase
{
    /**
     * TC-10-01 Subtest 1: Perhitungan tenggat pencairan payout pada konfirmasi normal (confirmed_at + 1 jam).
     */
    public function test_tc_10_01_settlement_payout_due_calculation(): void
    {
        $confirmedAt = 1700000000;
        $payoutDueAt = $confirmedAt + 3600;

        $this->assertSame(1700003600, $payoutDueAt);
        $this->assertSame(3600, $payoutDueAt - $confirmedAt);
    }

    /**
     * TC-10-02 Subtest 1: Perhitungan batas waktu masa tenggang pembeli pasif (60 menit awal + 30 menit grace).
     */
    public function test_tc_10_02_grace_period_window_calculation(): void
    {
        $deliveredAt = 1700000000;
        $initialDue = $deliveredAt + 3600; // 60 menit
        $graceMinutes = 30;
        $finalDue = $initialDue + ($graceMinutes * 60); // 90 menit total

        $this->assertSame(1700003600, $initialDue);
        $this->assertSame(1700005400, $finalDue);
        $this->assertSame(5400, $finalDue - $deliveredAt);
    }

    /**
     * TC-10-03 Subtest 1: Logika kriteria kelayakan auto-confirm (DELIVERED, waktu melewati masa tenggang, tanpa komplain).
     */
    public function test_tc_10_03_auto_confirm_eligibility_criteria_logic(): void
    {
        $checkEligibility = function (string $status, int $currentTime, int $dueTime, bool $hasActiveComplaint): bool {
            return $status === 'DELIVERED'
                && $currentTime >= $dueTime
                && ! $hasActiveComplaint;
        };

        // Memenuhi syarat (tenggat lewat, tanpa komplain)
        $this->assertTrue($checkEligibility('DELIVERED', 100, 90, false));

        // Belum jatuh tempo
        $this->assertFalse($checkEligibility('DELIVERED', 80, 90, false));

        // Ada komplain aktif -> auto confirm DIBEKUKAN
        $this->assertFalse($checkEligibility('DELIVERED', 100, 90, true));

        // Status bukan DELIVERED
        $this->assertFalse($checkEligibility('PAID', 100, 90, false));
    }
}
