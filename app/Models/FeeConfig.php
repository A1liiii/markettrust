<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeConfig extends Model
{
    use HasFactory;

    protected $table = 'fee_configs';

    protected $primaryKey = 'fee_config_id';

    public $timestamps = false;

    protected $fillable = [
        'min_amount',
        'max_amount',
        'fee_percent',
        'flat_fee',
        'is_active',
        'effective_from',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'fee_percent' => 'decimal:2',
            'flat_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'fee_config_id', 'fee_config_id');
    }
}
