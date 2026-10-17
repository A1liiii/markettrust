<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agreement extends Model
{
    use HasFactory;

    protected $table = 'agreements';

    protected $primaryKey = 'agreement_id';

    protected $fillable = [
        'created_by',
        'buyer_id',
        'seller_id',
        'variant_id',
        'item_title',
        'item_description',
        'quantity',
        'base_price',
        'fee_type',
        'fee_amount',
        'buyer_total',
        'seller_net',
        'delivery_method',
        'delivery_within_minutes',
        'agreement_note',
        'revision_no',
        'status',
        'buyer_accepted_at',
        'seller_accepted_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'base_price' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'buyer_total' => 'decimal:2',
            'seller_net' => 'decimal:2',
            'delivery_within_minutes' => 'integer',
            'revision_no' => 'integer',
            'fee_type' => FeeType::class,
            'status' => AgreementStatus::class,
            'buyer_accepted_at' => 'datetime',
            'seller_accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'agreement_id', 'agreement_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'agreement_id', 'agreement_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'agreement_id', 'agreement_id');
    }
}
