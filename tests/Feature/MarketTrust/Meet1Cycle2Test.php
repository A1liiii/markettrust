<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Meet1Cycle2Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to set up testing context with 2 active users and a forum post conversation.
     *
     * @return array{buyer: User, seller: User, variant: ProductVariant, conversation: Conversation}
     */
    protected function setupConversationContext(): array
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $post = ForumPost::create([
            'author_id' => $seller->user_id,
            'category_code' => 'WTS',
            'variant_id' => $variant->variant_id,
            'title' => 'WTS Diamond MLBB Legal Aman Terpercaya',
            'content' => 'Dijual diamond mlbb legal fast response.',
            'price_offer' => 75000,
            'status' => 'ACTIVE',
        ]);

        $conversation = Conversation::create([
            'participant_one_id' => min($buyer->user_id, $seller->user_id),
            'participant_two_id' => max($buyer->user_id, $seller->user_id),
            'post_id' => $post->post_id,
        ]);

        return [
            'buyer' => $buyer,
            'seller' => $seller,
            'variant' => $variant,
            'conversation' => $conversation,
        ];
    }

    /**
     * TC-C2-01: Verifikasi seluruh copy & layout homepage, header, dan footer hasil Meet 1.
     */
    public function test_homepage_and_layouts_wording_matches_meet1_spec(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // 1. Header: badge Digital Escrow harus sudah dihapus
        $response->assertDontSee('Digital Escrow');

        // 2. Footer
        $response->assertSee('Platform Marketplace & Rekber Digital Terpercaya.', false);
        $response->assertSee('Transaksi Item Aman');
        $response->assertSee('Rekber Otomatis');
        $response->assertSee('Komplain');
        $response->assertDontSee('Escrow Rekber Otomatis');
        $response->assertDontSee('Resolusi Sengketa Terpercaya');

        // 3. Home page: Kalkulator fee (kata 'final' dihapus)
        $response->assertSee('Gunakan simulasi ini untuk menyepakati harga akhir di Forum atau percakapan DM sebelum membuat kesepakatan.');
        $response->assertDontSee('sebelum membuat kesepakatan final.');
        $response->assertSee('Dana ditahan di rekber');
        $response->assertDontSee('Dana ditahan di escrow');

        // 4. Pilar Forum
        $response->assertSee('Forum untuk WTB (Want to Buy), WTS (Want to Sell), dan Umum. Komentar, like, dan dapat berlangsung ke direct message untuk negosiasi');

        // 5. Standar Keamanan Transaksi (4 Langkah)
        $response->assertSee('Setiap transaksi dilindungi oleh data kesepakatan yang mengikat kedua belah pihak tanpa celah kecurangan.');
        $response->assertDontSee('Agreement Snapshot');
        $response->assertSee('Kesepakatan');
        $response->assertSee('Buyer Payment');
        $response->assertSee('Deliver Item');
        $response->assertSee('Penjual menyerahkan item dan bukti digital melalui chat terenkripsi.');
        $response->assertDontSee('Penjual menyerahkan akun/item');
    }

    /**
     * TC-C2-02: Verifikasi alur Tolak kesepakatan dan dilanjutkan Revisi harga/ketentuan baru.
     */
    public function test_reject_agreement_allows_subsequent_revision(): void
    {
        $ctx = $this->setupConversationContext();

        // 1. Buyer mengajukan usulan kesepakatan Rev #1
        $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'MLBB 250 Diamonds Paket Cepat',
                'item_description' => 'Penyerahan diamond via user id & zone id',
                'quantity' => 1,
                'base_price' => 75000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ])
            ->assertRedirect();

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;
        $this->assertNotNull($agreement);
        $this->assertSame(1, $agreement->revision_no);
        $this->assertSame(AgreementStatus::DRAFT->value, $agreement->status->value ?? (string) $agreement->status);

        // 2. Seller menolak usulan kesepakatan (Tolak bukan akhir, ingin ubah harga)
        $rejectRes = $this->actingAs($ctx['seller'])
            ->post(route('conversations.agreement.reject', $ctx['conversation']->conversation_id), [
                'rejection_reason' => 'Harga diamond naik menjadi Rp 80.000, mohon disesuaikan.',
            ]);

        $rejectRes->assertRedirect(route('conversations.show', $ctx['conversation']));
        $agreement->refresh();
        $this->assertSame(AgreementStatus::REJECTED->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertSame($ctx['seller']->user_id, $agreement->rejected_by);
        $this->assertSame('Harga diamond naik menjadi Rp 80.000, mohon disesuaikan.', $agreement->rejection_reason);

        // 3. Buyer merespons dengan mengajukan Revisi #2 dengan harga yang disepakati
        $reviseRes = $this->actingAs($ctx['buyer'])
            ->put(route('conversations.agreement.update', $ctx['conversation']->conversation_id), [
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'MLBB 250 Diamonds Paket Cepat (Revisi Harga)',
                'item_description' => 'Penyerahan diamond via user id & zone id setelah revisi',
                'quantity' => 1,
                'base_price' => 80000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ]);

        $reviseRes->assertRedirect(route('conversations.show', $ctx['conversation']));
        $agreement->refresh();
        $this->assertSame(2, $agreement->revision_no);
        $this->assertSame(AgreementStatus::DRAFT->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertEquals(80000, $agreement->base_price);
        $this->assertNull($agreement->rejected_by);
        $this->assertNull($agreement->rejection_reason);
    }

    /**
     * TC-C2-03: Verifikasi opsi "Akhiri Agreement" membatalkan usulan kesepakatan secara permanen.
     */
    public function test_cancel_agreement_terminates_proposal(): void
    {
        $ctx = $this->setupConversationContext();

        // 1. Seller mengajukan usulan kesepakatan
        $this->actingAs($ctx['seller'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'SELLER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'MLBB 250 Diamonds Langsung Kirim',
                'item_description' => 'Siap dikirim dalam 15 menit',
                'quantity' => 1,
                'base_price' => 70000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 15,
            ])
            ->assertRedirect();

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;
        $this->assertNotNull($agreement);

        // 2. Buyer memutuskan mengakhiri kesepakatan (Akhiri Agreement)
        $cancelRes = $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.cancel', $ctx['conversation']->conversation_id), [
                'cancel_reason' => 'Sudah membeli dari toko lain, kesepakatan diakhiri.',
            ]);

        $cancelRes->assertRedirect(route('conversations.show', $ctx['conversation']));
        $agreement->refresh();
        $this->assertSame(AgreementStatus::CANCELLED->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertSame($ctx['buyer']->user_id, $agreement->rejected_by);
        $this->assertSame('Sudah membeli dari toko lain, kesepakatan diakhiri.', $agreement->rejection_reason);
    }

    /**
     * TC-C2-04: Verifikasi alur Setujui dan Konfirmasi kesepakatan menghasilkan Transaksi resmi.
     */
    public function test_accept_and_confirm_agreement_finalizes_transaction(): void
    {
        $ctx = $this->setupConversationContext();

        // 1. Propose draft
        $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'MLBB 250 Diamonds Paket Cepat',
                'item_description' => 'Format pesanan lengkap',
                'quantity' => 1,
                'base_price' => 75000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ]);

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;

        // 2. Seller setujui dan konfirmasi
        $acceptRes = $this->actingAs($ctx['seller'])
            ->post(route('conversations.agreement.accept', $ctx['conversation']->conversation_id), [
                'revision_no' => $agreement->revision_no,
            ]);

        $agreement->refresh();
        $this->assertSame(AgreementStatus::FINALIZED->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertNotNull($agreement->finalized_at);

        $transaction = Transaction::where('agreement_id', $agreement->agreement_id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame(TransactionStatus::WAITING_PAYMENT->value, $transaction->status->value ?? (string) $transaction->status);

        $acceptRes->assertRedirect(route('transactions.show', $transaction));
    }
}
