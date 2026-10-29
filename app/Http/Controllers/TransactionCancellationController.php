<?php

namespace App\Http\Controllers;

use App\Contracts\Services\CancellationServiceInterface;
use App\Http\Requests\Transaction\RequestCancellationRequest;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Throwable;

class TransactionCancellationController extends Controller
{
    public function __construct(
        protected CancellationServiceInterface $cancellationService
    ) {}

    /**
     * Ajukan atau proses pembatalan transaksi oleh Buyer atau Seller.
     */
    public function store(RequestCancellationRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('create', [TransactionCancellation::class, $transaction]);

        try {
            $reason = $request->validated('reason');
            $cancellation = $this->cancellationService->requestCancellation($transaction, $request->user(), $reason);

            if ($cancellation->isApproved()) {
                return redirect()
                    ->route('transactions.show', $transaction)
                    ->with('success', 'Transaksi berhasil dibatalkan sebelum pembayaran. Akses pembayaran telah ditutup.');
            }

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Pengajuan pembatalan telah berhasil dikirimkan dan menunggu peninjauan oleh Admin.');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memproses pembatalan: '.$e->getMessage())->withInput();
        }
    }
}

