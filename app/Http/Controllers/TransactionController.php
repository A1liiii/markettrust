<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PaymentServiceInterface;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class TransactionController extends Controller
{
    public function __construct(
        protected PaymentServiceInterface $paymentService
    ) {}

    /**
     * Tampilkan riwayat daftar transaksi pengguna (sebagai pembeli maupun penjual).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = (string) $request->query('role', 'all');
        $status = $request->query('status');

        $query = Transaction::query()
            ->whereHas('agreement', function ($q) use ($user, $role): void {
                if ($role === 'buyer') {
                    $q->where('buyer_id', $user->user_id);
                } elseif ($role === 'seller') {
                    $q->where('seller_id', $user->user_id);
                } else {
                    $q->where(function ($sub) use ($user): void {
                        $sub->where('buyer_id', $user->user_id)
                            ->orWhere('seller_id', $user->user_id);
                    });
                }
            })
            ->with([
                'agreement.buyer',
                'agreement.seller',
                'agreement.variant',
                'listing',
                'payment',
            ])
            ->latest('transaction_id');

        if ($status && is_string($status) && TransactionStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate(10)->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'currentRole' => $role,
            'currentStatus' => $status,
            'user' => $user,
        ]);
    }

    /**
     * Tampilkan lembar kesepakatan (Agreement Snapshot) dan rincian transaksi.
     */
    public function show(Request $request, Transaction $transaction): View
    {
        $user = $request->user();
        $agreement = $transaction->agreement;

        // Otorisasi ketat: hanya Buyer, Seller, atau Admin yang berhak melihat rincian
        if (! $agreement || ($user->user_id !== $agreement->buyer_id && $user->user_id !== $agreement->seller_id && ! $user->isAdmin())) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat lembar kesepakatan transaksi ini.');
        }

        // Auto-sinkronisasi status dengan gateway jika masih WAITING_PAYMENT dan telah memiliki order pembayaran
        if ($transaction->status === TransactionStatus::WAITING_PAYMENT && $transaction->payment?->gateway_order_id) {
            try {
                $this->paymentService->syncPaymentStatus($transaction);
                $transaction->refresh();
            } catch (Throwable) {
                // Jangan gagalkan render jika gateway eksternal sedang mengalami kendala
            }
        }

        $transaction->load([
            'agreement.buyer',
            'agreement.seller',
            'agreement.variant.product.category',
            'agreement.conversation',
            'listing',
            'payment',
            'statusHistories.changedBy',
            'evidenceFiles',
            'deadlineEvents',
        ]);

        return view('transactions.show', [
            'transaction' => $transaction,
            'agreement' => $agreement,
            'isBuyer' => $user->user_id === $agreement->buyer_id,
            'isSeller' => $user->user_id === $agreement->seller_id,
        ]);
    }
}
