<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumComment extends Model
{
    use HasFactory;

    protected $table = 'forum_comments';

    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'post_id',
        'author_id',
        'content',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id', 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumLike::class, 'comment_id', 'comment_id');
    }
}
