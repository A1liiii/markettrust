<?php

namespace App\Contracts\Services;

use App\DTOs\AgreementSnapshotDTO;
use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;

interface AgreementServiceInterface
{
    /**
     * Create an agreement from a marketplace listing.
     */
    public function createFromListing(Listing $listing, User $buyer, User $seller): Agreement;

    /**
     * Create an immutable snapshot DTO from an agreement.
     */
    public function createSnapshot(Agreement $agreement): AgreementSnapshotDTO;

    /**
     * Propose a new draft agreement within a conversation.
     *
     * @param  array<string, mixed>  $data
     */
    public function proposeFromConversation(Conversation $conversation, User $proposer, array $data): Agreement;

    /**
     * Revise an existing unfinalized agreement.
     *
     * @param  array<string, mixed>  $data
     */
    public function reviseProposal(Agreement $agreement, User $reviser, array $data): Agreement;

    /**
     * Accept a matching revision of an agreement, locking snapshot and creating a Transaction.
     */
    public function acceptProposal(Agreement $agreement, User $acceptor, int $revisionNo): Transaction;

    /**
     * Reject an agreement with reason.
     */
    public function rejectProposal(Agreement $agreement, User $rejector, string $reason): Agreement;
}
