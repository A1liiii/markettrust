<?php

namespace Tests\Feature\Forum;

use App\Models\Conversation;
use App\Models\ForumComment;
use App\Models\ForumLike;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(string $status = 'ACTIVE', ?User $author = null): ForumPost
    {
        $author = $author ?? User::factory()->create();

        return ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Topik Diskusi Terbuka',
            'content' => 'Bagaimana tips bertransaksi aman di MarketTrust?',
            'status' => $status,
        ]);
    }

    public function test_authenticated_user_can_comment_on_active_post(): void
    {
        $author = User::factory()->create();
        $post = $this->createPost('ACTIVE', $author);

        $commenter = User::factory()->create();

        $response = $this->actingAs($commenter)->post("/forum/{$post->post_id}/comments", [
            'content' => 'Pastikan menggunakan sistem escrow resmi untuk perlindungan dana.',
        ]);

        $response->assertRedirect("/forum/{$post->post_id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('forum_comments', [
            'post_id' => $post->post_id,
            'author_id' => $commenter->user_id,
            'content' => 'Pastikan menggunakan sistem escrow resmi untuk perlindungan dana.',
        ]);
    }

    public function test_guest_cannot_comment_on_post(): void
    {
        $post = $this->createPost();

        $response = $this->post("/forum/{$post->post_id}/comments", [
            'content' => 'Komentar dari tamu.',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('forum_comments', 0);
    }

    public function test_comment_validation_requires_content_with_minimum_length(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        $response = $this->actingAs($user)->post("/forum/{$post->post_id}/comments", [
            'content' => 'ab',
        ]);

        $response->assertSessionHasErrors(['content']);
        $this->assertDatabaseCount('forum_comments', 0);
    }

    public function test_user_cannot_comment_on_closed_post(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost('CLOSED');

        $response = $this->actingAs($user)->post("/forum/{$post->post_id}/comments", [
            'content' => 'Mencoba komentar pada post yang sudah ditutup.',
        ]);

        $response->assertRedirect("/forum/{$post->post_id}");
        $response->assertSessionHas('warning');
        $this->assertDatabaseCount('forum_comments', 0);
    }

    public function test_comment_author_can_delete_their_own_comment(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $user->user_id,
            'content' => 'Komentar yang ingin saya hapus.',
        ]);

        $response = $this->actingAs($user)->delete("/forum/comments/{$comment->comment_id}");

        $response->assertRedirect("/forum/{$post->post_id}");
        $response->assertSessionHas('info');
        $this->assertDatabaseMissing('forum_comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $commentAuthor = User::factory()->create();
        $admin = User::factory()->create(['account_type' => 'ADMIN']);
        $post = $this->createPost();

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $commentAuthor->user_id,
            'content' => 'Komentar yang melanggar aturan komunitas.',
        ]);

        $response = $this->actingAs($admin)->delete("/forum/comments/{$comment->comment_id}");

        $response->assertRedirect("/forum/{$post->post_id}");
        $response->assertSessionHas('info');
        $this->assertDatabaseMissing('forum_comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    public function test_non_author_non_admin_cannot_delete_comment(): void
    {
        $commentAuthor = User::factory()->create();
        $otherUser = User::factory()->create(['account_type' => 'USER']);
        $post = $this->createPost();

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $commentAuthor->user_id,
            'content' => 'Komentar sah orang lain.',
        ]);

        $response = $this->actingAs($otherUser)->delete("/forum/comments/{$comment->comment_id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('forum_comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    public function test_authenticated_user_can_toggle_like_and_unlike_post_atomically(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        // 1. Like the post
        $responseLike = $this->actingAs($user)->post("/forum/{$post->post_id}/like");
        $responseLike->assertRedirect();

        $this->assertDatabaseHas('forum_likes', [
            'user_id' => $user->user_id,
            'post_id' => $post->post_id,
            'comment_id' => null,
        ]);
        $this->assertSame(1, ForumLike::count());

        // 2. Unlike the post (toggle)
        $responseUnlike = $this->actingAs($user)->post("/forum/{$post->post_id}/like");
        $responseUnlike->assertRedirect();

        $this->assertDatabaseMissing('forum_likes', [
            'user_id' => $user->user_id,
            'post_id' => $post->post_id,
        ]);
        $this->assertSame(0, ForumLike::count());
    }

    public function test_authenticated_user_can_toggle_like_and_unlike_comment(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        $comment = ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $user->user_id,
            'content' => 'Komentar keren.',
        ]);

        // 1. Like the comment
        $responseLike = $this->actingAs($user)->post("/forum/comments/{$comment->comment_id}/like");
        $responseLike->assertRedirect();

        $this->assertDatabaseHas('forum_likes', [
            'user_id' => $user->user_id,
            'post_id' => null,
            'comment_id' => $comment->comment_id,
        ]);
        $this->assertSame(1, ForumLike::count());

        // 2. Unlike the comment
        $responseUnlike = $this->actingAs($user)->post("/forum/comments/{$comment->comment_id}/like");
        $responseUnlike->assertRedirect();

        $this->assertDatabaseMissing('forum_likes', [
            'user_id' => $user->user_id,
            'comment_id' => $comment->comment_id,
        ]);
        $this->assertSame(0, ForumLike::count());
    }

    public function test_like_endpoints_support_json_requests(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost();

        $responseJson = $this->actingAs($user)
            ->postJson("/forum/{$post->post_id}/like");

        $responseJson->assertOk();
        $responseJson->assertJson([
            'liked' => true,
            'likes_count' => 1,
        ]);

        $responseJsonUnlike = $this->actingAs($user)
            ->postJson("/forum/{$post->post_id}/like");

        $responseJsonUnlike->assertOk();
        $responseJsonUnlike->assertJson([
            'liked' => false,
            'likes_count' => 0,
        ]);
    }

    public function test_user_can_initiate_negotiation_conversation_and_author_cannot(): void
    {
        $postAuthor = User::factory()->create();
        $negotiator = User::factory()->create();
        $post = $this->createPost('ACTIVE', $postAuthor);

        // 1. Buyer initiates conversation
        $response = $this->actingAs($negotiator)->post("/forum/{$post->post_id}/negotiate");
        $response->assertRedirect("/forum/{$post->post_id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('conversations', [
            'post_id' => $post->post_id,
            'participant_one_id' => min($negotiator->user_id, $postAuthor->user_id),
            'participant_two_id' => max($negotiator->user_id, $postAuthor->user_id),
        ]);

        // 2. Author tries to negotiate with own post
        $responseAuthor = $this->actingAs($postAuthor)->post("/forum/{$post->post_id}/negotiate");
        $responseAuthor->assertRedirect("/forum/{$post->post_id}");
        $responseAuthor->assertSessionHas('warning');

        // Total conversations remains 1 (idempotent / no invalid self-conversation)
        $this->assertSame(1, Conversation::count());
    }
}
