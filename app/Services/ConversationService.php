<?php

namespace App\Services;

use App\Contracts\Services\ConversationServiceInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ConversationService implements ConversationServiceInterface
{
    /**
     * Check if user is a participant of conversation.
     */
    public function isParticipant(Conversation $conversation, User $user): bool
    {
        return $conversation->participant_one_id === $user->user_id
            || $conversation->participant_two_id === $user->user_id;
    }

    /**
     * Send message in conversation.
     */
    public function sendMessage(Conversation $conversation, User $sender, string $content): Message
    {
        if (! $this->isParticipant($conversation, $sender)) {
            throw new AuthorizationException('Pengirim bukan merupakan peserta percakapan.');
        }

        return Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $sender->user_id,
            'content' => trim($content),
            'sent_at' => now(),
            'read_at' => null,
        ]);
    }
}
