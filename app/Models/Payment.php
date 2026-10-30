<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'transaction_id',
        'gateway_order_id',
        'gateway_transaction_id',
        'payment_channel',
        'amount',
        'gateway_fee_amount',
        'status',
        'gateway_status',
        'latest_payload',
        'last_notification_at',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_fee_amount' => 'decimal:2',
            'latest_payload' => 'array',
            'last_notification_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function hasVerifiedGatewayFee(): bool
    {
        return $this->gateway_fee_amount !== null;
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'payment_id', 'payment_id');
    }
}
