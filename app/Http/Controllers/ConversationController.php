<?php

namespace App\Http\Controllers;

use App\Http\Requests\Conversation\SendMessageRequest;
use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Message;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService,
    ) {}

    /**
     * Show inbox list of all conversations for the authenticated user.
     */
    public function index(): JsonResponse|View
    {
        $user = auth()->user();
        $conversations = $this->conversationService->getUserConversations($user);

        if (request()->wantsJson() || request()->ajax()) {
            $data = $conversations->map(function ($conv) use ($user) {
                $other = $this->conversationService->getOtherParticipant($conv, $user);
                $lastMsg = $conv->messages->first();
                $unread = $conv->unreadCountFor($user);

                $contextType = 'DIRECT';
                $contextTitle = 'Percakapan Pribadi';
                if ($conv->listing) {
                    $contextType = 'MARKETPLACE';
                    $contextTitle = $conv->listing->title;
                } elseif ($conv->post) {
                    $contextType = 'FORUM';
                    $contextTitle = $conv->post->title;
                } elseif ($conv->agreement) {
                    $contextType = 'AGREEMENT';
                    $contextTitle = 'Agreement #'.$conv->agreement->agreement_id;
                }

                return [
                    'conversation_id' => $conv->conversation_id,
                    'other_user' => [
                        'user_id' => $other?->user_id,
                        'username' => $other?->username,
                        'avatar_url' => $other?->avatar_url,
                        'account_status' => $other?->account_status,
                    ],
                    'context_type' => $contextType,
                    'context_title' => $contextTitle,
                    'last_message' => $lastMsg ? [
                        'content' => $lastMsg->content ?: ($lastMsg->hasAttachment() ? '📎 [Lampiran Dokumen]' : ''),
                        'sent_at' => $lastMsg->sent_at?->diffForHumans(),
                        'is_me' => $lastMsg->sender_id === $user->user_id,
                    ] : null,
                    'unread_count' => $unread,
                    'updated_at' => $lastMsg?->sent_at?->diffForHumans() ?? $conv->created_at?->diffForHumans(),
                ];
            });

            return response()->json([
                'conversations' => $data,
                'total_unread' => $this->conversationService->countUnread($user),
            ]);
        }

        return view('conversations.index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Start or open a DM initiated from a Listing.
     */
    public function startFromListing(Listing $listing): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        if ($user->user_id === $listing->seller_id) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Anda tidak dapat mengirim pesan ke listing milik Anda sendiri.'], 422);
            }

            return back()->with('warning', 'Anda tidak dapat mengirim pesan ke listing milik Anda sendiri.');
        }

        try {
            $conversation = $this->conversationService->startFromListing($user, $listing);

            if (request()->expectsJson()) {
                return response()->json([
                    'conversation_id' => $conversation->conversation_id,
                ]);
            }

            return redirect()->route('marketplace.show', [
                'listing' => $listing,
                'open_dm' => $conversation->conversation_id,
            ]);
        } catch (InvalidArgumentException $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

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
     * JSON endpoint to start or retrieve a DM from Forum Post for side-panel integration.
     */
    public function startFromPostQuick(ForumPost $post): JsonResponse
    {
        $user = auth()->user();

        if ($user->user_id === $post->author_id) {
            return response()->json([
                'error' => 'Anda tidak dapat mengirim pesan ke postingan milik Anda sendiri.',
            ], 422);
        }

        try {
            $conversation = $this->conversationService->startFromPost($user, $post);

            // Automatically mark incoming messages as read
            $this->conversationService->markMessagesAsRead($conversation, $user);

            $conversation->load([
                'participantOne',
                'participantTwo',
                'post',
                'messages' => function ($query) {
                    $query->with('sender')->orderBy('sent_at', 'asc');
                },
            ]);

            $otherUser = $this->conversationService->getOtherParticipant($conversation, $user);

            $messagesData = $conversation->messages->map(fn ($msg) => $this->formatMessageJson($msg, $user->user_id));

            return response()->json([
                'conversation_id' => $conversation->conversation_id,
                'other_user' => [
                    'user_id' => $otherUser?->user_id,
                    'username' => $otherUser?->username,
                ],
                'post' => [
                    'post_id' => $post->post_id,
                    'title' => $post->title,
                    'category_code' => $post->category_code,
                    'price_offer' => $post->price_offer ? number_format($post->price_offer, 0, ',', '.') : null,
                ],
                'messages' => $messagesData,
                'full_url' => route('conversations.show', $conversation->conversation_id),
                'send_url' => route('conversations.messages.send', $conversation->conversation_id),
                'sync_url' => route('conversations.sync', $conversation->conversation_id),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Display a specific conversation thread and mark unread messages as read.
     */
    public function show(Conversation $conversation): JsonResponse|View
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

        if (request()->wantsJson() || request()->ajax()) {
            $messagesData = $conversation->messages->map(fn ($msg) => $this->formatMessageJson($msg, $user->user_id));

            return response()->json([
                'conversation_id' => $conversation->conversation_id,
                'other_user' => [
                    'user_id' => $otherUser?->user_id,
                    'username' => $otherUser?->username,
                    'avatar_url' => $otherUser?->avatar_url,
                    'account_status' => $otherUser?->account_status,
                ],
                'context' => [
                    'type' => $conversation->listing ? 'MARKETPLACE' : ($conversation->post ? 'FORUM' : ($conversation->agreement ? 'AGREEMENT' : 'DIRECT')),
                    'title' => $conversation->listing?->title ?? $conversation->post?->title ?? ($conversation->agreement ? 'Agreement #'.$conversation->agreement->agreement_id : 'Percakapan Pribadi'),
                    'price' => $conversation->listing ? 'Rp '.number_format($conversation->listing->price, 0, ',', '.') : ($conversation->post?->price_offer ? 'Rp '.number_format($conversation->post->price_offer, 0, ',', '.') : null),
                ],
                'messages' => $messagesData,
                'full_url' => route('conversations.show', $conversation->conversation_id),
                'send_url' => route('conversations.messages.send', $conversation->conversation_id),
                'sync_url' => route('conversations.sync', $conversation->conversation_id),
            ]);
        }

        return view('conversations.show', [
            'conversation' => $conversation,
            'otherUser' => $otherUser,
        ]);
    }

    /**
     * Send a new message to the conversation.
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengirim pesan di percakapan ini.');
        }

        $message = $this->conversationService->sendMessage(
            $conversation,
            $user,
            $request->validated('content'),
            $request->file('attachment')
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $this->formatMessageJson($message, $user->user_id),
            ]);
        }

        return redirect()->route('conversations.show', $conversation)
            ->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Synchronize read receipts and fetch new messages (Lightweight Live Sync).
     */
    public function sync(Request $request, Conversation $conversation): JsonResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Akses tidak sah.');
        }

        // Mark incoming messages as read since recipient has the conversation active
        $this->conversationService->markMessagesAsRead($conversation, $user);

        $afterId = (int) ($request->query('after_id') ?? $request->query('last_id') ?? 0);

        // Check read receipts on messages sent by ME
        $myReadMessageIds = Message::where('conversation_id', $conversation->conversation_id)
            ->where('sender_id', $user->user_id)
            ->whereNotNull('read_at')
            ->pluck('message_id')
            ->all();

        // Get new messages sent by OTHER participant since afterId
        $newMessages = Message::with('sender')
            ->where('conversation_id', $conversation->conversation_id)
            ->where('sender_id', '!=', $user->user_id)
            ->when($afterId > 0, fn ($q) => $q->where('message_id', '>', $afterId))
            ->orderBy('sent_at', 'asc')
            ->get()
            ->map(fn ($msg) => $this->formatMessageJson($msg, $user->user_id));

        return response()->json([
            'my_read_message_ids' => $myReadMessageIds,
            'read_ids' => $myReadMessageIds,
            'new_messages' => $newMessages,
        ]);
    }

    /**
     * Securely download or preview document/media attachment for authorized participants only.
     */
    public function downloadAttachment(Conversation $conversation, Message $message): Response
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh lampiran ini.');
        }

        if ($message->conversation_id !== $conversation->conversation_id || ! $message->hasAttachment()) {
            abort(404, 'Lampiran tidak ditemukan.');
        }

        if (! Storage::disk('local')->exists($message->attachment_path)) {
            abort(404, 'File lampiran tidak tersedia pada server.');
        }

        // Support inline streaming for images, videos, and PDFs
        if (request()->has('inline')) {
            return Storage::disk('local')->response(
                $message->attachment_path,
                $message->attachment_name,
                ['Content-Type' => $message->attachment_mime ?: 'application/octet-stream']
            );
        }

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name);
    }

    /**
     * Format message item for JSON responses with rich media metadata.
     */
    private function formatMessageJson(Message $msg, int $currentUserId): array
    {
        return [
            'message_id' => $msg->message_id,
            'sender_id' => $msg->sender_id,
            'sender_name' => $msg->sender?->username,
            'is_me' => $msg->sender_id === $currentUserId,
            'content' => $msg->content,
            'has_attachment' => $msg->hasAttachment(),
            'attachment_name' => $msg->attachment_name,
            'attachment_size' => $msg->formattedAttachmentSize(),
            'attachment_mime' => $msg->attachment_mime,
            'is_image' => $msg->isImage(),
            'is_video' => $msg->isVideo(),
            'is_pdf' => $msg->isPdf(),
            'preview_url' => $msg->hasAttachment() ? route('conversations.messages.attachment', [$msg->conversation_id, $msg->message_id, 'inline' => 1]) : null,
            'download_url' => $msg->hasAttachment() ? route('conversations.messages.attachment', [$msg->conversation_id, $msg->message_id]) : null,
            'sent_at' => $msg->sent_at?->format('H:i'),
            'is_read' => ! empty($msg->read_at),
        ];
    }
}
