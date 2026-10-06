<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceFile extends Model
{
    use HasFactory;

    protected $table = 'evidence_files';

    protected $primaryKey = 'evidence_id';

    public $timestamps = false;

    protected $fillable = [
        'uploaded_by',
        'payment_id',
        'delivery_transaction_id',
        'complaint_id',
        'response_id',
        'report_id',
        'settlement_id',
        'file_name',
        'file_url',
        'mime_type',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function deliveryTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'delivery_transaction_id', 'transaction_id');
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id', 'complaint_id');
    }

    public function complaintResponse(): BelongsTo
    {
        return $this->belongsTo(ComplaintResponse::class, 'response_id', 'response_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(AccountReport::class, 'report_id', 'report_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id', 'settlement_id');
    }
}
