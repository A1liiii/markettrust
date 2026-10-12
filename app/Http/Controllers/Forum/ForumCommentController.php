<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StoreForumCommentRequest;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Services\ForumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ForumCommentController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    /**
     * Store a newly created comment on a forum post.
     */
    public function store(StoreForumCommentRequest $request, ForumPost $post): RedirectResponse
    {
        if ($post->status !== 'ACTIVE') {
            return redirect()->route('forum.show', $post->post_id)
                ->with('warning', 'Postingan ini sudah tidak aktif sehingga tidak dapat menerima komentar baru.');
        }

        $this->forumService->addComment($request->user(), $post, $request->validated('content'));

        return redirect()->route('forum.show', $post->post_id)
            ->with('success', 'Komentar berhasil dipublikasikan!');
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(ForumComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $postId = $comment->post_id;
        $this->forumService->deleteComment($comment);

        return redirect()->route('forum.show', $postId)
            ->with('info', 'Komentar telah dihapus.');
    }
}
