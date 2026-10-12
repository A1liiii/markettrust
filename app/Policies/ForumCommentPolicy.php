<?php

namespace App\Policies;

use App\Models\ForumComment;
use App\Models\User;

class ForumCommentPolicy
{
    /**
     * Determine whether the user can delete the forum comment.
     */
    public function delete(User $user, ForumComment $comment): bool
    {
        return $user->user_id === $comment->author_id || $user->account_type === 'ADMIN';
    }
}
