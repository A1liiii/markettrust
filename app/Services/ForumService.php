<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ForumComment;
use App\Models\ForumLike;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ForumService
{
    public function __construct(
        protected SupabaseStorageService $storageService
    ) {}

    /**
     * Retrieve paginated active forum posts with optional category, search, and variant filters.
     */
    public function getFeedPosts(?string $category = 'GENERAL', ?string $search = null, ?int $variantId = null, int $perPage = 12): LengthAwarePaginator
    {
        $selectedCategory = ($category && in_array($category, ['GENERAL', 'WTB', 'WTS'], true)) ? $category : 'GENERAL';

        $query = ForumPost::with(['author', 'variant.product'])
            ->withCount(['comments', 'likes'])
            ->where('status', 'ACTIVE')
            ->where('category_code', $selectedCategory);

        if ($variantId && in_array($selectedCategory, ['WTB', 'WTS'], true)) {
            $query->where('variant_id', $variantId);
        }

        if ($search && trim($search) !== '') {
            $term = '%'.mb_strtolower(trim($search)).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
            });
        }

        return $query->latest('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new forum post with optional uploaded images.
     *
     * @param  array<string, mixed>  $data
     * @param  array<UploadedFile>|null  $uploadedFiles
     */
    public function createPost(User $user, array $data, ?array $uploadedFiles = null): ForumPost
    {
        $imageUrls = [];

        if (! empty($uploadedFiles)) {
            try {
                foreach ($uploadedFiles as $file) {
                    if ($file instanceof UploadedFile) {
                        $imageUrls[] = $this->storageService->uploadFile($file);
                    }
                }
            } catch (\Throwable $e) {
                foreach ($imageUrls as $url) {
                    $this->storageService->deleteFile($url);
                }
                throw $e;
            }
        }

        $category = $data['category_code'];
        $variantId = $category === 'GENERAL' ? null : ($data['variant_id'] ?? null);

        return ForumPost::create([
            'author_id' => $user->user_id,
            'category_code' => $category,
            'variant_id' => $variantId,
            'title' => $data['title'],
            'content' => $data['content'],
            'image_urls' => ! empty($imageUrls) ? array_values($imageUrls) : null,
            'price_offer' => $data['price_offer'] ?? null,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Update an existing forum post.
     *
     * @param  array<string, mixed>  $data
     * @param  array<UploadedFile>|null  $newFiles
     * @param  array<string>|null  $keepImages
     */
    public function updatePost(ForumPost $post, array $data, ?array $newFiles = null, ?array $keepImages = null): ForumPost
    {
        $existingImages = is_array($post->image_urls) ? $post->image_urls : [];
        $finalImages = [];

        // 1. Filter existing images that the author explicitly wanted to keep
        if (is_array($keepImages)) {
            foreach ($keepImages as $url) {
                if (in_array($url, $existingImages, true)) {
                    $finalImages[] = $url;
                }
            }
        }

        // 2. Upload new files first, rolling back if upload fails
        $newUploadedUrls = [];
        if (! empty($newFiles)) {
            try {
                foreach ($newFiles as $file) {
                    if ($file instanceof UploadedFile && (count($finalImages) + count($newUploadedUrls)) < 4) {
                        $newUploadedUrls[] = $this->storageService->uploadFile($file);
                    }
                }
            } catch (\Throwable $e) {
                foreach ($newUploadedUrls as $url) {
                    $this->storageService->deleteFile($url);
                }
                throw $e;
            }
        }

        // 3. Remove discarded images from storage after new uploads succeed
        $discardedImages = array_diff($existingImages, $finalImages);
        foreach ($discardedImages as $discardedUrl) {
            $this->storageService->deleteFile($discardedUrl);
        }

        $allImages = array_merge($finalImages, $newUploadedUrls);

        $category = $data['category_code'];
        $variantId = $category === 'GENERAL' ? null : ($data['variant_id'] ?? null);

        $post->update([
            'category_code' => $category,
            'variant_id' => $variantId,
            'title' => $data['title'],
            'content' => $data['content'],
            'image_urls' => ! empty($allImages) ? array_values($allImages) : null,
            'price_offer' => $data['price_offer'] ?? null,
        ]);

        return $post->fresh();
    }

    /**
     * Close an active forum post.
     */
    public function closePost(ForumPost $post): void
    {
        $post->update(['status' => 'CLOSED']);
    }

    /**
     * Add a comment to an active forum post.
     */
    public function addComment(User $user, ForumPost $post, string $content): ForumComment
    {
        if ($post->status !== 'ACTIVE') {
            throw new InvalidArgumentException('Tidak dapat menambahkan komentar pada postingan yang sudah tidak aktif.');
        }

        return ForumComment::create([
            'post_id' => $post->post_id,
            'author_id' => $user->user_id,
            'content' => $content,
        ]);
    }

    /**
     * Delete a forum comment.
     */
    public function deleteComment(ForumComment $comment): void
    {
        $comment->delete();
    }

    /**
     * Toggle like/unlike on a forum post.
     *
     * @return array{liked: bool, likes_count: int}
     */
    public function toggleLikePost(User $user, ForumPost $post): array
    {
        $existing = ForumLike::where('user_id', $user->user_id)
            ->where('post_id', $post->post_id)
            ->first();

        if ($existing) {
            $existing->delete();

            return [
                'liked' => false,
                'likes_count' => $post->likes()->count(),
            ];
        }

        ForumLike::create([
            'user_id' => $user->user_id,
            'post_id' => $post->post_id,
            'comment_id' => null,
            'created_at' => now(),
        ]);

        return [
            'liked' => true,
            'likes_count' => $post->likes()->count(),
        ];
    }

    /**
     * Toggle like/unlike on a forum comment.
     *
     * @return array{liked: bool, likes_count: int}
     */
    public function toggleLikeComment(User $user, ForumComment $comment): array
    {
        $existing = ForumLike::where('user_id', $user->user_id)
            ->where('comment_id', $comment->comment_id)
            ->first();

        if ($existing) {
            $existing->delete();

            return [
                'liked' => false,
                'likes_count' => $comment->likes()->count(),
            ];
        }

        ForumLike::create([
            'user_id' => $user->user_id,
            'post_id' => null,
            'comment_id' => $comment->comment_id,
            'created_at' => now(),
        ]);

        return [
            'liked' => true,
            'likes_count' => $comment->likes()->count(),
        ];
    }

    /**
     * Initiate or retrieve an existing DM conversation related to a forum post.
     */
    public function initiateConversation(User $user, ForumPost $post): Conversation
    {
        if ($user->user_id === $post->author_id) {
            throw new InvalidArgumentException('Anda tidak dapat memulai negosiasi dengan postingan milik Anda sendiri.');
        }

        return Conversation::firstOrCreate([
            'participant_one_id' => min($user->user_id, $post->author_id),
            'participant_two_id' => max($user->user_id, $post->author_id),
            'post_id' => $post->post_id,
        ]);
    }
}
