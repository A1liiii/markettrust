<?php

namespace Tests\Feature;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\ForumPost;
use App\Models\KeepDeduction;
use App\Models\KeepObligation;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFinancialDetailTest extends TestCase
{
    use RefreshDatabase;

    protected FeeCalculationServiceInterface $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeService = app(FeeCalculationServiceInterface::class);
    }

    protected function createVariant(): ProductVariant
    {
        $category = ProductCategory::create([
            'category_name' => 'Kategori Digital '.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Produk Digital '.uniqid(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Lisensi '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createMarketplaceTransaction(
        User $buyer,
        User $seller,
        float $basePrice = 1000000.00,
        array $transactionOverrides = [],
        array $agreementOverrides = []
    ): Transaction {
        $variant = $this->createVariant();

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Lisensi Enterprise Marketplace',
            'description' => 'Lisensi produk digital R1 resmi',
            'price' => $basePrice,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $calc = $this->feeService->calculateForMarketplace($basePrice);

        $agreement = Agreement::create(array_merge([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => $calc->basePrice,
            'fee_type' => $calc->feeType,
            'fee_amount' => $calc->feeAmount,
            'buyer_total' => $calc->buyerTotal,
            'seller_net' => $calc->sellerNet,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Snapshot marketplace',
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
            'payment_due_at' => now()->addHour(),
            'delivery_due_at' => now()->addHour(),
        ], $transactionOverrides));
    }

    protected function createForumTransaction(
        User $buyer,
        User $seller,
        float $basePrice,
        FeeType $feeType,
        array $transactionOverrides = []
    ): Transaction {
        $variant = $this->createVariant();

        $post = ForumPost::create([
            'author_id' => $seller->user_id,
            'category_code' => 'WTS',
            'variant_id' => $variant->variant_id,
            'title' => 'WTS Lisensi Forum Digital',
            'content' => 'Penawaran produk digital via forum',
            'price_offer' => $basePrice,
            'status' => 'ACTIVE',
        ]);

        $calc = $this->feeService->calculate($basePrice, $feeType);

        $agreement = Agreement::create([
            'created_by' => $seller->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => $post->title,
            'item_description' => $post->content,
            'quantity' => 1,
            'base_price' => $calc->basePrice,
            'fee_type' => $calc->feeType,
            'fee_amount' => $calc->feeAmount,
            'buyer_total' => $calc->buyerTotal,
            'seller_net' => $calc->sellerNet,
            'delivery_method' => 'DIRECT_CHAT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Snapshot forum '.$feeType->value,
            'revision_no' => 2,
            'status' => AgreementStatus::FINALIZED,
            'buyer_accepted_at' => now(),
            'seller_accepted_at' => now(),
            'finalized_at' => now(),
        ]);

        return Transaction::create(array_merge([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::FORUM,
            'post_id' => $post->post_id,
            'status' => TransactionStatus::PAID,
            'payment_due_at' => now()->addHour(),
            'delivery_due_at' => now()->addHour(),
        ], $transactionOverrides));
    }

    public function test_marketplace_transaction_displays_simple_total_to_buyer_and_full_breakdown_to_seller_and_admin(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $admin = User::factory()->create(['account_type' => 'ADMIN']);
        $outsider = User::factory()->create();

        // Harga dasar Rp1.000.000 -> Fee Rp10.000, Buyer Total Rp1.010.000, Seller Net Rp1.000.000
        $transaction = $this->createMarketplaceTransaction($buyer, $seller, 1000000.00);

        // Buyer hanya melihat Harga Produk & Total Pembayaran (Rp 1.010.000) tanpa +fee atau seller_net
        $resBuyer = $this->actingAs($buyer)->get(route('transactions.show', $transaction));
        $resBuyer->assertStatus(200);
        $resBuyer->assertSee('data-cy="financial-detail-section"', false);
        $resBuyer->assertSee('data-cy="financial-buyer-card"', false);
        $resBuyer->assertSee('Harga Produk:');
        $resBuyer->assertSee('Total Pembayaran:');
        $resBuyer->assertSee('Rp 1.010.000');
        $resBuyer->assertDontSee('+ Rp 10.000');
        $resBuyer->assertDontSee('Pencairan Hak Bersih Penjual:');
        $resBuyer->assertDontSee('data-cy="snapshot-seller-net"', false);

        // Seller & Admin melihat rincian lengkap dalam 1 kartu ringkas
        foreach ([$seller, $admin] as $authorizedUser) {
            $response = $this->actingAs($authorizedUser)->get(route('transactions.show', $transaction));

            $response->assertStatus(200);
            $response->assertSee('data-cy="financial-detail-section"', false);
            $response->assertSee('data-cy="financial-platform-card"', false);
            $response->assertSee('EXCLUDE (Tetap)');
            $response->assertSee('Rp 1.000.000');
            $response->assertSee('+ Rp 10.000');
            $response->assertSee('Rp 1.010.000');
            $response->assertSee('Pencairan Hak Bersih Penjual:');
            $response->assertSee('data-cy="snapshot-seller-net"', false);
        }

        // Non-pihak ditolak (TC-12-05)
        $this->actingAs($outsider)
            ->get(route('transactions.show', $transaction))
            ->assertStatus(403);
    }

    public function test_forum_transaction_displays_exclude_and_include_fee_modes_to_seller_and_simple_total_to_buyer(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        // 1. Forum EXCLUDE: Base Rp1.000.000 -> Fee Rp10.000, Buyer Total Rp1.010.000, Seller Net Rp1.000.000
        $txExclude = $this->createForumTransaction($buyer, $seller, 1000000.00, FeeType::EXCLUDE);

        $resExcludeBuyer = $this->actingAs($buyer)->get(route('transactions.show', $txExclude));
        $resExcludeBuyer->assertStatus(200);
        $resExcludeBuyer->assertSee('Rp 1.010.000');
        $resExcludeBuyer->assertDontSee('+ Rp 10.000');
        $resExcludeBuyer->assertDontSee('data-cy="snapshot-seller-net"', false);

        $resExcludeSeller = $this->actingAs($seller)->get(route('transactions.show', $txExclude));
        $resExcludeSeller->assertStatus(200);
        $resExcludeSeller->assertSee('EXCLUDE');
        $resExcludeSeller->assertSee('+ Rp 10.000');
        $resExcludeSeller->assertSee('Rp 1.010.000');
        $resExcludeSeller->assertSee('Rp 1.000.000');
        $resExcludeSeller->assertSee('data-cy="snapshot-seller-net"', false);

        // 2. Forum INCLUDE: Base Rp1.000.000 -> Fee Rp10.000, Buyer Total Rp1.000.000, Seller Net Rp990.000
        $txInclude = $this->createForumTransaction($buyer, $seller, 1000000.00, FeeType::INCLUDE);

        $resIncludeBuyer = $this->actingAs($buyer)->get(route('transactions.show', $txInclude));
        $resIncludeBuyer->assertStatus(200);
        $resIncludeBuyer->assertSee('Rp 1.000.000');
        $resIncludeBuyer->assertDontSee('- Rp 10.000');
        $resIncludeBuyer->assertDontSee('Rp 990.000');

        $resIncludeSeller = $this->actingAs($seller)->get(route('transactions.show', $txInclude));
        $resIncludeSeller->assertStatus(200);
        $resIncludeSeller->assertSee('INCLUDE');
        $resIncludeSeller->assertSee('- Rp 10.000');
        $resIncludeSeller->assertSee('Rp 1.000.000');
        $resIncludeSeller->assertSee('Rp 990.000');
        $resIncludeSeller->assertSee('data-cy="snapshot-seller-net"', false);
    }

    public function test_unverified_gateway_fee_is_never_displayed_as_zero_and_verified_gateway_fee_is_separated(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        // Transaksi dengan payment yang belum memiliki gateway_fee_amount (null)
        $txUnverified = $this->createMarketplaceTransaction($buyer, $seller, 200000.00);
        Payment::create([
            'transaction_id' => $txUnverified->transaction_id,
            'gateway_order_id' => 'TRX-UNVERIFIED-1',
            'payment_channel' => 'qris',
            'amount' => 205502.00,
            'gateway_fee_amount' => null,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now(),
        ]);

        $resUnverified = $this->actingAs($seller)->get(route('transactions.show', $txUnverified));
        $resUnverified->assertStatus(200);
        $resUnverified->assertSee('data-cy="gateway-fee-unverified"', false);
        $resUnverified->assertSee('Menunggu verifikasi');
        $resUnverified->assertDontSee('data-cy="gateway-fee-amount"', false);

        // Transaksi dengan gateway_fee_amount terverifikasi (mis. Rp4.000 pada bank_transfer)
        $txVerified = $this->createMarketplaceTransaction($buyer, $seller, 200000.00);
        Payment::create([
            'transaction_id' => $txVerified->transaction_id,
            'gateway_order_id' => 'TRX-VERIFIED-2',
            'payment_channel' => 'bank_transfer',
            'amount' => 205502.00,
            'gateway_fee_amount' => 4000.00,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now(),
        ]);

        $resVerified = $this->actingAs($seller)->get(route('transactions.show', $txVerified));
        $resVerified->assertStatus(200);
        $resVerified->assertSee('data-cy="gateway-fee-amount"', false);
        $resVerified->assertSee('Rp 4.000');
        $resVerified->assertSee('BANK_TRANSFER');
        // Pastikan snapshot fee platform (Rp 5.502), buyer_total (Rp 205.502), dan seller_net (Rp 200.000) tidak berubah
        $resVerified->assertSee('+ Rp 5.502');
        $resVerified->assertSee('Rp 205.502');
        $resVerified->assertSee('Rp 200.000');
    }

    public function test_keep_obligation_states_open_partial_settled_and_awaiting_gateway_fee_are_displayed_correctly(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $admin = User::factory()->create(['account_type' => 'ADMIN']);

        // 1. KEEP berstatus OPEN - BANK_VA_FLAT Rp4.000
        $txOpen = $this->createMarketplaceTransaction($buyer, $seller, 100000.00, [
            'status' => TransactionStatus::CANCELLED,
        ]);
        $cancelOpen = TransactionCancellation::create([
            'transaction_id' => $txOpen->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Stok habis mendadak',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
        ]);
        KeepObligation::create([
            'cancellation_id' => $cancelOpen->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'bank_transfer',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 1,
            'amount' => 4000.00,
            'remaining_amount' => 4000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        $resOpen = $this->actingAs($seller)->get(route('transactions.show', $txOpen));
        $resOpen->assertStatus(200);
        $resOpen->assertSee('data-cy="keep-obligation-status"', false);
        $resOpen->assertSee('Biaya Tetap Bank/VA (Rp 4.000)');
        $resOpen->assertSee('Rp 4.000');

        // 2. KEEP berstatus OPEN (Parsial) - 3x Gateway Fee (Total Rp12.000, Sisa Rp5.000)
        $txPartial = $this->createMarketplaceTransaction($buyer, $seller, 100000.00, [
            'status' => TransactionStatus::CANCELLED,
        ]);
        $cancelPartial = TransactionCancellation::create([
            'transaction_id' => $txPartial->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Kendala lisensi penjual',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
        ]);
        KeepObligation::create([
            'cancellation_id' => $cancelPartial->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'qris',
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 3,
            'amount' => 12000.00,
            'remaining_amount' => 5000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        $resPartial = $this->actingAs($seller)->get(route('transactions.show', $txPartial));
        $resPartial->assertStatus(200);
        $resPartial->assertSee('OPEN (Parsial)');
        $resPartial->assertSee('3x Biaya Gateway Pembayaran');
        $resPartial->assertSee('Rp 12.000');
        $resPartial->assertSee('Rp 5.000');

        // 3. KEEP berstatus SETTLED
        $txSettled = $this->createMarketplaceTransaction($buyer, $seller, 100000.00, [
            'status' => TransactionStatus::REFUNDED,
        ]);
        $cancelSettled = TransactionCancellation::create([
            'transaction_id' => $txSettled->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Pembatalan disetujui',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
        ]);
        KeepObligation::create([
            'cancellation_id' => $cancelSettled->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'bank_transfer',
            'calculation_rule' => KeepObligation::RULE_BANK_VA_FLAT,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 1,
            'amount' => 4000.00,
            'remaining_amount' => 0.00,
            'status' => KeepObligation::STATUS_SETTLED,
        ]);

        $resSettled = $this->actingAs($seller)->get(route('transactions.show', $txSettled));
        $resSettled->assertStatus(200);
        $resSettled->assertSee('SETTLED');

        // 4. Pembatalan SELLER yang menunggu verifikasi biaya gateway aktual (belum ada record KeepObligation)
        $txAwaitingFee = $this->createMarketplaceTransaction($buyer, $seller, 100000.00, [
            'status' => TransactionStatus::CANCELLED,
        ]);
        TransactionCancellation::create([
            'transaction_id' => $txAwaitingFee->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Menunggu verifikasi MDR',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
        ]);

        $resAwaiting = $this->actingAs($seller)->get(route('transactions.show', $txAwaitingFee));
        $resAwaiting->assertStatus(200);
        $resAwaiting->assertSee('data-cy="keep-awaiting-gateway-fee"', false);
        $resAwaiting->assertSee('Menunggu verifikasi biaya gateway');
    }

    public function test_settlement_payout_separates_seller_net_gross_keep_deduction_and_protects_seller_ledger_privacy(): void
    {
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $seller = User::factory()->create();
        $admin = User::factory()->create(['account_type' => 'ADMIN']);

        // Transaksi 1 (dibatalkan karena Seller -> timbul KEEP Rp12.000, sisa Rp5.000 setelah dipotong Rp7.000)
        $txOrigin = $this->createMarketplaceTransaction($buyer1, $seller, 150000.00, [
            'status' => TransactionStatus::REFUNDED,
        ]);
        $cancellationOrigin = TransactionCancellation::create([
            'transaction_id' => $txOrigin->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Gagal serah transaksi asal',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
        ]);
        $keepOrigin = KeepObligation::create([
            'cancellation_id' => $cancellationOrigin->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'gopay',
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 3,
            'amount' => 12000.00,
            'remaining_amount' => 5000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        // Transaksi 2 (selesai dengan seller_net = Rp100.000, dipotong KEEP Rp7.000 -> payout bersih Rp93.000)
        $txCurrent = $this->createMarketplaceTransaction($buyer2, $seller, 100000.00, [
            'status' => TransactionStatus::COMPLETED,
        ]);
        $settlement = Settlement::create([
            'transaction_id' => $txCurrent->transaction_id,
            'settlement_type' => 'PAYOUT',
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Seller Test',
            'gross_amount' => 100000.00,
            'keep_deducted_amount' => 7000.00,
            'amount' => 93000.00,
            'status' => 'COMPLETED',
            'recorded_by' => $admin->user_id,
            'reference_no' => 'SIM-PAYOUT-001',
            'completed_at' => now(),
        ]);
        KeepDeduction::create([
            'keep_obligation_id' => $keepOrigin->keep_obligation_id,
            'settlement_id' => $settlement->settlement_id,
            'amount' => 7000.00,
            'deducted_at' => now(),
        ]);

        // Seller melihat rincian utuh: seller_net Rp100.000, gross Rp100.000, potongan KEEP -Rp7.000, net payout Rp93.000, dan sisa KEEP transaksi lain
        $resSeller = $this->actingAs($seller)->get(route('transactions.show', $txCurrent));
        $resSeller->assertStatus(200);
        $resSeller->assertSee('data-cy="snapshot-seller-net"', false);
        $resSeller->assertSee('data-cy="settlement-gross-amount"', false);
        $resSeller->assertSee('data-cy="settlement-keep-deducted"', false);
        $resSeller->assertSee('- Rp 7.000');
        $resSeller->assertSee('data-cy="settlement-net-amount"', false);
        $resSeller->assertSee('Rp 93.000');
        $resSeller->assertSee('data-cy="seller-keep-ledger-summary"', false);
        $resSeller->assertSee('Rp 5.000');

        // Buyer2 hanya melihat total pembayarannya (Rp105.000) tanpa seller_net dan tanpa ledger KEEP milik Seller
        $resBuyer2 = $this->actingAs($buyer2)->get(route('transactions.show', $txCurrent));
        $resBuyer2->assertStatus(200);
        $resBuyer2->assertSee('Rp 105.000');
        $resBuyer2->assertDontSee('data-cy="snapshot-seller-net"', false);
        $resBuyer2->assertDontSee('data-cy="seller-keep-ledger-summary"', false);
    }

    public function test_agreement_snapshot_immutability_regression_when_listing_price_changes(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        // Finalkan transaksi dengan harga awal Rp1.000.000 (Fee Rp10.000, Total Rp1.010.000, Net Rp1.000.000)
        $transaction = $this->createMarketplaceTransaction($buyer, $seller, 1000000.00);

        // Ubah harga listing menjadi Rp5.000.000 setelah agreement FINALIZED
        $transaction->listing->update(['price' => 5000000.00]);

        // Panggil applyToAgreement pada agreement yang sudah FINALIZED -> tidak boleh mengubah nominal
        $this->feeService->applyToAgreement($transaction->agreement->fresh(), TransactionSourceType::MARKETPLACE);

        $responseSeller = $this->actingAs($seller)->get(route('transactions.show', $transaction));
        $responseSeller->assertStatus(200);
        $responseSeller->assertSee('Rp 1.000.000');
        $responseSeller->assertSee('+ Rp 10.000');
        $responseSeller->assertSee('Rp 1.010.000');
        $responseSeller->assertDontSee('Rp 5.000.000');
    }

    public function test_waiting_payment_deadline_time_travel_is_idempotent_and_preserves_financial_snapshot(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $transaction = $this->createMarketplaceTransaction($buyer, $seller, 500000.00, [
            'status' => TransactionStatus::WAITING_PAYMENT,
            'payment_due_at' => now()->addHour(),
            'delivery_due_at' => null,
        ]);

        // Time travel melewati batas pembayaran 1 jam (61 menit)
        $this->travel(61)->minutes();

        $resFirst = $this->actingAs($seller)->get(route('transactions.show', $transaction));
        $resFirst->assertStatus(200);
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->fresh()->status);

        $historyCountAfterFirst = $transaction->statusHistories()->count();

        // Akses berulang harus idempoten (tidak membuat riwayat status ganda) dan mempertahankan rincian finansial
        $resSecond = $this->actingAs($seller)->get(route('transactions.show', $transaction));
        $resSecond->assertStatus(200);
        $this->assertEquals($historyCountAfterFirst, $transaction->statusHistories()->count());

        $resSecond->assertSee('Rp 500.000');
        $resSecond->assertSee('+ Rp 7.006');
        $resSecond->assertSee('Rp 507.006');
        $resSecond->assertSee('data-cy="snapshot-seller-net"', false);
        $resSecond->assertSee('data-cy="gateway-fee-unverified"', false);
    }
}
