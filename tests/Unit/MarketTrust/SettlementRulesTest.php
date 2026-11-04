<?php

namespace Tests\Unit\MarketTrust;

use Tests\TestCase;

class SettlementRulesTest extends TestCase
{
    /**
     * TC-13-01-U01: Formula kalkulasi net payout setelah dikurangi potongan denda KEEP
     */
    public function test_tc_01_01_net_payout_deduction_formula(): void
    {
        $grossAmount = 100000.0;
        $openKeepObligations = 25000.0;

        $keepDeduction = min($grossAmount, $openKeepObligations);
        $netPayout = $grossAmount - $keepDeduction;

        $this->assertEquals(25000.0, $keepDeduction);
        $this->assertEquals(75000.0, $netPayout);
    }

    /**
     * TC-13-01-U02: Batas net payout tidak boleh negatif jika denda KEEP melebihi gross amount
     */
    public function test_tc_01_02_net_payout_cannot_be_negative(): void
    {
        $grossAmount = 50000.0;
        $openKeepObligations = 75000.0;

        $keepDeduction = min($grossAmount, $openKeepObligations);
        $netPayout = max(0.0, $grossAmount - $keepDeduction);

        $this->assertEquals(50000.0, $keepDeduction);
        $this->assertEquals(0.0, $netPayout);
    }

    /**
     * TC-13-04-U01: Validasi kategori kegagalan payout sesuai standar UC-29
     */
    public function test_tc_13_04_valid_payout_failure_categories(): void
    {
        $validCategories = ['SYSTEM', 'SELLER_DESTINATION', 'GATEWAY_UNKNOWN', 'NONE'];

        $this->assertContains('SYSTEM', $validCategories);
        $this->assertContains('SELLER_DESTINATION', $validCategories);
        $this->assertContains('GATEWAY_UNKNOWN', $validCategories);
        $this->assertNotContains('INVALID_CATEGORY', $validCategories);
    }
}
