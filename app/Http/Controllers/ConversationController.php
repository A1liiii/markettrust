<?php

namespace App\Http\Controllers;

use App\Http\Requests\Conversation\SendMessageRequest;
use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService,
    ) {}

    /**
     * Show inbox list of all conversations for the authenticated user.
     */
    public function index(): View
    {
        $user = auth()->user();
        $conversations = $this->conversationService->getUserConversations($user);

        return view('conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Start or open a DM initiated from a Listing.
     */
    public function startFromListing(Listing $listing): RedirectResponse
    {
        $user = auth()->user();

        if ($user->user_id === $listing->seller_id) {
            return back()->with('warning', 'Anda tidak dapat mengirim pesan ke listing milik Anda sendiri.');
        }

        try {
            $conversation = $this->conversationService->startFromListing($user, $listing);

            return redirect()->route('conversations.show', $conversation);
        } catch (InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Start or open a DM initiated from a Forum Post.
     */
    public function startFromPost(ForumPost $post): RedirectResponse
    {
        $user = auth()->user();

        if ($user->user_id === $post->author_id) {
            return back()->with('warning', 'Anda tidak dapat mengirim pesan ke postingan milik Anda sendiri.');
        }

        try {
            $conversation = $this->conversationService->startFromPost($user, $post);

            return redirect()->route('conversations.show', $conversation);
        } catch (InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Display a specific conversation thread and mark unread messages as read.
     */
    public function show(Conversation $conversation): View
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki akses ke percakapan ini.');
        }

        // Mark incoming messages as read by the receiver
        $this->conversationService->markMessagesAsRead($conversation, $user);

        $conversation->load([
            'participantOne',
            'participantTwo',
            'listing',
            'post',
            'agreement',
            'messages' => function ($query) {
                $query->with('sender')->orderBy('sent_at', 'asc');
            },
        ]);

        $otherUser = $this->conversationService->getOtherParticipant($conversation, $user);

        return view('conversations.show', [
            'conversation' => $conversation,
            'otherUser' => $otherUser,
        ]);
    }

    /**
     * Send a new message to the conversation.
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengirim pesan di percakapan ini.');
        }

        $this->conversationService->sendMessage(
            $conversation,
            $user,
            $request->validated('content')
        );

        return redirect()->route('conversations.show', $conversation)
            ->with('success', 'Pesan berhasil dikirim.');
    }
}
