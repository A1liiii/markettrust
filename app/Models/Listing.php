<?php

namespace App\Models;

use App\Contracts\Services\FeeCalculationServiceInterface;
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
        'image_urls',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'estimated_delivery_value' => 'integer',
            'image_urls' => 'array',
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

    /**
     * Scope query untuk listing milik seller tertentu.
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope query untuk listing berstatus aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Format estimasi durasi penyerahan ke teks ramah pengguna (contoh: "30 Menit", "2 Jam").
     */
    public function getEstimatedDeliveryFormattedAttribute(): string
    {
        $unitLabel = match ($this->estimated_delivery_unit) {
            'MINUTE' => 'Menit',
            'HOUR' => 'Jam',
            'DAY' => 'Hari',
            default => $this->estimated_delivery_unit,
        };

        return "{$this->estimated_delivery_value} {$unitLabel}";
    }

    /**
     * Periksa apakah listing dimiliki oleh pengguna tertentu.
     */
    public function isOwnedBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->user_id : $user;

        return $this->seller_id === $userId;
    }

    /**
     * Dapatkan URL gambar sampul utama listing.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (is_array($this->image_urls) && ! empty($this->image_urls)) {
            return $this->image_urls[0];
        }

        return null;
    }

    /**
     * Dapatkan seluruh URL gambar yang valid.
     *
     * @return list<string>
     */
    public function getAllImageUrlsAttribute(): array
    {
        return is_array($this->image_urls) ? array_values($this->image_urls) : [];
    }

    /**
     * Konversi durasi estimasi penyerahan ke total menit.
     */
    public function getEstimatedMinutesAttribute(): int
    {
        return match ($this->estimated_delivery_unit) {
            'HOUR' => $this->estimated_delivery_value * 60,
            'DAY' => $this->estimated_delivery_value * 1440,
            default => $this->estimated_delivery_value,
        };
    }

    /**
     * Total harga yang dibayar oleh pembeli (harga seller + fee layanan).
     */
    public function getBuyerTotalAttribute(): float
    {
        $feeService = app(FeeCalculationServiceInterface::class);

        return $feeService->calculateForMarketplace((float) $this->price)->buyerTotal;
    }
}
