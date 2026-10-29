<?php

namespace App\Models;

use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'agreement_id',
        'source_type',
        'listing_id',
        'post_id',
        'status',
        'payment_due_at',
        'delivery_due_at',
        'confirmation_due_at',
        'delivery_note',
        'result_link',
        'delivered_at',
        'confirmed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => TransactionSourceType::class,
            'status' => TransactionStatus::class,
            'payment_due_at' => 'datetime',
            'delivery_due_at' => 'datetime',
            'confirmation_due_at' => 'datetime',
            'delivered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id', 'agreement_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id', 'listing_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id', 'post_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class, 'transaction_id', 'transaction_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'transaction_id');
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class, 'transaction_id', 'transaction_id');
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class, 'transaction_id', 'transaction_id');
    }

    public function accountReports(): HasMany
    {
        return $this->hasMany(AccountReport::class, 'transaction_id', 'transaction_id');
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class, 'transaction_id', 'transaction_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'delivery_transaction_id', 'transaction_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'transaction_id', 'transaction_id');
    }

    public function deadlineEvents(): HasMany
    {
        return $this->hasMany(TransactionDeadlineEvent::class, 'transaction_id', 'transaction_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(TransactionCancellation::class, 'transaction_id', 'transaction_id');
    }

    public function latestCancellation(): HasOne
    {
        return $this->hasOne(TransactionCancellation::class, 'transaction_id', 'transaction_id')
            ->latestOfMany('cancellation_id');
    }

    public function pendingCancellation(): HasOne
    {
        return $this->hasOne(TransactionCancellation::class, 'transaction_id', 'transaction_id')
            ->where('status', TransactionCancellation::STATUS_REQUESTED)
            ->latestOfMany('cancellation_id');
    }

    public function hasPendingCancellation(): bool
    {
        return $this->cancellations()
            ->where('status', TransactionCancellation::STATUS_REQUESTED)
            ->exists();
    }

    public function approvedPostPaymentCancellation(): ?TransactionCancellation
    {
        if ($this->relationLoaded('cancellations')) {
            return $this->cancellations
                ->sortByDesc('cancellation_id')
                ->first(fn (TransactionCancellation $c) => $c->status === TransactionCancellation::STATUS_APPROVED
                    && $c->stage === TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY);
        }

        return $this->cancellations()
            ->where('status', TransactionCancellation::STATUS_APPROVED)
            ->where('stage', TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY)
            ->latest('cancellation_id')
            ->first();
    }

    public function hasActiveComplaint(): bool
    {
        $complaint = $this->relationLoaded('complaint')
            ? $this->complaint
            : $this->complaint()->first();

        if (! $complaint) {
            return false;
        }

        return in_array(strtoupper((string) $complaint->status), ['OPEN', 'REVIEWING'], true);
    }

    public function isEligibleForPayout(): bool
    {
        $statusStr = $this->status instanceof TransactionStatus ? $this->status->value : (string) $this->status;

        if (in_array($statusStr, ['COMPLETED', 'REFUNDED', 'CANCELLED', 'WAITING_PAYMENT'], true)) {
            return false;
        }

        if ($this->hasActiveComplaint()) {
            return false;
        }

        $settlement = $this->relationLoaded('settlement') ? $this->settlement : $this->settlement()->first();
        if ($settlement && $settlement->status === Settlement::STATUS_COMPLETED) {
            return false;
        }

        if ($statusStr === 'CONFIRMED') {
            return true;
        }

        $complaint = $this->relationLoaded('complaint') ? $this->complaint : $this->complaint()->first();

        return $complaint !== null
            && strtoupper((string) $complaint->status) === 'RESOLVED'
            && strtoupper((string) $complaint->decision) === 'PAYOUT';
    }

    public function isEligibleForRefund(): bool
    {
        $statusStr = $this->status instanceof TransactionStatus ? $this->status->value : (string) $this->status;

        if (in_array($statusStr, ['COMPLETED', 'REFUNDED', 'WAITING_PAYMENT'], true)) {
            return false;
        }

        if ($this->hasActiveComplaint()) {
            return false;
        }

        $settlement = $this->relationLoaded('settlement') ? $this->settlement : $this->settlement()->first();
        if ($settlement && $settlement->status === Settlement::STATUS_COMPLETED) {
            return false;
        }

        $complaint = $this->relationLoaded('complaint') ? $this->complaint : $this->complaint()->first();
        if ($complaint && strtoupper((string) $complaint->status) === 'RESOLVED' && strtoupper((string) $complaint->decision) === 'REFUND') {
            return true;
        }

        return $this->approvedPostPaymentCancellation() !== null;
    }

    public function isDeliveryOverdue(): bool
    {
        $statusStr = $this->status instanceof TransactionStatus ? $this->status->value : (string) $this->status;

        return $statusStr === 'PAID'
            && $this->delivery_due_at !== null
            && now()->greaterThan($this->delivery_due_at)
            && $this->delivered_at === null;
    }

    public function hasDeliveryExtension(): bool
    {
        return $this->deadlineEvents()
            ->where('event_type', 'DELIVERY_EXTENSION')
            ->exists();
    }

    public function isFlaggedForAdmin(): bool
    {
        return $this->deadlineEvents()
            ->where('is_flagged_for_admin', true)
            ->exists();
    }
}
