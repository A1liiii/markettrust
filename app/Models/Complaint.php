<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Complaint extends Model
{
    use HasFactory;

    protected $table = 'complaints';

    protected $primaryKey = 'complaint_id';

    protected $fillable = [
        'transaction_id',
        'raised_by',
        'reason',
        'status',
        'decision',
        'decision_reason',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by', 'user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by', 'user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ComplaintResponse::class, 'complaint_id', 'complaint_id');
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class, 'complaint_id', 'complaint_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'complaint_id', 'complaint_id');
    }
}
