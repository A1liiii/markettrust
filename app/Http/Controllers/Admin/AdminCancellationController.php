<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\CancellationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewCancellationRequest;
use App\Models\TransactionCancellation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Throwable;

class AdminCancellationController extends Controller
{
    public function __construct(
        protected CancellationServiceInterface $cancellationService
    ) {}

    /**
     * Tinjau dan putuskan pengajuan pembatalan transaksi.
     */
    public function review(ReviewCancellationRequest $request, TransactionCancellation $cancellation): RedirectResponse
    {
        Gate::authorize('review', $cancellation);

        try {
            $decision = (string) $request->validated('decision');
            $responsibleParty = $request->validated('responsible_party');
            $decisionNote = $request->validated('decision_note');

            $this->cancellationService->reviewCancellation(
                $cancellation,
                $request->user(),
                $decision,
                $responsibleParty,
                $decisionNote
            );

            $msg = $decision === 'APPROVED'
                ? 'Pengajuan pembatalan telah disetujui. Status transaksi kini CANCELLED untuk proses pengembalian dana.'
                : 'Pengajuan pembatalan telah ditolak. Transaksi tetap dilanjutkan dalam status PAID.';

            return back()->with('success', $msg);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memproses keputusan peninjauan: '.$e->getMessage())->withInput();
        }
    }
}

