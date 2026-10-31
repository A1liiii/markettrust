<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReport extends Model
{
    use HasFactory;

    protected $table = 'account_reports';

    protected $primaryKey = 'report_id';

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_REVIEWING = 'REVIEWING';

    public const STATUS_RESOLVED = 'RESOLVED';

    public const STATUS_DISMISSED = 'DISMISSED';

    public const SOURCE_USER = 'USER';

    public const SOURCE_SYSTEM = 'SYSTEM';

    public const SOURCE_ADMIN = 'ADMIN';

    protected $fillable = [
        'reported_user_id',
        'reporter_id',
        'transaction_id',
        'source',
        'reason',
        'status',
        'reviewed_by',
        'review_note',
        'reviewed_at',
    ];

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isReviewing(): bool
    {
        return $this->status === self::STATUS_REVIEWING;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_REVIEWING], true);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STATUS_REVIEWING => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_DISMISSED => 'bg-slate-100 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Menunggu Tinjauan',
            self::STATUS_REVIEWING => 'Sedang Diinvestigasi',
            self::STATUS_RESOLVED => 'Telah Diputuskan',
            self::STATUS_DISMISSED => 'Ditolak / Tidak Terbukti',
            default => $this->status,
        };
    }

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id', 'user_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id', 'user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class, 'report_id', 'report_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'report_id', 'report_id');
    }
}
