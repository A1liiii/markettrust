<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StoreForumCommentRequest;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Services\ForumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ForumCommentController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    /**
     * Store a newly created comment on a forum post.
     */
    public function store(StoreForumCommentRequest $request, ForumPost $post): RedirectResponse|JsonResponse
    {
        if ($post->status !== 'ACTIVE') {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Postingan ini sudah tidak aktif sehingga tidak dapat menerima komentar baru.',
                ], 422);
            }

            return redirect()->route('forum.show', $post->post_id)
                ->with('warning', 'Postingan ini sudah tidak aktif sehingga tidak dapat menerima komentar baru.');
        }

        $comment = $this->forumService->addComment($request->user(), $post, $request->validated('content'));
        $comment->load('author');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil dipublikasikan!',
                'comment' => [
                    'comment_id' => $comment->comment_id,
                    'author_username' => $comment->author->username,
                    'author_avatar' => $comment->author->avatar_url,
                    'is_post_author' => $comment->author_id === $post->author_id,
                    'content' => $comment->content,
                    'formatted_time' => $comment->formatted_time,
                    'can_delete' => true,
                    'likes_count' => 0,
                    'user_liked' => false,
                    'delete_url' => route('forum.comments.destroy', $comment->comment_id),
                    'like_url' => route('forum.comments.like', $comment->comment_id),
                ],
                'comments_count' => $post->comments()->count(),
            ]);
        }

        return redirect()->route('forum.show', $post->post_id)
            ->with('success', 'Komentar berhasil dipublikasikan!');
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(Request $request, ForumComment $comment): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $comment);

        $postId = $comment->post_id;
        $this->forumService->deleteComment($comment);

        if ($request->wantsJson()) {
            $post = ForumPost::find($postId);

            return response()->json([
                'success' => true,
                'message' => 'Komentar telah dihapus.',
                'comments_count' => $post ? $post->comments()->count() : 0,
            ]);
        }

        return redirect()->route('forum.show', $postId)
            ->with('info', 'Komentar telah dihapus.');
    }
}
