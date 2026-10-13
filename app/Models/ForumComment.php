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

    /**
     * Format timestamp according to TC-FR-02 & TC-FR-03:
     * - Under 7 days: relative time (diffForHumans) in Indonesian.
     * - 7 days or older: exact date and time in WIB (e.g. "12 Okt 2026, 14:30 WIB").
     */
    public function getFormattedTimeAttribute(): string
    {
        if (! $this->created_at) {
            return '';
        }

        $time = $this->created_at->timezone('Asia/Jakarta');
        if ($time->diffInDays(now('Asia/Jakarta')) < 7) {
            return $time->locale('id')->diffForHumans();
        }

        return $time->locale('id')->translatedFormat('d M Y, H:i').' WIB';
    }

    /**
     * Get exact date and time in WIB.
     */
    public function getExactWibTimeAttribute(): string
    {
        if (! $this->created_at) {
            return '';
        }

        return $this->created_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i').' WIB';
    }
}
