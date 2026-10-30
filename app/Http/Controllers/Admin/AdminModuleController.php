<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KeepObligation;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminModuleController extends Controller
{
    /**
     * Tampilkan halaman pengawasan Transaksi & Escrow, peninjauan pembatalan, dan kewajiban KEEP.
     */
    public function transactions(Request $request): View
    {
        $pendingCancellations = TransactionCancellation::query()
            ->where('status', TransactionCancellation::STATUS_REQUESTED)
            ->with([
                'transaction.agreement.buyer',
                'transaction.agreement.seller',
                'transaction.payment',
                'requestedBy',
            ])
            ->latest('cancellation_id')
            ->get();

        $keepObligations = KeepObligation::query()
            ->with([
                'seller',
                'cancellation.transaction.agreement',
            ])
            ->latest('keep_obligation_id')
            ->paginate(15, ['*'], 'keeps_page')
            ->withQueryString();

        $transactions = Transaction::query()
            ->with([
                'agreement.buyer',
                'agreement.seller',
                'payment',
                'latestCancellation.keepObligation',
            ])
            ->latest('transaction_id')
            ->paginate(15, ['*'], 'tx_page')
            ->withQueryString();

        return view('admin.transactions.index', [
            'pendingCancellations' => $pendingCancellations,
            'keepObligations' => $keepObligations,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Sengketa & Komplain.
     */
    public function disputes(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'disputes',
            'title' => 'Penyelesaian Sengketa & Komplain',
            'description' => 'Investigasi laporan sengketa pembeli-penjual, peninjauan berkas bukti digital, dan keputusan resolusi komplain.',
            'icon' => 'scale',
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Pengguna & Akun.
     */
    public function users(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'users',
            'title' => 'Manajemen Pengguna & Reputasi',
            'description' => 'Kelola direktori pengguna, tinjauan reputasi (LEGIT / CAUTION), dan penangguhan akun bermasalah.',
            'icon' => 'users',
        ]);
    }

    /**
     * Tampilkan halaman placeholder modul Audit Log.
     */
    public function auditLogs(): View
    {
        return view('admin.placeholder', [
            'moduleKey' => 'audit_logs',
            'title' => 'Audit Log & Jejak Aktivitas',
            'description' => 'Rekam jejak seluruh aktivitas administratif, perubahan status finansial, dan audit sistem transaksi.',
            'icon' => 'clipboard-document-list',
        ]);
    }
}
