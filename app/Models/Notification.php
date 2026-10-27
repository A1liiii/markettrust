<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $primaryKey = 'notification_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'event_type',
        'message',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
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

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            return $this->update(['read_at' => now()]);
        }

        return true;
    }

    public function getEventTypeEnumAttribute(): ?NotificationEventType
    {
        return NotificationEventType::tryFrom($this->event_type);
    }

    public function getEventLabelAttribute(): string
    {
        return $this->eventTypeEnum?->label() ?? $this->event_type;
    }

    public function getBadgeClassAttribute(): string
    {
        return $this->eventTypeEnum?->badgeClass() ?? 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
