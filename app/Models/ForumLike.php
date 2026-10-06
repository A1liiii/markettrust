<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumLike extends Model
{
    use HasFactory;

    protected $table = 'forum_likes';

    protected $primaryKey = 'like_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'comment_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id', 'post_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'comment_id', 'comment_id');
    }
}
