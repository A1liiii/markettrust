<?php

namespace App\Policies;

use App\Models\ForumPost;
use App\Models\User;

class ForumPostPolicy
{
    /**
     * Determine whether the user can update the forum post.
     */
    public function update(User $user, ForumPost $post): bool
    {
        return $user->user_id === $post->author_id;
    }

    /**
     * Determine whether the user can close the forum post.
     */
    public function close(User $user, ForumPost $post): bool
    {
        return $user->user_id === $post->author_id;
    }

    /**
     * Determine whether the user can delete the forum post.
     */
    public function delete(User $user, ForumPost $post): bool
    {
        return $user->user_id === $post->author_id || $user->account_type === 'ADMIN';
    }
}
