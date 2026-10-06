<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $primaryKey = 'variant_id';

    protected $fillable = [
        'product_id',
        'variant_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'variant_id', 'variant_id');
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'variant_id', 'variant_id');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'variant_id', 'variant_id');
    }
}
