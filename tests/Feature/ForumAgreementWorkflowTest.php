<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\NotificationEventType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Agreement;
use App\Models\ForumPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumAgreementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createVariant(): ProductVariant
    {
        $category = ProductCategory::create([
            'category_name' => 'Voucher Game '.fake()->unique()->randomNumber(4),
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends Diamonds',
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => '1000 Diamonds',
            'is_active' => true,
        ]);
    }

    protected function createForumPost(User $author, ProductVariant $variant): ForumPost
    {
        return ForumPost::create([
            'author_id' => $author->user_id,
            'variant_id' => $variant->variant_id,
            'category_code' => 'WTS',
            'title' => 'WTS Akun Sultan MLBB All Skin',
            'content' => 'Bisa nego santai via DM escrow MarketTrust.',
            'price_offer' => 50000.00,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * 1. Pengajuan kesepakatan baru dengan mode fee EXCLUDE berhasil dihitung & disimpan sebagai DRAFT Rev #1.
     */
    public function test_participant_can_propose_agreement_with_exclude_fee_mode(): void
    {
        $seller = User::factory()->create(['username' => 'seller_forum']);
        $buyer = User::factory()->create(['username' => 'buyer_forum']);
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        $payload = [
            'role' => 'BUYER',
            'variant_id' => $variant->variant_id,
            'item_title' => 'Akun MLBB Sultan 100 Skin',
            'item_description' => 'Penyerahan data login lengkap dengan email pembuat.',
            'quantity' => 1,
            'base_price' => 50000.00,
            'fee_type' => 'EXCLUDE',
            'delivery_method' => 'DIRECT_CHAT',
            'delivery_within_minutes' => 60,
            'agreement_note' => 'Mohon serahkan secepatnya.',
        ];

        $response = $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            $payload
        );

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $conversation->refresh();
        $this->assertNotNull($conversation->agreement_id);

        $agreement = Agreement::find($conversation->agreement_id);
        $this->assertNotNull($agreement);
        $this->assertEquals($buyer->user_id, $agreement->buyer_id);
        $this->assertEquals($seller->user_id, $agreement->seller_id);
        $this->assertEquals($buyer->user_id, $agreement->created_by);
        $this->assertEquals(AgreementStatus::DRAFT, $agreement->status);
        $this->assertEquals(1, $agreement->revision_no);
        $this->assertEquals(FeeType::EXCLUDE, $agreement->fee_type);
        $this->assertEquals(50000.00, (float) $agreement->base_price);
        $this->assertEquals(2500.00, (float) $agreement->fee_amount);
        $this->assertEquals(52500.00, (float) $agreement->buyer_total);
        $this->assertEquals(50000.00, (float) $agreement->seller_net);

        // Pengaju (buyer) otomatis tercatat menyetujui, pihak lawan (seller) belum
        $this->assertNotNull($agreement->buyer_accepted_at);
        $this->assertNull($agreement->seller_accepted_at);

        // Pihak lawan menerima notifikasi proposal
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'event_type' => NotificationEventType::AGREEMENT_PROPOSED->value,
        ]);
    }

    /**
     * 2. Pengajuan kesepakatan dengan mode fee INCLUDE (fee dipotong dari hak penjual).
     */
    public function test_participant_can_propose_agreement_with_include_fee_mode(): void
    {
        $seller = User::factory()->create(['username' => 'seller_inc']);
        $buyer = User::factory()->create(['username' => 'buyer_inc']);
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        $payload = [
            'role' => 'SELLER',
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item Include Fee',
            'item_description' => 'Spesifikasi item.',
            'base_price' => 100000.00,
            'fee_type' => 'INCLUDE',
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
        ];

        // Seller mengajukan proposal
        $this->actingAs($seller)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            $payload
        )->assertRedirect();

        $agreement = Agreement::where('item_title', 'Item Include Fee')->first();
        $this->assertNotNull($agreement);
        $this->assertEquals(FeeType::INCLUDE, $agreement->fee_type);
        $this->assertEquals(100000.00, (float) $agreement->base_price);
        $this->assertEquals(5000.00, (float) $agreement->fee_amount);
        $this->assertEquals(100000.00, (float) $agreement->buyer_total);
        $this->assertEquals(95000.00, (float) $agreement->seller_net);

        // Seller yang mengajukan sudah tercatat setuju, buyer belum
        $this->assertNotNull($agreement->seller_accepted_at);
        $this->assertNull($agreement->buyer_accepted_at);

        // Buyer menerima notifikasi
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'event_type' => NotificationEventType::AGREEMENT_PROPOSED->value,
        ]);
    }

    /**
     * 3. Validasi menolak nominal harga di bawah Rp5.000 atau field wajib kosong.
     */
    public function test_proposal_validation_enforces_minimum_price_and_required_fields(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Harga di bawah Rp5.000
        $resLow = $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Judul Valid',
                'item_description' => 'Deskripsi cukup panjang.',
                'base_price' => 4999,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ]
        );
        $resLow->assertSessionHasErrors(['base_price']);

        // Judul item terlalu pendek (< 3 karakter)
        $resTitle = $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'AB',
                'item_description' => 'Deskripsi cukup panjang.',
                'base_price' => 10000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ]
        );
        $resTitle->assertSessionHasErrors(['item_title']);
    }

    /**
     * 4. Pihak ketiga di luar percakapan ditolak HTTP 403.
     */
    public function test_non_participant_cannot_propose_or_act_on_agreement(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $intruder = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Intruder mencoba mengajukan
        $this->actingAs($intruder)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Percobaan Hacker',
                'item_description' => 'Deskripsi.',
                'base_price' => 10000,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 30,
            ]
        )->assertStatus(403);
    }

    /**
     * 5. Revisi usulan sebelum final menaikkan revision_no dan membatalkan persetujuan revisi lama.
     */
    public function test_revising_agreement_increments_revision_and_invalidates_prior_acceptance(): void
    {
        $seller = User::factory()->create(['username' => 'seller_rev']);
        $buyer = User::factory()->create(['username' => 'buyer_rev']);
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // 1. Buyer mengajukan Rev #1 seharga 50.000
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Akun Game Awal',
                'item_description' => 'Deskripsi awal.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        $agreement = Agreement::where('buyer_id', $buyer->user_id)->first();
        $this->assertEquals(1, $agreement->revision_no);
        $this->assertNotNull($agreement->buyer_accepted_at);
        $this->assertNull($agreement->seller_accepted_at);

        // 2. Seller melakukan revisi menjadi 75.000
        $responseRev = $this->actingAs($seller)->put(
            route('conversations.agreement.update', $conversation->conversation_id),
            [
                'variant_id' => $variant->variant_id,
                'item_title' => 'Akun Game Revisi Seller',
                'item_description' => 'Tambahan bonus skin langka.',
                'base_price' => 75000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 45,
            ]
        );

        $responseRev->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $agreement->refresh();
        $this->assertEquals(2, $agreement->revision_no);
        $this->assertEquals('Akun Game Revisi Seller', $agreement->item_title);
        $this->assertEquals(75000.00, (float) $agreement->base_price);
        $this->assertEquals(3750.00, (float) $agreement->fee_amount); // 5% dari 75.000

        // Seller yang merevisi tercatat setuju, persetujuan buyer lama DIBATALKAN (null)
        $this->assertNotNull($agreement->seller_accepted_at);
        $this->assertNull($agreement->buyer_accepted_at);

        // Buyer menerima notifikasi AGREEMENT_REVISED
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'event_type' => NotificationEventType::AGREEMENT_REVISED->value,
        ]);
    }

    /**
     * 6. Percobaan menyetujui revisi usang (stale revision_no) ditolak.
     */
    public function test_accepting_stale_revision_is_rejected(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Buyer ajukan Rev #1
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Item Uji Stale',
                'item_description' => 'Deskripsi.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Seller revisi ke Rev #2
        $this->actingAs($seller)->put(
            route('conversations.agreement.update', $conversation->conversation_id),
            [
                'variant_id' => $variant->variant_id,
                'item_title' => 'Item Uji Stale Rev 2',
                'item_description' => 'Deskripsi 2.',
                'base_price' => 60000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Buyer mencoba menyetujui Rev #1 yang sudah usang
        $response = $this->actingAs($buyer)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        $response->assertSessionHas('warning');
        $this->assertStringContainsString('revisi usang', session('warning'));

        $agreement = Agreement::where('buyer_id', $buyer->user_id)->first();
        $this->assertEquals(AgreementStatus::DRAFT, $agreement->status);
        $this->assertNull($agreement->finalized_at);
        $this->assertDatabaseMissing('transactions', ['agreement_id' => $agreement->agreement_id]);
    }

    /**
     * 7. Pengaju usulan tidak dapat menyetujui usulannya sendiri mendahului pihak lawan.
     */
    public function test_proposer_cannot_accept_their_own_unreviewed_proposal(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Buyer ajukan Rev #1
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Self Accept Test',
                'item_description' => 'Deskripsi.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Buyer mencoba menyetujui lagi
        $response = $this->actingAs($buyer)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        $response->assertSessionHas('warning');
        $this->assertStringContainsString('Menunggu persetujuan dari pihak lawan', session('warning'));
    }

    /**
     * 8. Persetujuan pada revisi yang cocok mengunci kesepakatan menjadi FINALIZED dan membuat tepat 1 transaksi WAITING_PAYMENT dengan batas 1 jam.
     */
    public function test_matching_revision_approval_locks_snapshot_and_creates_transaction(): void
    {
        $seller = User::factory()->create(['username' => 'seller_final']);
        $buyer = User::factory()->create(['username' => 'buyer_final']);
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // 1. Buyer ajukan proposal Rev #1
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Kesepakatan Final Disetujui',
                'item_description' => 'Deskripsi penyerahan resmi.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // 2. Seller menyetujui Rev #1 yang cocok
        $response = $this->actingAs($seller)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        $agreement = Agreement::where('item_title', 'Kesepakatan Final Disetujui')->first();
        $this->assertNotNull($agreement);
        $this->assertEquals(AgreementStatus::FINALIZED, $agreement->status);
        $this->assertNotNull($agreement->finalized_at);
        $this->assertNotNull($agreement->buyer_accepted_at);
        $this->assertNotNull($agreement->seller_accepted_at);

        // Transaksi terbentuk
        $transaction = Transaction::where('agreement_id', $agreement->agreement_id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionSourceType::FORUM, $transaction->source_type);
        $this->assertEquals(TransactionStatus::WAITING_PAYMENT, $transaction->status);
        $this->assertEquals($post->post_id, $transaction->post_id);

        // Tenggat pembayaran tepat 1 jam dari waktu finalisasi
        $expectedDue = $agreement->finalized_at->copy()->addHour();
        $this->assertEquals($expectedDue->toDateTimeString(), $transaction->payment_due_at->toDateTimeString());

        // Redirect ke detail transaksi
        $response->assertRedirect(route('transactions.show', $transaction));

        // Notifikasi TRANSACTION_CREATED terkirim ke kedua belah pihak
        $this->assertDatabaseHas('notifications', [
            'user_id' => $buyer->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::TRANSACTION_CREATED->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $seller->user_id,
            'transaction_id' => $transaction->transaction_id,
            'event_type' => NotificationEventType::TRANSACTION_CREATED->value,
        ]);
    }

    /**
     * 9. Idempotensi: Persetujuan berulang tidak membuat transaksi ganda.
     */
    public function test_concurrent_or_duplicate_acceptance_is_idempotent(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Idempotent Acceptance',
                'item_description' => 'Desc.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Seller setuju pertama kali
        $this->actingAs($seller)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        $agreement = Agreement::where('item_title', 'Idempotent Acceptance')->first();
        $txCountBefore = Transaction::where('agreement_id', $agreement->agreement_id)->count();
        $this->assertEquals(1, $txCountBefore);

        // Seller submit setuju kedua kali (misal double click)
        $this->actingAs($seller)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        $txCountAfter = Transaction::where('agreement_id', $agreement->agreement_id)->count();
        $this->assertEquals(1, $txCountAfter);
    }

    /**
     * 10. Penolakan kesepakatan mencatat rejected_by, rejected_at, rejection_reason, dan status REJECTED tanpa membuat transaksi.
     */
    public function test_rejecting_agreement_saves_rejection_data_without_creating_transaction(): void
    {
        $seller = User::factory()->create(['username' => 'seller_rej']);
        $buyer = User::factory()->create(['username' => 'buyer_rej']);
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Buyer ajukan proposal
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Proposal to be Rejected',
                'item_description' => 'Desc.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        $agreement = Agreement::where('item_title', 'Proposal to be Rejected')->first();

        // Seller menolak usulan
        $reason = 'Harga terlalu rendah untuk akun dengan banyak skin eksklusif.';
        $response = $this->actingAs($seller)->post(
            route('conversations.agreement.reject', $conversation->conversation_id),
            ['rejection_reason' => $reason]
        );

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $agreement->refresh();
        $this->assertEquals(AgreementStatus::REJECTED, $agreement->status);
        $this->assertEquals($seller->user_id, $agreement->rejected_by);
        $this->assertNotNull($agreement->rejected_at);
        $this->assertEquals($reason, $agreement->rejection_reason);

        // Tidak ada transaksi yang dibentuk
        $this->assertDatabaseMissing('transactions', ['agreement_id' => $agreement->agreement_id]);
    }

    /**
     * 11. Kesepakatan yang ditolak dapat dinegosiasikan ulang melalui revisi usulan (kembali ke DRAFT Rev #2).
     */
    public function test_revising_rejected_agreement_reopens_negotiation_to_draft(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        // Buyer ajukan
        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Item Reopen',
                'item_description' => 'Desc.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Seller menolak
        $this->actingAs($seller)->post(
            route('conversations.agreement.reject', $conversation->conversation_id),
            ['rejection_reason' => 'Harga tidak cocok.']
        );

        // Buyer menaikkan harga melalui revisi
        $this->actingAs($buyer)->put(
            route('conversations.agreement.update', $conversation->conversation_id),
            [
                'variant_id' => $variant->variant_id,
                'item_title' => 'Item Reopen Naik Harga',
                'item_description' => 'Desc direvisi.',
                'base_price' => 70000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        )->assertRedirect();

        $agreement = Agreement::where('buyer_id', $buyer->user_id)->first();
        $this->assertEquals(AgreementStatus::DRAFT, $agreement->status);
        $this->assertEquals(2, $agreement->revision_no);
        $this->assertNull($agreement->rejected_by);
        $this->assertNull($agreement->rejection_reason);
        $this->assertEquals(70000.00, (float) $agreement->base_price);
    }

    /**
     * 12. Kesepakatan yang sudah FINALIZED dilarang direvisi atau ditolak.
     */
    public function test_cannot_revise_or_reject_finalized_agreement(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $variant = $this->createVariant();
        $post = $this->createForumPost($seller, $variant);

        $convService = app(ConversationService::class);
        $conversation = $convService->startFromPost($buyer, $post);

        $this->actingAs($buyer)->post(
            route('conversations.agreement.store', $conversation->conversation_id),
            [
                'role' => 'BUYER',
                'variant_id' => $variant->variant_id,
                'item_title' => 'Final Lock Test',
                'item_description' => 'Desc.',
                'base_price' => 50000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );

        // Seller menyetujui -> FINALIZED
        $this->actingAs($seller)->post(
            route('conversations.agreement.accept', $conversation->conversation_id),
            ['revision_no' => 1]
        );

        // Percobaan revisi setelah final harus ditolak
        $resRev = $this->actingAs($buyer)->put(
            route('conversations.agreement.update', $conversation->conversation_id),
            [
                'variant_id' => $variant->variant_id,
                'item_title' => 'Coba Ubah Final',
                'item_description' => 'Desc.',
                'base_price' => 60000.00,
                'fee_type' => 'EXCLUDE',
                'delivery_method' => 'DIRECT_CHAT',
                'delivery_within_minutes' => 60,
            ]
        );
        $resRev->assertSessionHas('warning');

        // Percobaan tolak setelah final harus ditolak
        $resRej = $this->actingAs($seller)->post(
            route('conversations.agreement.reject', $conversation->conversation_id),
            ['rejection_reason' => 'Tolak setelah final']
        );
        $resRej->assertSessionHas('warning');
    }
}
