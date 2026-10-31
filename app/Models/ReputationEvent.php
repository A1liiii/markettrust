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

    public const EVENT_TRANSACTION_COMPLETED = 'TRANSACTION_COMPLETED';

    public const EVENT_ADMIN_MODERATION = 'ADMIN_MODERATION';

    public const EVENT_TRANSACTION_CANCELLATION = 'TRANSACTION_CANCELLATION';

    public const EVENT_COMPLAINT_RESOLVED = 'COMPLAINT_RESOLVED';

    public const EVENT_KEEP_OBLIGATION_UNMET = 'KEEP_OBLIGATION_UNMET';

    protected function casts(): array
    {
        return [
            'points_delta' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function eventTypeLabel(): string
    {
        return match ($this->event_type) {
            self::EVENT_TRANSACTION_COMPLETED, 'TX_COMPLETED' => 'Transaksi Escrow Sukses',
            self::EVENT_ADMIN_MODERATION => 'Keputusan Moderasi Admin',
            self::EVENT_TRANSACTION_CANCELLATION => 'Pembatalan Transaksi',
            self::EVENT_COMPLAINT_RESOLVED => 'Penyelesaian Komplain',
            self::EVENT_KEEP_OBLIGATION_UNMET => 'Kewajiban KEEP Belum Dipenuhi',
            default => ucwords(str_replace('_', ' ', strtolower((string) ($this->event_type ?? 'Peristiwa Reputasi')))),
        };
    }

    public function pointsDeltaBadgeClass(): string
    {
        if ($this->points_delta > 0) {
            return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20';
        }

        if ($this->points_delta < 0) {
            return 'bg-rose-500/10 text-rose-600 border border-rose-500/20';
        }

        return 'bg-slate-500/10 text-slate-600 border border-slate-500/20';
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'LEGIT' => 'glass-badge-success',
            'PROBLEMATIC' => 'glass-badge-danger',
            'UNREVIEWED' => 'glass-badge-neutral',
            default => 'glass-badge-neutral',
        };
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
