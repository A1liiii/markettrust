<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    protected $table = 'listings';

    protected $primaryKey = 'listing_id';

    protected $fillable = [
        'seller_id',
        'variant_id',
        'title',
        'description',
        'price',
        'delivery_method',
        'estimated_delivery_value',
        'estimated_delivery_unit',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'estimated_delivery_value' => 'integer',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'listing_id', 'listing_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'listing_id', 'listing_id');
    }
}
