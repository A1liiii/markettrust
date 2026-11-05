<?php

namespace Tests\Feature\MarketTrust;

use App\Models\Conversation;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-05-01 — Pembuatan Postingan Forum Jual/Beli
     * Uji alur store postingan kategori GENERAL, validasi WTB/WTS tanpa variant_id, dan store WTS sukses.
     */
    public function test_tc_05_01_pembuatan_postingan_forum_jual_beli(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        // 1. Buat postingan GENERAL sukses tanpa variant_id
        $responseGeneral = $this->actingAs($user)->post(route('forum.store'), [
            'category_code' => 'GENERAL',
            'title' => 'Diskusi Komunitas Mengenai Escrow MarketTrust',
            'content' => 'Bagaimana pengalaman teman-teman bertransaksi di MarketTrust?',
        ]);

        $responseGeneral->assertRedirect();
        $this->assertDatabaseHas('forum_posts', [
            'author_id' => $user->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Diskusi Komunitas Mengenai Escrow MarketTrust',
            'status' => 'ACTIVE',
        ]);

        // 2. Validasi gagal: WTS tanpa variant_id wajib ditolak
        $responseInvalidWts = $this->actingAs($user)->post(route('forum.store'), [
            'category_code' => 'WTS',
            'title' => 'Jual Akun Game Sultan',
            'content' => 'Dijual akun tier tertinggi',
            'variant_id' => null,
            'price_offer' => 150000,
        ]);
        $responseInvalidWts->assertSessionHasErrors(['variant_id']);

        // 3. Buat postingan WTS sukses dengan variant_id dan price_offer
        $responseValidWts = $this->actingAs($user)->post(route('forum.store'), [
            'category_code' => 'WTS',
            'variant_id' => $variant->variant_id,
            'title' => 'WTS: Lisensi Software Original',
            'content' => 'Lisensi resmi durasi 1 tahun garansi aktif',
            'price_offer' => 75000,
        ]);

        $responseValidWts->assertRedirect();
        $this->assertDatabaseHas('forum_posts', [
            'author_id' => $user->user_id,
            'category_code' => 'WTS',
            'variant_id' => $variant->variant_id,
            'title' => 'WTS: Lisensi Software Original',
            'price_offer' => 75000,
        ]);
    }

    /**
     * TC-05-02 — Interaksi Komentar dan Like pada Postingan Forum
     * Uji tambah komentar via AJAX, penolakan komentar kosong, toggle like post & comment,
     * idempotensi toggle like, dan penghapusan komentar oleh pemiliknya.
     */
    public function test_tc_05_02_interaksi_komentar_dan_like_postingan_forum(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Post Interaktif Komunitas',
            'content' => 'Mari berdiskusi dengan sopan',
            'status' => 'ACTIVE',
        ]);

        // 1. Tolak komentar kosong
        $emptyCommentRes = $this->actingAs($commenter)
            ->postJson(route('forum.comments.store', $post->post_id), [
                'content' => '',
            ]);
        $emptyCommentRes->assertStatus(422);

        // 2. Tambah komentar sah melalui AJAX
        $commentRes = $this->actingAs($commenter)
            ->postJson(route('forum.comments.store', $post->post_id), [
                'content' => 'Setuju sekali, escrow sangat membantu keamanan transaksi.',
            ]);
        $commentRes->assertOk();
        $commentRes->assertJsonPath('success', true);
        $commentId = $commentRes->json('comment.comment_id');
        $this->assertNotNull($commentId);

        // 3. Toggle Like Postingan (Like -> Batal Like)
        $likePostRes1 = $this->actingAs($commenter)
            ->postJson(route('forum.posts.like', $post->post_id));
        $likePostRes1->assertOk();
        $likePostRes1->assertJsonPath('liked', true);
        $this->assertDatabaseHas('forum_likes', [
            'user_id' => $commenter->user_id,
            'post_id' => $post->post_id,
        ]);

        $likePostRes2 = $this->actingAs($commenter)
            ->postJson(route('forum.posts.like', $post->post_id));
        $likePostRes2->assertOk();
        $likePostRes2->assertJsonPath('liked', false);
        $this->assertDatabaseMissing('forum_likes', [
            'user_id' => $commenter->user_id,
            'post_id' => $post->post_id,
        ]);

        // 4. Toggle Like Komentar
        $comment = ForumComment::findOrFail($commentId);
        $likeCommentRes = $this->actingAs($author)
            ->postJson(route('forum.comments.like', $comment->comment_id));
        $likeCommentRes->assertOk();
        $likeCommentRes->assertJsonPath('liked', true);
        $this->assertDatabaseHas('forum_likes', [
            'user_id' => $author->user_id,
            'comment_id' => $comment->comment_id,
        ]);

        // 5. Hapus komentar oleh pemilik komentar
        $deleteCommentRes = $this->actingAs($commenter)
            ->deleteJson(route('forum.comments.destroy', $comment->comment_id));
        $deleteCommentRes->assertOk();
        $deleteCommentRes->assertJsonPath('success', true);
        $this->assertDatabaseMissing('forum_comments', [
            'comment_id' => $commentId,
        ]);
    }

    /**
     * TC-05-03 — Tombol Negosiasi / Ajukan Penawaran dari Forum Mengarah ke DM
     * Uji klik tombol negosiasi pada postingan anggota lain menginisiasi percakapan DM,
     * serta pencegahan negosiasi terhadap postingan diri sendiri.
     */
    public function test_tc_05_03_tombol_negosiasi_forum_mengarah_ke_dm(): void
    {
        $author = User::factory()->create();
        $buyer = User::factory()->create();

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'WTS',
            'title' => 'WTS: Item Spesial Koleksi',
            'content' => 'Bisa nego tipis lewat DM',
            'price_offer' => 200000,
            'status' => 'ACTIVE',
        ]);

        // 1. Author mencoba menego postingannya sendiri -> ditolak dengan warning
        $selfNegotiateRes = $this->actingAs($author)
            ->post(route('forum.negotiate', $post->post_id));
        $selfNegotiateRes->assertRedirect(route('forum.show', $post->post_id));
        $selfNegotiateRes->assertSessionHas('warning');

        // 2. Buyer sah menekan negosiasi -> percakapan DM terinisiasi dan diarahkan dengan open_dm
        $negotiateRes = $this->actingAs($buyer)
            ->post(route('forum.negotiate', $post->post_id));

        $conversation = Conversation::where('post_id', $post->post_id)
            ->where('participant_one_id', min($buyer->user_id, $author->user_id))
            ->where('participant_two_id', max($buyer->user_id, $author->user_id))
            ->first();

        $this->assertNotNull($conversation);
        $negotiateRes->assertRedirect(route('forum.show', [
            'post' => $post->post_id,
            'open_dm' => $conversation->conversation_id,
        ]));
    }
}
