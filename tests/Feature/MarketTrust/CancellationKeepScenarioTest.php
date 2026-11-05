<?php

namespace Tests\Feature\MarketTrust;

use App\Contracts\Services\CancellationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
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
use Tests\TestCase;

class CancellationKeepScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected User $admin;

    protected ProductVariant $variant;

    protected Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create([
            'username' => 'alibuyer',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $this->seller = User::factory()->create([
            'username' => 'aliseller',
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $this->admin = User::factory()->create([
            'username' => 'aliadmin',
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        $category = ProductCategory::create([
            'category_name' => 'Lisensi Software',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'JetBrains All Products Pack',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => '1 Tahun Edu',
            'is_active' => true,
        ]);

        $this->listing = Listing::create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variant->variant_id,
            'title' => 'JetBrains All Products 1 Tahun',
            'description' => 'Lisensi resmi aktivasi email sendiri',
            'price' => 100000.00,
            'delivery_method' => 'DIRECT_CHAT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-12-01: Pembatalan Transaksi Pre-Payment oleh Buyer (Sebelum Bayar).
     * Memastikan status langsung CANCELLED, akses pembayaran ditutup, listing tetap ACTIVE di katalog, dan tidak menerbitkan KEEP.
     */
    public function test_tc_12_01_buyer_cancels_pre_payment_transaction_immediately(): void
    {
        $transaction = $this->createMarketplaceTransaction(TransactionStatus::WAITING_PAYMENT);

        $response = $this->actingAs($this->buyer)
            ->post(route('transactions.cancel', $transaction), [
                'reason' => 'Salah memilih varian produk',
            ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        // 1. Status transaksi langsung berubah menjadi CANCELLED
        $this->assertSame(TransactionStatus::CANCELLED, $transaction->fresh()->status);

        // 2. Record pembatalan tercatat sebagai BEFORE_PAYMENT dan langsung APPROVED
        $this->assertDatabaseHas('transaction_cancellations', [
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $this->buyer->user_id,
            'stage' => TransactionCancellation::STAGE_BEFORE_PAYMENT,
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => null,
        ]);

        // 3. Riwayat status transaksi mencatat transisi WAITING_PAYMENT -> CANCELLED
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => TransactionStatus::WAITING_PAYMENT->value,
            'to_status' => TransactionStatus::CANCELLED->value,
            'changed_by' => $this->buyer->user_id,
        ]);

        // 4. Listing asal tetap ACTIVE (tersedia kembali untuk dibeli di katalog)
        $this->assertSame('ACTIVE', $this->listing->fresh()->status);

        // 5. Tidak ada denda KEEP untuk pembatalan pra-bayar
        $this->assertDatabaseCount('keep_obligations', 0);
    }

    /**
     * TC-12-02: Pengajuan Pembatalan Post-Payment Sebelum Dikirim (Status PAID).
     * Memastikan alasan wajib diisi, status pengajuan menjadi REQUESTED menunggu review Admin, dan status transaksi tetap PAID.
     */
    public function test_tc_12_02_post_payment_cancellation_requires_reason_and_waits_for_admin_review(): void
    {
        $transaction = $this->createMarketplaceTransaction(TransactionStatus::PAID);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => 'MT-ORDER-PAID-01',
            'payment_channel' => 'bca_va',
            'amount' => 105000.00,
            'gateway_fee_amount' => 4000.00,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now()->subMinutes(10),
        ]);

        // Subtest A: Pengajuan tanpa alasan pada status PAID -> Ditolak validasi
        $invalidResponse = $this->actingAs($this->buyer)
            ->post(route('transactions.cancel', $transaction), [
                'reason' => '',
            ]);

        $invalidResponse->assertSessionHasErrors(['reason']);
        $this->assertDatabaseCount('transaction_cancellations', 0);

        // Subtest B: Pengajuan dengan alasan valid (>= 5 karakter) -> Masuk antrean REQUESTED
        $validResponse = $this->actingAs($this->buyer)
            ->post(route('transactions.cancel', $transaction), [
                'reason' => 'Penjual belum merespons chat dan saya butuh akun segera.',
            ]);

        $validResponse->assertRedirect(route('transactions.show', $transaction));
        $validResponse->assertSessionHas('success');

        $this->assertDatabaseHas('transaction_cancellations', [
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $this->buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'status' => TransactionCancellation::STATUS_REQUESTED,
            'reason' => 'Penjual belum merespons chat dan saya butuh akun segera.',
        ]);

        // Status transaksi tetap PAID sampai Admin memberi keputusan
        $this->assertSame(TransactionStatus::PAID, $transaction->fresh()->status);
        $this->assertTrue($transaction->fresh()->hasPendingCancellation());
    }

    /**
     * TC-12-03: Admin Meninjau Pembatalan dan Menetapkan Seller Bersalah (Penerbitan KEEP / UC-28B).
     * Menguji aturan nominal KEEP resmi:
     * - Channel Bank/VA (BANK_VA_FLAT): Flat Rp 4.000
     * - Channel Non-Bank/QRIS (OTHER_TRIPLE_GATEWAY_FEE): 3 x gateway_fee_amount
     */
    public function test_tc_12_03_admin_approves_cancellation_with_seller_at_fault_and_issues_keep_obligations(): void
    {
        // Subtest A: Channel Bank VA (bca_va) -> Wajib menerbitkan KEEP sebesar Rp 4.000 (BANK_VA_FLAT)
        $txBankVa = $this->createMarketplaceTransaction(TransactionStatus::PAID);

        Payment::create([
            'transaction_id' => $txBankVa->transaction_id,
            'gateway_order_id' => 'MT-ORDER-VA-101',
            'payment_channel' => 'bca_va',
            'amount' => 105000.00,
            'gateway_fee_amount' => 4000.00,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now()->subMinutes(15),
        ]);

        $cancellationVa = TransactionCancellation::create([
            'transaction_id' => $txBankVa->transaction_id,
            'requested_by' => $this->buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Seller membatalkan pesanan karena stok habis.',
            'status' => TransactionCancellation::STATUS_REQUESTED,
        ]);

        $reviewVaResponse = $this->actingAs($this->admin)
            ->post(route('admin.cancellations.review', $cancellationVa), [
                'decision' => 'APPROVED',
                'responsible_party' => 'SELLER',
                'decision_note' => 'Seller terbukti tidak dapat menyerahkan produk digital.',
            ]);

        $reviewVaResponse->assertSessionHas('success');

        $this->assertSame(TransactionStatus::CANCELLED, $txBankVa->fresh()->status);
        $this->assertTrue($txBankVa->fresh()->isEligibleForRefund());

        // Expected nominal dari aturan yang disahkan untuk Bank/VA: Rp 4.000 flat
        $this->assertDatabaseHas('keep_obligations', [
            'cancellation_id' => $cancellationVa->cancellation_id,
            'seller_id' => $this->seller->user_id,
            'payment_channel_snapshot' => 'bca_va',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 1,
            'amount' => 4000.00,
            'remaining_amount' => 4000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        // Subtest B: Channel Non-Bank/VA (qris dengan gateway fee Rp 1.500) -> Wajib menerbitkan KEEP 3 x 1.500 = Rp 4.500
        $txQris = $this->createMarketplaceTransaction(TransactionStatus::PAID);

        Payment::create([
            'transaction_id' => $txQris->transaction_id,
            'gateway_order_id' => 'MT-ORDER-QRIS-102',
            'payment_channel' => 'qris',
            'amount' => 105000.00,
            'gateway_fee_amount' => 1500.00,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now()->subMinutes(12),
        ]);

        $cancellationQris = TransactionCancellation::create([
            'transaction_id' => $txQris->transaction_id,
            'requested_by' => $this->seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Maaf stok lisensi sedang kosong dari distributor.',
            'status' => TransactionCancellation::STATUS_REQUESTED,
        ]);

        $reviewQrisResponse = $this->actingAs($this->admin)
            ->post(route('admin.cancellations.review', $cancellationQris), [
                'decision' => 'APPROVED',
                'responsible_party' => 'SELLER',
                'decision_note' => 'Pembatalan atas permintaan seller setelah pembayaran QRIS.',
            ]);

        $reviewQrisResponse->assertSessionHas('success');

        // Expected nominal dari aturan resmi untuk Non-Bank/VA: 3 x Rp 1.500 = Rp 4.500
        $this->assertDatabaseHas('keep_obligations', [
            'cancellation_id' => $cancellationQris->cancellation_id,
            'seller_id' => $this->seller->user_id,
            'payment_channel_snapshot' => 'qris',
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 1500.00,
            'multiplier' => 3,
            'amount' => 4500.00,
            'remaining_amount' => 4500.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        // Total akumulasi tunggakan KEEP milik Seller harus Rp 4.000 + Rp 4.500 = Rp 8.500
        $cancellationService = app(CancellationServiceInterface::class);
        $this->assertEquals(8500.00, $cancellationService->getTotalRemainingKeep($this->seller));
    }

    protected function createMarketplaceTransaction(TransactionStatus $status): Transaction
    {
        $agreement = Agreement::create([
            'created_by' => $this->buyer->user_id,
            'buyer_id' => $this->buyer->user_id,
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variant->variant_id,
            'item_title' => $this->listing->title,
            'item_description' => $this->listing->description,
            'quantity' => 1,
            'base_price' => 100000.00,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 5000.00,
            'buyer_total' => 105000.00,
            'seller_net' => 100000.00,
            'delivery_method' => 'DIRECT_CHAT',
            'delivery_within_minutes' => 30,
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED->value,
            'buyer_accepted_at' => now(),
            'seller_accepted_at' => now(),
            'finalized_at' => now(),
        ]);

        return Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => 'MARKETPLACE',
            'listing_id' => $this->listing->listing_id,
            'status' => $status->value,
            'payment_due_at' => now()->addHour(),
            'delivery_due_at' => $status === TransactionStatus::PAID ? now()->addHour() : null,
        ]);
    }
}
