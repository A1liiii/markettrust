<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDeadlineEvent extends Model
{
    use HasFactory;

    protected $table = 'transaction_deadline_events';

    protected $primaryKey = 'deadline_event_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'event_type',
        'previous_deadline',
        'new_deadline',
        'extension_minutes',
        'reason',
        'granted_by',
        'is_flagged_for_admin',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_deadline' => 'datetime',
            'new_deadline' => 'datetime',
            'extension_minutes' => 'integer',
            'is_flagged_for_admin' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');
    }
}
