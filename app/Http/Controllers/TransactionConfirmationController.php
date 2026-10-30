<?php

namespace App\Http\Controllers;

use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionConfirmationController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService
    ) {}

    /**
     * Konfirmasi penerimaan hasil pesanan oleh Buyer (UC-15).
     */
    public function confirm(Request $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // 1. Otorisasi ketat: Hanya Pembeli terkait
        if (! $transaction->agreement || $transaction->agreement->buyer_id !== $user->user_id) {
            abort(403, 'Hanya Pembeli terkait yang berhak mengonfirmasi penerimaan pesanan.');
        }

        // 2. Validasi status transaksi harus DELIVERED
        $statusStr = $transaction->status instanceof TransactionStatus
            ? $transaction->status->value
            : (string) $transaction->status;

        if ($statusStr !== TransactionStatus::DELIVERED->value) {
            $message = 'Konfirmasi hanya dapat dilakukan saat pesanan berstatus ITEM DISERAHKAN (DELIVERED).';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $message);
        }

        // 3. Guard komplain aktif: menahan konfirmasi & payout
        if ($transaction->hasActiveComplaint()) {
            $message = 'Pesanan tidak dapat dikonfirmasi karena terdapat komplain aktif.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $message);
        }

        $confirmedTx = $this->transactionService->confirmOrder($transaction, $user);

        $successMessage = 'Pesanan berhasil dikonfirmasi. Dana escrow telah dialokasikan ke antrean payout Penjual.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMessage,
                'transaction_id' => $confirmedTx->transaction_id,
                'status_label' => $confirmedTx->status->label(),
                'confirmed_at' => $confirmedTx->confirmed_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', $successMessage);
    }
}
