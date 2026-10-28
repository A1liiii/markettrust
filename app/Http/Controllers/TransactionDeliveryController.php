<?php

namespace App\Http\Controllers;

use App\Contracts\Services\TransactionServiceInterface;
use App\Enums\TransactionStatus;
use App\Http\Requests\Transaction\ExtendDeliveryDeadlineRequest;
use App\Http\Requests\Transaction\StoreDeliveryRequest;
use App\Models\EvidenceFile;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionDeliveryController extends Controller
{
    public function __construct(
        protected TransactionServiceInterface $transactionService
    ) {}

    /**
     * Serahkan hasil resmi dan bukti transaksi oleh Penjual.
     */
    public function store(StoreDeliveryRequest $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Otorisasi ketat status transaksi: Hanya dapat diserahkan jika berstatus PAID
        $statusStr = $transaction->status instanceof TransactionStatus
            ? $transaction->status->value
            : (string) $transaction->status;

        if ($statusStr !== TransactionStatus::PAID->value) {
            $message = 'Hasil transaksi hanya dapat diserahkan saat pesanan berstatus SUDAH DIBAYAR (PAID).';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $message);
        }

        $deliveredTx = $this->transactionService->deliverOrder(
            $transaction,
            $user,
            $request->input('delivery_note'),
            $request->input('result_link'),
            $request->file('evidence_file')
        );

        $successMessage = 'Hasil resmi transaksi berhasil diserahkan. Batas konfirmasi Pembeli adalah 1 jam sejak penyerahan.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMessage,
                'transaction_id' => $deliveredTx->transaction_id,
                'status_label' => $deliveredTx->status->label(),
                'delivered_at' => $deliveredTx->delivered_at?->toIso8601String(),
                'confirmation_due_at' => $deliveredTx->confirmation_due_at?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', $successMessage);
    }

    /**
     * Unduh atau pratinjau bukti resmi penyerahan untuk Buyer, Seller, atau Admin (MT-A04/05/11).
     */
    public function downloadEvidence(Request $request, Transaction $transaction, EvidenceFile $evidence): BinaryFileResponse|StreamedResponse
    {
        $user = $request->user();
        $agreement = $transaction->agreement;

        if (! $agreement || ($user->user_id !== $agreement->buyer_id && $user->user_id !== $agreement->seller_id && ! $user->isAdmin())) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat atau mengunduh bukti penyerahan ini.');
        }

        if ((int) $evidence->delivery_transaction_id !== (int) $transaction->transaction_id) {
            abort(404, 'Bukti penyerahan tidak cocok dengan transaksi yang diminta.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($evidence->file_url)) {
            abort(404, 'Berkas bukti fisik tidak ditemukan pada media penyimpanan.');
        }

        $isInline = $request->boolean('inline');
        if ($isInline) {
            return response()->file(
                $disk->path($evidence->file_url),
                [
                    'Content-Type' => $evidence->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.addslashes($evidence->file_name).'"',
                ]
            );
        }

        return $disk->download($evidence->file_url, $evidence->file_name);
    }

    /**
     * Catat perpanjangan waktu tenggat penyerahan oleh Admin / Seller.
     */
    public function extendDeadline(ExtendDeliveryDeadlineRequest $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        $event = $this->transactionService->extendDeliveryDeadline(
            $transaction,
            (int) $request->input('extension_minutes'),
            $request->input('reason'),
            $request->user()
        );

        $message = "Tambahan waktu penyerahan {$event->extension_minutes} menit berhasil dicatat.";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'new_deadline' => $event->new_deadline?->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', $message);
    }
}
