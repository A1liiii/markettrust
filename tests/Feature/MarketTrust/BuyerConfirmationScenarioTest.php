<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\NotificationEventType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Complaint;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian skenario integrasi HTTP, Database & Scheduler untuk TS-12: Konfirmasi Penerimaan & Auto-Confirm.
 * Mencakup TC-10-01, TC-10-02, dan TC-10-03.
 */
class BuyerConfirmationScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Kategori Digital '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Produk Lisensi '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Standar '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createListing(User $seller): Listing
    {
        $variant = $this->createVariant();

        return Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Lisensi Software Desain Pro',
            'description' => 'Lisensi resmi original 1 tahun',
            'price' => 150000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 60,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    protected function createDeliveredTransaction(User $buyer, User $seller, array $transactionOverrides = [], array $agreementOverrides = []): Transaction
    {
        $listing = $this->createListing($seller);

        $agreement = Agreement::create(array_merge([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $listing->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => 150000.00,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => 5000.00,
            'buyer_total' => 155000.00,
            'seller_net' => 150000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Kesepakatan pengujian konfirmasi buyer',
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED,
            'buyer_accepted_at' => now()->subHours(2),
            'seller_accepted_at' => now()->subHours(2),
            'finalized_at' => now()->subHours(2),
        ], $agreementOverrides));

        $deliveredAt = now()->subMinutes(30);

        return Transaction::create(array_merge([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::DELIVERED,
            'payment_due_at' => now()->subHours(2),
            'delivery_due_at' => now()->subHour(),
            'confirmation_due_at' => $deliveredAt->copy()->addHour(),
            'delivered_at' => $deliveredAt,
            'delivery_note' => 'Kunci lisensi telah diserahkan dengan sukses.',
        ], $transactionOverrides));
    }

    /**
     * TC-10-01: Pembeli dapat melakukan konfirmasi penerimaan manual dan antrean payout terjadwal.
     */
    public function test_tc_10_01_buyer_can_manually_confirm_delivered_transaction(): void
    {
        $confirmedAt = now()->startOfSecond();
        $this->travelTo($confirmedAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller);

        $response = $this->actingAs($buyer)->post(route('transactions.confirm', $transaction));

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();

        // 1. Verifikasi status dan confirmed_at
        $this->assertEquals(TransactionStatus::CONFIRMED, $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->confirmed_at->equalTo($confirmedAt));

        // 2. Status history mencatat changed_by = buyer_id
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => $buyer->user_id,
        ]);

        // 3. Antrean payout tercatat di tabel settlements dengan status PLANNED
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $transaction->transaction_id,
            'settlement_type' => 'PAYOUT',
            'status' => 'PLANNED',
        ]);

        $settlement = Settlement::where('transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($settlement);
        $this->assertTrue($settlement->payout_due_at->equalTo($confirmedAt->copy()->addHour()));

        // 4. Notifikasi PAYOUT_READY terkirim ke Penjual dan Admin
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::PAYOUT_READY->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::PAYOUT_READY->value,
        ]);
    }

    /**
     * TC-10-01: Penjual atau pengguna luar ditolak saat mencoba mengonfirmasi transaksi (403 Forbidden).
     */
    public function test_tc_10_01_seller_or_stranger_cannot_confirm_delivery(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller);

        // Penjual mencoba konfirmasi
        $resSeller = $this->actingAs($seller)->post(route('transactions.confirm', $transaction));
        $resSeller->assertForbidden();

        // Stranger mencoba konfirmasi
        $resStranger = $this->actingAs($stranger)->post(route('transactions.confirm', $transaction));
        $resStranger->assertForbidden();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
    }

    /**
     * TC-10-02: Scheduler memberikan grace period 30 menit pada pembeli pasif setelah 1 jam awal habis.
     */
    public function test_tc_10_02_scheduler_grants_30_minute_grace_period_to_passive_buyer(): void
    {
        $deliveredAt = now()->startOfSecond();
        $this->travelTo($deliveredAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $initialDue = $deliveredAt->copy()->addHour();
        $transaction = $this->createDeliveredTransaction($buyer, $seller, [
            'delivered_at' => $deliveredAt,
            'confirmation_due_at' => $initialDue,
        ]);

        // Pada t + 30 menit (belum jatuh tempo) -> tidak ada perpanjangan
        $this->travelTo($deliveredAt->copy()->addMinutes(30));
        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();
        $this->assertEquals(0, TransactionDeadlineEvent::where('event_type', 'BUYER_GRACE_PERIOD')->count());

        // Pada t + 61 menit (tenggat 1 jam terlewati) -> berikan masa tenggang 30 menit
        $this->travelTo($deliveredAt->copy()->addMinutes(61));
        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();

        $transaction->refresh();

        // 1. Status tetap DELIVERED (belum auto-confirm)
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);

        // 2. Tenggat diperpanjang 30 menit dari tenggat awal
        $expectedGraceDeadline = $initialDue->copy()->addMinutes(30);
        $this->assertTrue($transaction->confirmation_due_at->equalTo($expectedGraceDeadline));

        // 3. Event BUYER_GRACE_PERIOD dicatat
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'BUYER_GRACE_PERIOD',
            'extension_minutes' => 30,
        ]);

        // 4. Notifikasi pengingat konfirmasi terkirim ke Buyer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::BUYER_GRACE_WARNING->value,
        ]);
    }

    /**
     * TC-10-03: Scheduler melakukan auto-confirm sistem setelah grace period berakhir.
     */
    public function test_tc_10_03_scheduler_auto_confirms_after_grace_period_expires(): void
    {
        $deliveredAt = now()->startOfSecond();
        $this->travelTo($deliveredAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $initialDue = $deliveredAt->copy()->addHour();
        $transaction = $this->createDeliveredTransaction($buyer, $seller, [
            'delivered_at' => $deliveredAt,
            'confirmation_due_at' => $initialDue,
        ]);

        // Berikan masa tenggang pada t + 61 menit
        $this->travelTo($deliveredAt->copy()->addMinutes(61));
        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);

        // Majukan waktu ke t + 95 menit (masa tenggang 30 menit berakhir)
        $autoConfirmTime = $deliveredAt->copy()->addMinutes(95);
        $this->travelTo($autoConfirmTime);

        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();

        $transaction->refresh();

        // 1. Status beralih ke CONFIRMED
        $this->assertEquals(TransactionStatus::CONFIRMED, $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->confirmed_at->equalTo($autoConfirmTime));

        // 2. Status history mencatat changed_by = null (Sistem)
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => null,
        ]);

        // 3. Antrean payout di settlements terbentuk
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $transaction->transaction_id,
            'settlement_type' => 'PAYOUT',
            'status' => 'PLANNED',
        ]);

        // 4. Event AUTO_CONFIRMED dicatat
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'AUTO_CONFIRMED',
        ]);
    }

    /**
     * TC-10-03: Sengketa aktif (DISPUTED) menahan eksekusi auto-confirm oleh scheduler.
     */
    public function test_tc_10_03_active_complaint_halts_auto_confirm(): void
    {
        $deliveredAt = now()->startOfSecond();
        $this->travelTo($deliveredAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $initialDue = $deliveredAt->copy()->addHour();
        $transaction = $this->createDeliveredTransaction($buyer, $seller, [
            'delivered_at' => $deliveredAt,
            'confirmation_due_at' => $initialDue,
        ]);

        // Berikan masa tenggang pada t + 61 menit
        $this->travelTo($deliveredAt->copy()->addMinutes(61));
        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();

        // Pada t + 70 menit, Buyer mengajukan komplain sehingga status menjadi DISPUTED
        $this->travelTo($deliveredAt->copy()->addMinutes(70));
        Complaint::create([
            'transaction_id' => $transaction->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Lisensi tidak bekerja sama sekali.',
            'status' => 'OPEN',
        ]);
        $transaction->update(['status' => TransactionStatus::DISPUTED]);

        // Majukan waktu ke t + 95 menit (melewati masa tenggang)
        $this->travelTo($deliveredAt->copy()->addMinutes(95));
        $this->artisan('transactions:check-confirmation-deadlines')->assertSuccessful();

        $transaction->refresh();

        // Auto-confirm terhenti! Status tidak berubah menjadi CONFIRMED
        $this->assertEquals(TransactionStatus::DISPUTED, $transaction->status);
        $this->assertNull($transaction->confirmed_at);
        $this->assertEquals(0, Settlement::where('transaction_id', $transaction->transaction_id)->count());
    }
}
