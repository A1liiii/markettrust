<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Settlement extends Model
{
    use HasFactory;

    protected $table = 'settlements';

    protected $primaryKey = 'settlement_id';

    public const TYPE_PAYOUT = 'PAYOUT';

    public const TYPE_REFUND = 'REFUND';

    public const STATUS_PLANNED = 'PLANNED';

    public const STATUS_READY = 'READY';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'transaction_id',
        'complaint_id',
        'settlement_type',
        'destination_type',
        'destination_provider',
        'destination_number',
        'destination_holder',
        'destination_qris_image_url',
        'gross_amount',
        'keep_deducted_amount',
        'amount',
        'status',
        'payout_due_at',
        'recorded_by',
        'reference_no',
        'note',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'keep_deducted_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'payout_due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id', 'complaint_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'settlement_id', 'settlement_id');
    }

    public function keepDeductions(): HasMany
    {
        return $this->hasMany(KeepDeduction::class, 'settlement_id', 'settlement_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(SettlementAttempt::class, 'settlement_id', 'settlement_id')
            ->orderByDesc('attempt_no');
    }

    public function latestAttempt(): HasOne
    {
        return $this->hasOne(SettlementAttempt::class, 'settlement_id', 'settlement_id')
            ->latestOfMany('attempt_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasUnresolvedFailure(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        $latest = $this->relationLoaded('latestAttempt')
            ? $this->latestAttempt
            : $this->latestAttempt()->first();

        return $latest !== null && in_array($latest->status, [
            SettlementAttempt::STATUS_FAILED,
            SettlementAttempt::STATUS_UNKNOWN,
        ], true);
    }

    public function requiresSellerDestinationUpdate(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        $latest = $this->relationLoaded('latestAttempt')
            ? $this->latestAttempt
            : $this->latestAttempt()->first();

        return $latest !== null && (bool) $latest->requires_destination_update;
    }
}
