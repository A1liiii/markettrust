<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StoreForumPostRequest;
use App\Http\Requests\Forum\UpdateForumPostRequest;
use App\Models\ForumPost;
use App\Models\ProductVariant;
use App\Services\ForumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ForumPostController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    /**
     * Display a listing of active forum posts.
     */
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $search = $request->query('q');
        $variantId = $request->query('variant_id') ? (int) $request->query('variant_id') : null;

        $posts = $this->forumService->getFeedPosts($category, $search, $variantId);

        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('variant_name')
            ->get();

        return view('forum.index', compact('posts', 'variants', 'category', 'search', 'variantId'));
    }

    /**
     * Show the form for creating a new forum post.
     */
    public function create(): View
    {
        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('variant_name')
            ->get();

        return view('forum.create', compact('variants'));
    }

    /**
     * Store a newly created forum post in storage.
     */
    public function store(StoreForumPostRequest $request): RedirectResponse
    {
        $post = $this->forumService->createPost(
            $request->user(),
            $request->validated(),
            $request->file('images')
        );

        return redirect()->route('forum.show', $post->post_id)
            ->with('success', 'Postingan forum berhasil dipublikasikan!');
    }

    /**
     * Display the specified forum post.
     */
    public function show(ForumPost $post): View
    {
        $post->load([
            'author',
            'variant.product',
            'likes',
            'comments' => function ($q) {
                $q->with(['author', 'likes'])->oldest('created_at');
            },
        ])->loadCount(['likes', 'comments']);

        return view('forum.show', compact('post'));
    }

    /**
     * Show the form for editing the specified forum post.
     */
    public function edit(ForumPost $post): View
    {
        Gate::authorize('update', $post);

        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('variant_name')
            ->get();

        return view('forum.edit', compact('post', 'variants'));
    }

    /**
     * Update the specified forum post in storage.
     */
    public function update(UpdateForumPostRequest $request, ForumPost $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $this->forumService->updatePost(
            $post,
            $request->validated(),
            $request->file('images'),
            $request->input('keep_images')
        );

        return redirect()->route('forum.show', $post->post_id)
            ->with('success', 'Postingan forum berhasil diperbarui!');
    }

    /**
     * Close the specified forum post.
     */
    public function close(ForumPost $post): RedirectResponse
    {
        Gate::authorize('close', $post);

        $this->forumService->closePost($post);

        return redirect()->route('forum.show', $post->post_id)
            ->with('info', 'Postingan forum telah ditutup.');
    }

    /**
     * Initiate negotiation conversation related to the forum post.
     */
    public function negotiate(Request $request, ForumPost $post): RedirectResponse
    {
        if ($request->user()->user_id === $post->author_id) {
            return redirect()->route('forum.show', $post->post_id)
                ->with('warning', 'Anda tidak dapat memulai percakapan negosiasi dengan postingan Anda sendiri.');
        }

        $conversation = $this->forumService->initiateConversation($request->user(), $post);

        return redirect()->route('forum.show', [
            'post' => $post->post_id,
            'open_dm' => $conversation->conversation_id,
        ]);
    }
}
