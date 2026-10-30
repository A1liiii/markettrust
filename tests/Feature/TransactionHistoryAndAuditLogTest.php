<?php

namespace Tests\Feature;

use App\Contracts\Services\CancellationServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\ComplaintResponse;
use App\Models\EvidenceFile;
use App\Models\KeepObligation;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\TransactionDeadlineEvent;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionHistoryAndAuditLogTest extends TestCase
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
            'title' => 'Lisensi Pro Cloud '.uniqid(),
            'description' => 'Lisensi resmi original aktivasi instan',
            'price' => 200000.00,
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
            'base_price' => 200000.00,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => 5000.00,
            'buyer_total' => 205000.00,
            'seller_net' => 200000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Kesepakatan pengujian riwayat transaksi',
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

    public function test_admin_audit_logs_requires_admin_role(): void
    {
        $regularUser = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);
        $admin = User::factory()->create([
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        // Non-admin ditolak
        $response = $this->actingAs($regularUser)->get(route('admin.audit-logs.index'));
        $response->assertStatus(403);

        // Admin berhasil mengakses
        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Audit Log &amp; Jejak Aktivitas Sistem', false);
        $response->assertSee('Total Log Tercatat');
    }

    public function test_admin_audit_logs_can_filter_by_action_and_entity_and_search(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
            'username' => 'superadmin_audit',
        ]);

        AuditLog::create([
            'actor_id' => $admin->user_id,
            'action' => 'RECORD_PAYOUT_COMPLETED',
            'entity_type' => 'settlement',
            'entity_id' => 101,
            'old_value' => ['status' => 'PLANNED'],
            'new_value' => ['status' => 'COMPLETED', 'amount' => 200000],
            'created_at' => now()->subDay(),
        ]);

        AuditLog::create([
            'actor_id' => $admin->user_id,
            'action' => 'TRANSACTION_CANCEL_APPROVED',
            'entity_type' => 'transaction_cancellation',
            'entity_id' => 202,
            'old_value' => ['status' => 'REQUESTED'],
            'new_value' => ['status' => 'APPROVED', 'responsible_party' => 'SELLER'],
            'created_at' => now(),
        ]);

        AuditLog::create([
            'actor_id' => null,
            'action' => 'TRANSACTION_AUTO_CONFIRMED',
            'entity_type' => 'transaction',
            'entity_id' => 303,
            'old_value' => ['status' => 'DELIVERED'],
            'new_value' => ['status' => 'CONFIRMED'],
            'created_at' => now(),
        ]);

        // Filter by action
        $resAction = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['action' => 'RECORD_PAYOUT_COMPLETED']));
        $resAction->assertStatus(200);
        $resAction->assertSee('audit-log-row-1', false);
        $resAction->assertDontSee('audit-log-row-2', false);
        $resAction->assertDontSee('audit-log-row-3', false);

        // Filter by entity_type
        $resEntity = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['entity_type' => 'transaction_cancellation']));
        $resEntity->assertStatus(200);
        $resEntity->assertSee('audit-log-row-2', false);
        $resEntity->assertDontSee('audit-log-row-1', false);
        $resEntity->assertDontSee('audit-log-row-3', false);

        // Search query
        $resSearch = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['search' => 'superadmin_audit']));
        $resSearch->assertStatus(200);
        $resSearch->assertSee('audit-log-row-1', false);
        $resSearch->assertSee('audit-log-row-2', false);
        $resSearch->assertDontSee('audit-log-row-3', false);
    }

    public function test_admin_approving_and_rejecting_cancellation_records_audit_log(): void
    {
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);

        $cancellationService = app(CancellationServiceInterface::class);

        // 1. Skenario Penolakan (REJECTED)
        $tx1 = $this->createTransaction($buyer, $seller);
        $cancellation1 = TransactionCancellation::create([
            'transaction_id' => $tx1->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Ingin batalkan pesanan karena salah varian',
            'status' => TransactionCancellation::STATUS_REQUESTED,
        ]);

        $cancellationService->reviewCancellation(
            $cancellation1,
            $admin,
            'REJECTED',
            null,
            'Produk sudah mulai diproses penjual'
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'TRANSACTION_CANCEL_REJECTED',
            'entity_type' => 'transaction_cancellation',
            'entity_id' => $cancellation1->cancellation_id,
        ]);

        // 2. Skenario Persetujuan (APPROVED)
        $tx2 = $this->createTransaction($buyer, $seller);
        Payment::create([
            'transaction_id' => $tx2->transaction_id,
            'gateway_order_id' => 'ORDER-'.uniqid(),
            'amount' => 205000.00,
            'status' => 'PAID',
            'payment_channel' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        $cancellation2 = TransactionCancellation::create([
            'transaction_id' => $tx2->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Penjual tidak dapat dihubungi',
            'status' => TransactionCancellation::STATUS_REQUESTED,
        ]);

        $cancellationService->reviewCancellation(
            $cancellation2,
            $admin,
            'APPROVED',
            TransactionCancellation::PARTY_SELLER,
            'Persetujuan pembatalan: kesalahan pihak penjual'
        );

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'TRANSACTION_CANCEL_APPROVED',
            'entity_type' => 'transaction_cancellation',
            'entity_id' => $cancellation2->cancellation_id,
        ]);
    }

    public function test_system_auto_confirm_order_records_audit_log_with_null_actor(): void
    {
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::DELIVERED,
            'delivered_at' => now()->subHours(2),
            'confirmation_due_at' => now()->subMinutes(10),
            'delivery_note' => 'Hasil diserahkan',
        ]);

        $transactionService = app(TransactionServiceInterface::class);
        $transactionService->autoConfirmOrder($tx);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => null,
            'action' => 'TRANSACTION_AUTO_CONFIRMED',
            'entity_type' => 'transaction',
            'entity_id' => $tx->transaction_id,
        ]);
    }

    public function test_extend_delivery_deadline_records_audit_log(): void
    {
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivery_due_at' => now()->addMinutes(30),
        ]);

        $transactionService = app(TransactionServiceInterface::class);
        $transactionService->extendDeliveryDeadline($tx, 45, 'Pemadaman listrik darurat', $admin);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'DELIVERY_DEADLINE_EXTENDED',
            'entity_type' => 'transaction',
            'entity_id' => $tx->transaction_id,
        ]);
    }

    public function test_transaction_show_displays_all_history_sections_and_stepper(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'buyer_reza']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'seller_budi']);
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::DELIVERED,
            'delivered_at' => now()->subMinutes(30),
            'confirmation_due_at' => now()->addMinutes(30),
            'delivery_note' => 'Kredensial lisensi: ABCD-1234-XYZ',
            'result_link' => 'https://drive.google.com/sample-file',
        ]);

        // Buat bukti penyerahan
        EvidenceFile::create([
            'uploaded_by' => $seller->user_id,
            'delivery_transaction_id' => $tx->transaction_id,
            'file_name' => 'bukti_serah_resmi.png',
            'file_url' => 'evidence/delivery/bukti_serah_resmi.png',
            'mime_type' => 'image/png',
            'uploaded_at' => now(),
        ]);

        // Buat event deadline
        TransactionDeadlineEvent::create([
            'transaction_id' => $tx->transaction_id,
            'event_type' => 'DELIVERY_EXTENSION',
            'previous_deadline' => now()->subHour(),
            'new_deadline' => now()->addHour(),
            'extension_minutes' => 60,
            'reason' => 'Perpanjangan SLA atas konfirmasi penjual',
            'granted_by' => $admin->user_id,
            'created_at' => now(),
        ]);

        // Buat komplain
        $complaint = Complaint::create([
            'transaction_id' => $tx->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Kunci aktivasi invalid ketika dicoba',
            'status' => 'RESOLVED',
            'decision' => 'PAYOUT',
            'decision_reason' => 'Penjual telah memberikan kunci pengganti yang valid.',
            'decided_by' => $admin->user_id,
            'decided_at' => now(),
        ]);

        ComplaintResponse::create([
            'complaint_id' => $complaint->complaint_id,
            'responder_id' => $seller->user_id,
            'response_text' => 'Sudah kami berikan kunci lisensi baru via chat.',
            'created_at' => now(),
        ]);

        // Buat settlement
        Settlement::create([
            'transaction_id' => $tx->transaction_id,
            'settlement_type' => 'PAYOUT',
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Budi Santoso',
            'gross_amount' => 200000.00,
            'keep_deducted_amount' => 0.00,
            'amount' => 200000.00,
            'status' => 'COMPLETED',
            'reference_no' => 'TRX-PAY-202610-001',
            'completed_at' => now(),
            'recorded_by' => $admin->user_id,
        ]);

        // Buat status history
        TransactionStatusHistory::create([
            'transaction_id' => $tx->transaction_id,
            'from_status' => 'PAID',
            'to_status' => 'DELIVERED',
            'changed_by' => $seller->user_id,
            'note' => 'Penjual menyerahkan item.',
            'changed_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->get(route('transactions.show', $tx));
        $response->assertStatus(200);

        // Stepper & Snapshot preserved
        $response->assertSee('Proses Transaksi Escrow');
        $response->assertSee('Detail Pesanan Produk');
        $response->assertSee('Lisensi Pro Cloud');

        // Delivery results & link
        $response->assertSee('Kredensial lisensi: ABCD-1234-XYZ');
        $response->assertSee('https://drive.google.com/sample-file');
        $response->assertSee('bukti_serah_resmi.png');

        // Deadline events
        $response->assertSee('Riwayat Penyesuaian Tenggat Waktu');
        $response->assertSee('+60 Menit');
        $response->assertSee('Perpanjangan SLA atas konfirmasi penjual');

        // Complaint details
        $response->assertSee('Informasi Komplain &amp; Mediasi Sengketa', false);
        $response->assertSee('Kunci aktivasi invalid ketika dicoba');
        $response->assertSee('Sudah kami berikan kunci lisensi baru via chat.');
        $response->assertSee('Putusan Mediasi Administrator');

        // Settlement details
        $response->assertSee('Rincian Settlement Payout (Penjual)');
        $response->assertSee('TRX-PAY-202610-001');

        // Status history with author tag
        $response->assertSee('Riwayat Perjalanan Transaksi');
        $response->assertSee('@seller_budi');
    }

    public function test_transaction_show_distinguishes_manual_confirm_vs_auto_confirm(): void
    {
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'buyer_manual']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'seller_manual']);

        // 1. Manual confirm
        $txManual = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::CONFIRMED,
            'delivered_at' => now()->subHour(),
            'confirmed_at' => now()->subMinutes(10),
            'delivery_note' => 'Pengiriman manual',
        ]);
        TransactionStatusHistory::create([
            'transaction_id' => $txManual->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => $buyer->user_id,
            'note' => 'Pembeli mengonfirmasi pesanan.',
            'changed_at' => now()->subMinutes(10),
        ]);

        $resManual = $this->actingAs($buyer)->get(route('transactions.show', $txManual));
        $resManual->assertStatus(200);
        $resManual->assertSee('Pesanan Dikonfirmasi Manual oleh Pembeli');
        $resManual->assertSee('Manual');

        // 2. Auto-confirm
        $txAuto = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::CONFIRMED,
            'delivered_at' => now()->subHours(2),
            'confirmed_at' => now()->subMinutes(5),
            'delivery_note' => 'Pengiriman auto',
        ]);
        TransactionStatusHistory::create([
            'transaction_id' => $txAuto->transaction_id,
            'from_status' => 'DELIVERED',
            'to_status' => 'CONFIRMED',
            'changed_by' => null,
            'note' => 'Konfirmasi otomatis oleh sistem (Auto-Confirm).',
            'changed_at' => now()->subMinutes(5),
        ]);
        TransactionDeadlineEvent::create([
            'transaction_id' => $txAuto->transaction_id,
            'event_type' => 'AUTO_CONFIRMED',
            'reason' => 'Auto confirm grace period expired',
            'created_at' => now()->subMinutes(5),
        ]);

        $resAuto = $this->actingAs($buyer)->get(route('transactions.show', $txAuto));
        $resAuto->assertStatus(200);
        $resAuto->assertSee('Pesanan Dikonfirmasi Otomatis oleh Sistem (Auto-Confirm)');
        $resAuto->assertSee('Sistem');
    }

    public function test_transaction_show_protects_private_settlement_accounts_knf06(): void
    {
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::COMPLETED,
            'delivered_at' => now()->subHours(3),
            'confirmed_at' => now()->subHours(2),
        ]);

        Settlement::create([
            'transaction_id' => $tx->transaction_id,
            'settlement_type' => 'PAYOUT',
            'destination_type' => 'BANK',
            'destination_provider' => 'MANDIRI',
            'destination_number' => '1400019283746',
            'destination_holder' => 'Budi Santoso',
            'gross_amount' => 200000.00,
            'keep_deducted_amount' => 0.00,
            'amount' => 200000.00,
            'status' => 'COMPLETED',
            'reference_no' => 'PAY-MANDIRI-999',
            'completed_at' => now(),
            'recorded_by' => $admin->user_id,
        ]);

        // Seller melihat nomor rekening lengkap miliknya
        $resSeller = $this->actingAs($seller)->get(route('transactions.show', $tx));
        $resSeller->assertStatus(200);
        $resSeller->assertSee('1400019283746');

        // Admin melihat nomor rekening lengkap untuk verifikasi
        $resAdmin = $this->actingAs($admin)->get(route('transactions.show', $tx));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('1400019283746');

        // Buyer (pihak lawan) melihat nomor rekening yang disamarkan (masking)
        $resBuyer = $this->actingAs($buyer)->get(route('transactions.show', $tx));
        $resBuyer->assertStatus(200);
        $resBuyer->assertDontSee('1400019283746');
        $resBuyer->assertSee('1400******746');
    }

    public function test_transaction_show_cancellation_and_keep_obligation_visibility(): void
    {
        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'buyer_cancellation']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE', 'username' => 'seller_cancellation']);
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::CANCELLED,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $tx->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Penjual membatalkan sepihak karena stok habis',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'decision_note' => 'Penjual melanggar kesepakatan penyerahan',
            'reviewed_at' => now(),
        ]);

        KeepObligation::create([
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'bank_transfer',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 1,
            'amount' => 4000.00,
            'remaining_amount' => 4000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        // Seller melihat kartu KEEP dan sisa kewajiban
        $resSeller = $this->actingAs($seller)->get(route('transactions.show', $tx));
        $resSeller->assertStatus(200);
        $resSeller->assertSee('Beban Kewajiban KEEP Penjual');
        $resSeller->assertSee('Rp 4.000');
        $resSeller->assertSee('Belum Lunas (OPEN)');

        // Admin juga melihat kartu KEEP
        $resAdmin = $this->actingAs($admin)->get(route('transactions.show', $tx));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Beban Kewajiban KEEP Penjual');
        $resAdmin->assertSee('Rp 4.000');

        // Buyer melihat alasan pembatalan dan penanggung jawab, TETAPI TIDAK melihat rincian KEEP internal seller
        $resBuyer = $this->actingAs($buyer)->get(route('transactions.show', $tx));
        $resBuyer->assertStatus(200);
        $resBuyer->assertSee('Catatan Pembatalan & Pertanggungjawaban');
        $resBuyer->assertSee('Penjual (Seller)');
        $resBuyer->assertSee('Penjual membatalkan sepihak karena stok habis');
        $resBuyer->assertDontSee('Beban Kewajiban KEEP Penjual');
    }

    public function test_transaction_evidence_download_authorization(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('evidence/delivery/secret_file.pdf', 'PDF-CONTENT-MARKETTRUST');

        $buyer = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);
        $admin = User::factory()->create(['account_type' => 'ADMIN', 'account_status' => 'ACTIVE']);
        $intruder = User::factory()->create(['account_type' => 'USER', 'account_status' => 'ACTIVE']);

        $tx = $this->createTransaction($buyer, $seller);

        $evidence = EvidenceFile::create([
            'uploaded_by' => $seller->user_id,
            'delivery_transaction_id' => $tx->transaction_id,
            'file_name' => 'secret_file.pdf',
            'file_url' => 'evidence/delivery/secret_file.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        // Buyer, Seller, dan Admin diizinkan
        $this->actingAs($buyer)->get(route('transactions.delivery-evidence', [$tx, $evidence]))->assertStatus(200);
        $this->actingAs($seller)->get(route('transactions.delivery-evidence', [$tx, $evidence]))->assertStatus(200);
        $this->actingAs($admin)->get(route('transactions.delivery-evidence', [$tx, $evidence]))->assertStatus(200);

        // Intruder (pengguna lain yang tidak berhak) ditolak 403
        $this->actingAs($intruder)->get(route('transactions.delivery-evidence', [$tx, $evidence]))->assertStatus(403);
    }
}
