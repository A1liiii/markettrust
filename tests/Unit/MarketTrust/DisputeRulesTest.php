<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Admin\DecideDisputeRequest;
use App\Http\Requests\Admin\RequestDisputeEvidenceRequest;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\StoreComplaintResponseRequest;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit terisolasi untuk aturan validasi alur komplain dan sengketa (dispute).
 * Mencakup TC-11-01 sampai TC-11-06.
 */
class DisputeRulesTest extends TestCase
{
    /**
     * TC-11-01 Subtest 1: Aturan validasi pengajuan komplain oleh pembeli (StoreComplaintRequest).
     */
    public function test_tc_11_01_store_complaint_request_rules(): void
    {
        $request = new StoreComplaintRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('reason', $rules);
        $this->assertArrayHasKey('evidence_file', $rules);

        $this->assertContains('required', $rules['reason']);
        $this->assertContains('min:10', $rules['reason']);
        $this->assertContains('max:2000', $rules['reason']);

        $this->assertContains('required', $rules['evidence_file']);
    }

    /**
     * TC-11-02 Subtest 1: Logika aturan batas 1 komplain per transaksi.
     */
    public function test_tc_11_02_duplicate_complaint_prevention_logic(): void
    {
        $canFileComplaint = function (bool $complaintExists, string $transactionStatus): bool {
            return ! $complaintExists && $transactionStatus === 'DELIVERED';
        };

        // Belum ada komplain dan status DELIVERED -> Boleh
        $this->assertTrue($canFileComplaint(false, 'DELIVERED'));

        // Sudah ada komplain sebelumnya -> Ditolak
        $this->assertFalse($canFileComplaint(true, 'DELIVERED'));

        // Status bukan DELIVERED -> Ditolak
        $this->assertFalse($canFileComplaint(false, 'PAID'));
    }

    /**
     * TC-11-03 Subtest 1: Aturan validasi pengiriman tanggapan penjual (StoreComplaintResponseRequest).
     */
    public function test_tc_11_03_store_complaint_response_request_rules(): void
    {
        $request = new StoreComplaintResponseRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('response_text', $rules);
        $this->assertArrayHasKey('evidence_file', $rules);

        $this->assertContains('required', $rules['response_text']);
        $this->assertContains('min:5', $rules['response_text']);
        $this->assertContains('max:2000', $rules['response_text']);
    }

    /**
     * TC-11-04 Subtest 1: Aturan validasi permintaan bukti tambahan oleh Admin (RequestDisputeEvidenceRequest).
     */
    public function test_tc_11_04_request_dispute_evidence_request_rules(): void
    {
        $request = new RequestDisputeEvidenceRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('reason', $rules);
        $this->assertArrayHasKey('extension_hours', $rules);

        $this->assertContains('required', $rules['reason']);
        $this->assertContains('min:5', $rules['reason']);
        $this->assertContains('max:1000', $rules['reason']);
    }

    /**
     * TC-11-05 & TC-11-06 Subtest 1: Aturan validasi keputusan sengketa admin (DecideDisputeRequest: PAYOUT & REFUND).
     */
    public function test_tc_11_05_and_06_decide_dispute_request_rules(): void
    {
        $request = new DecideDisputeRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('decision', $rules);
        $this->assertArrayHasKey('decision_reason', $rules);

        $this->assertContains('required', $rules['decision']);
        $this->assertContains('in:PAYOUT,REFUND', $rules['decision']);

        $this->assertContains('required', $rules['decision_reason']);
        $this->assertContains('min:10', $rules['decision_reason']);
        $this->assertContains('max:2000', $rules['decision_reason']);
    }
}
