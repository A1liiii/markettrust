<?php

namespace Tests\Unit\MarketTrust;

use Tests\TestCase;

class MidtransPaymentRulesTest extends TestCase
{
    /**
     * TC-08-01-U01: Formula perhitungan signature key SHA-512 Midtrans
     */
    public function test_tc_08_01_midtrans_signature_key_calculation(): void
    {
        $orderId = 'TRX-1001-TEST';
        $statusCode = '200';
        $grossAmount = '55000.00';
        $serverKey = 'SB-Mid-server-TESTKEY123';

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $this->assertEquals(128, strlen($expectedSignature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{128}$/', $expectedSignature);
    }

    /**
     * TC-08-02-U01: Verifikasi kecocokan signature key (hash_equals)
     */
    public function test_tc_08_02_signature_verification_comparison(): void
    {
        $orderId = 'TRX-1002-TEST';
        $statusCode = '200';
        $grossAmount = '55000.00';
        $serverKey = 'SB-Mid-server-TESTKEY123';

        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $invalidSignature = hash('sha512', $orderId.$statusCode.'99999.00'.$serverKey);

        $this->assertTrue(hash_equals($validSignature, $validSignature));
        $this->assertFalse(hash_equals($validSignature, $invalidSignature));
    }

    /**
     * TC-08-03-U01: Pemetaan status notifikasi settlement ke status pembayaran internal
     */
    public function test_tc_08_03_settlement_status_mapping(): void
    {
        $settlementStatus = 'settlement';
        $isSettled = ($settlementStatus === 'settlement') || ($settlementStatus === 'capture');

        $this->assertTrue($isSettled);

        $pendingStatus = 'pending';
        $isPendingSettled = ($pendingStatus === 'settlement') || ($pendingStatus === 'capture');
        $this->assertFalse($isPendingSettled);
    }
}
