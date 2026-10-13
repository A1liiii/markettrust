<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    public function formattedAttachmentSize(): string
    {
        if (! $this->attachment_size) {
            return '';
        }

        if ($this->attachment_size >= 1048576) {
            return number_format($this->attachment_size / 1048576, 1).' MB';
        }

        if ($this->attachment_size >= 1024) {
            return number_format($this->attachment_size / 1024, 0).' KB';
        }

        return $this->attachment_size.' B';
    }

    public function isImage(): bool
    {
        return ! empty($this->attachment_mime) && str_starts_with($this->attachment_mime, 'image/');
    }

    public function isVideo(): bool
    {
        return ! empty($this->attachment_mime) && str_starts_with($this->attachment_mime, 'video/');
    }

    public function isPdf(): bool
    {
        return $this->attachment_mime === 'application/pdf';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }
}
