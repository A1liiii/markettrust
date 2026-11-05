<?php

namespace Tests\Unit\MarketTrust;

use App\Enums\PayoutDestinationType;
use App\Models\SellerPayoutAccount;
use PHPUnit\Framework\TestCase;

class PayoutAccountRulesTest extends TestCase
{
    /**
     * TC-02-01: Verifikasi kelengkapan data tipe BANK pada Payout Destination.
     */
    public function test_tc_02_01_bank_payout_completeness_rules(): void
    {
        $account = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Ali Owzey',
        ]);

        $this->assertTrue($account->isComplete());
        $this->assertSame('•••• •••• 7890', $account->masked_destination_number);
        $this->assertSame('Rekening Bank', PayoutDestinationType::BANK->label());

        // Tidak lengkap jika nomor rekening kosong
        $incomplete = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '',
            'destination_holder' => 'Ali Owzey',
        ]);
        $this->assertFalse($incomplete->isComplete());
    }

    /**
     * TC-02-02: Verifikasi kelengkapan data tipe EWALLET pada Payout Destination.
     */
    public function test_tc_02_02_ewallet_payout_completeness_rules(): void
    {
        $account = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::EWALLET,
            'destination_provider' => 'GoPay',
            'destination_number' => '081234567890',
            'destination_holder' => 'Ali Owzey',
        ]);

        $this->assertTrue($account->isComplete());
        $this->assertSame('Dompet Digital (E-Wallet)', PayoutDestinationType::EWALLET->label());
    }

    /**
     * TC-02-03: Verifikasi kelengkapan data tipe QRIS memerlukan gambar atau identifier.
     */
    public function test_tc_02_03_qris_payout_requires_image_or_identifier(): void
    {
        // QRIS tanpa gambar dan tanpa identifier -> belum lengkap
        $accountWithoutImage = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::QRIS,
            'destination_holder' => 'Ali Toko',
            'destination_number' => null,
            'destination_qris_image_url' => null,
        ]);
        $this->assertFalse($accountWithoutImage->isComplete());

        // QRIS dengan URL gambar -> lengkap
        $accountWithImage = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::QRIS,
            'destination_holder' => 'Ali Toko',
            'destination_qris_image_url' => '/storage/payout_qris/qris1.png',
        ]);
        $this->assertTrue($accountWithImage->isComplete());
        $this->assertSame('QRIS Interaktif', PayoutDestinationType::QRIS->label());
    }

    /**
     * TC-02-04: Verifikasi masking nomor rekening pendek (<= 4 karakter).
     */
    public function test_tc_02_04_short_account_number_masking_rules(): void
    {
        $shortAccount = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '123',
            'destination_holder' => 'Ali',
        ]);

        $this->assertSame('•••• 123', $shortAccount->masked_destination_number);
    }
}
