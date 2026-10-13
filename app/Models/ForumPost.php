<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    use HasFactory;

    protected $table = 'forum_posts';

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'author_id',
        'category_code',
        'variant_id',
        'title',
        'content',
        'image_urls',
        'price_offer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'image_urls' => 'array',
            'price_offer' => 'decimal:2',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'post_id', 'post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumLike::class, 'post_id', 'post_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'post_id', 'post_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'post_id', 'post_id');
    }

    /**
     * Format timestamp in Indonesian locale and WIB timezone.
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
