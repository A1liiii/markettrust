<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\PayoutDestinationType;
use App\Models\SellerPayoutAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayoutAccountScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'username' => 'aliseller',
            'email' => 'aliseller@markettrust.test',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-02-01: Penambahan Rekening Payout Tipe BANK.
     */
    public function test_tc_02_01_seller_can_add_bank_payout_account(): void
    {
        $payload = [
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '8720192831',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->seller)
            ->post(route('seller.payout-accounts.store'), $payload);

        $response->assertRedirect(route('seller.payout-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_payout_accounts', [
            'seller_id' => $this->seller->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '8720192831',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => true,
        ]);
    }

    /**
     * TC-02-02: Aturan Atomik Hanya 1 Rekening Payout yang Aktif per Seller.
     */
    public function test_tc_02_02_atomic_single_active_payout_account_enforcement(): void
    {
        // 1. Buat rekening BANK pertama (otomatis aktif)
        $firstAccount = SellerPayoutAccount::create([
            'seller_id' => $this->seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '11112222',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => true,
        ]);

        $this->assertTrue($firstAccount->fresh()->is_active);

        // 2. Tambah rekening EWALLET kedua dan tandai sebagai aktif
        $payload = [
            'destination_type' => 'EWALLET',
            'destination_provider' => 'GoPay',
            'destination_number' => '08123456789',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->seller)
            ->post(route('seller.payout-accounts.store'), $payload);

        $response->assertRedirect(route('seller.payout-accounts.index'));

        // Rekening kedua harus aktif
        $this->assertDatabaseHas('seller_payout_accounts', [
            'seller_id' => $this->seller->user_id,
            'destination_type' => 'EWALLET',
            'destination_number' => '08123456789',
            'is_active' => true,
        ]);

        // Rekening pertama harus otomatis dinonaktifkan
        $this->assertFalse($firstAccount->fresh()->is_active);

        // Pastikan hanya 1 rekening aktif milik seller
        $activeCount = SellerPayoutAccount::where('seller_id', $this->seller->user_id)
            ->where('is_active', true)
            ->count();
        $this->assertSame(1, $activeCount);
    }

    /**
     * TC-02-03: Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar.
     */
    public function test_tc_02_03_qris_payout_requires_image_upload_and_stores_url(): void
    {
        Storage::fake('public');

        // Subtest A: Submit QRIS tanpa berkas gambar -> Gagal validasi
        $invalidPayload = [
            'destination_type' => 'QRIS',
            'destination_holder' => 'Toko Digital Ali',
        ];

        $responseInvalid = $this->actingAs($this->seller)
            ->post(route('seller.payout-accounts.store'), $invalidPayload);

        $responseInvalid->assertSessionHasErrors(['qris_image']);

        // Subtest B: Submit QRIS dengan berkas gambar valid -> Sukses
        $file = UploadedFile::fake()->image('qris.png', 400, 400);

        $validPayload = [
            'destination_type' => 'QRIS',
            'destination_holder' => 'Toko Digital Ali',
            'qris_image' => $file,
            'is_active' => true,
        ];

        $responseValid = $this->actingAs($this->seller)
            ->post(route('seller.payout-accounts.store'), $validPayload);

        $responseValid->assertRedirect(route('seller.payout-accounts.index'));
        $responseValid->assertSessionHas('success');

        $account = SellerPayoutAccount::where('seller_id', $this->seller->user_id)
            ->where('destination_type', 'QRIS')
            ->first();

        $this->assertNotNull($account);
        $this->assertNotNull($account->destination_qris_image_url);
        $this->assertTrue($account->is_active);
    }

    /**
     * TC-02-04: Seller Menghapus Rekening Payout Non-Aktif dan Fallback Pengaktifan Rekening.
     */
    public function test_tc_02_04_seller_can_delete_inactive_payout_account(): void
    {
        // 1. Rekening aktif
        $activeAccount = SellerPayoutAccount::create([
            'seller_id' => $this->seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '11112222',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => true,
        ]);

        // 2. Rekening cadangan non-aktif
        $inactiveAccount = SellerPayoutAccount::create([
            'seller_id' => $this->seller->user_id,
            'destination_type' => PayoutDestinationType::EWALLET,
            'destination_provider' => 'DANA',
            'destination_number' => '08999888777',
            'destination_holder' => 'Muhammad Ali',
            'is_active' => false,
        ]);

        // Hapus rekening non-aktif
        $response = $this->actingAs($this->seller)
            ->delete(route('seller.payout-accounts.destroy', $inactiveAccount));

        $response->assertRedirect(route('seller.payout-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('seller_payout_accounts', [
            'seller_payout_account_id' => $inactiveAccount->seller_payout_account_id,
        ]);

        // Rekening aktif awal tetap ada dan aktif
        $this->assertTrue($activeAccount->fresh()->is_active);
    }
}
