<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Services\ForumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumLikeController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    /**
     * Toggle like/unlike on a forum post.
     */
    public function togglePost(Request $request, ForumPost $post): JsonResponse|RedirectResponse
    {
        $result = $this->forumService->toggleLikePost($request->user(), $post);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $message = $result['liked'] ? 'Postingan disukai!' : 'Batal menyukai postingan.';

        return back()->with('info', $message);
    }

    /**
     * Toggle like/unlike on a forum comment.
     */
    public function toggleComment(Request $request, ForumComment $comment): JsonResponse|RedirectResponse
    {
        $result = $this->forumService->toggleLikeComment($request->user(), $comment);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        $message = $result['liked'] ? 'Komentar disukai!' : 'Batal menyukai komentar.';

        return back()->with('info', $message);
    }
}
