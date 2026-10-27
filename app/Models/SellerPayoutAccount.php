<?php

namespace App\Models;

use App\DTOs\PayoutDestinationDTO;
use App\Enums\PayoutDestinationType;
use Database\Factories\SellerPayoutAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SellerPayoutAccount extends Model
{
    /** @use HasFactory<SellerPayoutAccountFactory> */
    use HasFactory;

    protected $table = 'seller_payout_accounts';

    protected $primaryKey = 'seller_payout_account_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'seller_id',
        'destination_type',
        'destination_provider',
        'destination_number',
        'destination_holder',
        'destination_qris_image_url',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'destination_type' => PayoutDestinationType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Seller pemilik akun payout.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    /**
     * Scope untuk rekening yang berstatus aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk rekening milik seller tertentu.
     */
    public function scopeForSeller(Builder $query, int $sellerId): Builder
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Konversi ke PayoutDestinationDTO standar.
     */
    public function toDTO(): PayoutDestinationDTO
    {
        return new PayoutDestinationDTO(
            destinationType: $this->destination_type,
            destinationHolder: $this->destination_holder,
            destinationProvider: $this->destination_provider,
            destinationNumber: $this->destination_number,
            destinationQrisImageUrl: $this->destination_qris_image_url
        );
    }

    /**
     * Snapshot tujuan payout untuk dicatat ke tabel settlements (MT-A07).
     *
     * @return array<string, mixed>
     */
    public function toSettlementSnapshot(): array
    {
        return [
            'destination_type' => $this->destination_type instanceof PayoutDestinationType
                ? $this->destination_type->value
                : (string) $this->destination_type,
            'destination_provider' => $this->destination_provider,
            'destination_number' => $this->destination_number,
            'destination_holder' => $this->destination_holder,
            'destination_qris_image_url' => $this->destination_qris_image_url,
        ];
    }

    /**
     * Cek apakah kelengkapan data rekening payout memenuhi syarat pencairan dana.
     */
    public function isComplete(): bool
    {
        if (empty(trim((string) $this->destination_holder))) {
            return false;
        }

        $type = $this->destination_type instanceof PayoutDestinationType
            ? $this->destination_type
            : PayoutDestinationType::tryFrom(strtoupper((string) $this->destination_type));

        return match ($type) {
            PayoutDestinationType::BANK, PayoutDestinationType::EWALLET => ! empty($this->destination_provider) && ! empty($this->destination_number),
            PayoutDestinationType::QRIS => ! empty($this->destination_qris_image_url) || ! empty($this->destination_number),
            default => false,
        };
    }

    /**
     * Accessor nomor rekening tersamar untuk perlindungan data sensitif.
     */
    public function getMaskedDestinationNumberAttribute(): string
    {
        $raw = trim((string) $this->destination_number);

        if (empty($raw)) {
            return '-';
        }

        if (strlen($raw) <= 4) {
            return '•••• '.$raw;
        }

        $lastFour = substr($raw, -4);

        return '•••• •••• '.$lastFour;
    }

    /**
     * Accessor URL lengkap untuk gambar QRIS jika tersedia.
     */
    public function getQrisImageFullUrlAttribute(): ?string
    {
        if (empty($this->destination_qris_image_url)) {
            return null;
        }

        if (str_starts_with($this->destination_qris_image_url, 'http://') || str_starts_with($this->destination_qris_image_url, 'https://')) {
            return $this->destination_qris_image_url;
        }

        return Storage::disk('public')->url(ltrim($this->destination_qris_image_url, '/'));
    }
}
