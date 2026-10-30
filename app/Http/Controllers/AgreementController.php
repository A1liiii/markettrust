<?php

namespace App\Http\Controllers;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\ConversationServiceInterface;
use App\Http\Requests\Agreement\AcceptAgreementRequest;
use App\Http\Requests\Agreement\ProposeAgreementRequest;
use App\Http\Requests\Agreement\RejectAgreementRequest;
use App\Http\Requests\Agreement\ReviseAgreementRequest;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use LogicException;

class AgreementController extends Controller
{
    public function __construct(
        protected AgreementServiceInterface $agreementService,
        protected ConversationServiceInterface $conversationService,
    ) {}

    /**
     * Ajukan usulan lembar kesepakatan baru (UC-10).
     */
    public function store(ProposeAgreementRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki hak untuk mengajukan kesepakatan pada percakapan ini.');
        }

        try {
            $agreement = $this->agreementService->proposeFromConversation(
                $conversation,
                $user,
                $request->validated()
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usulan kesepakatan berhasil diajukan.',
                    'agreement_id' => $agreement->agreement_id,
                    'revision_no' => $agreement->revision_no,
                    'status' => $agreement->status->value ?? (string) $agreement->status,
                ], 201);
            }

            return redirect()->route('conversations.show', $conversation)
                ->with('success', 'Usulan kesepakatan (Rev #1) berhasil diajukan! Menunggu tinjauan pihak lawan.');
        } catch (InvalidArgumentException|LogicException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Revisi usulan lembar kesepakatan (UC-10 Alternate Flow c).
     */
    public function update(ReviseAgreementRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki hak untuk merevisi kesepakatan pada percakapan ini.');
        }

        $agreement = $conversation->agreement;
        if (! $agreement) {
            abort(404, 'Lembar kesepakatan tidak ditemukan pada percakapan ini.');
        }

        try {
            $updated = $this->agreementService->reviseProposal(
                $agreement,
                $user,
                $request->validated()
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Revisi kesepakatan berhasil diajukan.',
                    'agreement_id' => $updated->agreement_id,
                    'revision_no' => $updated->revision_no,
                    'status' => $updated->status->value ?? (string) $updated->status,
                ]);
            }

            return redirect()->route('conversations.show', $conversation)
                ->with('success', sprintf('Revisi usulan kesepakatan (Rev #%d) berhasil disimpan!', $updated->revision_no));
        } catch (InvalidArgumentException|LogicException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Setujui lembar kesepakatan pada revisi yang cocok dan bentuk transaksi (UC-11).
     */
    public function accept(AcceptAgreementRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki hak untuk menyetujui kesepakatan pada percakapan ini.');
        }

        $agreement = $conversation->agreement;
        if (! $agreement) {
            abort(404, 'Lembar kesepakatan tidak ditemukan pada percakapan ini.');
        }

        $revisionNo = (int) $request->validated('revision_no');

        try {
            $transaction = $this->agreementService->acceptProposal(
                $agreement,
                $user,
                $revisionNo
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kesepakatan disetujui! Transaksi telah dibentuk.',
                    'transaction_id' => $transaction->transaction_id,
                    'redirect_url' => route('transactions.show', $transaction),
                ]);
            }

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Kesepakatan final disetujui! Transaksi berhasil dibentuk dan menunggu pembayaran escrow.');
        } catch (InvalidArgumentException|LogicException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('warning', $e->getMessage());
        }
    }

    /**
     * Tolak lembar kesepakatan dengan alasan (UC-11).
     */
    public function reject(RejectAgreementRequest $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (! $this->conversationService->isParticipant($conversation, $user)) {
            abort(403, 'Anda tidak memiliki hak untuk menolak kesepakatan pada percakapan ini.');
        }

        $agreement = $conversation->agreement;
        if (! $agreement) {
            abort(404, 'Lembar kesepakatan tidak ditemukan pada percakapan ini.');
        }

        try {
            $this->agreementService->rejectProposal(
                $agreement,
                $user,
                (string) $request->validated('rejection_reason')
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usulan kesepakatan telah ditolak.',
                    'agreement_id' => $agreement->agreement_id,
                ]);
            }

            return redirect()->route('conversations.show', $conversation)
                ->with('info', 'Usulan kesepakatan telah ditolak.');
        } catch (InvalidArgumentException|LogicException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('warning', $e->getMessage());
        }
    }
}
