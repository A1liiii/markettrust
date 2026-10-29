<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeepObligation extends Model
{
    use HasFactory;

    protected $table = 'keep_obligations';

    protected $primaryKey = 'keep_obligation_id';

    public const RULE_BANK_VA_FLAT = 'BANK_VA_FLAT';

    public const RULE_OTHER_TRIPLE_GATEWAY_FEE = 'OTHER_TRIPLE_GATEWAY_FEE';

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_SETTLED = 'SETTLED';

    protected $fillable = [
        'cancellation_id',
        'seller_id',
        'payment_channel_snapshot',
        'calculation_rule',
        'fee_basis_amount',
        'multiplier',
        'amount',
        'remaining_amount',
        'status',
    ];

    protected $casts = [
        'fee_basis_amount' => 'decimal:2',
        'multiplier' => 'integer',
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cancellation(): BelongsTo
    {
        return $this->belongsTo(TransactionCancellation::class, 'cancellation_id', 'cancellation_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN && (float) $this->remaining_amount > 0;
    }
}
