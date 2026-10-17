<?php

namespace Tests\Feature;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceCheckoutWorkflowTest extends TestCase
{
    use RefreshDatabase;

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

    protected function createListing(User $seller, array $attributes = []): Listing
    {
        $variant = $this->createVariant();

        return Listing::create(array_merge([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun Mythic Murah Meriah',
            'description' => 'Akun pol siap pakai',
            'price' => 100000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ], $attributes));
    }

    protected function attachSellerPayout(User $seller): void
    {
        DB::table('seller_payout_accounts')->insert([
            'seller_id' => $seller->user_id,
            'destination_type' => 'BANK',
            'destination_provider' => 'BCA',
            'destination_number' => '1234567890',
            'destination_holder' => $seller->username ?? 'Seller Holder',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Tamu tanpa autentikasi diarahkan ke halaman login saat mencoba checkout.
     */
    public function test_guest_is_redirected_to_login_when_accessing_checkout(): void
    {
        $seller = User::factory()->create();
        $listing = $this->createListing($seller);

        $response = $this->get(route('marketplace.checkout.review', $listing));

        $response->assertRedirect('/login');
    }

    /**
     * Penjual dilarang membeli penawaran listing miliknya sendiri (TC-09-02).
     */
    public function test_seller_cannot_purchase_their_own_listing(): void
    {
        $seller = User::factory()->create();
        $listing = $this->createListing($seller);

        $response = $this->actingAs($seller)->get(route('marketplace.checkout.review', $listing));

        $response->assertRedirect(route('marketplace.show', $listing));
        $response->assertSessionHas('warning', 'Anda tidak dapat membeli item pada listing milik Anda sendiri.');
    }

    /**
     * Listing yang tidak aktif (DRAFT/CLOSED) tidak dapat dicheckout (TC-09-03).
     */
    public function test_inactive_or_draft_listing_cannot_be_reviewed_or_checked_out(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $draftListing = $this->createListing($seller, ['status' => 'DRAFT']);

        $response = $this->actingAs($buyer)->get(route('marketplace.checkout.review', $draftListing));

        $response->assertRedirect(route('marketplace.index'));
        $response->assertSessionHas('warning', 'Listing ini sudah tidak aktif atau telah ditutup oleh penjual.');
    }

    /**
     * Halaman review checkout menampilkan ringkasan biaya EXCLUDE yang transparan (TC-09-01).
     */
    public function test_checkout_review_page_displays_accurate_fee_breakdown(): void
    {
        $seller = User::factory()->create();
        $this->attachSellerPayout($seller);
        $buyer = User::factory()->create();
        $listing = $this->createListing($seller, ['price' => 100000.00]);

        $response = $this->actingAs($buyer)->get(route('marketplace.checkout.review', $listing));

        $response->assertOk();
        $response->assertSee('Tinjauan Rincian');
        $response->assertSee('Kesepakatan Final Marketplace');
        $response->assertSee('Rp 100.000'); // Harga dasar
        $response->assertSee('Rp 5.000');   // Fee tier 50k-100k (5%) = 5000
        $response->assertSee('Rp 105.000'); // Total bayar pembeli
        $response->assertSee('Rp 100.000'); // Penerimaan bersih penjual
    }

    /**
     * Konfirmasi checkout berhasil membuat Percakapan, Perjanjian (FINALIZED),
     * dan Transaksi (WAITING_PAYMENT, source MARKETPLACE, due 1 jam) secara atomik.
     */
    public function test_successful_checkout_creates_conversation_agreement_and_transaction_atomically(): void
    {
        $seller = User::factory()->create();
        $this->attachSellerPayout($seller);
        $buyer = User::factory()->create();
        $listing = $this->createListing($seller, [
            'price' => 200000.00,
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
        ]);

        $submissionToken = Str::uuid()->toString();

        $response = $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => $submissionToken,
            'expected_price' => 200000.00,
        ]);

        $transaction = Transaction::where('listing_id', $listing->listing_id)->first();
        $this->assertNotNull($transaction, 'Transaksi harus terbuat di database.');

        $response->assertRedirect(route('transactions.show', $transaction));

        // Verifikasi Transaction
        $this->assertEquals(TransactionSourceType::MARKETPLACE, $transaction->source_type);
        $this->assertEquals(TransactionStatus::WAITING_PAYMENT, $transaction->status);
        $this->assertNotNull($transaction->payment_due_at);
        $this->assertTrue(now()->addMinutes(59)->lte($transaction->payment_due_at));
        $this->assertTrue(now()->addMinutes(61)->gte($transaction->payment_due_at));

        $feeService = app(FeeCalculationServiceInterface::class);
        $expectedFee = $feeService->calculateForMarketplace(200000.00);

        // Verifikasi Agreement
        $agreement = $transaction->agreement;
        $this->assertNotNull($agreement);
        $this->assertEquals($buyer->user_id, $agreement->buyer_id);
        $this->assertEquals($seller->user_id, $agreement->seller_id);
        $this->assertEquals(200000.00, (float) $agreement->base_price);
        $this->assertEquals($expectedFee->feeAmount, (float) $agreement->fee_amount);
        $this->assertEquals(FeeType::EXCLUDE, $agreement->fee_type);
        $this->assertEquals($expectedFee->buyerTotal, (float) $agreement->buyer_total);
        $this->assertEquals(AgreementStatus::FINALIZED, $agreement->status);
        $this->assertEquals(30, $agreement->delivery_within_minutes);

        // Verifikasi Conversation
        $conversation = Conversation::where('agreement_id', $agreement->agreement_id)->first();
        $this->assertNotNull($conversation);
        $this->assertTrue(
            ($conversation->participant_one_id === $buyer->user_id && $conversation->participant_two_id === $seller->user_id) ||
            ($conversation->participant_one_id === $seller->user_id && $conversation->participant_two_id === $buyer->user_id)
        );

        // Verifikasi initial Message terkirim
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $buyer->user_id,
        ]);

