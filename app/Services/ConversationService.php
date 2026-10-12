<?php

namespace App\Services;

use App\Contracts\Services\ConversationServiceInterface;
use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
     * Helper to order participant user IDs canonically (smaller user_id always first).
     *
     * @return array{0: int, 1: int}
     */
    public function getCanonicalParticipants(int $userAId, int $userBId): array
    {
        return $userAId < $userBId
            ? [$userAId, $userBId]
            : [$userBId, $userAId];
    }

    /**
     * Get the opposing participant in a conversation for a given user.
     */
    public function getOtherParticipant(Conversation $conversation, User $user): ?User
    {
        if ($conversation->participant_one_id === $user->user_id) {
            return $conversation->participantTwo;
        }

        if ($conversation->participant_two_id === $user->user_id) {
            return $conversation->participantOne;
        }

        return null;
    }

    /**
     * Start or retrieve a conversation initiated from a Listing.
     * Context: listing_id.
     */
    public function startFromListing(User $initiator, Listing $listing): Conversation
    {
        if ($initiator->user_id === $listing->seller_id) {
            throw new InvalidArgumentException('Tidak dapat memulai percakapan dengan listing milik sendiri.');
        }

        [$oneId, $twoId] = $this->getCanonicalParticipants($initiator->user_id, $listing->seller_id);

        $existing = Conversation::where('participant_one_id', $oneId)
            ->where('participant_two_id', $twoId)
            ->where('listing_id', $listing->listing_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($oneId, $twoId, $listing) {
            return Conversation::create([
                'participant_one_id' => $oneId,
                'participant_two_id' => $twoId,
                'listing_id' => $listing->listing_id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Start or retrieve a conversation initiated from a Forum Post.
     * Context: post_id.
     */
    public function startFromPost(User $initiator, ForumPost $post): Conversation
    {
        if ($initiator->user_id === $post->author_id) {
            throw new InvalidArgumentException('Tidak dapat memulai percakapan dengan postingan milik sendiri.');
        }

        [$oneId, $twoId] = $this->getCanonicalParticipants($initiator->user_id, $post->author_id);

        $existing = Conversation::where('participant_one_id', $oneId)
            ->where('participant_two_id', $twoId)
            ->where('post_id', $post->post_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($oneId, $twoId, $post) {
            return Conversation::create([
                'participant_one_id' => $oneId,
                'participant_two_id' => $twoId,
                'post_id' => $post->post_id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Start or retrieve a conversation for an agreement.
     * Ensures max one primary DM per agreement_id.
     */
    public function startWithAgreement(User $initiator, User $recipient, int $agreementId): Conversation
    {
        $existing = Conversation::where('agreement_id', $agreementId)->first();
        if ($existing) {
            return $existing;
        }

        [$oneId, $twoId] = $this->getCanonicalParticipants($initiator->user_id, $recipient->user_id);

        return DB::transaction(function () use ($oneId, $twoId, $agreementId) {
            return Conversation::create([
                'participant_one_id' => $oneId,
                'participant_two_id' => $twoId,
                'agreement_id' => $agreementId,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Send message in conversation.
     * Validates that sender is a participant.
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

    /**
     * Mark unread messages sent by the opposing participant as read.
     * Returns count of marked messages.
     */
    public function markMessagesAsRead(Conversation $conversation, User $reader): int
    {
        if (! $this->isParticipant($conversation, $reader)) {
            throw new AuthorizationException('Pengguna bukan peserta percakapan.');
        }

        return Message::where('conversation_id', $conversation->conversation_id)
            ->where('sender_id', '!=', $reader->user_id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    /**
     * Get all conversations for a user with relations, ordered by latest activity.
     *
     * @return Collection<int, Conversation>
     */
    public function getUserConversations(User $user): Collection
    {
        return Conversation::query()
            ->where(function ($query) use ($user) {
                $query->where('participant_one_id', $user->user_id)
                    ->orWhere('participant_two_id', $user->user_id);
            })
            ->with([
                'participantOne',
                'participantTwo',
                'listing',
                'post',
                'agreement',
                'messages' => function ($query) {
                    $query->latest('sent_at');
                },
            ])
            ->get()
            ->sortByDesc(function (Conversation $conv) {
                $latestMsg = $conv->messages->first();

                return $latestMsg ? $latestMsg->sent_at : $conv->created_at;
            })
            ->values();
    }

    /**
     * Count total unread messages for a user across all their conversations.
     */
    public function countUnread(User $user): int
    {
        return Message::whereHas('conversation', function ($q) use ($user) {
            $q->where('participant_one_id', $user->user_id)
                ->orWhere('participant_two_id', $user->user_id);
        })
            ->where('sender_id', '!=', $user->user_id)
            ->whereNull('read_at')
            ->count();
    }
}
