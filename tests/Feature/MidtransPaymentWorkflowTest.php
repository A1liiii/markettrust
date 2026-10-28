<?php

namespace Tests\Feature;

use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Services\MidtransPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MidtransPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected string $testServerKey = 'SB-Mid-server-test-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.midtrans.server_key', $this->testServerKey);
        Config::set('services.midtrans.client_key', 'SB-Mid-client-test-key-12345');
        Config::set('services.midtrans.is_production', false);
    }

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Kategori Game '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Varian Mythic '.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createListing(User $seller): Listing
    {
        $variant = $this->createVariant();

        return Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Murah Meriah',
            'description' => 'Akun pol siap pakai',
            'price' => 100000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Helper to create a test transaction with agreement.
     */
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
            'base_price' => 100000.00,
            'fee_type' => FeeType::EXCLUDE,
            'fee_amount' => 5000.00,
            'buyer_total' => 105000.00,
            'seller_net' => 100000.00,
            'delivery_method' => 'DIRECT_CHAT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Kesepakatan pengujian',
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

    /**
     * Bind a mock Snap API service to avoid actual HTTP calls during testing.
     */
    protected function bindMockMidtransService(): void
    {
        $mockService = new class($this->app->make(TransactionServiceInterface::class)) extends MidtransPaymentService
        {
            protected function requestSnapTransaction(array $params): object
            {
                return (object) [
                    'token' => 'dummy-snap-token-12345',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/dummy-snap-token-12345',
                ];
            }
        };

        $this->app->instance(PaymentServiceInterface::class, $mockService);
    }

    public function test_buyer_can_generate_snap_token_for_waiting_payment_transaction(): void
    {
        $this->bindMockMidtransService();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $response = $this->actingAs($buyer)->postJson(route('transactions.payment-token', $transaction));

        $response->assertStatus(200);
        $response->assertJson([
            'snap_token' => 'dummy-snap-token-12345',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/dummy-snap-token-12345',
        ]);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => 105000.00,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
        ]);
    }

    public function test_non_buyer_cannot_generate_snap_token(): void
    {
        $this->bindMockMidtransService();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        // Seller tries to pay
        $response1 = $this->actingAs($seller)->postJson(route('transactions.payment-token', $transaction));
        $response1->assertStatus(422);

        // Other user tries to pay
        $response2 = $this->actingAs($otherUser)->postJson(route('transactions.payment-token', $transaction));
        $response2->assertStatus(422);
    }

    public function test_cannot_generate_snap_token_if_transaction_is_not_waiting_payment(): void
    {
        $this->bindMockMidtransService();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller, [
            'status' => TransactionStatus::PAID,
        ]);

        $response = $this->actingAs($buyer)->postJson(route('transactions.payment-token', $transaction));
        $response->assertStatus(422);
    }

    public function test_cannot_generate_snap_token_if_payment_due_has_passed(): void
    {
        $this->bindMockMidtransService();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller, [
            'payment_due_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($buyer)->postJson(route('transactions.payment-token', $transaction));
        $response->assertStatus(422);
    }

    public function test_valid_settlement_webhook_updates_payment_and_transaction_to_paid(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
            'latest_payload' => ['snap_token' => 'dummy-token'],
            'expires_at' => now()->addHour(),
        ]);

        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'midtrans-gateway-tx-999',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'status' => 'PAID',
            'payment_channel' => 'bank_transfer',
            'gateway_transaction_id' => 'midtrans-gateway-tx-999',
            'gateway_status' => 'settlement',
        ]);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
        $this->assertNotNull($transaction->delivery_due_at);

        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'from_status' => 'WAITING_PAYMENT',
            'to_status' => 'PAID',
        ]);
    }

    public function test_invalid_signature_webhook_is_rejected(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
        ]);

        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '105000.00',
            'signature_key' => 'invalid-forged-signature',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertStatus(403);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::WAITING_PAYMENT, $transaction->status);
    }

    public function test_idempotent_duplicate_webhook_does_not_duplicate_status_histories(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
        ]);

        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'midtrans-gateway-tx-999',
        ];

        // First notification
        $response1 = $this->postJson(route('midtrans.notification'), $payload);
        $response1->assertStatus(200);
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());

        // Second notification (duplicate webhook callback from Midtrans)
        $response2 = $this->postJson(route('midtrans.notification'), $payload);
        $response2->assertStatus(200);
        $response2->assertJson([
            'status' => 'already_paid',
        ]);

        // History count MUST still be 1 (no duplicate entries)
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());
    }

    public function test_expired_webhook_updates_payment_and_cancels_transaction(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
        ]);

        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'expire',
            'payment_type' => 'bank_transfer',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transaction->transaction_id,
            'status' => 'EXPIRED',
            'gateway_status' => 'expire',
        ]);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->status);
    }

    public function test_transaction_show_page_displays_pay_button_for_buyer(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        // Buyer view
        $responseBuyer = $this->actingAs($buyer)->get(route('transactions.show', $transaction));
        $responseBuyer->assertStatus(200);
        $responseBuyer->assertSee('data-cy="btn-pay-midtrans"', false);
        $responseBuyer->assertSee('Bayar Sekarang (Escrow)');

        // Seller view
        $responseSeller = $this->actingAs($seller)->get(route('transactions.show', $transaction));
        $responseSeller->assertStatus(200);
        $responseSeller->assertDontSee('data-cy="btn-pay-midtrans"', false);
        $responseSeller->assertSee('Menunggu Pembeli Membayar');
    }

    public function test_buyer_can_sync_payment_status_directly_from_gateway(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
        ]);

        $mockService = new class($this->app->make(TransactionServiceInterface::class), $orderId, $statusCode, $grossAmount, $signature) extends MidtransPaymentService
        {
            public function __construct(
                TransactionServiceInterface $transactionService,
                private string $orderId,
                private string $statusCode,
                private string $grossAmount,
                private string $signature
            ) {
                parent::__construct($transactionService);
            }

            protected function queryMidtransTransactionStatus(string $orderId): object
            {
                return (object) [
                    'order_id' => $this->orderId,
                    'status_code' => $this->statusCode,
                    'gross_amount' => $this->grossAmount,
                    'signature_key' => $this->signature,
                    'transaction_status' => 'settlement',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'synced-gateway-tx-555',
                ];
            }
        };

        $this->app->instance(PaymentServiceInterface::class, $mockService);

        $response = $this->actingAs($buyer)->postJson(route('transactions.sync-payment', $transaction));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $transaction->refresh();
        $this->assertEquals(TransactionStatus::PAID, $transaction->status);
    }

    public function test_settlement_sets_delivery_due_at_to_exactly_one_hour_from_paid_at_regardless_of_agreement_delivery_minutes(): void
    {
        $verifiedAt = now()->startOfSecond();
        $this->travelTo($verifiedAt);

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller, [], [
            'delivery_within_minutes' => 15,
        ]);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';

        $payment = Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
            'gateway_status' => 'pending',
        ]);

        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'midtrans-gateway-tx-1001',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);
        $response->assertStatus(200)->assertJson(['status' => 'success']);

        $payment->refresh();
        $transaction->refresh();

        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($transaction->delivery_due_at);
        $this->assertTrue($payment->paid_at->equalTo($verifiedAt));
        $this->assertTrue($transaction->delivery_due_at->equalTo($verifiedAt->copy()->addHour()));
        $this->assertSame(3600, (int) $payment->paid_at->diffInSeconds($transaction->delivery_due_at, false));
    }

    public function test_repeated_callback_and_sync_do_not_shift_paid_at_or_delivery_deadline(): void
    {
        $initialPaidAt = now()->startOfSecond();
        $this->travelTo($initialPaidAt);

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller, [], [
            'delivery_within_minutes' => 1440,
        ]);

        $orderId = 'TRX-'.$transaction->transaction_id.'-'.time();
        $grossAmount = '105000.00';
        $statusCode = '200';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->testServerKey);

        $payment = Payment::create([
            'transaction_id' => $transaction->transaction_id,
            'gateway_order_id' => $orderId,
            'amount' => 105000.00,
            'status' => 'PENDING',
        ]);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'midtrans-gateway-tx-2002',
        ];

        // 1. Callback pertama pada t0
        $this->postJson(route('midtrans.notification'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $payment->refresh();
        $transaction->refresh();
        $expectedDeadline = $initialPaidAt->copy()->addHour();

        $this->assertTrue($payment->paid_at->equalTo($initialPaidAt));
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedDeadline));

        // 2. Majukan waktu +20 menit lalu kirim callback duplikat
        $this->travel(20)->minutes();

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'already_paid']);

        $payment->refresh();
        $transaction->refresh();
        $this->assertTrue($payment->paid_at->equalTo($initialPaidAt));
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedDeadline));

        // 3. Majukan waktu +10 menit lagi lalu panggil sync-payment
        $this->travel(10)->minutes();

        $this->actingAs($buyer)->postJson(route('transactions.sync-payment', $transaction))
            ->assertStatus(200)
            ->assertJson(['status' => 'already_paid']);

        $payment->refresh();
        $transaction->refresh();
        $this->assertTrue($payment->paid_at->equalTo($initialPaidAt));
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedDeadline));

        // 4. Transaksi beralih ke DELIVERED, lalu callback settlement terlambat datang lagi +15 menit kemudian
        $transaction->update(['status' => TransactionStatus::DELIVERED]);
        $this->travel(15)->minutes();

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'already_paid']);

        $payment->refresh();
        $transaction->refresh();
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->status);
        $this->assertTrue($payment->paid_at->equalTo($initialPaidAt));
        $this->assertTrue($transaction->delivery_due_at->equalTo($expectedDeadline));
        $this->assertEquals(1, TransactionStatusHistory::where('transaction_id', $transaction->transaction_id)->count());
    }
}
