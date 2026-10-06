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
}
