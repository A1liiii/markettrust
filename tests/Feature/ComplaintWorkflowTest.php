<?php

namespace Tests\Feature;

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
use App\Models\TransactionDeadlineEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintWorkflowTest extends TestCase
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
            'description' => 'Lisensi resmi 1 tahun',
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
            'delivery_note' => 'Item telah dikirim ke email.',
        ], $transactionOverrides));
    }

    public function test_buyer_can_file_complaint_with_valid_reason_and_evidence(): void
    {
        Storage::fake('public');
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller);

        $evidenceFile = UploadedFile::fake()->create('bukti_eror.png', 500, 'image/png');

        $response = $this->actingAs($buyer)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Kunci lisensi yang dikirimkan tidak valid dan sudah kedaluwarsa.',
                'evidence_file' => $evidenceFile,
            ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'status' => 'OPEN',
            'reason' => 'Kunci lisensi yang dikirimkan tidak valid dan sudah kedaluwarsa.',
        ]);

        $complaint = Complaint::where('transaction_id', $tx->transaction_id)->first();
        $this->assertNotNull($complaint);

        // Bukti terhubung ke complaint_id
        $this->assertDatabaseHas('evidence_files', [
            'complaint_id' => $complaint->complaint_id,
            'uploaded_by' => $buyer->user_id,
            'file_name' => 'bukti_eror.png',
        ]);

        // Status transaksi berubah jadi DISPUTED dan confirmation_due_at di-reset
        $tx->refresh();
        $this->assertEquals(TransactionStatus::DISPUTED, $tx->status);
        $this->assertNull($tx->confirmation_due_at);

        // Riwayat status tercatat
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $tx->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'DISPUTED',
            'changed_by' => $buyer->user_id,
        ]);

        // Event tenggat seller 24 jam tercatat
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'SELLER_COMPLAINT_RESPONSE_DUE',
            'extension_minutes' => 1440,
        ]);
    }

    public function test_buyer_cannot_file_complaint_without_evidence_file(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller);

        $response = $this->actingAs($buyer)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Produk tidak dapat diakses dan tidak ada bukti.',
            ]);

        $response->assertSessionHasErrors(['evidence_file']);
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_non_buyer_cannot_file_complaint(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller);

        $evidenceFile = UploadedFile::fake()->create('bukti.png', 200, 'image/png');

        // Seller mencoba komplain pesanan miliknya sendiri
        $responseSeller = $this->actingAs($seller)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Alasan komplain penjual.',
                'evidence_file' => $evidenceFile,
            ]);
        $responseSeller->assertForbidden();

        // User lain mencoba komplain
        $responseStranger = $this->actingAs($stranger)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Alasan komplain pihak ketiga.',
                'evidence_file' => $evidenceFile,
            ]);
        $responseStranger->assertForbidden();

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_buyer_cannot_file_complaint_when_transaction_is_not_delivered(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivered_at' => null,
        ]);

        $evidenceFile = UploadedFile::fake()->create('bukti.png', 200, 'image/png');

        $response = $this->actingAs($buyer)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Mencoba komplain saat status masih PAID.',
                'evidence_file' => $evidenceFile,
            ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_buyer_cannot_file_duplicate_complaint_on_same_transaction(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller);

        Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Komplain pertama yang sudah ada.',
            'status' => 'OPEN',
        ]);

        $evidenceFile = UploadedFile::fake()->create('bukti2.png', 200, 'image/png');

        $response = $this->actingAs($buyer)
            ->post(route('transactions.complaints.store', $tx), [
                'reason' => 'Mencoba mengajukan komplain kedua kalinya.',
                'evidence_file' => $evidenceFile,
            ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('complaints', 1);
    }

    public function test_seller_can_respond_to_complaint_with_text_and_evidence_starting_admin_deadline(): void
    {
        Storage::fake('public');
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Lisensi ditolak oleh server aktivasi.',
            'status' => 'OPEN',
        ]);

        $responseEvidence = UploadedFile::fake()->create('bukti_aktivasi_valid.png', 300, 'image/png');

        $response = $this->actingAs($seller)
            ->post(route('transactions.complaints.respond', [$tx, $complaint]), [
                'response_text' => 'Lisensi telah kami verifikasi aktif dan kami sertakan bukti tangkapan layar aktivasi server.',
                'evidence_file' => $responseEvidence,
            ]);

        $response->assertRedirect(route('transactions.show', $tx));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('complaint_responses', [
            'complaint_id' => $complaint->complaint_id,
            'responder_id' => $seller->user_id,
            'response_text' => 'Lisensi telah kami verifikasi aktif dan kami sertakan bukti tangkapan layar aktivasi server.',
        ]);

        $cResponse = ComplaintResponse::where('complaint_id', $complaint->complaint_id)->first();
        $this->assertNotNull($cResponse);

        $this->assertDatabaseHas('evidence_files', [
            'response_id' => $cResponse->response_id,
            'uploaded_by' => $seller->user_id,
            'file_name' => 'bukti_aktivasi_valid.png',
        ]);

        // Tanggapan pertama memulai tenggat admin 24 jam (AC #3)
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
            'extension_minutes' => 1440,
        ]);
    }

    public function test_non_seller_cannot_respond_to_complaint(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE']);
        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Lisensi bermasalah.',
            'status' => 'OPEN',
        ]);

        // Buyer mencoba menanggapi form tanggapan seller
        $resBuyer = $this->actingAs($buyer)
            ->post(route('transactions.complaints.respond', [$tx, $complaint]), [
                'response_text' => 'Tanggapan oleh buyer.',
            ]);
        $resBuyer->assertForbidden();

        // Stranger mencoba menanggapi
        $resStranger = $this->actingAs($stranger)
            ->post(route('transactions.complaints.respond', [$tx, $complaint]), [
                'response_text' => 'Tanggapan oleh pihak ketiga.',
            ]);
        $resStranger->assertForbidden();

        $this->assertDatabaseCount('complaint_responses', 0);
    }

    public function test_scheduler_starts_admin_deadline_when_seller_times_out_without_auto_winning_buyer(): void
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
            'reason' => 'Item belum diserahkan dengan benar.',
            'status' => 'OPEN',
        ]);

        $sellerDeadline = $now->copy()->addHours(24);

        TransactionDeadlineEvent::create([
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'SELLER_COMPLAINT_RESPONSE_DUE',
            'previous_deadline' => null,
            'new_deadline' => $sellerDeadline,
            'extension_minutes' => 1440,
            'reason' => 'Tenggat 24 jam penjual.',
            'granted_by' => $buyer->user_id,
            'is_flagged_for_admin' => false,
            'created_at' => $now,
        ]);

        // Majukan waktu melewati 24 jam seller deadline (misal 25 jam kemudian)
        $this->travelTo($now->copy()->addHours(25));

        // Jalankan artisan scheduler
        Artisan::call('transactions:check-complaint-deadlines');

        // Verifikasi: Tenggat admin dibuat dari akhir batas tenggat seller (AC #4)
        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
            'previous_deadline' => $sellerDeadline,
            'new_deadline' => $sellerDeadline->copy()->addHours(24),
            'is_flagged_for_admin' => true,
        ]);

        // Verifikasi penting: Penjual diam TIDAK otomatis memenangkan pembeli!
        $complaint->refresh();
        $tx->refresh();
        $this->assertEquals('OPEN', $complaint->status);
        $this->assertEquals(TransactionStatus::DISPUTED, $tx->status);
        $this->assertNull($complaint->decision);
    }

    public function test_admin_can_request_additional_evidence_extending_deadline(): void
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
            'reason' => 'Akun tidak bisa login.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.disputes.request-evidence', $complaint), [
                'reason' => 'Mohon pembeli melampirkan rekaman video saat memasukkan kredensial.',
                'extension_hours' => 48,
            ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $response->assertSessionHas('success');

        $complaint->refresh();
        $this->assertEquals('REVIEWING', $complaint->status);

        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
            'extension_minutes' => 48 * 60,
        ]);
    }

    public function test_admin_can_resolve_complaint_as_payout_creating_single_planned_settlement(): void
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
            'reason' => 'Klaim sepihak pembeli.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.disputes.decide', $complaint), [
                'decision' => 'PAYOUT',
                'decision_reason' => 'Penjual telah membuktikan login dan produk sah aktif pada saat penyerahan.',
            ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $response->assertSessionHas('success');

        $complaint->refresh();
        $this->assertEquals('RESOLVED', $complaint->status);
        $this->assertEquals('PAYOUT', $complaint->decision);
        $this->assertEquals($admin->user_id, $complaint->decided_by);

        $tx->refresh();
        $this->assertEquals(TransactionStatus::CONFIRMED, $tx->status);

        // Menyiapkan tepat satu record Settlement sesuai kontrak MT-A07
        $this->assertDatabaseCount('settlements', 1);
        $settlement = Settlement::first();
        $this->assertNotNull($settlement);
        $this->assertEquals($complaint->complaint_id, $settlement->complaint_id);
        $this->assertEquals($tx->transaction_id, $settlement->transaction_id);
        $this->assertEquals('PAYOUT', $settlement->settlement_type);
        $this->assertEquals('PLANNED', $settlement->status); // Belum transfer selesai!
        $this->assertEquals(200000.00, (float) $settlement->amount);
    }

    public function test_admin_can_resolve_complaint_as_refund_creating_single_planned_settlement(): void
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
            'reason' => 'Lisensi palsu dan diblokir vendor.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.disputes.decide', $complaint), [
                'decision' => 'REFUND',
                'decision_reason' => 'Terbukti lisensi tidak dapat digunakan dan penjual tidak menyanggah dalam waktu yang diberikan.',
            ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $response->assertSessionHas('success');

        $complaint->refresh();
        $this->assertEquals('RESOLVED', $complaint->status);
        $this->assertEquals('REFUND', $complaint->decision);

        $tx->refresh();
        $this->assertEquals(TransactionStatus::REFUNDED, $tx->status);

        // Tepat satu record Settlement refund berstatus PLANNED
        $this->assertDatabaseCount('settlements', 1);
        $settlement = Settlement::first();
        $this->assertNotNull($settlement);
        $this->assertEquals($complaint->complaint_id, $settlement->complaint_id);
        $this->assertEquals($tx->transaction_id, $settlement->transaction_id);
        $this->assertEquals('REFUND', $settlement->settlement_type);
        $this->assertEquals('PLANNED', $settlement->status); // Belum transfer selesai!
        $this->assertEquals(205000.00, (float) $settlement->amount);
    }

    public function test_cannot_resolve_already_resolved_complaint(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::CONFIRMED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Sengketa terdahulu.',
            'status' => 'RESOLVED',
            'decision' => 'PAYOUT',
            'decision_reason' => 'Selesai terdahulu.',
            'decided_by' => $admin->user_id,
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.disputes.decide', $complaint), [
                'decision' => 'REFUND',
                'decision_reason' => 'Mencoba membalikkan putusan.',
            ]);

        $response->assertRedirect(route('admin.disputes.show', $complaint));
        $response->assertSessionHas('error');
    }

    public function test_unauthorized_user_cannot_access_or_decide_admin_disputes(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Sengketa.',
            'status' => 'OPEN',
        ]);

        // Buyer tidak boleh akses admin disputes index
        $resBuyer = $this->actingAs($buyer)->get(route('admin.disputes.index'));
        $resBuyer->assertForbidden();

        // Seller tidak boleh memutuskan sengketa
        $resSeller = $this->actingAs($seller)->post(route('admin.disputes.decide', $complaint), [
            'decision' => 'PAYOUT',
            'decision_reason' => 'Penjual memenangkan dirinya sendiri.',
        ]);
        $resSeller->assertForbidden();
    }

    public function test_parties_and_admin_can_view_and_download_evidence_files(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $stranger = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Lisensi tidak bekerja.',
            'status' => 'OPEN',
        ]);

        $fakeFile = UploadedFile::fake()->create('dokumen_bukti.pdf', 100, 'application/pdf');
        $filePath = $fakeFile->store('evidence/complaints', 'public');

        $evidence = EvidenceFile::create([
            'uploaded_by' => $buyer->user_id,
            'complaint_id' => $complaint->complaint_id,
            'file_name' => 'dokumen_bukti.pdf',
            'file_url' => $filePath,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        // Buyer akses bukti
        $resBuyer = $this->actingAs($buyer)->get(route('transactions.complaints.evidence', [$tx, $evidence, 'inline' => 1]));
        $resBuyer->assertOk();

        // Seller akses bukti
        $resSeller = $this->actingAs($seller)->get(route('transactions.complaints.evidence', [$tx, $evidence]));
        $resSeller->assertOk();

        // Admin akses bukti
        $resAdmin = $this->actingAs($admin)->get(route('transactions.complaints.evidence', [$tx, $evidence]));
        $resAdmin->assertOk();

        // Pihak ketiga dilarang (403)
        $resStranger = $this->actingAs($stranger)->get(route('transactions.complaints.evidence', [$tx, $evidence]));
        $resStranger->assertForbidden();
    }

    public function test_admin_disputes_index_displays_complaints_and_filters(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Pengujian filter sengketa index.',
            'status' => 'OPEN',
        ]);

        // Akses halaman index admin
        $response = $this->actingAs($admin)->get(route('admin.disputes.index'));
        $response->assertOk();
        $response->assertSee('Penyelesaian Sengketa & Komplain', false);
        $response->assertSee("Transaksi #{$tx->transaction_id}");

        // Filter OPEN
        $resOpen = $this->actingAs($admin)->get(route('admin.disputes.index', ['status' => 'OPEN']));
        $resOpen->assertOk();
        $resOpen->assertSee("Transaksi #{$tx->transaction_id}");

        // Filter RESOLVED (harus kosong)
        $resResolved = $this->actingAs($admin)->get(route('admin.disputes.index', ['status' => 'RESOLVED']));
        $resResolved->assertOk();
        $resResolved->assertDontSee("Transaksi #{$tx->transaction_id}");

        // Detail show admin
        $resShow = $this->actingAs($admin)->get(route('admin.disputes.show', $complaint));
        $resShow->assertOk();
        $resShow->assertSee("Investigasi Sengketa Transaksi #{$tx->transaction_id}");
    }

    public function test_scheduler_flags_admin_dispute_overdue_when_admin_deadline_passes(): void
    {
        $now = now()->startOfSecond();
        $this->travelTo($now);

        $buyer = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);

        $tx = $this->createDeliveredTransaction($buyer, $seller, ['status' => TransactionStatus::DISPUTED]);

        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Menunggu keputusan admin.',
            'status' => 'OPEN',
        ]);

        $adminDeadline = $now->copy()->addHours(24);

        TransactionDeadlineEvent::create([
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_DECISION_DUE',
            'previous_deadline' => null,
            'new_deadline' => $adminDeadline,
            'extension_minutes' => 1440,
            'reason' => 'Tenggat 24 jam admin.',
            'granted_by' => null,
            'is_flagged_for_admin' => false,
            'created_at' => $now,
        ]);

        // Majukan waktu melewati 24 jam tenggat admin
        $this->travelTo($now->copy()->addHours(25));

        Artisan::call('transactions:check-complaint-deadlines');

        $this->assertDatabaseHas('transaction_deadline_events', [
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'ADMIN_DISPUTE_OVERDUE',
            'is_flagged_for_admin' => true,
        ]);
    }
}
