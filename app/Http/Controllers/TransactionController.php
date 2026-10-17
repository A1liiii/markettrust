<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
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

        $transaction->load([
            'agreement.buyer',
            'agreement.seller',
            'agreement.variant.product.category',
            'listing',
            'payment',
            'statusHistories.changedBy',
        ]);

        return view('transactions.show', [
            'transaction' => $transaction,
            'agreement' => $agreement,
            'isBuyer' => $user->user_id === $agreement->buyer_id,
            'isSeller' => $user->user_id === $agreement->seller_id,
        ]);
    }
}