        // Verifikasi Status History
        $this->assertDatabaseHas('transaction_status_histories', [
            'transaction_id' => $transaction->transaction_id,
            'to_status' => 'WAITING_PAYMENT',
        ]);
    }

    /**
     * Konfirmasi checkout ditolak jika penjual belum memiliki rekening payout aktif (TC-09-06).
     */
    public function test_checkout_blocked_if_seller_has_no_active_payout_account(): void
    {
        $seller = User::factory()->create(); // Payout tidak di-attach
        $buyer = User::factory()->create();
        $listing = $this->createListing($seller, ['price' => 100000.00]);

        $submissionToken = Str::uuid()->toString();

        $response = $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => $submissionToken,
            'expected_price' => 100000.00,
        ]);

        $response->assertSessionHas('warning', 'Penjual belum melengkapi rekening/tujuan pencairan (payout) yang aktif. Pembayaran belum dapat diproses.');
        $this->assertDatabaseMissing('transactions', [
            'listing_id' => $listing->listing_id,
        ]);
    }

    /**
     * Konfirmasi checkout ditolak jika harga listing berubah saat pembeli sedang review (TC-09-04).
     */
    public function test_checkout_fails_if_seller_updated_price_concurrently(): void
    {
        $seller = User::factory()->create();
        $this->attachSellerPayout($seller);
        $buyer = User::factory()->create();
        $listing = $this->createListing($seller, ['price' => 100000.00]);

        $submissionToken = Str::uuid()->toString();

        // Kirim expected_price 90000 padahal harga di DB 100000
        $response = $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => $submissionToken,
            'expected_price' => 90000.00,
        ]);

        $response->assertRedirect(route('marketplace.show', $listing));
        $response->assertSessionHas('warning');
        $this->assertDatabaseMissing('transactions', [
            'listing_id' => $listing->listing_id,
        ]);
    }

    /**
     * Submisi ganda dengan submission_token yang sama bersifat idempoten dan tidak menduplikasi transaksi.
     */
    public function test_duplicate_submission_token_is_idempotent(): void
    {
        $seller = User::factory()->create();
        $this->attachSellerPayout($seller);
        $buyer = User::factory()->create();
        $listing = $this->createListing($seller, ['price' => 150000.00]);

        $submissionToken = Str::uuid()->toString();

        // Request 1
        $response1 = $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => $submissionToken,
            'expected_price' => 150000.00,
        ]);

        $this->assertEquals(1, Transaction::where('listing_id', $listing->listing_id)->count());
        $transaction = Transaction::where('listing_id', $listing->listing_id)->first();
        $response1->assertRedirect(route('transactions.show', $transaction));

        // Request 2 (double submit / burst)
        $response2 = $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => $submissionToken,
            'expected_price' => 150000.00,
        ]);

        $this->assertEquals(1, Transaction::where('listing_id', $listing->listing_id)->count());
        $response2->assertRedirect(route('transactions.show', $transaction));
    }

    /**
     * Halaman detail transaksi (/transactions/{transaction}) dapat diakses oleh pembeli dan penjual,
     * tetapi ditolak (403) untuk pengguna pihak ketiga.
     */
    public function test_transaction_show_page_authorization(): void
    {
        $seller = User::factory()->create();
        $this->attachSellerPayout($seller);
        $buyer = User::factory()->create();
        $intruder = User::factory()->create();
        $listing = $this->createListing($seller, ['price' => 80000.00]);

        // Checkout oleh buyer
        $this->actingAs($buyer)->post(route('marketplace.checkout.confirm', $listing), [
            'submission_token' => Str::uuid()->toString(),
            'expected_price' => 80000.00,
        ]);

        $transaction = Transaction::where('listing_id', $listing->listing_id)->first();
        $this->assertNotNull($transaction);

        // Buyer akses -> OK
        $this->actingAs($buyer)->get(route('transactions.show', $transaction))->assertOk();

        // Seller akses -> OK
        $this->actingAs($seller)->get(route('transactions.show', $transaction))->assertOk();

        // Pengguna lain (pihak ketiga) -> 403 Forbidden
        $this->actingAs($intruder)->get(route('transactions.show', $transaction))->assertForbidden();

        // Guest -> redirect login
        auth()->logout();
        $this->get(route('transactions.show', $transaction))->assertRedirect('/login');
    }
}
