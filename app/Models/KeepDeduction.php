<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeepDeduction extends Model
{
    use HasFactory;

    protected $table = 'keep_deductions';

    protected $primaryKey = 'keep_deduction_id';

    protected $fillable = [
        'keep_obligation_id',
        'settlement_id',
        'amount',
        'deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deducted_at' => 'datetime',
        ];
    }

    public function keepObligation(): BelongsTo
    {
        return $this->belongsTo(KeepObligation::class, 'keep_obligation_id', 'keep_obligation_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id', 'settlement_id');
    }
}
