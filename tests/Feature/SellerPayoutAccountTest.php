<?php

namespace Tests\Feature;

use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\DTOs\PayoutDestinationDTO;
use App\Enums\PayoutDestinationType;
use App\Models\SellerPayoutAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerPayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu tanpa autentikasi tidak dapat mengakses atau memodifikasi rekening payout.
     */
    public function test_guest_cannot_access_or_modify_payout_accounts(): void
    {
        $seller = User::factory()->create();
        $account = SellerPayoutAccount::factory()->create(['seller_id' => $seller->user_id]);

        $this->get(route('seller.payout-accounts.index'))->assertRedirect('/login');
        $this->get(route('seller.payout-accounts.create'))->assertRedirect('/login');
        $this->post(route('seller.payout-accounts.store'), [])->assertRedirect('/login');
        $this->get(route('seller.payout-accounts.edit', $account))->assertRedirect('/login');
        $this->put(route('seller.payout-accounts.update', $account), [])->assertRedirect('/login');
        $this->patch(route('seller.payout-accounts.activate', $account))->assertRedirect('/login');
        $this->delete(route('seller.payout-accounts.destroy', $account))->assertRedirect('/login');
    }

    /**
     * Seller dapat melihat halaman daftar rekening payout miliknya.
     */
    public function test_seller_can_view_payout_accounts_index(): void
    {
        $seller = User::factory()->create();
        $account = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Seller Utama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($seller)->get(route('seller.payout-accounts.index'));

        $response->assertOk();
        $response->assertSee('Pengaturan Rekening Payout');
        $response->assertSee('BCA');
        $response->assertSee($account->masked_destination_number);
        $response->assertSee('Seller Utama');
    }

    /**
     * Seller dapat menambahkan rekening BANK dengan data valid.
     */
    public function test_seller_can_create_bank_payout_account(): void
    {
        $seller = User::factory()->create();

        $response = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '8880123456',
            'destination_holder' => 'Kaye Seller',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('seller.payout-accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_payout_accounts', [
            'seller_id' => $seller->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '8880123456',
            'destination_holder' => 'Kaye Seller',
            'is_active' => true,
        ]);
    }

    /**
     * Seller dapat menambahkan rekening EWALLET dengan data valid.
     */
    public function test_seller_can_create_ewallet_payout_account(): void
    {
        $seller = User::factory()->create();

        $response = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'EWALLET',
            'destination_provider' => 'GoPay',
            'destination_number' => '081234567890',
            'destination_holder' => 'Kaye GoPay',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('seller.payout-accounts.index'));

        $this->assertDatabaseHas('seller_payout_accounts', [
            'seller_id' => $seller->user_id,
            'destination_type' => 'EWALLET',
            'destination_provider' => 'GoPay',
            'destination_number' => '081234567890',
            'destination_holder' => 'Kaye GoPay',
            'is_active' => true,
        ]);
    }

    /**
     * Seller dapat menambahkan tujuan QRIS dengan mengunggah gambar barcode.
     */
    public function test_seller_can_create_qris_payout_account_with_image_upload(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create();

        $file = UploadedFile::fake()->image('my_qris.png', 400, 400);

        $response = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'QRIS',
            'destination_provider' => 'QRIS BCA',
            'destination_number' => 'NMID123456',
            'destination_holder' => 'Toko Kaye Digital',
            'qris_image' => $file,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('seller.payout-accounts.index'));

        $account = SellerPayoutAccount::where('seller_id', $seller->user_id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(PayoutDestinationType::QRIS, $account->destination_type);
        $this->assertEquals('Toko Kaye Digital', $account->destination_holder);
        $this->assertNotNull($account->destination_qris_image_url);

        $storedPath = str_replace('/storage/', '', $account->destination_qris_image_url);
        Storage::disk('public')->assertExists($storedPath);
    }

    /**
     * Validasi gagal jika atribut wajib per jenis tujuan tidak diisi.
     */
    public function test_validation_rejects_missing_required_fields_per_type(): void
    {
        $seller = User::factory()->create();

        // 1. BANK tanpa provider dan nomor
        $res1 = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'BANK',
            'destination_holder' => 'Kaye',
        ]);
        $res1->assertSessionHasErrors(['destination_provider', 'destination_number']);

        // 2. EWALLET tanpa nomor ponsel
        $res2 = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'EWALLET',
            'destination_provider' => 'DANA',
            'destination_holder' => 'Kaye',
        ]);
        $res2->assertSessionHasErrors(['destination_number']);

        // 3. QRIS tanpa gambar barcode
        $res3 = $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'QRIS',
            'destination_holder' => 'Toko Kaye',
        ]);
        $res3->assertSessionHasErrors(['qris_image']);
    }

    /**
     * Hanya satu rekening yang aktif pada satu waktu saat menambah rekening baru.
     */
    public function test_creating_active_account_atomically_deactivates_previous_active_account(): void
    {
        $seller = User::factory()->create();

        // Rekening pertama
        $account1 = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'is_active' => true,
        ]);

        $this->assertTrue($account1->fresh()->is_active);

        // Rekening kedua dibuat dengan is_active = 1
        $this->actingAs($seller)->post(route('seller.payout-accounts.store'), [
            'destination_type' => 'EWALLET',
            'destination_provider' => 'OVO',
            'destination_number' => '08999999999',
            'destination_holder' => 'Kaye OVO',
            'is_active' => '1',
        ]);

        $account1->refresh();
        $account2 = SellerPayoutAccount::where('seller_id', $seller->user_id)
            ->where('destination_provider', 'OVO')
            ->first();

        $this->assertFalse($account1->is_active);
        $this->assertTrue($account2->is_active);

        // Pastikan total rekening aktif seller tepat satu
        $this->assertEquals(1, SellerPayoutAccount::where('seller_id', $seller->user_id)->where('is_active', true)->count());
    }

    /**
     * Aktivasi rekening secara atomik menonaktifkan rekening aktif lainnya.
     */
    public function test_activating_account_atomically_switches_active_flag(): void
    {
        $seller = User::factory()->create();

        $accA = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'is_active' => true,
        ]);

        $accB = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::EWALLET,
            'is_active' => false,
        ]);

        $response = $this->actingAs($seller)->patch(route('seller.payout-accounts.activate', $accB));
        $response->assertRedirect(route('seller.payout-accounts.index'));

        $this->assertFalse($accA->fresh()->is_active);
        $this->assertTrue($accB->fresh()->is_active);
        $this->assertEquals(1, SellerPayoutAccount::where('seller_id', $seller->user_id)->where('is_active', true)->count());
    }

    /**
     * Partial unique index di tingkat basis data menolak keberadaan dua akun aktif bersamaan.
     */
    public function test_database_enforces_single_active_account_constraint(): void
    {
        $seller = User::factory()->create();

        SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'is_active' => true,
        ]);

        // Coba insert langsung via DB query melompati model untuk membuktikan penegakan level DB
        $this->expectException(QueryException::class);

        DB::table('seller_payout_accounts')->insert([
            'seller_id' => $seller->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BNI',
            'destination_number' => '999888777',
            'destination_holder' => 'Kaye BNI',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Pengguna lain dilarang melihat, mengubah, mengaktifkan, atau menghapus rekening seller lain (Policy 403).
     */
    public function test_user_cannot_access_or_modify_other_sellers_payout_account(): void
    {
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();

        $accountA = SellerPayoutAccount::factory()->create([
            'seller_id' => $sellerA->user_id,
            'is_active' => true,
        ]);

        // Seller B mencoba mengakses rekening milik Seller A
        $this->actingAs($sellerB)->get(route('seller.payout-accounts.edit', $accountA))->assertForbidden();

        $this->actingAs($sellerB)->put(route('seller.payout-accounts.update', $accountA), [
            'destination_type' => 'BANK',
            'destination_provider' => 'Hacked Bank',
            'destination_number' => '11111111',
            'destination_holder' => 'Attacker',
        ])->assertForbidden();

        $this->actingAs($sellerB)->patch(route('seller.payout-accounts.activate', $accountA))->assertForbidden();

        $this->actingAs($sellerB)->delete(route('seller.payout-accounts.destroy', $accountA))->assertForbidden();
    }

    /**
     * Seller dapat memperbarui dan menghapus rekening miliknya sendiri.
     */
    public function test_seller_can_update_and_delete_their_own_payout_account(): void
    {
        $seller = User::factory()->create();
        $account = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '12345',
            'destination_holder' => 'Nama Lama',
            'is_active' => true,
        ]);

        // Update
        $this->actingAs($seller)->put(route('seller.payout-accounts.update', $account), [
            'destination_type' => 'BANK',
            'destination_provider' => 'Mandiri',
            'destination_number' => '67890',
            'destination_holder' => 'Nama Baru',
        ])->assertRedirect(route('seller.payout-accounts.index'));

        $this->assertDatabaseHas('seller_payout_accounts', [
            'seller_payout_account_id' => $account->seller_payout_account_id,
            'destination_provider' => 'Mandiri',
            'destination_holder' => 'Nama Baru',
        ]);

        // Delete
        $this->actingAs($seller)->delete(route('seller.payout-accounts.destroy', $account))
            ->assertRedirect(route('seller.payout-accounts.index'));

        $this->assertDatabaseMissing('seller_payout_accounts', [
            'seller_payout_account_id' => $account->seller_payout_account_id,
        ]);
    }

    /**
     * Menghapus rekening aktif otomatis mengaktifkan rekening lain yang tersisa (jika ada).
     */
    public function test_deleting_active_account_activates_remaining_account(): void
    {
        $seller = User::factory()->create();

        $activeAccount = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'is_active' => true,
        ]);

        $inactiveAccount = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'is_active' => false,
        ]);

        $this->actingAs($seller)->delete(route('seller.payout-accounts.destroy', $activeAccount));

        $this->assertTrue($inactiveAccount->fresh()->is_active);
    }

    /**
     * PayoutDestinationService membaca rekening aktif seller dan menghasilkan snapshot untuk settlement MT-A07.
     */
    public function test_payout_destination_service_and_snapshot_contract(): void
    {
        $seller = User::factory()->create();
        $account = SellerPayoutAccount::factory()->create([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '8880123456',
            'destination_holder' => 'Kaye Settlement Test',
            'is_active' => true,
        ]);

        $service = app(PayoutDestinationServiceInterface::class);
        $dto = $service->getDestinationForUser($seller);

        $this->assertInstanceOf(PayoutDestinationDTO::class, $dto);
        $this->assertEquals(PayoutDestinationType::BANK, $dto->destinationType);
        $this->assertEquals('BCA', $dto->destinationProvider);
        $this->assertEquals('8880123456', $dto->destinationNumber);
        $this->assertEquals('Kaye Settlement Test', $dto->destinationHolder);
        $this->assertTrue($service->validateDestination($dto));

        // Snapshot kontrak settlement
        $snapshot = $account->toSettlementSnapshot();
        $this->assertArrayHasKey('destination_type', $snapshot);
        $this->assertArrayHasKey('destination_provider', $snapshot);
        $this->assertArrayHasKey('destination_number', $snapshot);
        $this->assertArrayHasKey('destination_holder', $snapshot);
        $this->assertArrayHasKey('destination_qris_image_url', $snapshot);
        $this->assertEquals('BANK', $snapshot['destination_type']);
        $this->assertEquals('BCA', $snapshot['destination_provider']);
    }

    /**
     * Penyamaran data sensitif (masked_destination_number) menyamarkan nomor rekening/HP dengan aman.
     */
    public function test_sensitive_account_number_is_masked(): void
    {
        $account = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_number' => '1234567890',
            'destination_holder' => 'Kaye Masking',
        ]);

        $this->assertEquals('•••• •••• 7890', $account->masked_destination_number);

        $shortAccount = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_number' => '123',
            'destination_holder' => 'Short',
        ]);

        $this->assertEquals('•••• 123', $shortAccount->masked_destination_number);
    }

    /**
     * Rekening dengan data tidak lengkap tidak lolos verifikasi dan menahan payout.
     */
    public function test_incomplete_payout_account_fails_validation_and_holds_payout(): void
    {
        $account = new SellerPayoutAccount([
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => '',
            'destination_number' => '',
            'destination_holder' => '',
        ]);

        $this->assertFalse($account->isComplete());

        $service = app(PayoutDestinationServiceInterface::class);
        $invalidDto = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: '',
            destinationProvider: 'BCA',
            destinationNumber: ''
        );

        $this->assertFalse($service->validateDestination($invalidDto));
    }
}
