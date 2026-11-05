<?php

namespace Tests\Unit\MarketTrust;

use App\Models\ForumComment;
use App\Models\ForumLike;
use App\Models\ForumPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji konfigurasi atribut dasar, casting, dan default pada model ForumPost.
     */
    public function test_forum_post_status_and_defaults(): void
    {
        $author = User::factory()->create();

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Diskusi Seputar Keamanan Escrow',
            'content' => 'Bagaimana mekanisme pengamanan transaksi menggunakan MarketTrust?',
            'image_urls' => ['forum/images/img1.jpg', 'forum/images/img2.jpg'],
            'price_offer' => 50000.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertSame('GENERAL', $post->category_code);
        $this->assertSame('ACTIVE', $post->status);
        $this->assertIsArray($post->image_urls);
        $this->assertCount(2, $post->image_urls);
        $this->assertEquals(50000.00, (float) $post->price_offer);
    }

    /**
     * Uji accessor formatted_time dan exact_wib_time pada model ForumPost.
     */
    public function test_forum_post_formatted_time_accessor(): void
    {
        $author = User::factory()->create();

        Carbon::setTestNow('2026-11-04 12:00:00');

        // Post yang dibuat 2 jam lalu (kurang dari 7 hari: format relatif)
        $recentPost = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Post Baru Saja',
            'content' => 'Konten postingan baru',
            'status' => 'ACTIVE',
        ]);
        $recentPost->created_at = Carbon::now()->subHours(2);
        $recentPost->save();

        $this->assertNotEmpty($recentPost->formatted_time);
        $this->assertStringContainsString('WIB', $recentPost->exact_wib_time);

        // Post yang dibuat 10 hari lalu (>= 7 hari: format tanggal lengkap WIB)
        $oldPost = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Post Lama Sekali',
            'content' => 'Konten postingan lama',
            'status' => 'ACTIVE',
        ]);
        $oldPost->created_at = Carbon::now()->subDays(10);
        $oldPost->save();

        $this->assertStringContainsString('WIB', $oldPost->formatted_time);

        Carbon::setTestNow();
    }

    /**
     * Uji accessor formatted_time dan exact_wib_time pada model ForumComment.
     */
    public function test_forum_comment_formatted_time_accessor(): void
    {
        $author = User::factory()->create();
        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Post Diskusi Komentar',
            'content' => 'Diskusi mengenai transaksi aman',
            'status' => 'ACTIVE',
        ]);

        Carbon::setTestNow('2026-11-04 14:00:00');

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $author->user_id,
            'content' => 'Komentar pengujian format waktu',
        ]);
        $comment->created_at = Carbon::now()->subMinutes(30);
        $comment->save();

        $this->assertNotEmpty($comment->formatted_time);
        $this->assertStringContainsString('WIB', $comment->exact_wib_time);

        Carbon::setTestNow();
    }

    /**
     * Uji relasi eloquent pada ForumPost, ForumComment, dan ForumLike.
     */
    public function test_forum_post_and_comment_relationships(): void
    {
        $author = User::factory()->create();
        $user2 = User::factory()->create();

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Post dengan Relasi Lengkap',
            'content' => 'Uji relasi author, comments, likes',
            'status' => 'ACTIVE',
        ]);

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $user2->user_id,
            'content' => 'Tanggapan pertama',
        ]);

        $likePost = ForumLike::create([
            'user_id' => $user2->user_id,
            'post_id' => $post->post_id,
            'comment_id' => null,
            'created_at' => now(),
        ]);

        $likeComment = ForumLike::create([
            'user_id' => $author->user_id,
            'post_id' => null,
            'comment_id' => $comment->comment_id,
            'created_at' => now(),
        ]);

        $this->assertTrue($post->author->is($author));
        $this->assertCount(1, $post->comments);
        $this->assertCount(1, $post->likes);
        $this->assertTrue($comment->post->is($post));
        $this->assertTrue($comment->author->is($user2));
        $this->assertCount(1, $comment->likes);
        $this->assertTrue($likePost->post->is($post));
        $this->assertTrue($likeComment->comment->is($comment));
    }
}
