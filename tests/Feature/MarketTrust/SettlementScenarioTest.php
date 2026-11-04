<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\KeepObligation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\SellerPayoutAccount;
use App\Models\Settlement;
use App\Models\SettlementAttempt;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $seller;

    protected User $buyer;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_settlement',
            'account_type' => 'ADMIN',
            'account_status' => 'ACTIVE',
        ]);

        $this->seller = User::factory()->create(['username' => 'seller_settlement']);
        $this->buyer = User::factory()->create(['username' => 'buyer_settlement']);

        $category = ProductCategory::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['category_id' => $category->category_id, 'is_active' => true]);
        $this->variant = ProductVariant::factory()->create(['product_id' => $product->product_id, 'is_active' => true]);
    }

    protected function createConfirmedTransaction(float $sellerNet = 100000.0): Transaction
    {
        $agreement = Agreement::create([
            'created_by' => $this->buyer->user_id,
            'buyer_id' => $this->buyer->user_id,
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variant->variant_id,
            'item_title' => 'Digital Service Plan',
            'item_description' => 'Aktivasi layanan digital',
            'quantity' => 1,
            'base_price' => $sellerNet,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 5000.0,
            'buyer_total' => $sellerNet + 5000.0,
            'seller_net' => $sellerNet,
            'delivery_method' => 'Direct Message',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        return Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE->value,
            'status' => TransactionStatus::CONFIRMED->value,
            'payment_due_at' => now()->subHours(2),
            'delivery_due_at' => now()->subHour(),
        ]);
    }

    /**
     * TC-13-01: Admin Mengeksekusi Payout Simulatif dengan Pemotongan Denda KEEP Otomatis
     */
    public function test_tc_13_01_simulated_payout_with_automatic_keep_deduction(): void
    {
        // Berikan seller rekening payout aktif
        SellerPayoutAccount::factory()->bank()->create([
            'seller_id' => $this->seller->user_id,
            'is_active' => true,
        ]);

        // Buat transaksi sebelumnya dan pembatalan pasca-bayar yang disetujui (kesalahan seller)
        $prevTx = $this->createConfirmedTransaction(50000.0);
        $prevCancellation = TransactionCancellation::create([
            'transaction_id' => $prevTx->transaction_id,
            'requested_by' => $this->buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Penjual gagal mengirimkan produk',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $this->admin->user_id,
            'reviewed_at' => now(),
        ]);

        // Buat kewajiban denda KEEP OPEN sebesar Rp25.000 untuk seller
        $obligation = KeepObligation::create([
            'seller_id' => $this->seller->user_id,
            'cancellation_id' => $prevCancellation->cancellation_id,
            'payment_channel_snapshot' => 'BANK_TRANSFER',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 25000.0,
            'multiplier' => 1,
            'amount' => 25000.0,
            'remaining_amount' => 25000.0,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        $transaction = $this->createConfirmedTransaction(100000.0);

        $response = $this->actingAs($this->admin)->post(
            route('admin.settlements.payout', $transaction),
            [
                'execution_outcome' => 'SUCCESS',
                'reference_no' => 'PAYOUT-REF-1001',
                'amount' => 75000.0, // 100.000 - 25.000 (KEEP)
                'note' => 'Payout simulasi berhasil ditransfer',
            ]
        );

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');

        // Pastikan record settlement tersimpan dengan nilai terpotong KEEP
        $settlement = Settlement::where('transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($settlement);
        $this->assertEquals(Settlement::STATUS_COMPLETED, $settlement->status);
        $this->assertEquals(100000.0, (float) $settlement->gross_amount);
        $this->assertEquals(25000.0, (float) $settlement->keep_deducted_amount);
        $this->assertEquals(75000.0, (float) $settlement->amount);

        // Status kewajiban denda KEEP harus menjadi SETTLED
        $this->assertEquals(KeepObligation::STATUS_SETTLED, $obligation->fresh()->status);
        $this->assertEquals(0.0, (float) $obligation->fresh()->remaining_amount);
    }

    /**
     * TC-13-02: Payout Tertahan Jika Seller Belum Mengatur Rekening Payout
     */
    public function test_tc_13_02_payout_held_when_seller_has_no_active_payout_account(): void
    {
        // Seller TIDAK memiliki SellerPayoutAccount aktif
        $transaction = $this->createConfirmedTransaction(100000.0);

        $response = $this->actingAs($this->admin)->post(
            route('admin.settlements.payout', $transaction),
            [
                'execution_outcome' => 'SUCCESS',
                'reference_no' => 'PAYOUT-REF-NO-ACC',
                'note' => 'Mencoba payout tanpa rekening',
            ]
        );

        $response->assertSessionHas('error');
        $this->assertNull(Settlement::where('transaction_id', $transaction->transaction_id)->first());
    }

    /**
     * TC-13-03: Eksekusi Refund Simulatif untuk Pembeli
     */
    public function test_tc_13_03_simulated_refund_for_buyer(): void
    {
        $agreement = Agreement::create([
            'created_by' => $this->buyer->user_id,
            'buyer_id' => $this->buyer->user_id,
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variant->variant_id,
            'item_title' => 'Item Dibatalkan',
            'item_description' => 'Pesanan dibatalkan',
            'quantity' => 1,
            'base_price' => 50000.0,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 2500.0,
            'buyer_total' => 52500.0,
            'seller_net' => 50000.0,
            'delivery_method' => 'Direct Message',
            'delivery_within_minutes' => 30,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        $transaction = Transaction::create([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE->value,
            'status' => TransactionStatus::CANCELLED->value, // Memenuhi syarat refund
            'payment_due_at' => now()->subHours(2),
        ]);

        TransactionCancellation::create([
            'transaction_id' => $transaction->transaction_id,
            'requested_by' => $this->buyer->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Penjual membatalkan pesanan',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $this->admin->user_id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.settlements.refund', $transaction),
            [
                'destination_type' => 'BANK',
                'destination_provider' => 'BCA',
                'destination_number' => '0987654321',
                'destination_holder' => 'Buyer Refund Account',
                'reference_no' => 'REFUND-REF-5001',
                'amount' => 52500.0,
                'note' => 'Pengembalian dana escrow pembeli penuh',
            ]
        );

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertEquals('REFUNDED', is_string($transaction->status) ? $transaction->status : $transaction->status->value);

        $settlement = Settlement::where('transaction_id', $transaction->transaction_id)->first();
        $this->assertNotNull($settlement);
        $this->assertEquals(Settlement::TYPE_REFUND, $settlement->settlement_type);
        $this->assertEquals(Settlement::STATUS_COMPLETED, $settlement->status);
        $this->assertEquals(52500.0, (float) $settlement->amount);
    }

    /**
     * TC-13-04: Penanganan Payout Gagal & Pencatatan Percobaan Ulang (UC-29)
     */
    public function test_tc_13_04_failed_payout_attempt_is_recorded(): void
    {
        $transaction = $this->createConfirmedTransaction(100000.0);

        $response = $this->actingAs($this->admin)->post(
            route('admin.settlements.payout', $transaction),
            [
                'execution_outcome' => 'FAILED',
                'failure_category' => 'SYSTEM',
                'failure_reason' => 'Koneksi API perbankan timeout saat request transfer payout',
                'follow_up_action' => 'Jadwalkan percobaan ulang 15 menit lagi',
                'reference_no' => 'ATTEMPT-FAIL-01',
            ]
        );

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');

        $attempt = SettlementAttempt::whereHas('settlement', function ($q) use ($transaction) {
            $q->where('transaction_id', $transaction->transaction_id);
        })->latest('settlement_attempt_id')->first();

        $this->assertNotNull($attempt);
        $this->assertEquals('FAILED', $attempt->status);
        $this->assertEquals('SYSTEM', $attempt->failure_category);
        $this->assertStringContainsString('timeout', $attempt->failure_reason);
    }
}
