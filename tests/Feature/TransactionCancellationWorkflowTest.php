<?php

namespace Tests\Feature;

use App\Contracts\Services\CancellationServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\KeepObligation;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TransactionCancellationWorkflowTest extends TestCase
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
            'title' => 'Item Game Digital Pro',
            'description' => 'Item resmi pengiriman instan',
            'price' => 100000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 60,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    protected function createTransaction(
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
            'base_price' => 100000.00,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => 5000.00,
            'buyer_total' => 105000.00,
            'seller_net' => 100000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Kesepakatan pengujian pembatalan',
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
            'status' => TransactionStatus::WAITING_PAYMENT,
            'payment_due_at' => now()->addHour(),
        ], $transactionOverrides));
    }

    public function test_can_cancel_pre_payment_transaction_when_gateway_reconciliation_confirms_unpaid(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::WAITING_PAYMENT,
        ]);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'bank_transfer',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'pending',
            'amount' => 105000.00,
        ]);

        $mockPaymentService = Mockery::mock(PaymentServiceInterface::class);
        $mockPaymentService->shouldReceive('reconcileGatewayStatus')
            ->once()
            ->with(Mockery::on(fn ($tx) => $tx->transaction_id === $transaction->transaction_id))
            ->andReturn([
                'state' => 'pending',
                'can_cancel_or_expire' => true,
                'gateway_status' => 'pending',
                'message' => 'Pembayaran belum dilakukan.',
            ]);

        $this->app->instance(PaymentServiceInterface::class, $mockPaymentService);

        $response = $this->actingAs($buyer)->post(route('transactions.cancel', $transaction), [
            'reason' => 'Ingin mengganti metode pembayaran lain.',
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->status);

        $this->assertDatabaseHas('transaction_cancellations', [
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_BEFORE_PAYMENT,
            'status' => TransactionCancellation::STATUS_APPROVED,
        ]);
    }

    public function test_cannot_cancel_pre_payment_transaction_if_gateway_reports_settled(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::WAITING_PAYMENT,
        ]);

        $mockPaymentService = Mockery::mock(PaymentServiceInterface::class);
        $mockPaymentService->shouldReceive('reconcileGatewayStatus')
            ->once()
            ->andReturn([
                'state' => 'settlement',
                'can_cancel_or_expire' => false,
                'gateway_status' => 'settlement',
                'message' => 'Dana telah berhasil dibayar pembeli.',
            ]);

        $this->app->instance(PaymentServiceInterface::class, $mockPaymentService);

        $response = $this->actingAs($buyer)->post(route('transactions.cancel', $transaction), [
            'reason' => 'Batal sepihak',
        ]);

        $response->assertSessionHas('error');
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::WAITING_PAYMENT, $transaction->status);
    }

    public function test_buyer_can_request_cancellation_after_payment_before_delivery_with_reason(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
            'delivery_due_at' => now()->addHour(),
        ]);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'bank_transfer',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'settlement',
            'amount' => 105000.00,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.cancel', $transaction), [
            'reason' => 'Penjual tidak dapat dihubungi dan melewati batas waktu awal.',
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();
        // Transaksi tetap PAID dan masuk ke antrean tinjauan admin
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);

        $this->assertDatabaseHas('transaction_cancellations', [
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Penjual tidak dapat dihubungi dan melewati batas waktu awal.',
        ]);
    }

    public function test_post_payment_cancellation_requires_mandatory_reason(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.cancel', $transaction), [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors(['reason']);
        $this->assertDatabaseMissing('transaction_cancellations', [
            'transaction_id' => $transaction->transaction_id,
        ]);
    }

    public function test_cannot_request_duplicate_cancellation_while_pending(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Pengajuan pertama sedang berjalan.',
        ]);

        $response = $this->actingAs($seller)->post(route('transactions.cancel', $transaction), [
            'reason' => 'Pengajuan kedua yang tumpang tindih.',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_cancel_transaction_after_delivered(): void
    {
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::DELIVERED,
        ]);

        $response = $this->actingAs($buyer)->post(route('transactions.cancel', $transaction), [
            'reason' => 'Ingin membatalkan padahal barang sudah dikirim.',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_reject_cancellation_and_transaction_resumes_paid(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Salah klik varian.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cancellations.review', $cancellation), [
            'decision' => 'REJECTED',
            'decision_note' => 'Penjual sudah memproses dan pesanan tidak dapat dibatalkan sepihak.',
        ]);

        $response->assertSessionHas('success');

        $cancellation->refresh();
        $this->assertEquals(TransactionCancellation::STATUS_REJECTED, $cancellation->status);
        $this->assertEquals($admin->user_id, $cancellation->reviewed_by);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
    }

    public function test_admin_approves_cancellation_with_seller_fault_creates_bank_va_flat_keep(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'bca_va',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'settlement',
            'amount' => 105000.00,
            'gateway_fee_amount' => 4000.00,
            'gateway_fee_verified_at' => now(),
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Penjual kehabisan stok lisensi.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cancellations.review', $cancellation), [
            'decision' => 'APPROVED',
            'responsible_party' => 'SELLER',
            'decision_note' => 'Penjual mengakui tidak memiliki stok.',
        ]);

        $response->assertSessionHas('success');

        $cancellation->refresh();
        $this->assertEquals(TransactionCancellation::STATUS_APPROVED, $cancellation->status);
        $this->assertEquals('SELLER', $cancellation->responsible_party);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->status);

        $this->assertDatabaseHas('keep_obligations', [
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $seller->user_id,
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'amount' => 4000.00,
            'remaining_amount' => 4000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);
    }

    public function test_admin_approves_cancellation_with_seller_fault_creates_triple_gateway_fee_keep(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'gopay',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'settlement',
            'amount' => 105000.00,
            'gateway_fee_amount' => 2000.00,
            'gateway_fee_verified_at' => now(),
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Penjual membatalkan sepihak.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.cancellations.review', $cancellation), [
            'decision' => 'APPROVED',
            'responsible_party' => 'SELLER',
            'decision_note' => 'Pembatalan disetujui, kewajiban 3x fee gateway dibebankan ke penjual.',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('keep_obligations', [
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $seller->user_id,
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 2000.00,
            'multiplier' => 3,
            'amount' => 6000.00,
            'remaining_amount' => 6000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);
    }

    public function test_keep_obligation_is_held_if_gateway_fee_unverified(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        // Non Bank/VA tapi fee gateway null (belum diverifikasi webhook/reconcile)
        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'qris',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'settlement',
            'amount' => 105000.00,
            'gateway_fee_amount' => null,
            'gateway_fee_verified_at' => null,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Penjual membatalkan.',
        ]);

        $this->actingAs($admin)->post(route('admin.cancellations.review', $cancellation), [
            'decision' => 'APPROVED',
            'responsible_party' => 'SELLER',
        ]);

        // KEEP ditunda dan tidak langsung dibuat dengan nilai 0
        $this->assertDatabaseMissing('keep_obligations', [
            'cancellation_id' => $cancellation->cancellation_id,
        ]);
    }

    public function test_admin_approves_cancellation_with_buyer_fault_creates_no_keep(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Pembeli berubah pikiran.',
        ]);

        $this->actingAs($admin)->post(route('admin.cancellations.review', $cancellation), [
            'decision' => 'APPROVED',
            'responsible_party' => 'BUYER',
            'decision_note' => 'Pembatalan disetujui karena permohonan pembeli.',
        ]);

        $this->assertDatabaseMissing('keep_obligations', [
            'cancellation_id' => $cancellation->cancellation_id,
        ]);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->status);
    }

    public function test_cancellation_service_helpers_and_idempotency(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'payment_channel' => 'bank_transfer',
            'gateway_order_id' => 'MT-ORD-'.$transaction->transaction_id,
            'gateway_status' => 'settlement',
            'amount' => 105000.00,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => 'SELLER',
        ]);

        $service = app(CancellationServiceInterface::class);

        // Sync pertama
        $keep1 = $service->syncKeepObligation($cancellation);
        $this->assertNotNull($keep1);

        // Sync kedua (idempotent, tidak duplikasi)
        $keep2 = $service->syncKeepObligation($cancellation);
        $this->assertEquals($keep1->keep_obligation_id, $keep2->keep_obligation_id);
        $this->assertEquals(1, KeepObligation::where('cancellation_id', $cancellation->cancellation_id)->count());

        // Helper getRemainingKeepObligations & getTotalRemainingKeep
        $obligations = $service->getRemainingKeepObligations($seller);
        $this->assertCount(1, $obligations);

        $totalRemaining = $service->getTotalRemainingKeep($seller);
        $this->assertEquals(4000.00, $totalRemaining);

        // Helper getApprovedRefundDecision
        $decision = $service->getApprovedRefundDecision($transaction);
        $this->assertNotNull($decision);
        $this->assertEquals($cancellation->cancellation_id, $decision->cancellation_id);
    }

    public function test_unauthorized_user_cannot_cancel_or_review(): void
    {
        $intruder = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'USER']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Pengajuan pengujian otorisasi.',
        ]);

        // Intruder mencoba mengajukan pembatalan pada transaksi orang lain
        $this->actingAs($intruder)
            ->post(route('transactions.cancel', $transaction), ['reason' => 'Intruder request'])
            ->assertForbidden();

        // Intruder (non-admin) mencoba meninjau pembatalan
        $this->actingAs($intruder)
            ->post(route('admin.cancellations.review', $cancellation), [
                'decision' => 'APPROVED',
                'responsible_party' => 'SELLER',
            ])
            ->assertForbidden();
    }

    public function test_admin_transactions_management_view_renders_correctly(): void
    {
        $admin = User::factory()->create(['account_status' => 'ACTIVE', 'account_type' => 'ADMIN']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Antrean tes admin.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index'));

        $response->assertOk();
        $response->assertSee('Pengawasan Transaksi');
        $response->assertSee('Antrean tes admin.');
    }
}
