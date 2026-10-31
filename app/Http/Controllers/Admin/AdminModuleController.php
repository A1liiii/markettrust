<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\SettlementServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KeepObligation;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Tampilkan halaman antrean moderasi laporan akun dan manajemen pengguna (UC-24).
     */
    public function users(Request $request): View
    {
        return app(AdminUserModerationController::class)->index($request);
    }

    /**
     * Tampilkan riwayat audit log aktivitas administratif dan operasional sistem.
     */
    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with('actor')->latest('log_id');

        if ($request->filled('action')) {
            $query->where('action', (string) $request->query('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', (string) $request->query('entity_type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $lowerSearch = mb_strtolower($search);
            $query->where(function ($q) use ($lowerSearch, $search) {
                $q->where(DB::raw('LOWER(action)'), 'like', "%{$lowerSearch}%")
                    ->orWhere(DB::raw('LOWER(entity_type)'), 'like', "%{$lowerSearch}%")
                    ->orWhereHas('actor', function ($aq) use ($lowerSearch) {
                        $aq->where(DB::raw('LOWER(username)'), 'like', "%{$lowerSearch}%")
                            ->orWhere(DB::raw('LOWER(email)'), 'like', "%{$lowerSearch}%");
                    });

                if (is_numeric($search)) {
                    $q->orWhere('entity_id', (int) $search);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $auditLogs = $query->paginate(20)->withQueryString();

        $availableActions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $availableEntityTypes = AuditLog::query()
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return view('admin.audit_logs.index', [
            'auditLogs' => $auditLogs,
            'availableActions' => $availableActions,
            'availableEntityTypes' => $availableEntityTypes,
            'currentAction' => $request->query('action'),
            'currentEntityType' => $request->query('entity_type'),
            'currentSearch' => $request->query('search'),
            'currentDateFrom' => $request->query('date_from'),
            'currentDateTo' => $request->query('date_to'),
        ]);
    }
}
