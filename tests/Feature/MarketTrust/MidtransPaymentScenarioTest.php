<?php

namespace Tests\Feature\MarketTrust;

use App\Contracts\Services\PaymentServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class MidtransPaymentScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected Agreement $agreement;

    protected Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => 'TEST_SERVER_KEY_123']);

        $this->buyer = User::factory()->create(['username' => 'buyer_midtrans']);
        $this->seller = User::factory()->create(['username' => 'seller_midtrans']);

        $category = ProductCategory::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['category_id' => $category->category_id, 'is_active' => true]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id, 'is_active' => true]);

        $this->agreement = Agreement::create([
            'created_by' => $this->buyer->user_id,
            'buyer_id' => $this->buyer->user_id,
            'seller_id' => $this->seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Voucher Game 100 Diamond',
            'item_description' => 'Kode voucher resmi',
            'quantity' => 1,
            'base_price' => 50000.0,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 2500.0,
            'buyer_total' => 52500.0,
            'seller_net' => 50000.0,
            'delivery_method' => 'Direct Message',
            'delivery_within_minutes' => 60,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        $this->transaction = Transaction::create([
            'agreement_id' => $this->agreement->agreement_id,
            'source_type' => TransactionSourceType::MARKETPLACE->value,
            'status' => TransactionStatus::WAITING_PAYMENT->value,
            'payment_due_at' => now()->addHour(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-08-01: Pembuatan Snap Token Pembayaran
     */
    public function test_tc_08_01_create_snap_token_success(): void
    {
        $mockPaymentService = Mockery::mock(PaymentServiceInterface::class);
        $mockPaymentService->shouldReceive('getOrCreateSnapToken')
            ->once()
            ->with(Mockery::on(fn ($tx) => $tx->transaction_id === $this->transaction->transaction_id), Mockery::any())
            ->andReturn([
                'token' => 'mock-snap-token-xyz-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-xyz-123',
                'order_id' => 'TRX-'.$this->transaction->transaction_id.'-17000000',
            ]);

        $this->app->instance(PaymentServiceInterface::class, $mockPaymentService);

        $response = $this->actingAs($this->buyer)
            ->postJson(route('transactions.payment-token', $this->transaction));

        $response->assertOk();
        $response->assertJson([
            'snap_token' => 'mock-snap-token-xyz-123',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/mock-snap-token-xyz-123',
        ]);
    }

    /**
     * TC-08-02: Webhook Notifikasi Pembayaran Berhasil (Settlement)
     */
    public function test_tc_08_02_webhook_notification_settlement_updates_status_to_paid(): void
    {
        $orderId = 'TRX-'.$this->transaction->transaction_id.'-PAY';
        $grossAmount = '52500.00';
        $statusCode = '200';
        $serverKey = 'TEST_SERVER_KEY_123';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        Payment::create([
            'transaction_id' => $this->transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 52500.00,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'midtrans-gateway-tx-001',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->transaction->refresh();
        $this->assertEquals('PAID', is_string($this->transaction->status) ? $this->transaction->status : $this->transaction->status->value);
    }

    /**
     * TC-08-03: Sinkronisasi Status Pembayaran Manual (Sync Payment)
     */
    public function test_tc_08_03_manual_payment_status_sync(): void
    {
        $mockPaymentService = Mockery::mock(PaymentServiceInterface::class);
        $mockPaymentService->shouldReceive('syncPaymentStatus')
            ->once()
            ->with(Mockery::on(fn ($tx) => $tx->transaction_id === $this->transaction->transaction_id))
            ->andReturn([
                'status' => 'synced',
                'transaction_status' => 'PAID',
                'message' => 'Status pembayaran berhasil disinkronisasi.',
            ]);

        $this->app->instance(PaymentServiceInterface::class, $mockPaymentService);

        $response = $this->actingAs($this->buyer)
            ->postJson(route('transactions.sync-payment', $this->transaction));

        $response->assertOk();
        $response->assertJson([
            'status' => 'synced',
            'transaction_status' => 'PAID',
        ]);
    }

    /**
     * TC-08-04: Penanganan Callback Terlambat (Late Payment Callback / UC-13B)
     */
    public function test_tc_08_04_late_payment_callback_held_for_reconciliation(): void
    {
        $this->transaction->update([
            'status' => TransactionStatus::CANCELLED->value,
        ]);

        $orderId = 'TRX-'.$this->transaction->transaction_id.'-LATE';
        $grossAmount = '52500.00';
        $statusCode = '200';
        $serverKey = 'TEST_SERVER_KEY_123';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        Payment::create([
            'transaction_id' => $this->transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 52500.00,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
            'expires_at' => now()->subHour(),
        ]);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'midtrans-gateway-tx-late',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertOk();
        $response->assertJson([
            'status' => 'held_for_reconciliation',
        ]);
    }

    /**
     * TC-08-05: Scheduler Pembatalan Pembayaran Kadaluarsa (CheckPaymentDeadlines / UC-13C)
     */
    public function test_tc_08_05_check_payment_deadlines_cancels_expired_transactions(): void
    {
        $this->transaction->update([
            'status' => TransactionStatus::WAITING_PAYMENT->value,
            'payment_due_at' => now()->subMinutes(15),
        ]);

        // Jalankan perintah scheduler console
        $exitCode = Artisan::call('transactions:check-payment-deadlines');
        $this->assertEquals(0, $exitCode);

        $this->transaction->refresh();
        $this->assertEquals('CANCELLED', is_string($this->transaction->status) ? $this->transaction->status : $this->transaction->status->value);
    }
}
