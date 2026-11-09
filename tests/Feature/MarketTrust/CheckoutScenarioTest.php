<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\FeeType;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\SellerPayoutAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected ProductVariant $variant;

    protected Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create(['username' => 'buyer_checkout']);
        $this->seller = User::factory()->create(['username' => 'seller_checkout']);

        $category = ProductCategory::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['category_id' => $category->category_id, 'is_active' => true]);
        $this->variant = ProductVariant::factory()->create(['product_id' => $product->product_id, 'is_active' => true]);

        $this->listing = Listing::factory()->create([
            'seller_id' => $this->seller->user_id,
            'variant_id' => $this->variant->variant_id,
            'title' => 'Akun Game Premium Level 100',
            'price' => 50000,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-07-01: Halaman Review Checkout Menampilkan Pemisahan Rincian Harga Transparan
     */
    public function test_tc_07_01_checkout_review_displays_transparent_price_breakdown(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('marketplace.checkout.review', $this->listing));

        $response->assertOk();
        $response->assertViewIs('marketplace.checkout');

        $viewData = $response->viewData('feeCalculation');
        $this->assertNotNull($viewData);
        $this->assertEquals(50000.0, $viewData->basePrice);
        $this->assertEquals(FeeType::EXCLUDE, $viewData->feeType);
        $this->assertGreaterThan(0, $viewData->feeAmount);
        $this->assertEquals(50000.0 + $viewData->feeAmount, $viewData->buyerTotal);
    }

    /**
     * TC-07-02: Peringatan Edukatif Saat Penjual Belum Mengatur Rekening Payout
     */
    public function test_tc_07_02_warning_displayed_when_seller_has_no_payout_account(): void
    {
        // Seller belum memiliki rekening payout aktif
        $response = $this->actingAs($this->buyer)->get(route('marketplace.checkout.review', $this->listing));

        $response->assertOk();
        $this->assertFalse($response->viewData('hasSellerPayout'));
        $response->assertSee('Tujuan Pencairan:', false);
    }

    /**
     * TC-07-03: Indikator Siap Saat Penjual Sudah Memiliki Rekening Payout Aktif
     */
    public function test_tc_07_03_ready_indicator_when_seller_has_active_payout_account(): void
    {
        SellerPayoutAccount::factory()->bank()->create([
            'seller_id' => $this->seller->user_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->buyer)->get(route('marketplace.checkout.review', $this->listing));

        $response->assertOk();
        $this->assertTrue($response->viewData('hasSellerPayout'));
        $response->assertSee('Tujuan Pencairan Penjual Siap:', false);
    }

    /**
     * TC-07-04: Konfirmasi Checkout Membuat Transaksi Escrow WAITING_PAYMENT
     */
    public function test_tc_07_04_checkout_confirmation_creates_waiting_payment_transaction(): void
    {
        $token = (string) Str::uuid();

        $response = $this->actingAs($this->buyer)->post(route('marketplace.checkout.confirm', $this->listing), [
            'submission_token' => $token,
            'expected_price' => 50000.0,
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('listing_id', $this->listing->listing_id)->latest('transaction_id')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('WAITING_PAYMENT', is_string($transaction->status) ? $transaction->status : $transaction->status->value);
        $this->assertNotNull($transaction->payment_due_at);

        $agreement = $transaction->agreement;
        $this->assertNotNull($agreement);
        $this->assertEquals($this->buyer->user_id, $agreement->buyer_id);
        $this->assertEquals($this->seller->user_id, $agreement->seller_id);
        $this->assertEquals('FINALIZED', is_string($agreement->status) ? $agreement->status : $agreement->status->value);
        $this->assertEquals(50000.0, (float) $agreement->base_price);
        $this->assertEquals('EXCLUDE', is_string($agreement->fee_type) ? $agreement->fee_type : $agreement->fee_type->value);
    }
}
