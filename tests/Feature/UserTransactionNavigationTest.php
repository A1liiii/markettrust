<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTransactionNavigationTest extends TestCase
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
            'delivery_method' => 'INSTANT',
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

    public function test_guest_is_redirected_to_login_when_accessing_transactions_index(): void
    {
        $response = $this->get(route('transactions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_transactions_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));
        $response->assertStatus(200);
        $response->assertSee('Transaksi & Pesanan Saya');
        $response->assertSee('Belum Ada Transaksi');
    }

    public function test_navbar_displays_transactions_link_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee(route('transactions.index'));
        $response->assertSee('Transaksi');
    }

    public function test_user_can_filter_transactions_by_buyer_or_seller_role(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 1 transaction as buyer
        $txBuyer = $this->createTransaction($user, $otherUser);

        // 1 transaction as seller
        $txSeller = $this->createTransaction($otherUser, $user);

        // Filter: All
        $resAll = $this->actingAs($user)->get(route('transactions.index', ['role' => 'all']));
        $resAll->assertStatus(200);
        $resAll->assertSee('#TRX-'.$txBuyer->transaction_id);
        $resAll->assertSee('#TRX-'.$txSeller->transaction_id);

        // Filter: Buyer only
        $resBuyer = $this->actingAs($user)->get(route('transactions.index', ['role' => 'buyer']));
        $resBuyer->assertStatus(200);
        $resBuyer->assertSee('#TRX-'.$txBuyer->transaction_id);
        $resBuyer->assertDontSee('#TRX-'.$txSeller->transaction_id);

        // Filter: Seller only
        $resSeller = $this->actingAs($user)->get(route('transactions.index', ['role' => 'seller']));
        $resSeller->assertStatus(200);
        $resSeller->assertDontSee('#TRX-'.$txBuyer->transaction_id);
        $resSeller->assertSee('#TRX-'.$txSeller->transaction_id);
    }

    public function test_marketplace_transaction_show_page_hides_seller_net_from_buyer(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        // Buyer view
        $responseBuyer = $this->actingAs($buyer)->get(route('transactions.show', $transaction));
        $responseBuyer->assertStatus(200);
        $responseBuyer->assertSee('Total Pembayaran:');
        $responseBuyer->assertDontSee('Pencairan Hak Bersih Penjual:');

        // Seller view
        $responseSeller = $this->actingAs($seller)->get(route('transactions.show', $transaction));
        $responseSeller->assertStatus(200);
        $responseSeller->assertSee('Pencairan Hak Bersih Penjual:');
        $responseSeller->assertSee('data-cy="snapshot-seller-net"', false);
    }

    public function test_marketplace_transaction_show_page_displays_friendly_labels_without_raw_enums(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $transaction = $this->createTransaction($buyer, $seller);

        $response = $this->actingAs($buyer)->get(route('transactions.show', $transaction));
        $response->assertStatus(200);

        // Stepper card is rendered
        $response->assertSee('data-cy="transaction-stepper-card"', false);
        $response->assertSee('Proses Transaksi Escrow');
        $response->assertSee('Pembayaran Escrow');
        $response->assertSee('Penyerahan Item');
        $response->assertSee('Konfirmasi Pembeli');
        $response->assertSee('Pesanan Marketplace');

        // Friendly label for status
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee('Pengiriman Otomatis (Instan)');

        // Irrelevant contract revision is not displayed for marketplace transaction
        $response->assertDontSee('Revisi #1 (Final)');
    }
}
