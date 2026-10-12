<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $primaryKey = 'conversation_id';

    public $timestamps = false;

    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'listing_id',
        'post_id',
        'agreement_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id', 'user_id');
    }

    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id', 'user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id', 'listing_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id', 'post_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id', 'agreement_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'conversation_id');
    }

    /**
     * Get the opposing user for a given participant.
     */
    public function getOtherParticipant(User $user): ?User
    {
        if ($this->participant_one_id === $user->user_id) {
            return $this->participantTwo;
        }

        if ($this->participant_two_id === $user->user_id) {
            return $this->participantOne;
        }

        return null;
    }

    /**
     * Count unread messages for a given user in this conversation.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->user_id)
            ->whereNull('read_at')
            ->count();
    }
}
