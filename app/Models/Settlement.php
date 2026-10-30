<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    use HasFactory;

    protected $table = 'settlements';

    protected $primaryKey = 'settlement_id';

    protected $fillable = [
        'transaction_id',
        'complaint_id',
        'settlement_type',
        'destination_type',
        'destination_provider',
        'destination_number',
        'destination_holder',
        'destination_qris_image_url',
        'amount',
        'gross_amount',
        'keep_deducted_amount',
        'status',
        'recorded_by',
        'reference_no',
        'note',
        'payout_due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'keep_deducted_amount' => 'decimal:2',
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
}
