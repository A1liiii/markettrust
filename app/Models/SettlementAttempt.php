<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementAttempt extends Model
{
    use HasFactory;

    protected $table = 'settlement_attempts';

    protected $primaryKey = 'attempt_id';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_UNKNOWN = 'UNKNOWN';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const CATEGORY_NONE = 'NONE';

    public const CATEGORY_SYSTEM = 'SYSTEM';

    public const CATEGORY_SELLER_DESTINATION = 'SELLER_DESTINATION';

    public const CATEGORY_GATEWAY_UNKNOWN = 'GATEWAY_UNKNOWN';

    protected $fillable = [
        'settlement_id',
        'attempt_no',
        'status',
        'failure_category',
        'destination_type',
        'destination_provider',
        'destination_number',
        'destination_holder',
        'destination_qris_image_url',
        'amount_attempted',
        'reference_no',
        'failure_reason',
        'follow_up_action',
        'requires_destination_update',
        'is_system_disruption_announced',
        'recorded_by',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_no' => 'integer',
            'amount_attempted' => 'decimal:2',
            'requires_destination_update' => 'boolean',
            'is_system_disruption_announced' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id', 'settlement_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isUnknown(): bool
    {
        return $this->status === self::STATUS_UNKNOWN;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
}
