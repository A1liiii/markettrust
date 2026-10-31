<?php

namespace App\Http\Controllers;

use App\Contracts\Services\ComplaintServiceInterface;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\StoreComplaintResponseRequest;
use App\Models\Complaint;
use App\Models\EvidenceFile;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintServiceInterface $complaintService
    ) {}

    /**
     * Buyer mengajukan komplain resmi berbukti atas pesanan DELIVERED (UC-16).
     */
    public function store(StoreComplaintRequest $request, Transaction $transaction): RedirectResponse
    {
        try {
            $this->complaintService->fileComplaint(
                $transaction,
                $request->user(),
                $request->validated('reason'),
                $request->file('evidence_file')
            );

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Komplain resmi berhasil diajukan. Penjual diberikan waktu 24 jam untuk memberikan tanggapan.');
        } catch (Throwable $e) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Seller memberikan tanggapan / klarifikasi resmi atas komplain (UC-17).
     */
    public function respond(
        StoreComplaintResponseRequest $request,
        Transaction $transaction,
        Complaint $complaint
    ): RedirectResponse {
        try {
            $this->complaintService->respondToComplaint(
                $complaint,
                $request->user(),
                $request->validated('response_text'),
                $request->file('evidence_file')
            );

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Tanggapan komplain berhasil dikirimkan.');
        } catch (Throwable $e) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Unduh atau pratinjau bukti komplain atau tanggapan (UC-16 / UC-17 / UC-18).
     */
    public function downloadEvidence(
        Request $request,
        Transaction $transaction,
        EvidenceFile $evidence
    ): BinaryFileResponse|StreamedResponse {
        $user = $request->user();
        $agreement = $transaction->agreement;

        if (! $agreement || ($user->user_id !== $agreement->buyer_id && $user->user_id !== $agreement->seller_id && ! $user->isAdmin())) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat atau mengunduh bukti sengketa ini.');
        }

        // Verifikasi bahwa berkas bukti terkait dengan komplain atau respons pada transaksi ini
        $isComplaintMatch = $evidence->complaint && (int) $evidence->complaint->transaction_id === (int) $transaction->transaction_id;
        $isResponseMatch = $evidence->complaintResponse && (int) $evidence->complaintResponse->complaint?->transaction_id === (int) $transaction->transaction_id;

        if (! $isComplaintMatch && ! $isResponseMatch) {
            abort(404, 'Berkas bukti tidak cocok dengan sengketa transaksi yang diminta.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($evidence->file_url)) {
            abort(404, 'Berkas fisik bukti sengketa tidak ditemukan pada media penyimpanan.');
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
}

