<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReputationEvent extends Model
{
    use HasFactory;

    protected $table = 'reputation_events';

    protected $primaryKey = 'reputation_event_id';

    public const EVENT_ADMIN_MODERATION = 'ADMIN_MODERATION';

    public $timestamps = false;

    public static function generateReportEventKey(int|string $reportId): string
    {
        return "account_report:{$reportId}:resolution";
    }

    protected $fillable = [
        'user_id',
        'event_type',
        'points_delta',
        'previous_status',
        'new_status',
        'transaction_id',
        'report_id',
        'actor_id',
        'reason',
        'event_key',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'points_delta' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(AccountReport::class, 'report_id', 'report_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'user_id');
    }
}
