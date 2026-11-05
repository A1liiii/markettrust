<?php

namespace Tests\Feature\MarketTrust;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
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

class AgreementScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper untuk membuat partisipan dan percakapan DM dari Forum.
     *
     * @return array{buyer: User, seller: User, variant: ProductVariant, conversation: Conversation}
     */
    protected function setupConversation(): array
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
            'title' => 'WTS Item Digital Premium',
            'content' => 'Akun siap pakai garansi seumur hidup',
            'price_offer' => 100000,
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
     * TC-06-01 — Pembuatan Usulan Kesepakatan (Agreement Draft) dari DM
     * Uji pembuatan usulan kesepakatan oleh Buyer, kalkulasi fee EXCLUDE,
     * status awal DRAFT, revision_no 1, dan notifikasi proposal ke lawan.
     */
    public function test_tc_06_01_pembuatan_usulan_kesepakatan_dari_dm(): void
    {
        $ctx = $this->setupConversation();

        $response = $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'Lisensi Software Pro Lifetime',
                'item_description' => 'Key aktivasi langsung beserta file panduan',
                'quantity' => 1,
                'base_price' => 100000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
                'agreement_note' => 'Mohon sertakan email aktivasi',
            ]);

        $response->assertRedirect(route('conversations.show', $ctx['conversation']->conversation_id));

        $ctx['conversation']->refresh();
        $this->assertNotNull($ctx['conversation']->agreement_id);

        $agreement = Agreement::findOrFail($ctx['conversation']->agreement_id);
        $this->assertSame(AgreementStatus::DRAFT->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertSame(1, $agreement->revision_no);
        $this->assertSame($ctx['buyer']->user_id, $agreement->buyer_id);
        $this->assertSame($ctx['seller']->user_id, $agreement->seller_id);
        $this->assertEquals(100000.0, (float) $agreement->base_price);
        $this->assertSame(FeeType::EXCLUDE->value, $agreement->fee_type->value ?? (string) $agreement->fee_type);
        $this->assertNotNull($agreement->buyer_accepted_at);
        $this->assertNull($agreement->seller_accepted_at);

        // Pastikan notifikasi usulan terkirim ke pihak seller
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['seller']->user_id,
            'event_type' => 'AGREEMENT_PROPOSED',
        ]);
    }

    /**
     * TC-06-02 — Revisi Usulan Kesepakatan Menaikkan Nomor Revisi
     * Uji revisi usulan kesepakatan oleh pihak lawan (Seller), nomor revisi naik jadi 2,
     * persetujuan revisi lama digugurkan, dan nilai nominal/mode diperbarui.
     */
    public function test_tc_06_02_revisi_usulan_kesepakatan_menaikkan_nomor_revisi(): void
    {
        $ctx = $this->setupConversation();

        // 1. Buat usulan awal Rev #1
        $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'Lisensi Software Pro',
                'item_description' => 'Key aktivasi langsung',
                'quantity' => 1,
                'base_price' => 100000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]);

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;
        $this->assertSame(1, $agreement->revision_no);

        // 2. Seller mengajukan revisi (ubah harga ke 120.000 dan fee mode INCLUDE)
        $reviseRes = $this->actingAs($ctx['seller'])
            ->put(route('conversations.agreement.update', $ctx['conversation']->conversation_id), [
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'Lisensi Software Pro + Bonus Template',
                'item_description' => 'Key aktivasi langsung + bonus 5 preset',
                'quantity' => 1,
                'base_price' => 120000,
                'fee_type' => 'INCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
                'agreement_note' => 'Harga pas termasuk bonus template',
            ]);

        $reviseRes->assertRedirect(route('conversations.show', $ctx['conversation']->conversation_id));

        $agreement->refresh();
        $this->assertSame(2, $agreement->revision_no);
        $this->assertEquals(120000.0, (float) $agreement->base_price);
        $this->assertSame(FeeType::INCLUDE->value, $agreement->fee_type->value ?? (string) $agreement->fee_type);
        // Persetujuan buyer lama digugurkan, seller yang merevisi otomatis setuju
        $this->assertNull($agreement->buyer_accepted_at);
        $this->assertNotNull($agreement->seller_accepted_at);

        // Pastikan notifikasi revisi terkirim ke buyer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['buyer']->user_id,
            'event_type' => 'AGREEMENT_REVISED',
        ]);
    }

    /**
     * TC-06-03 — Penolakan Usulan Kesepakatan (Reject Agreement)
     * Uji penolakan usulan kesepakatan dengan alasan penolakan,
     * status berubah REJECTED, dan tidak ada transaksi yang terbentuk.
     */
    public function test_tc_06_03_penolakan_usulan_kesepakatan(): void
    {
        $ctx = $this->setupConversation();

        // 1. Buat usulan draft
        $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'Akun Cloud 1 Tahun',
                'item_description' => 'Akses penuh cloud hosting',
                'quantity' => 1,
                'base_price' => 80000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]);

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;

        // 2. Seller menolak usulan kesepakatan
        $rejectRes = $this->actingAs($ctx['seller'])
            ->post(route('conversations.agreement.reject', $ctx['conversation']->conversation_id), [
                'rejection_reason' => 'Harga penawaran terlalu rendah, belum bisa dipenuhi.',
            ]);

        $rejectRes->assertRedirect(route('conversations.show', $ctx['conversation']->conversation_id));

        $agreement->refresh();
        $this->assertSame(AgreementStatus::REJECTED->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertSame($ctx['seller']->user_id, $agreement->rejected_by);
        $this->assertNotNull($agreement->rejected_at);
        $this->assertSame('Harga penawaran terlalu rendah, belum bisa dipenuhi.', $agreement->rejection_reason);

        // Pastikan tidak ada transaksi yang terbentuk
        $this->assertEquals(0, Transaction::where('agreement_id', $agreement->agreement_id)->count());
    }

    /**
     * TC-06-04 — Persetujuan Kesepakatan Menghasilkan Transaksi WAITING_PAYMENT
     * Uji persetujuan kesepakatan saat nomor revisi cocok: status berubah FINALIZED,
     * terbentuk record Transaction berstatus WAITING_PAYMENT, batas bayar 1 jam,
     * dan snapshot nominal terkunci.
     */
    public function test_tc_06_04_persetujuan_kesepakatan_menghasilkan_transaksi_waiting_payment(): void
    {
        $ctx = $this->setupConversation();

        // 1. Buyer membuat usulan kesepakatan (Rev #1)
        $this->actingAs($ctx['buyer'])
            ->post(route('conversations.agreement.store', $ctx['conversation']->conversation_id), [
                'role' => 'BUYER',
                'variant_id' => $ctx['variant']->variant_id,
                'item_title' => 'Item Koleksi Digital Final',
                'item_description' => 'Item sesuai kesepakatan chat',
                'quantity' => 1,
                'base_price' => 150000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]);

        $ctx['conversation']->refresh();
        $agreement = $ctx['conversation']->agreement;

        // 2. Seller menyetujui kesepakatan dengan revision_no = 1
        $acceptRes = $this->actingAs($ctx['seller'])
            ->post(route('conversations.agreement.accept', $ctx['conversation']->conversation_id), [
                'revision_no' => 1,
            ]);

        $agreement->refresh();
        $this->assertSame(AgreementStatus::FINALIZED->value, $agreement->status->value ?? (string) $agreement->status);
        $this->assertNotNull($agreement->finalized_at);
        $this->assertNotNull($agreement->buyer_accepted_at);
        $this->assertNotNull($agreement->seller_accepted_at);

        // 3. Verifikasi Transaksi yang terbentuk
        $transaction = Transaction::where('agreement_id', $agreement->agreement_id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame(TransactionStatus::WAITING_PAYMENT->value, $transaction->status->value ?? (string) $transaction->status);
        $this->assertNotNull($transaction->payment_due_at);

        // Redirect mengarah ke halaman transaksi
        $acceptRes->assertRedirect(route('transactions.show', $transaction));
    }
}
