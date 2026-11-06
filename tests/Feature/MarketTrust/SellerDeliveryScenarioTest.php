<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\NotificationEventType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\EvidenceFile;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian skenario integrasi HTTP, Database & Scheduler untuk TS-11: Penyerahan Produk Digital.
 * Mencakup TC-09-01, TC-09-02, TC-09-03, dan TC-09-04.
 */
class SellerDeliveryScenarioTest extends TestCase
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
            'title' => 'Lisensi Software Pro Digital',
            'description' => 'Lisensi resmi original 1 tahun garansi',
            'price' => 150000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 60,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    protected function createTransaction(User $buyer, User $seller, array $transactionOverrides = [], array $agreementOverrides = []): Transaction
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
            'agreement_note' => 'Kesepakatan pengujian penyerahan seller',
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED,
            'buyer_accepted_at' => now(),
            'seller_accepted_at' => now(),
            'finalized_at' => now(),
        ], $agreementOverrides));

        return Transaction::create(array_merge([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::PAID,
            'payment_due_at' => now()->subHour(),
            'delivery_due_at' => now()->addHour(),
        ], $transactionOverrides));
    }

    /**
     * TC-09-01: Penyerahan hasil oleh seller dengan bukti sah mengalihkan status ke DELIVERED.
     */
    public function test_tc_09_01_seller_can_deliver_order_with_valid_evidence_and_note(): void
    {
        Storage::fake('public');
        $deliveredAt = now()->startOfSecond();
        $this->travelTo($deliveredAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        $fakePdf = UploadedFile::fake()->create('bukti-penyerahan-lisensi.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => "Kode lisensi: PRO-2026-XYZ999\nPanduan aktivasi: https://example.com/guide",
            'result_link' => 'https://example.com/download/asset-pkg',
            'evidence_file' => $fakePdf,
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();

        // 1. Verifikasi status dan timestamp
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertNotNull($transaction->delivered_at);
        $this->assertTrue($transaction->delivered_at->equalTo($deliveredAt));

        // 2. Batas waktu konfirmasi 1 jam setelah penyerahan
        $this->assertNotNull($transaction->confirmation_due_at);
        $this->assertTrue($transaction->confirmation_due_at->equalTo($deliveredAt->copy()->addHour()));

        // 3. Catatan dan berkas tersimpan
        $this->assertStringContainsString('PRO-2026-XYZ999', $transaction->delivery_note);
        $this->assertEquals('https://example.com/download/asset-pkg', $transaction->result_link);

        $this->assertDatabaseHas('evidence_files', [
            'delivery_transaction_id' => $transaction->transaction_id,
            'uploaded_by' => $seller->user_id,
            'file_name' => 'bukti-penyerahan-lisensi.pdf',
        ]);

        // 4. Riwayat perubahan status mencatat seller
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'PAID',
            'to_status' => 'DELIVERED',
            'changed_by' => $seller->user_id,
        ]);

        // 5. Notifikasi terkirim ke Buyer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::ITEM_DELIVERED->value,
        ]);
    }

    /**
     * TC-09-02: Seller menyerahkan pesanan tanpa bukti/catatan ditolak dan status tetap PAID.
     */
    public function test_tc_09_02_delivery_without_note_or_file_rejected_and_status_remains_paid(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        // Kirim tanpa delivery_note, tanpa evidence_file, dan tanpa result_link
        $response = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => '',
            'result_link' => '',
        ]);

        $response->assertSessionHasErrors(['delivery_note']);
        $transaction->refresh();

        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
        $this->assertNull($transaction->delivered_at);
        $this->assertNull($transaction->confirmation_due_at);
    }

    /**
     * TC-09-02: Pengguna selain seller ditolak saat mencoba menyerahkan hasil (403 Forbidden).
     */
    public function test_tc_09_02_unauthorized_user_cannot_deliver_order(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        $response = $this->actingAs($stranger)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Penyerahan ilegal',
        ]);

        $response->assertForbidden();
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
    }

    /**
     * TC-09-03: Permintaan perpanjangan waktu penyerahan tercatat di event tenggat (Catatan: Kebijakan kuota TBD).
     */
    public function test_tc_09_03_seller_extension_request_recorded_with_specification_gap_noted(): void
    {
        $paidAt = now()->startOfSecond();
        $this->travelTo($paidAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $initialDue = $paidAt->copy()->addHour();
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivery_due_at' => $initialDue,
            'delivered_at' => null,
        ]);

        // Seller mengajukan perpanjangan 30 menit
        $response = $this->actingAs($seller)->post(route('transactions.extend-delivery', $transaction), [
            'extension_minutes' => 30,
            'reason' => 'Menunggu verifikasi lisensi dari server penerbit.',
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $transaction->refresh();

        $expectedDue = $initialDue->copy()->addMinutes(30);
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedDue));

        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_EXTENSION',
            'extension_minutes' => 30,
            'is_flagged_for_admin' => false,
        ]);

        // Catatan Evaluasi Gap Spesifikasi:
        // Kebijakan mengenai batas maksimum frekuensi permintaan perpanjangan oleh Seller belum difinalkan di dokumen.
        $this->assertTrue(true, 'Catatan spesifikasi gap: Kebijakan limit frekuensi perpanjangan masih TBD pada dokumen PMPL.');
    }

    /**
     * TC-09-04: Scheduler peringatan keterlambatan penyerahan mengirimkan notifikasi saat tenggat terlewati.
     */
    public function test_tc_09_04_scheduler_warns_overdue_delivery_and_is_idempotent(): void
    {
        $paidAt = now()->startOfSecond();
        $this->travelTo($paidAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $deliveryDueAt = $paidAt->copy()->addHour();
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivery_due_at' => $deliveryDueAt,
            'delivered_at' => null,
        ]);

        // 1. Pada t + 30 menit (belum jatuh tempo) -> tidak ada peringatan
        $this->travel(30)->minutes();
        $this->artisan('transactions:check-delivery-deadlines')->assertSuccessful();
        $this->assertEquals(0, Notification::where('event_type', NotificationEventType::SELLER_DELIVERY_WARNING->value)->count());

        // 2. Pada t + 61 menit (lewat jatuh tempo) -> peringatan dikirim
        $this->travel(31)->minutes();
        $this->artisan('transactions:check-delivery-deadlines')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::SELLER_DELIVERY_WARNING->value,
        ]);

        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_WARNING',
        ]);

        // 3. Verifikasi idempotensi (eksekusi ulang tidak menambah notifikasi ganda)
        $sellerNotifCount = Notification::where('user_id', $seller->user_id)->count();
        $this->artisan('transactions:check-delivery-deadlines')->assertSuccessful();
        $this->assertEquals($sellerNotifCount, Notification::where('user_id', $seller->user_id)->count());
    }
}
