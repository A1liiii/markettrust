<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransactionCancellation extends Model
{
    use HasFactory;

    protected $table = 'transaction_cancellations';

    protected $primaryKey = 'cancellation_id';

    public const STAGE_BEFORE_PAYMENT = 'BEFORE_PAYMENT';

    public const STAGE_AFTER_PAYMENT_BEFORE_DELIVERY = 'AFTER_PAYMENT_BEFORE_DELIVERY';

    public const STATUS_REQUESTED = 'REQUESTED';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const PARTY_BUYER = 'BUYER';

    public const PARTY_SELLER = 'SELLER';

    public const PARTY_PLATFORM = 'PLATFORM';

    public const PARTY_UNDETERMINED = 'UNDETERMINED';

    protected $fillable = [
        'transaction_id',
        'requested_by',
        'stage',
        'reason',
        'status',
        'responsible_party',
        'reviewed_by',
        'decision_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function keepObligation(): HasOne
    {
        return $this->hasOne(KeepObligation::class, 'cancellation_id', 'cancellation_id');
    }

    public function isRequested(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isBeforePayment(): bool
    {
        return $this->stage === self::STAGE_BEFORE_PAYMENT;
    }

    public function isAfterPaymentBeforeDelivery(): bool
    {
        return $this->stage === self::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY;
    }
}
