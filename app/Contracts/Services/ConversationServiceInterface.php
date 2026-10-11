<?php

namespace App\Contracts\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

interface ConversationServiceInterface
{
    /**
     * Check if user is a participant of conversation.
     */
    public function isParticipant(Conversation $conversation, User $user): bool;

    /**
     * Send message in conversation.
     */
    public function sendMessage(Conversation $conversation, User $sender, string $content): Message;
}
