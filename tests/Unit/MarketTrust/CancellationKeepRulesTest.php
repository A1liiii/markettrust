<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Admin\ReviewCancellationRequest;
use App\Models\KeepObligation;
use App\Models\Payment;
use App\Models\TransactionCancellation;
use PHPUnit\Framework\TestCase;

class CancellationKeepRulesTest extends TestCase
{
    /**
     * TC-12-01 & TC-12-02: Helper pemeriksaan stage dan status pada model TransactionCancellation.
     */
    public function test_tc_12_01_and_12_02_transaction_cancellation_stage_and_status_helpers(): void
    {
        $prePayment = new TransactionCancellation([
            'stage' => TransactionCancellation::STAGE_BEFORE_PAYMENT,
            'status' => TransactionCancellation::STATUS_APPROVED,
        ]);

        $this->assertTrue($prePayment->isBeforePayment());
        $this->assertFalse($prePayment->isAfterPaymentBeforeDelivery());
        $this->assertTrue($prePayment->isApproved());
        $this->assertFalse($prePayment->isRequested());
        $this->assertFalse($prePayment->isRejected());

        $postPayment = new TransactionCancellation([
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
        ]);

        $this->assertTrue($postPayment->isAfterPaymentBeforeDelivery());
        $this->assertFalse($postPayment->isBeforePayment());
        $this->assertTrue($postPayment->isRequested());
        $this->assertFalse($postPayment->isApproved());
    }

    /**
     * TC-12-03: Aturan label formula KEEP, status OPEN/SETTLED, dan perhitungan potongan pada model KeepObligation (UC-28B).
     */
    public function test_tc_12_03_keep_obligation_calculation_rules_and_deduction_helpers(): void
    {
        $bankVaKeep = new KeepObligation([
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 1,
            'amount' => 4000.00,
            'remaining_amount' => 4000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        $this->assertSame('Biaya Tetap Bank/VA (Rp 4.000)', $bankVaKeep->calculationRuleLabel());
        $this->assertTrue($bankVaKeep->isOpen());
        $this->assertFalse($bankVaKeep->isPartiallyDeducted());
        $this->assertSame(0.0, $bankVaKeep->deductedAmount());

        // Simulasi pemotongan sebagian (Rp 1.500 terpotong, sisa Rp 2.500)
        $bankVaKeep->remaining_amount = 2500.00;
        $this->assertTrue($bankVaKeep->isOpen());
        $this->assertTrue($bankVaKeep->isPartiallyDeducted());
        $this->assertSame(1500.0, $bankVaKeep->deductedAmount());

        $tripleFeeKeep = new KeepObligation([
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 1500.00,
            'multiplier' => 3,
            'amount' => 4500.00,
            'remaining_amount' => 0.00,
            'status' => KeepObligation::STATUS_SETTLED,
        ]);

        $this->assertSame('3x Biaya Gateway Pembayaran', $tripleFeeKeep->calculationRuleLabel());
        $this->assertFalse($tripleFeeKeep->isOpen());
        $this->assertFalse($tripleFeeKeep->isPartiallyDeducted());
        $this->assertSame(4500.0, $tripleFeeKeep->deductedAmount());
    }

    /**
     * TC-12-03: Verifikasi biaya gateway pembayaran dan aturan validasi ReviewCancellationRequest oleh Admin.
     */
    public function test_tc_12_03_payment_verified_gateway_fee_and_admin_review_request_rules(): void
    {
        $unverifiedPayment = new Payment(['gateway_fee_amount' => null]);
        $this->assertFalse($unverifiedPayment->hasVerifiedGatewayFee());

        $verifiedPayment = new Payment(['gateway_fee_amount' => 1500.00]);
        $this->assertTrue($verifiedPayment->hasVerifiedGatewayFee());

        $reviewRequest = new ReviewCancellationRequest;
        $rules = $reviewRequest->rules();

        $this->assertArrayHasKey('decision', $rules);
        $this->assertContains('in:APPROVED,REJECTED', $rules['decision']);

        $this->assertArrayHasKey('responsible_party', $rules);
        $this->assertContains('required_if:decision,APPROVED', $rules['responsible_party']);
        $this->assertContains('in:BUYER,SELLER,PLATFORM,UNDETERMINED', $rules['responsible_party']);
    }
}
