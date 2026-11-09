<?php

namespace Tests\Feature\MarketTrust;

use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian skenario integrasi untuk deliverable Meet 1 bagian Rio (Avrio):
 * 1. Marketplace (12 per page, badge baru, filter produk, pagination putih)
 * 2. Informasi Pasar Produk (halaman mandiri /informasi-pasar & link navbar)
 * 3. Checkout Marketplace (rincian pembayaran 3 baris, tombol "Bayar", hapus badge mode fee & card payout)
 * 4. Forum (10 per page, switcher PS5 2 kartu di viewport, nested replies & toggle Reply (x))
 */
class Meet1RioScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji Marketplace: pagination 12 item per halaman dan badge baru.
     */
    public function test_marketplace_index_uses_12_per_page_and_shows_new_badge(): void
    {
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        Listing::factory()->count(15)->create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->get(route('marketplace.index'));

        $response->assertStatus(200);
        $response->assertViewHas('listings', function ($listings) {
            return $listings->perPage() === 12;
        });
        $response->assertSee('Katalog Marketplace Terverifikasi');
        $response->assertDontSee('Katalog Terverifikasi Escrow');
        $response->assertSee('Produk');
    }

    /**
     * Uji Informasi Pasar: route mandiri /informasi-pasar dan link di navbar.
     */
    public function test_informasi_pasar_standalone_page_and_navbar_link(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $response = $this->get('/informasi-pasar');

        $response->assertStatus(200);
        $response->assertViewIs('marketplace.market-stats');
        $response->assertSee('Informasi Pasar Produk Digital');
        $response->assertSee('Periode Transaksi');

        // Verifikasi link ada di layout
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertSee('/informasi-pasar');
        $homeResponse->assertSee('Informasi Pasar');
    }

    /**
     * Uji Detail Marketplace: card info pasar dihapus dan card sejajar.
     */
    public function test_marketplace_show_does_not_contain_market_stats_widget(): void
    {
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $listing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'status' => 'ACTIVE',
            'price' => 50000,
        ]);

        $response = $this->get(route('marketplace.show', $listing->listing_id));

        $response->assertStatus(200);
        $response->assertDontSee('id="widget-informasi-pasar"', false);
        $response->assertSee($listing->title);
    }

    /**
     * Uji Checkout Marketplace: rincian pembayaran disederhanakan, tombol "Bayar",
     * tanpa badge mode fee EXCLUDE dan tanpa card tujuan pencairan penjual.
     */
    public function test_checkout_simplified_payment_breakdown_and_bayar_button(): void
    {
        $seller = User::factory()->create(['account_status' => 'ACTIVE']);
        $buyer = User::factory()->create(['account_status' => 'ACTIVE']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $listing = Listing::factory()->create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'status' => 'ACTIVE',
            'price' => 100000,
        ]);

        $response = $this->actingAs($buyer)->get(route('marketplace.checkout.review', $listing->listing_id));

        $response->assertStatus(200);
        $response->assertDontSee('Mode Fee: EXCLUDE');
        $response->assertDontSee('Tujuan Pencairan Penjual Siap');
        $response->assertSee('Harga Dasar Produk');
        $response->assertSee('Fee');
        $response->assertSee('Total Pembayaran Pembeli');
        $response->assertSee('Bayar');
        $response->assertDontSee('Setuju &amp; Mulai Transaksi');
    }

    /**
     * Uji Forum Feed: pagination 10 item per halaman dan kontainer switcher PS5.
     */
    public function test_forum_feed_uses_10_per_page_and_ps5_switcher(): void
    {
        $user = User::factory()->create(['account_status' => 'ACTIVE']);

        for ($i = 1; $i <= 12; $i++) {
            ForumPost::create([
                'author_id' => $user->user_id,
                'category_code' => 'GENERAL',
                'title' => "Postingan Forum ke-{$i}",
                'content' => "Konten diskusi komunitas ke-{$i}",
                'status' => 'ACTIVE',
            ]);
        }

        $response = $this->get(route('forum.index'));

        $response->assertStatus(200);
        $response->assertViewHas('posts', function ($posts) {
            return $posts->perPage() === 10;
        });
        $response->assertSee('id="forum-feed-scrollbox"', false);
        $response->assertSee('PS5 Switcher');
    }

    /**
     * Uji Forum: nested/threaded replies bisa berulang kali dan memiliki toggle Reply (jumlah).
     */
    public function test_forum_nested_threaded_replies(): void
    {
        $author = User::factory()->create(['account_status' => 'ACTIVE']);
        $user1 = User::factory()->create(['account_status' => 'ACTIVE']);
        $user2 = User::factory()->create(['account_status' => 'ACTIVE']);

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Diskusi Fitur Baru MarketTrust',
            'content' => 'Bagaimana tanggapan terkait fitur baru?',
            'status' => 'ACTIVE',
        ]);

        // 1. Tambah Komentar Utama (Root)
        $responseRoot = $this->actingAs($user1)->postJson(route('forum.comments.store', $post->post_id), [
            'content' => 'Komentar utama tingkat 1.',
        ]);
        $responseRoot->assertSuccessful();
        $rootCommentId = $responseRoot->json('comment.comment_id');

        $this->assertDatabaseHas('forum_comments', [
            'comment_id' => $rootCommentId,
            'post_id' => $post->post_id,
            'parent_comment_id' => null,
            'content' => 'Komentar utama tingkat 1.',
        ]);

        // 2. Balas Komentar Utama (Reply Level 1)
        $responseReply1 = $this->actingAs($user2)->postJson(route('forum.comments.store', $post->post_id), [
            'content' => 'Balasan pertama untuk komentar utama.',
            'parent_comment_id' => $rootCommentId,
        ]);
        $responseReply1->assertSuccessful();
        $reply1Id = $responseReply1->json('comment.comment_id');

        $this->assertDatabaseHas('forum_comments', [
            'comment_id' => $reply1Id,
            'post_id' => $post->post_id,
            'parent_comment_id' => $rootCommentId,
            'content' => 'Balasan pertama untuk komentar utama.',
        ]);

        // 3. Balas Balasan (Nested Reply Level 2)
        $responseReply2 = $this->actingAs($author)->postJson(route('forum.comments.store', $post->post_id), [
            'content' => 'Balasan bersarang kedua untuk balasan pertama.',
            'parent_comment_id' => $reply1Id,
        ]);
        $responseReply2->assertSuccessful();
        $reply2Id = $responseReply2->json('comment.comment_id');

        $this->assertDatabaseHas('forum_comments', [
            'comment_id' => $reply2Id,
            'post_id' => $post->post_id,
            'parent_comment_id' => $reply1Id,
            'content' => 'Balasan bersarang kedua untuk balasan pertama.',
        ]);

        // 4. Verifikasi Halaman Detail Menampilkan Komentar & Toggle Reply
        $showResponse = $this->get(route('forum.show', $post->post_id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Reply (1)');
        $showResponse->assertSee('Balasan pertama untuk komentar utama.');
        $showResponse->assertSee('Balasan bersarang kedua untuk balasan pertama.');
    }
}
