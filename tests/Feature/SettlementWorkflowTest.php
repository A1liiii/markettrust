<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\PayoutDestinationType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Complaint;
use App\Models\KeepDeduction;
use App\Models\KeepObligation;
use App\Models\Listing;
use App\Models\Payment;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettlementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createVariant(): ProductVariant
    {
        $category = ProductCategory::create([
            'category_name' => 'Kategori Settlement '.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Produk Settlement '.uniqid(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Settlement '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createActiveSellerPayoutAccount(User $seller, array $overrides = []): SellerPayoutAccount
    {
        return SellerPayoutAccount::create(array_merge([
            'seller_id' => $seller->user_id,
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => 'Seller Valid Holder',
            'is_active' => true,
        ], $overrides));
    }

    protected function createTransaction(
        User $buyer,
        User $seller,
        float $basePrice = 100000.00,
        float $feeAmount = 5000.00,
        array $transactionOverrides = [],
        array $agreementOverrides = []
    ): Transaction {
        $variant = $this->createVariant();

        $listing = Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Item Digital Settlement '.uniqid(),
            'description' => 'Deskripsi item digital untuk pengujian settlement',
            'price' => $basePrice,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);

        $agreement = Agreement::create(array_merge([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => $listing->title,
            'item_description' => $listing->description,
            'quantity' => 1,
            'base_price' => $basePrice,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => $feeAmount,
            'buyer_total' => $basePrice + $feeAmount,
            'seller_net' => $basePrice,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Snapshot pengujian settlement',
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED,
            'buyer_accepted_at' => now(),
            'seller_accepted_at' => now(),
            'finalized_at' => now(),
        ], $agreementOverrides));

        $tx = Transaction::create(array_merge([
            'agreement_id' => $agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE,
            'listing_id' => $listing->listing_id,
            'status' => TransactionStatus::CONFIRMED,
            'payment_due_at' => now()->subHours(2),
            'delivery_due_at' => now()->subHour(),
            'delivered_at' => now()->subMinutes(45),
            'confirmed_at' => now()->subMinutes(15),
        ], $transactionOverrides));

        Payment::create([
            'transaction_id' => $tx->transaction_id,
            'gateway_order_id' => 'ORD-STL-'.$tx->transaction_id.'-'.uniqid(),
            'payment_channel' => 'bank_transfer',
            'amount' => $agreement->buyer_total,
            'gateway_fee_amount' => 4000.00,
            'status' => 'PAID',
            'gateway_status' => 'settlement',
            'paid_at' => now()->subMinutes(90),
        ]);

        return $tx;
    }

    protected function createOriginKeepObligation(
        User $buyer,
        User $seller,
        User $admin,
        float $keepAmount = 12000.00,
        ?float $remainingAmount = null
    ): KeepObligation {
        $originTx = $this->createTransaction($buyer, $seller, 150000.00, 5000.00, [
            'status' => TransactionStatus::CANCELLED,
            'delivered_at' => null,
            'confirmed_at' => null,
        ]);

        $cancellation = TransactionCancellation::create([
            'transaction_id' => $originTx->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Kendala stok seller pada transaksi asal',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now()->subHour(),
        ]);

        return KeepObligation::create([
            'cancellation_id' => $cancellation->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'qris',
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => round($keepAmount / 3, 2),
            'multiplier' => 3,
            'amount' => $keepAmount,
            'remaining_amount' => $remainingAmount ?? $keepAmount,
            'status' => KeepObligation::STATUS_OPEN,
        ]);
    }

    public function test_payout_succeeds_for_confirmed_transaction_and_resolved_payout_complaint(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $this->createActiveSellerPayoutAccount($seller, [
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'Mandiri',
            'destination_number' => '9988776655',
            'destination_holder' => 'PT Seller Mandiri',
        ]);

        // 1. Transaksi CONFIRMED normal (TC-19-01)
        $txConfirmed = $this->createTransaction($buyer, $seller, 200000.00, 5502.00);
        $evidence = UploadedFile::fake()->create('bukti-payout-1.pdf', 200, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('admin.settlements.payout', $txConfirmed), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-CONF-001',
            'note' => 'Pencairan normal selesai',
            'evidence_file' => $evidence,
        ]);

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');

        $txConfirmed->refresh();
        $this->assertEquals(TransactionStatus::COMPLETED, $txConfirmed->status);
        $this->assertNotNull($txConfirmed->closed_at);

        $settlement1 = $txConfirmed->settlement;
        $this->assertNotNull($settlement1);
        $this->assertEquals(Settlement::TYPE_PAYOUT, $settlement1->settlement_type);
        $this->assertEquals(Settlement::STATUS_COMPLETED, $settlement1->status);
        $this->assertEquals('BANK', $settlement1->destination_type);
        $this->assertEquals('Mandiri', $settlement1->destination_provider);
        $this->assertEquals('9988776655', $settlement1->destination_number);
        $this->assertEquals('PT Seller Mandiri', $settlement1->destination_holder);
        $this->assertEquals(200000.00, (float) $settlement1->gross_amount);
        $this->assertEquals(0.00, (float) $settlement1->keep_deducted_amount);
        $this->assertEquals(200000.00, (float) $settlement1->amount);
        $this->assertEquals('SIM-PAYOUT-CONF-001', $settlement1->reference_no);
        $this->assertEquals($admin->user_id, $settlement1->recorded_by);
        $this->assertCount(1, $settlement1->evidenceFiles);

        // 2. Transaksi DISPUTED dengan keputusan komplain RESOLVED = PAYOUT (TC-19-02)
        $txDisputePayout = $this->createTransaction($buyer, $seller, 300000.00, 6000.00, [
            'status' => TransactionStatus::DISPUTED,
            'confirmed_at' => null,
        ]);
        $complaint = Complaint::create([
            'transaction_id' => $txDisputePayout->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Klaim pembeli tidak terbukti',
            'status' => 'RESOLVED',
            'decision' => 'PAYOUT',
            'decision_reason' => 'Bukti penyerahan seller sah',
            'decided_by' => $admin->user_id,
            'decided_at' => now()->subMinutes(10),
        ]);

        $resDispute = $this->actingAs($admin)->post(route('admin.settlements.payout', $txDisputePayout), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-DISP-002',
            'note' => 'Payout atas keputusan komplain',
        ]);

        $resDispute->assertRedirect(route('admin.transactions.index'));
        $txDisputePayout->refresh();
        $this->assertEquals(TransactionStatus::COMPLETED, $txDisputePayout->status);
        $this->assertEquals($complaint->complaint_id, $txDisputePayout->settlement->complaint_id);
        $this->assertEquals(300000.00, (float) $txDisputePayout->settlement->amount);
    }

    public function test_payout_is_held_when_seller_destination_is_missing_or_active_complaint_exists_or_not_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $sellerWithoutAccount = User::factory()->create();
        $sellerWithAccount = User::factory()->create();
        $this->createActiveSellerPayoutAccount($sellerWithAccount);

        // 1. Tujuan payout Seller belum ada -> ditahan
        $txNoDest = $this->createTransaction($buyer, $sellerWithoutAccount, 100000.00);
        $resNoDest = $this->actingAs($admin)->post(route('admin.settlements.payout', $txNoDest), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'REF-FAIL-1',
        ]);
        $resNoDest->assertSessionHas('error');
        $this->assertEquals(TransactionStatus::CONFIRMED, $txNoDest->fresh()->status);
        $this->assertNull($txNoDest->fresh()->settlement);

        // 2. Transaksi masih memiliki komplain aktif (OPEN / REVIEWING) -> ditahan (TC-19-03)
        $txActiveComplaint = $this->createTransaction($buyer, $sellerWithAccount, 100000.00, 5000.00, [
            'status' => TransactionStatus::DISPUTED,
        ]);
        Complaint::create([
            'transaction_id' => $txActiveComplaint->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Item bermasalah',
            'status' => 'OPEN',
        ]);

        $resComplaint = $this->actingAs($admin)->post(route('admin.settlements.payout', $txActiveComplaint), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'REF-FAIL-2',
        ]);
        $resComplaint->assertSessionHas('error');
        $this->assertEquals(TransactionStatus::DISPUTED, $txActiveComplaint->fresh()->status);
        $this->assertNull($txActiveComplaint->fresh()->settlement);

        // 3. Transaksi belum CONFIRMED (masih DELIVERED) -> ditolak (TC-19-03)
        $txDelivered = $this->createTransaction($buyer, $sellerWithAccount, 100000.00, 5000.00, [
            'status' => TransactionStatus::DELIVERED,
            'confirmed_at' => null,
        ]);
        $resDelivered = $this->actingAs($admin)->post(route('admin.settlements.payout', $txDelivered), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'REF-FAIL-3',
        ]);
        $resDelivered->assertSessionHas('error');
        $this->assertEquals(TransactionStatus::DELIVERED, $txDelivered->fresh()->status);
    }

    public function test_payout_keep_deduction_handles_full_settle_and_carryover_when_keep_exceeds_gross_down_to_zero(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $this->createActiveSellerPayoutAccount($seller);

        // Buat kewajiban KEEP OPEN sebesar Rp 75.000 dari transaksi asal yang batal karena kesalahan Seller
        $keepObligation = $this->createOriginKeepObligation($buyer, $seller, $admin, 75000.00);

        // Payout 1: seller_net = Rp 50.000 (lebih kecil dari KEEP Rp 75.000)
        // Harus memotong maksimal sebesar gross_amount (Rp 50.000), net payout = Rp 0 (tidak negatif!), sisa KEEP = Rp 25.000 tetap OPEN
        $txPayout1 = $this->createTransaction($buyer, $seller, 50000.00, 5000.00);

        $res1 = $this->actingAs($admin)->post(route('admin.settlements.payout', $txPayout1), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-KEEP-PARTIAL',
            'note' => 'Potong KEEP hingga Rp 0',
        ]);
        $res1->assertRedirect(route('admin.transactions.index'));
        $res1->assertSessionHas('success');

        $settlement1 = $txPayout1->fresh()->settlement;
        $this->assertNotNull($settlement1);
        $this->assertEquals(50000.00, (float) $settlement1->gross_amount);
        $this->assertEquals(50000.00, (float) $settlement1->keep_deducted_amount);
        $this->assertEquals(0.00, (float) $settlement1->amount);
        $this->assertEquals(Settlement::STATUS_COMPLETED, $settlement1->status);
        $this->assertEquals(TransactionStatus::COMPLETED, $txPayout1->fresh()->status);

        $keepObligation->refresh();
        $this->assertEquals(KeepObligation::STATUS_OPEN, $keepObligation->status);
        $this->assertEquals(25000.00, (float) $keepObligation->remaining_amount);

        // Payout 2 berikutnya: seller_net = Rp 60.000
        // Sisa KEEP Rp 25.000 dipotong dari Rp 60.000 -> net payout = Rp 35.000, dan KeepObligation menjadi SETTLED (remaining_amount = 0)
        $txPayout2 = $this->createTransaction($buyer, $seller, 60000.00, 5000.00);

        $res2 = $this->actingAs($admin)->post(route('admin.settlements.payout', $txPayout2), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-KEEP-SETTLED',
            'note' => 'Pelunasan sisa KEEP',
        ]);
        $res2->assertRedirect(route('admin.transactions.index'));
        $res2->assertSessionHas('success');

        $settlement2 = $txPayout2->fresh()->settlement;
        $this->assertEquals(60000.00, (float) $settlement2->gross_amount);
        $this->assertEquals(25000.00, (float) $settlement2->keep_deducted_amount);
        $this->assertEquals(35000.00, (float) $settlement2->amount);

        $keepObligation->refresh();
        $this->assertEquals(KeepObligation::STATUS_SETTLED, $keepObligation->status);
        $this->assertEquals(0.00, (float) $keepObligation->remaining_amount);
        $this->assertEquals(2, KeepDeduction::where('keep_obligation_id', $keepObligation->keep_obligation_id)->count());
    }

    public function test_failed_and_unknown_payout_attempts_are_recorded_without_finalizing_keep_or_transaction_and_retry_uses_same_settlement(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $payoutAccount = $this->createActiveSellerPayoutAccount($seller, [
            'destination_type' => PayoutDestinationType::BANK,
            'destination_provider' => 'BCA',
            'destination_number' => '0000111122',
            'destination_holder' => 'Nama Lama Salah',
        ]);

        // Seller punya KEEP OPEN Rp 4.000 dari transaksi asal lain
        $keepOrigin = $this->createOriginKeepObligation($buyer, $seller, $admin, 4000.00);
        $initialKeepCount = KeepObligation::count();

        $tx = $this->createTransaction($buyer, $seller, 100000.00, 5000.00);

        // 1. Percobaan pertama: FAILED karena tujuan Seller ditolak (TC-34-02)
        $resFail = $this->actingAs($admin)->post(route('admin.settlements.payout', $tx), [
            'execution_outcome' => 'FAILED',
            'failure_category' => 'SELLER_DESTINATION',
            'failure_reason' => 'Nomor rekening BCA ditolak oleh bank penerima',
            'follow_up_action' => 'Meminta seller memperbarui nomor rekening aktif',
            'reference_no' => 'ATTEMPT-FAIL-01',
        ]);
        $resFail->assertRedirect(route('admin.transactions.index'));

        $tx->refresh();
        $keepOrigin->refresh();

        // Pastikan TIDAK menyelesaikan transaksi, TIDAK memotong KEEP, dan TIDAK membuat KEEP baru
        $this->assertEquals(TransactionStatus::CONFIRMED, $tx->status);
        $this->assertEquals(4000.00, (float) $keepOrigin->remaining_amount);
        $this->assertEquals(KeepObligation::STATUS_OPEN, $keepOrigin->status);
        $this->assertEquals(0, KeepDeduction::count());
        $this->assertEquals($initialKeepCount, KeepObligation::count());

        $settlement = $tx->settlement;
        $this->assertNotNull($settlement);
        $this->assertEquals(Settlement::STATUS_PROCESSING, $settlement->status);
        $this->assertCount(1, $settlement->attempts);
        $this->assertTrue($settlement->requiresSellerDestinationUpdate());

        // 2. Percobaan kedua: UNKNOWN karena gangguan sistem + pengumuman gangguan luas (TC-34-01)
        $resUnknown = $this->actingAs($admin)->post(route('admin.settlements.payout', $tx), [
            'execution_outcome' => 'UNKNOWN',
            'failure_category' => 'SYSTEM',
            'failure_reason' => 'Timeout koneksi simulasi mutasi bank',
            'reference_no' => 'ATTEMPT-UNK-02',
            'announce_disruption' => '1',
        ]);
        $resUnknown->assertRedirect(route('admin.transactions.index'));

        $this->assertEquals($settlement->settlement_id, $tx->fresh()->settlement->settlement_id);
        $this->assertEquals(2, SettlementAttempt::where('settlement_id', $settlement->settlement_id)->count());
        $this->assertEquals(TransactionStatus::CONFIRMED, $tx->fresh()->status);
        $this->assertEquals(4000.00, (float) $keepOrigin->fresh()->remaining_amount);

        // 3. Seller memperbaiki rekening payout aktif (UC-26), lalu Admin melakukan sinkronisasi tujuan & retry SUCCESS pada settlement yang sama (UC-29B)
        $payoutAccount->update([
            'destination_Provider' => 'BNI',
            'destination_provider' => 'BNI',
            'destination_number' => '8899001122',
            'destination_holder' => 'Seller BNI Valid',
        ]);

        $resSync = $this->actingAs($admin)->post(route('admin.settlements.sync-destination', $tx));
        $resSync->assertRedirect(route('admin.transactions.index'));

        $settlement->refresh();
        $this->assertEquals('BNI', $settlement->destination_provider);
        $this->assertEquals('8899001122', $settlement->destination_number);
        $this->assertEquals(Settlement::STATUS_READY, $settlement->status);
        $this->assertFalse($settlement->requiresSellerDestinationUpdate());

        $resRetrySuccess = $this->actingAs($admin)->post(route('admin.settlements.payout', $tx), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-RETRY-OK',
            'note' => 'Berhasil pada percobaan ke-3 setelah perbaikan rekening',
        ]);
        $resRetrySuccess->assertRedirect(route('admin.transactions.index'));

        $this->assertEquals(1, Settlement::where('transaction_id', $tx->transaction_id)->count());
        $settlement->refresh();
        $this->assertEquals(Settlement::STATUS_COMPLETED, $settlement->status);
        $this->assertEquals(96000.00, (float) $settlement->amount);
        $this->assertEquals(4000.00, (float) $settlement->keep_deducted_amount);
        $this->assertEquals(3, SettlementAttempt::where('settlement_id', $settlement->settlement_id)->count());
        $this->assertEquals(TransactionStatus::COMPLETED, $tx->fresh()->status);
        $this->assertEquals(KeepObligation::STATUS_SETTLED, $keepOrigin->fresh()->status);
    }

    public function test_refund_from_complaint_and_post_payment_cancellation_does_not_deduct_seller_keep_from_buyer_and_validates_inputs(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        // 1. Refund dari pembatalan pasca-bayar (APPROVED) karena kesalahan SELLER (timbul KEEP Rp 12.000 pada Seller)
        // KEEP Seller TIDAK memotong refund Buyer (Buyer tetap menerima penuh buyer_total = Rp 205.502)
        $txCancelRefund = $this->createTransaction($buyer, $seller, 200000.00, 5502.00, [
            'status' => TransactionStatus::CANCELLED,
            'delivered_at' => null,
            'confirmed_at' => null,
        ]);
        $cancel = TransactionCancellation::create([
            'transaction_id' => $txCancelRefund->transaction_id,
            'requested_by' => $seller->user_id,
            'stage' => TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY,
            'reason' => 'Seller gagal menyediakan lisensi',
            'status' => TransactionCancellation::STATUS_APPROVED,
            'responsible_party' => TransactionCancellation::PARTY_SELLER,
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now()->subMinutes(20),
        ]);
        KeepObligation::create([
            'cancellation_id' => $cancel->cancellation_id,
            'seller_id' => $seller->user_id,
            'payment_channel_snapshot' => 'qris',
            'calculation_rule' => KeepObligation::RULE_OTHER_TRIPLE_GATEWAY_FEE,
            'fee_basis_amount' => 4000.00,
            'multiplier' => 3,
            'amount' => 12000.00,
            'remaining_amount' => 12000.00,
            'status' => KeepObligation::STATUS_OPEN,
        ]);

        // Uji nominal salah (TC-20-03) atau negatif (TC-20-07) -> ditolak, belum menjadi REFUNDED
        $resWrongAmount = $this->actingAs($admin)->post(route('admin.settlements.refund', $txCancelRefund), [
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '1122334455',
            'destination_holder' => 'Buyer Valid',
            'reference_no' => 'REF-WRONG',
            'amount' => 193502.00, // mencoba memotong KEEP dari refund buyer -> harus ditolak!
        ]);
        $resWrongAmount->assertSessionHas('error');
        $this->assertEquals(TransactionStatus::CANCELLED, $txCancelRefund->fresh()->status);

        // Uji tujuan tidak lengkap (TC-20-03) -> ditolak
        $resIncompleteDest = $this->actingAs($admin)->post(route('admin.settlements.refund', $txCancelRefund), [
            'destination_type' => 'BANK',
            'destination_provider' => '',
            'destination_number' => '',
            'destination_holder' => 'Buyer Valid',
            'reference_no' => 'REF-INCOMPLETE',
        ]);
        $resIncompleteDest->assertSessionHas('error');
        $this->assertEquals(TransactionStatus::CANCELLED, $txCancelRefund->fresh()->status);

        // Eksekusi Refund Valid -> baru berubah menjadi REFUNDED setelah sukses
        $evidence = UploadedFile::fake()->image('bukti-refund.jpg');
        $resValidRefund = $this->actingAs($admin)->post(route('admin.settlements.refund', $txCancelRefund), [
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '1122334455',
            'destination_holder' => 'Buyer Valid',
            'reference_no' => 'SIM-REFUND-001',
            'note' => 'Refund penuh buyer_total tanpa dipotong KEEP seller',
            'evidence_file' => $evidence,
        ]);
        $resValidRefund->assertRedirect(route('admin.transactions.index'));
        $resValidRefund->assertSessionHas('success');

        $txCancelRefund->refresh();
        $this->assertEquals(TransactionStatus::REFUNDED, $txCancelRefund->status);
        $this->assertEquals(Settlement::TYPE_REFUND, $txCancelRefund->settlement->settlement_type);
        $this->assertEquals(Settlement::STATUS_COMPLETED, $txCancelRefund->settlement->status);
        $this->assertEquals(205502.00, (float) $txCancelRefund->settlement->amount);
        $this->assertCount(1, $txCancelRefund->settlement->evidenceFiles);

        // 2. Refund dari keputusan komplain RESOLVED = REFUND (UC-18 -> UC-20)
        $txComplaintRefund = $this->createTransaction($buyer, $seller, 150000.00, 5000.00, [
            'status' => TransactionStatus::DISPUTED,
            'confirmed_at' => null,
        ]);
        Complaint::create([
            'transaction_id' => $txComplaintRefund->transaction_id,
            'raised_by' => $buyer->user_id,
            'reason' => 'Lisensi tidak valid',
            'status' => 'RESOLVED',
            'decision' => 'REFUND',
            'decision_reason' => 'Komplain buyer terbukti sah',
            'decided_by' => $admin->user_id,
            'decided_at' => now()->subMinutes(5),
        ]);

        $resComplaintRefund = $this->actingAs($admin)->post(route('admin.settlements.refund', $txComplaintRefund), [
            'destination_type' => 'EWALLET',
            'destination_provider' => 'GoPay',
            'destination_number' => '081234567890',
            'destination_holder' => 'Buyer GoPay',
            'reference_no' => 'SIM-REFUND-002',
        ]);
        $resComplaintRefund->assertRedirect(route('admin.transactions.index'));
        $this->assertEquals(TransactionStatus::REFUNDED, $txComplaintRefund->fresh()->status);
        $this->assertEquals(155000.00, (float) $txComplaintRefund->fresh()->settlement->amount);
    }

    public function test_authorization_idempotency_duplicate_prevention_and_keep_ledger_privacy(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $outsider = User::factory()->create();

        $this->createActiveSellerPayoutAccount($seller);
        $keepOrigin = $this->createOriginKeepObligation($buyer, $seller, $admin, 4000.00);
        $tx = $this->createTransaction($buyer, $seller, 100000.00, 5000.00);

        // 1. Pelaku non-admin (Buyer / Seller) ditolak (403) saat mencoba mencatat payout atau refund (TC-19-04 & TC-20-04)
        $this->actingAs($seller)
            ->post(route('admin.settlements.payout', $tx), [
                'execution_outcome' => 'SUCCESS',
                'reference_no' => 'HACK-01',
            ])
            ->assertForbidden();

        $this->actingAs($buyer)
            ->post(route('admin.settlements.refund', $tx), [
                'destination_type' => 'BANK',
                'destination_provider' => 'BCA',
                'destination_number' => '12345',
                'destination_holder' => 'Buyer',
                'reference_no' => 'HACK-02',
            ])
            ->assertForbidden();

        // 2. Admin menyelesaikan Payout pertama kali -> Berhasil
        $this->actingAs($admin)->post(route('admin.settlements.payout', $tx), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-FIRST',
        ])->assertSessionHas('success');

        $this->assertEquals(TransactionStatus::COMPLETED, $tx->fresh()->status);
        $this->assertEquals(1, Settlement::where('transaction_id', $tx->transaction_id)->count());
        $this->assertEquals(1, KeepDeduction::where('keep_obligation_id', $keepOrigin->keep_obligation_id)->count());

        // 3. Submit ulang Payout atau mencoba Refund setelah Payout selesai -> Ditolak & tidak mengubah data (TC-19-05 & TC-20-05)
        $this->actingAs($admin)->post(route('admin.settlements.payout', $tx), [
            'execution_outcome' => 'SUCCESS',
            'reference_no' => 'SIM-PAYOUT-SECOND',
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.settlements.refund', $tx), [
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '12345',
            'destination_holder' => 'Buyer',
            'reference_no' => 'SIM-REFUND-AFTER-PAYOUT',
        ])->assertSessionHas('error');

        $this->assertEquals('SIM-PAYOUT-FIRST', $tx->fresh()->settlement->reference_no);
        $this->assertEquals(1, Settlement::where('transaction_id', $tx->transaction_id)->count());
        $this->assertEquals(1, KeepDeduction::where('keep_obligation_id', $keepOrigin->keep_obligation_id)->count());

        // 4. Kerahasiaan Ledger KEEP (TC-34-06): Buyer / Outsider ditolak (403) saat membuka ledger KEEP milik Seller
        $this->actingAs($buyer)
            ->get(route('sellers.keep-ledger', $seller))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('sellers.keep-ledger', $seller))
            ->assertForbidden();

        // Seller pemilik dan Admin berhak mengakses (200)
        $this->actingAs($seller)
            ->get(route('sellers.keep-ledger', $seller))
            ->assertOk()
            ->assertSee('#KEEP-'.$keepOrigin->keep_obligation_id);

        $this->actingAs($admin)
            ->get(route('sellers.keep-ledger', $seller))
            ->assertOk()
            ->assertSee('#KEEP-'.$keepOrigin->keep_obligation_id);
    }
}
