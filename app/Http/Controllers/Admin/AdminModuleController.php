<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\SettlementServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\KeepObligation;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminModuleController extends Controller
{
    public function __construct(
        protected SettlementServiceInterface $settlementService
    ) {}

    /**
     * Tampilkan halaman operasional Transaksi, Escrow, Settlement (Payout & Refund Simulatif), dan Kewajiban KEEP.
     */
    public function transactions(Request $request): View
    {
        $hasAttemptsTable = Schema::hasTable('settlement_attempts');

        $payoutRelations = [
            'agreement.buyer',
            'agreement.seller.activePayoutAccount',
            'payment',
            'complaint',
            'settlement.keepDeductions',
            'settlement.evidenceFiles',
        ];
        if ($hasAttemptsTable) {
            $payoutRelations[] = 'settlement.attempts';
        }

        // 1. Antrean Transaksi Layak Payout (CONFIRMED atau komplain RESOLVED = PAYOUT, tanpa komplain aktif)
        $payoutCandidates = Transaction::query()
            ->whereNotIn('status', ['COMPLETED', 'REFUNDED', 'CANCELLED', 'WAITING_PAYMENT'])
            ->where(function ($q) {
                $q->where('status', 'CONFIRMED')
                    ->orWhereHas('complaint', function ($cq) {
                        $cq->where('status', 'RESOLVED')->where('decision', 'PAYOUT');
                    });
            })
            ->with($payoutRelations)
            ->latest('transaction_id')
            ->get()
            ->filter(fn (Transaction $tx) => $tx->isEligibleForPayout())
            ->values();

        $payoutPreviews = [];
        foreach ($payoutCandidates as $tx) {
            $payoutPreviews[$tx->transaction_id] = $this->settlementService->calculatePayoutPreview($tx);
        }

        // 2. Antrean Transaksi Layak Refund (Komplain RESOLVED = REFUND atau Pembatalan Pasca-Bayar APPROVED)
        $refundCandidates = Transaction::query()
            ->whereNotIn('status', ['COMPLETED', 'REFUNDED', 'WAITING_PAYMENT'])
            ->where(function ($q) {
                $q->whereHas('complaint', function ($cq) {
                    $cq->where('status', 'RESOLVED')->where('decision', 'REFUND');
                })->orWhereHas('cancellations', function ($canQ) {
                    $canQ->where('stage', TransactionCancellation::STAGE_AFTER_PAYMENT_BEFORE_DELIVERY)
                        ->where('status', TransactionCancellation::STATUS_APPROVED);
                });
            })
            ->with([
                'agreement.buyer',
                'agreement.seller',
                'payment',
                'complaint',
                'cancellations.keepObligation',
                'settlement.evidenceFiles',
            ])
            ->latest('transaction_id')
            ->get()
            ->filter(fn (Transaction $tx) => $tx->isEligibleForRefund())
            ->values();

        $refundPreviews = [];
        foreach ($refundCandidates as $tx) {
            $refundPreviews[$tx->transaction_id] = $this->settlementService->calculateRefundPreview($tx);
        }

        // 3. Antrean Pengajuan Pembatalan Pasca-Bayar (Kompatibel MT-A06)
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

        // 4. Daftar Kewajiban KEEP Penjual (Kompatibel MT-A06)
        $keepObligations = KeepObligation::query()
            ->with([
                'seller',
                'cancellation.transaction.agreement',
                'deductions.settlement',
            ])
            ->latest('keep_obligation_id')
            ->paginate(15, ['*'], 'keeps_page')
            ->withQueryString();

        // 5. Riwayat Settlement Selesai / Sedang Diproses
        $settlementRelations = [
            'transaction.agreement.buyer',
            'transaction.agreement.seller',
            'recordedBy',
            'keepDeductions.keepObligation',
            'evidenceFiles',
        ];
        if ($hasAttemptsTable) {
            $settlementRelations[] = 'attempts';
        }

        $recentSettlements = Settlement::query()
            ->with($settlementRelations)
            ->latest('settlement_id')
            ->limit(15)
            ->get();

        // 6. Seluruh Transaksi Sistem
        $transactions = Transaction::query()
            ->with([
                'agreement.buyer',
                'agreement.seller',
                'payment',
                'settlement',
                'complaint',
                'latestCancellation.keepObligation',
            ])
            ->latest('transaction_id')
            ->paginate(15, ['*'], 'tx_page')
            ->withQueryString();

        return view('admin.transactions.index', [
            'payoutCandidates' => $payoutCandidates,
            'payoutPreviews' => $payoutPreviews,
            'refundCandidates' => $refundCandidates,
            'refundPreviews' => $refundPreviews,
            'pendingCancellations' => $pendingCancellations,
            'keepObligations' => $keepObligations,
            'recentSettlements' => $recentSettlements,
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
