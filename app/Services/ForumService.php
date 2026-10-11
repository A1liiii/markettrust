<?php

namespace App\Services;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ForumService
{
    /**
     * Retrieve paginated active forum posts with optional category, search, and variant filters.
     */
    public function getFeedPosts(?string $category = null, ?string $search = null, ?int $variantId = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = ForumPost::with(['author', 'variant.product'])
            ->where('status', 'ACTIVE');

        if ($category && in_array($category, ['GENERAL', 'WTB', 'WTS'], true)) {
            $query->where('category_code', $category);
        }

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        if ($search && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('content', 'like', $term);
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
            foreach ($uploadedFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $path = $file->store('forum_images', 'public');
                    $imageUrls[] = Storage::url($path);
                }
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

        // 2. Remove discarded images from storage
        $discardedImages = array_diff($existingImages, $finalImages);
        foreach ($discardedImages as $discardedUrl) {
            $relativePath = str_replace('/storage/', '', $discardedUrl);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        // 3. Store new uploaded files
        if (! empty($newFiles)) {
            foreach ($newFiles as $file) {
                if ($file instanceof UploadedFile && count($finalImages) < 4) {
                    $path = $file->store('forum_images', 'public');
                    $finalImages[] = Storage::url($path);
                }
            }
        }

        $category = $data['category_code'];
        $variantId = $category === 'GENERAL' ? null : ($data['variant_id'] ?? null);

        $post->update([
            'category_code' => $category,
            'variant_id' => $variantId,
            'title' => $data['title'],
            'content' => $data['content'],
            'image_urls' => ! empty($finalImages) ? array_values($finalImages) : null,
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
}
