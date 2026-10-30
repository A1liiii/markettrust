<?php

namespace Tests\Feature;

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

class BuyerConfirmationWorkflowTest extends TestCase
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
            'product_name' => 'Produk Digital '.uniqid(),
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
            'delivery_note' => 'Kunci lisensi telah diserahkan.',
        ], $transactionOverrides));
    }

    public function test_buyer_can_manually_confirm_delivered_transaction(): void
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

        // 2. Verifikasi antrean payout di settlements
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $transaction->transaction_id,
            'settlement_type' => 'PAYOUT',
            'status' => 'PLANNED',
            'amount' => '150000.00',
            'gross_amount' => '150000.00',
        ]);

        $settlement = Settlement::where('transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($settlement);
        $this->assertNotNull($settlement->payout_due_at);
        $this->assertTrue($settlement->payout_due_at->equalTo($confirmedAt->copy()->addHour()));

        // 3. Verifikasi riwayat status dengan changed_by = buyer
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => $buyer->user_id,
        ]);

        // 4. Verifikasi notifikasi PAYOUT_READY ke Seller & Admin
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

    public function test_non_buyer_cannot_confirm_transaction(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $intruder = User::factory()->create(['account_status' => 'ACTIVE']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller);

        // Seller mencoba konfirmasi
        $resSeller = $this->actingAs($seller)->post(route('transactions.confirm', $transaction));
        $resSeller->assertStatus(403);

        // Pengguna lain mencoba konfirmasi
        $resIntruder = $this->actingAs($intruder)->post(route('transactions.confirm', $transaction));
        $resIntruder->assertStatus(403);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertNull($transaction->confirmed_at);
        $this->assertEquals(0, Settlement::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_cannot_confirm_when_status_is_not_delivered(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivered_at' => null,
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.confirm', $transaction));

        $response->assertSessionHas('error');
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
        $this->assertNull($transaction->confirmed_at);
    }

    public function test_cannot_confirm_when_active_complaint_exists(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller);

        // Buat komplain aktif status OPEN
        Complaint::create([
            'transaction_id' => $transaction->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Kunci lisensi tidak valid saat diaktivasi.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.confirm', $transaction));

        $response->assertSessionHas('error');

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertNull($transaction->confirmed_at);
        $this->assertEquals(0, Settlement::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_confirmation_is_idempotent_and_does_not_create_duplicate_settlements(): void
    {
        $confirmedAt = now()->startOfSecond();
        $this->travelTo($confirmedAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $transaction = $this->createDeliveredTransaction($buyer, $seller);

        // Konfirmasi pertama
        $this->actingAs($buyer)->post(route('transactions.confirm', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::CONFIRMED, $transaction->status);
        $this->assertEquals(1, Settlement::where('transaction_id', $transaction->transaction_id)->count());
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());

        // Majukan waktu 10 menit dan coba submit ulang
        $this->travel(10)->minutes();
        $this->actingAs($buyer)->post(route('transactions.confirm', $transaction));

        $transaction->refresh();
        $this->assertTrue($transaction->confirmed_at->equalTo($confirmedAt));
        $this->assertEquals(1, Settlement::where('transaction_id', $transaction->transaction_id)->count());
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_scheduler_triggers_buyer_grace_period_after_1_hour_deadline(): void
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

        // 1. Pada t + 30 menit (belum jatuh tempo 1 jam)
        $this->travel(30)->minutes();
        $this->artisan('transactions:check-confirmation-deadlines')
            ->assertSuccessful();

        $this->assertEquals(0, Notification::where('event_type', NotificationEventType::BUYER_GRACE_WARNING->value)->count());
        $this->assertEquals(0, TransactionDeadlineEvent::where('transaction_id', $transaction->transaction_id)->count());

        // 2. Pada t + 61 menit (lewat batas 1 jam)
        $this->travel(31)->minutes(); // sekarang t + 61 menit
        $this->artisan('transactions:check-confirmation-deadlines')
            ->assertSuccessful();

        $transaction->refresh();

        // Status masih DELIVERED, tetapi tenggat diperpanjang 30 menit
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertNull($transaction->confirmed_at);
        $expectedGraceDeadline = $initialDue->copy()->addMinutes(30);
        $this->assertTrue($transaction->confirmation_due_at->equalTo($expectedGraceDeadline));

        // Event BUYER_GRACE_PERIOD tercatat
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'BUYER_GRACE_PERIOD',
            'extension_minutes' => 30,
        ]);

        // Notifikasi BUYER_GRACE_WARNING ke Buyer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::BUYER_GRACE_WARNING->value,
        ]);

        $initialNotifCount = Notification::where('user_id', $buyer->user_id)->count();

        // 3. Menjalankan scheduler lagi saat masa tenggang masih berjalan: tidak menduplikasi
        $this->artisan('transactions:check-confirmation-deadlines')
            ->assertSuccessful();

        $this->assertEquals($initialNotifCount, Notification::where('user_id', $buyer->user_id)->count());
        $this->assertEquals(1, TransactionDeadlineEvent::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_scheduler_auto_confirms_after_grace_period_expires(): void
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

        // 1. Status berubah ke CONFIRMED
        $this->assertEquals(TransactionStatus::CONFIRMED, $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->confirmed_at->equalTo($autoConfirmTime));

        // 2. KNF-03: Riwayat status mencatat changed_by = null (Sistem)
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => null,
        ]);

        // 3. Antrean payout di settlements tercipta
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $transaction->transaction_id,
            'settlement_type' => 'PAYOUT',
            'status' => 'PLANNED',
        ]);

        $settlement = Settlement::where('transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($settlement);
        $this->assertTrue($settlement->payout_due_at->equalTo($autoConfirmTime->copy()->addHour()));

        // 4. Event AUTO_CONFIRMED di transaction_deadline_events
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'AUTO_CONFIRMED',
        ]);

        // 5. Notifikasi ke Seller, Buyer, dan Admin
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::PAYOUT_READY->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::PAYOUT_READY->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::PAYOUT_READY->value,
        ]);
    }

    public function test_scheduler_halts_auto_confirm_if_complaint_is_active(): void
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

        // Pada t + 70 menit, Buyer mengajukan komplain
        $this->travelTo($deliveredAt->copy()->addMinutes(70));
        Complaint::create([
            'transaction_id' => $transaction->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Akun tidak bisa digunakan sama sekali.',
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
