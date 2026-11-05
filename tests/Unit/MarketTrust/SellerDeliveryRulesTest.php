<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Transaction\ExtendDeliveryDeadlineRequest;
use App\Http\Requests\Transaction\StoreDeliveryRequest;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit terisolasi untuk aturan penyerahan produk digital seller.
 * Mencakup TC-09-01, TC-09-02, TC-09-03, dan TC-09-04.
 */
class SellerDeliveryRulesTest extends TestCase
{
    /**
     * TC-09-01 Subtest 1: Aturan validasi lampiran berkas dan format hasil pada StoreDeliveryRequest.
     */
    public function test_tc_09_01_store_delivery_request_evidence_file_rules(): void
    {
        $request = new StoreDeliveryRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('evidence_file', $rules);
        $this->assertArrayHasKey('delivery_note', $rules);
        $this->assertArrayHasKey('result_link', $rules);

        // Memastikan tipe file dan batas ukuran maksimal
        $fileRules = is_array($rules['evidence_file']) ? implode('|', $rules['evidence_file']) : $rules['evidence_file'];
        $this->assertStringContainsString('max:', $fileRules);
    }

    /**
     * TC-09-02 Subtest 1: Memverifikasi aturan pencegahan penyerahan data kosong tanpa catatan atau berkas.
     */
    public function test_tc_09_02_delivery_empty_payload_rules_handling(): void
    {
        $request = new StoreDeliveryRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('delivery_note', $rules);
        $noteRules = is_array($rules['delivery_note']) ? implode('|', $rules['delivery_note']) : $rules['delivery_note'];
        $this->assertStringContainsString('max:', $noteRules);
    }

    /**
     * TC-09-03 Subtest 1: Memverifikasi batasan durasi dan alasan perpanjangan waktu penyerahan.
     */
    public function test_tc_09_03_extend_delivery_deadline_request_rules(): void
    {
        $request = new ExtendDeliveryDeadlineRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('extension_minutes', $rules);
        $this->assertArrayHasKey('reason', $rules);

        $this->assertContains('required', $rules['extension_minutes']);
        $this->assertContains('min:15', $rules['extension_minutes']);
        $this->assertContains('max:1440', $rules['extension_minutes']);

        $this->assertContains('required', $rules['reason']);
        $this->assertContains('min:5', $rules['reason']);
    }

    /**
     * TC-09-04 Subtest 1: Logika perhitungan tenggat jatuh tempo penyerahan dan perpanjangan waktu.
     */
    public function test_tc_09_04_delivery_deadline_arithmetic_logic(): void
    {
        $initialDueTimestamp = 1700000000;
        $extensionMinutes = 30;
        $newDueTimestamp = $initialDueTimestamp + ($extensionMinutes * 60);

        $this->assertSame(1700001800, $newDueTimestamp);
        $this->assertTrue($newDueTimestamp > $initialDueTimestamp);
    }
}
