<?php

namespace Tests\Feature;

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
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerDeliveryWorkflowTest extends TestCase
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
            'agreement_note' => 'Kesepakatan pengujian pengiriman',
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

    public function test_seller_can_deliver_order_with_note_link_and_evidence(): void
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

        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertNotNull($transaction->delivered_at);
        $this->assertTrue($transaction->delivered_at->equalTo($deliveredAt));
        $this->assertNotNull($transaction->confirmation_due_at);
        $this->assertTrue($transaction->confirmation_due_at->equalTo($deliveredAt->copy()->addHour()));
        $this->assertSame(3600, (int) $transaction->delivered_at->diffInSeconds($transaction->confirmation_due_at, false));
        $this->assertStringContainsString('PRO-2026-XYZ999', $transaction->delivery_note);
        $this->assertEquals('https://example.com/download/asset-pkg', $transaction->result_link);

        // Verifikasi tabel evidence_files
        $this->assertDatabaseHas('evidence_files', [
            'delivery_transaction_id' => $transaction->transaction_id,
            'uploaded_by' => $seller->user_id,
            'file_name' => 'bukti-penyerahan-lisensi.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $evidence = EvidenceFile::where('delivery_transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($evidence);
        Storage::disk('public')->assertExists($evidence->file_url);

        // Verifikasi riwayat status
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'PAID',
            'to_status' => 'DELIVERED',
            'changed_by' => $seller->user_id,
        ]);

        // Verifikasi notifikasi ke Buyer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::ITEM_DELIVERED->value,
        ]);
    }

    public function test_non_seller_cannot_submit_delivery(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $intruder = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        // Percobaan oleh Buyer
        $resBuyer = $this->actingAs($buyer)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Mencoba menyerahkan pesanan oleh pembeli',
        ]);
        $resBuyer->assertStatus(403);

        // Percobaan oleh Pengguna Acak
        $resIntruder = $this->actingAs($intruder)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Mencoba menyerahkan pesanan orang lain',
        ]);
        $resIntruder->assertStatus(403);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
        $this->assertNull($transaction->delivered_at);
    }

    public function test_cannot_deliver_when_transaction_is_not_paid(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::WAITING_PAYMENT,
        ]);

        $response = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Menyerahkan sebelum dibayar',
        ]);

        $response->assertSessionHas('error');
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::WAITING_PAYMENT, $transaction->status);
        $this->assertNull($transaction->delivered_at);
    }

    public function test_evidence_file_mime_and_size_validation(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        // 1. Ekstensi tidak diizinkan (.zip)
        $invalidZip = UploadedFile::fake()->create('malicious.zip', 500, 'application/zip');
        $resZip = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Catatan penyerahan valid',
            'evidence_file' => $invalidZip,
        ]);
        $resZip->assertSessionHasErrors('evidence_file');

        // 2. Ukuran melebihi 5 MB (6000 KB)
        $oversizedPdf = UploadedFile::fake()->create('dokumen-raksasa.pdf', 6000, 'application/pdf');
        $resSize = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Catatan penyerahan valid',
            'evidence_file' => $oversizedPdf,
        ]);
        $resSize->assertSessionHasErrors('evidence_file');

        // 3. Catatan wajib minimal 3 karakter
        $shortNote = $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'ab',
        ]);
        $shortNote->assertSessionHasErrors('delivery_note');
    }

    public function test_delivery_submission_is_idempotent(): void
    {
        $deliveredAt = now()->startOfSecond();
        $this->travelTo($deliveredAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        // Submit pertama
        $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Hasil penyerahan pertama',
        ])->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertTrue($transaction->delivered_at->equalTo($deliveredAt));
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());

        // Majukan waktu 10 menit dan submit kedua kali
        $this->travel(10)->minutes();

        $this->actingAs($seller)->post(route('transactions.deliver', $transaction), [
            'delivery_note' => 'Hasil penyerahan duplikat',
        ]);

        $transaction->refresh();
        $this->assertTrue($transaction->delivered_at->equalTo($deliveredAt));
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());
        $this->assertEquals(1, Notification::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_authorized_users_can_download_and_preview_evidence(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $intruder = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller);

        $file = UploadedFile::fake()->create('bukti-resmi.pdf', 500, 'application/pdf');
        $storedPath = $file->store('evidence/delivery', 'public');

        $evidence = EvidenceFile::create([
            'uploaded_by' => $seller->user_id,
            'delivery_transaction_id' => $transaction->transaction_id,
            'file_name' => 'bukti-resmi.pdf',
            'file_url' => $storedPath,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        // Buyer berhak unduh
        $this->actingAs($buyer)->get(route('transactions.delivery-evidence', [$transaction, $evidence]))
            ->assertStatus(200);

        // Seller berhak unduh
        $this->actingAs($seller)->get(route('transactions.delivery-evidence', [$transaction, $evidence]))
            ->assertStatus(200);

        // Admin berhak unduh
        $this->actingAs($admin)->get(route('transactions.delivery-evidence', [$transaction, $evidence]))
            ->assertStatus(200);

        // Preview inline
        $this->actingAs($buyer)->get(route('transactions.delivery-evidence', [$transaction, $evidence, 'inline' => 1]))
            ->assertStatus(200);

        // Pengguna luar ditolak 403
        $this->actingAs($intruder)->get(route('transactions.delivery-evidence', [$transaction, $evidence]))
            ->assertStatus(403);
    }

    public function test_scheduler_warns_seller_and_admin_when_delivery_deadline_passed(): void
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

        // 1. Pada t + 30 menit (belum jatuh tempo)
        $this->travel(30)->minutes();
        $this->artisan('transactions:check-delivery-deadlines')
            ->assertSuccessful();

        $this->assertEquals(0, Notification::where('event_type', NotificationEventType::SELLER_DELIVERY_WARNING->value)->count());

        // 2. Pada t + 61 menit (lewat jatuh tempo)
        $this->travel(31)->minutes(); // sekarang t + 61 menit

        $this->artisan('transactions:check-delivery-deadlines')
            ->assertSuccessful();

        // Notifikasi ke Seller
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::SELLER_DELIVERY_WARNING->value,
        ]);

        // Notifikasi ke Admin
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::SELLER_DELIVERY_WARNING->value,
        ]);

        // Event di transaction_deadline_events tercatat
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_WARNING',
            'is_flagged_for_admin' => false,
        ]);

        $initialSellerNotifCount = Notification::where('user_id', $seller->user_id)->count();
        $initialAdminNotifCount = Notification::where('user_id', $admin->user_id)->count();

        // 3. Jalankan scheduler berulang kali: notifikasi tidak boleh berganda
        $this->artisan('transactions:check-delivery-deadlines')
            ->assertSuccessful();

        $this->assertEquals($initialSellerNotifCount, Notification::where('user_id', $seller->user_id)->count());
        $this->assertEquals($initialAdminNotifCount, Notification::where('user_id', $admin->user_id)->count());
    }

    public function test_deadline_extension_and_expired_extension_flagged_for_admin(): void
    {
        $paidAt = now()->startOfSecond();
        $this->travelTo($paidAt);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $initialDue = $paidAt->copy()->addHour();
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivery_due_at' => $initialDue,
            'delivered_at' => null,
        ]);

        // Seller/Admin memberikan perpanjangan 30 menit
        $this->actingAs($seller)->post(route('transactions.extend-delivery', $transaction), [
            'extension_minutes' => 30,
            'reason' => 'Perlu waktu tambahan verifikasi server lisensi pihak ketiga',
        ])->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $expectedExtendedDue = $initialDue->copy()->addMinutes(30);
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedExtendedDue));

        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_EXTENSION',
            'extension_minutes' => 30,
            'is_flagged_for_admin' => false,
        ]);

        // Majukan waktu ke t + 70 menit (lewat batas awal 60 menit, tetapi masih dalam tambahan waktu 90 menit)
        $this->travelTo($paidAt->copy()->addMinutes(70));
        $this->artisan('transactions:check-delivery-deadlines')->assertSuccessful();

        // Belum ditandai overdue untuk tinjauan admin
        $this->assertDatabaseMissing('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_OVERDUE',
        ]);

        // Majukan waktu ke t + 95 menit (tenggat perpanjangan telah habis tanpa penyerahan)
        $this->travelTo($paidAt->copy()->addMinutes(95));
        $this->artisan('transactions:check-delivery-deadlines')->assertSuccessful();

        // Ditandai untuk tinjauan admin
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $transaction->transaction_id,
            'event_type' => 'DELIVERY_OVERDUE',
            'is_flagged_for_admin' => true,
        ]);
        $this->assertTrue($transaction->isFlaggedForAdmin());
    }
}
