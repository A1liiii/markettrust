<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Complaint;
use App\Models\ComplaintResponse;
use App\Models\EvidenceFile;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian skenario integrasi HTTP & Database untuk TS-13: Komplain & Sengketa (Dispute).
 * Mencakup TC-11-01 sampai TC-11-06.
 */
class DisputeScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Kategori Uji '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Produk Uji '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Uji '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createListing(User $seller): Listing
    {
        $variant = $this->createVariant();

        return Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Lisensi Digital Pro',
            'description' => 'Lisensi resmi 1 tahun garansi',
            'price' => 200000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 60,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    protected function createDeliveredTransaction(
        User $buyer,
        User $seller,
        array $transactionOverrides = [],
        array $agreementOverrides = []
    ): Transaction {
        $listing = $this->createListing($seller);

        $agreement = Agreement::create(array_merge([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $listing->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => 200000.00,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => 5000.00,
            'buyer_total' => 205000.00,
            'seller_net' => 200000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Kesepakatan pengujian komplain',
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
            'status' => TransactionStatus::DELIVERED,
            'payment_due_at' => now()->subHours(2),
            'delivery_due_at' => now()->subHour(),
            'delivered_at' => now(),
            'confirmation_due_at' => now()->addHour(),
            'delivery_note' => 'Item telah dikirim ke catatan pesanan.',
        ], $transactionOverrides));
    }

    /**
     * TC-11-01: Pembeli mengajukan komplain sah saat status DELIVERED.
     */
    public function test_tc_11_01_buyer_can_file_valid_complaint_on_delivered_transaction(): void
    {
        Storage::fake('public');
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);

        $tx = $this->createDeliveredTransaction($buyer, $seller);
        $evidenceFile = UploadedFile::fake()->create('bukti_eror.png', 500, 'image/png');

        $response = $this->actingAs($buyer)->post(route('transactions.complaints.store', $tx), [
            'reason' => 'Kunci lisensi yang dikirimkan tidak valid dan sudah kedaluwarsa.',
            'evidence_file' => $evidenceFile,
        ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('success');

        $tx->refresh();

        // 1. Transaksi beralih ke DISPUTED
        $this->assertEquals(TransactionStatus::DISPUTED, $tx->status);

        // 2. Komplain tercatat dengan status OPEN
        $this->assertDatabaseHas('complaints', [
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Kunci lisensi yang dikirimkan tidak valid dan sudah kedaluwarsa.',
            'status' => 'OPEN',
        ]);

        // 3. Berkas bukti tersimpan dan terhubung ke sengketa
        $complaint = Complaint::where('transaction_id', $tx->transaction_id)->first();
        $this->assertNotNull($complaint);
        $this->assertDatabaseHas('evidence_files', [
            'complaint_id' => $complaint->complaint_id,
            'uploaded_by' => $buyer->user_id,
            'file_name' => 'bukti_eror.png',
        ]);
    }

    /**
     * TC-11-02: Pencegahan duplikasi komplain (maksimal 1 komplain per transaksi).
     */
    public function test_tc_11_02_duplicate_complaint_submission_is_prevented(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller);

        // Komplain pertama
        $this->actingAs($buyer)->post(route('transactions.complaints.store', $tx), [
            'reason' => 'Komplain pertama yang sah dari pembeli.',
            'evidence_file' => UploadedFile::fake()->create('bukti1.png', 100, 'image/png'),
        ]);

        $this->assertEquals(1, Complaint::where('transaction_id', $tx->transaction_id)->count());

        // Percobaan komplain kedua pada transaksi yang sama
        $resSecond = $this->actingAs($buyer)->post(route('transactions.complaints.store', $tx), [
            'reason' => 'Mencoba mengajukan komplain ganda kedua kali.',
            'evidence_file' => UploadedFile::fake()->create('bukti2.png', 100, 'image/png'),
        ]);

        // Tetap hanya ada tepat 1 komplain
        $this->assertEquals(1, Complaint::where('transaction_id', $tx->transaction_id)->count());
    }

    /**
     * TC-11-03: Seller mengirimkan tanggapan dan bukti sanggahan atas komplain.
     */
    public function test_tc_11_03_seller_can_respond_with_rebuttal_and_evidence(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Kunci aktivasi bermasalah.',
            'status' => 'OPEN',
        ]);

        // Orang luar ditolak saat mencoba menanggapi
        $resStranger = $this->actingAs($stranger)->post(route('transactions.complaints.respond', [$tx, $complaint]), [
            'response_text' => 'Tanggapan oleh pihak tidak berwenang.',
        ]);
        $resStranger->assertForbidden();

        // Seller yang sah mengirimkan sanggahan resmi
        $response = $this->actingAs($seller)->post(route('transactions.complaints.respond', [$tx, $complaint]), [
            'response_text' => 'Lisensi sudah diuji aktif pada server lisensi resmi penerbit.',
            'evidence_file' => UploadedFile::fake()->create('bukti_aktivasi_seller.pdf', 300, 'application/pdf'),
        ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('success');

        // Verifikasi tanggapan tersimpan
        $this->assertDatabaseHas('complaint_responses', [
            'complaint_id' => $complaint->complaint_id,
            'responder_id' => $seller->user_id,
            'response_text' => 'Lisensi sudah diuji aktif pada server lisensi resmi penerbit.',
        ]);
    }

    /**
     * TC-11-04: Admin meminta bukti tambahan dan sengketa beralih ke status REVIEWING.
     */
    public function test_tc_11_04_admin_can_request_additional_evidence_setting_status_reviewing(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Mohon investigasi admin.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.disputes.request-evidence', $complaint), [
            'reason' => 'Mohon pembeli melampirkan rekaman layar saat memasukkan serial key lisensi.',
            'extension_hours' => 24,
        ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $complaint->refresh();

        $this->assertEquals('REVIEWING', $complaint->status);
    }

    /**
     * TC-11-05: Keputusan final admin memenangkan Seller (Putusan PAYOUT).
     */
    public function test_tc_11_05_admin_decides_in_favor_of_seller_payout(): void
    {
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Klaim sepihak dari pembeli.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.disputes.decide', $complaint), [
            'decision' => 'PAYOUT',
            'decision_reason' => 'Bukti dari penjual membuktikan lisensi valid dan telah digunakan oleh pembeli.',
        ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $complaint->refresh();
        $tx->refresh();

        // 1. Sengketa RESOLVED dengan putusan PAYOUT
        $this->assertEquals('RESOLVED', $complaint->status);
        $this->assertEquals('PAYOUT', $complaint->decision);
        $this->assertEquals($admin->user_id, $complaint->decided_by);

        // 2. Transaksi beralih ke CONFIRMED
        $this->assertEquals(TransactionStatus::CONFIRMED, $tx->status);

        // 3. Antrean settlement PAYOUT berstatus PLANNED dibuat
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $tx->transaction_id,
            'settlement_type' => 'PAYOUT',
            'status' => 'PLANNED',
        ]);
    }

    /**
     * TC-11-06: Keputusan final admin memenangkan Buyer (Putusan REFUND).
     */
    public function test_tc_11_06_admin_decides_in_favor_of_buyer_refund(): void
    {
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Barang rusak permanen dan penjual tidak memberi solusi.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.disputes.decide', $complaint), [
            'decision' => 'REFUND',
            'decision_reason' => 'Penjual gagal membuktikan keaslian lisensi dan terbukti memberikan kredensial palsu.',
        ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $complaint->refresh();
        $tx->refresh();

        // 1. Sengketa RESOLVED dengan putusan REFUND
        $this->assertEquals('RESOLVED', $complaint->status);
        $this->assertEquals('REFUND', $complaint->decision);

        // 2. Transaksi beralih ke REFUNDED
        $this->assertEquals(TransactionStatus::REFUNDED, $tx->status);

        // 3. Antrean settlement REFUND berstatus PLANNED dibuat
        $this->assertDatabaseHas('settlements', [
            'transaction_id' => $tx->transaction_id,
            'settlement_type' => 'REFUND',
            'status' => 'PLANNED',
        ]);
    }
}
