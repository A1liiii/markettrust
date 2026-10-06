<?php

namespace App\Models;

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
}
