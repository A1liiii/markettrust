<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\AccountReport;
use App\Models\Agreement;
use App\Models\Complaint;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Tampilkan halaman Overview & Analitik Dashboard Admin lengkap.
     */
    public function index(Request $request): View|JsonResponse
    {
        $period = $request->query('period', '30d');

        if ($request->wantsJson()) {
            return response()->json($this->getAnalyticsData($period));
        }

        // 1. Metrik Kartu Ringkasan Status Transaksi (Summary Cards)
        $waitingPaymentTransactions = Transaction::whereIn('status', [TransactionStatus::WAITING_PAYMENT, 'WAITING_PAYMENT', 'PENDING_PAYMENT'])->count();
        $waitingDeliveryTransactions = Transaction::whereIn('status', [TransactionStatus::PAID, 'PAID'])->count();
        $waitingConfirmationTransactions = Transaction::whereIn('status', [TransactionStatus::DELIVERED, 'DELIVERED'])->count();
        $waitingPayoutTransactions = Transaction::whereIn('status', [TransactionStatus::CONFIRMED, 'CONFIRMED'])->count();
        $completedTransactions = Transaction::whereIn('status', [TransactionStatus::COMPLETED, 'COMPLETED'])->count();
        $disputedTransactions = Transaction::whereIn('status', [TransactionStatus::DISPUTED, 'DISPUTED'])->count();
        $cancelledTransactions = Transaction::whereIn('status', [TransactionStatus::CANCELLED, TransactionStatus::REFUNDED, 'CANCELLED', 'REFUNDED'])->count();

        $activeEscrowTransactions = $waitingDeliveryTransactions + $waitingConfirmationTransactions + $waitingPayoutTransactions;
        $inEscrowTransactions = $activeEscrowTransactions > 0
            ? $activeEscrowTransactions
            : Transaction::where('status', 'IN_ESCROW')->count();

        $failedPayoutsTransactions = Schema::hasTable('settlements')
            ? Settlement::where('status', 'FAILED')->count()
            : 0;

        $totalTransactions = Transaction::count();
        $pendingPaymentTransactions = $waitingPaymentTransactions;

        // 2. Metrik Finansial & Pemisahan Dana Escrow vs Fee Platform
        $totalGrossVolume = (float) (Agreement::sum('buyer_total') ?? 0);

        // Dana Escrow yang Masih Ditahan (Dana titipan buyer/seller di Midtrans/Rekber)
        $escrowHeldAmount = (float) (Agreement::whereHas('transaction', function ($q) {
            $q->whereIn('status', [
                TransactionStatus::PAID,
                TransactionStatus::DELIVERED,
                TransactionStatus::DISPUTED,
                'PAID',
                'DELIVERED',
                'DISPUTED',
                'IN_ESCROW',
            ]);
        })->sum('buyer_total') ?? 0);

        // Payout yang Menunggu Proses Admin (Status CONFIRMED)
        $waitingPayoutAmount = (float) (Agreement::whereHas('transaction', function ($q) {
            $q->whereIn('status', [TransactionStatus::CONFIRMED, 'CONFIRMED']);
        })->sum('seller_net') ?? 0);

        // Total Payout Berhasil (Sudah ditransfer ke seller)
        $completedPayoutAmount = Schema::hasTable('settlements')
            ? (float) (Settlement::where('settlement_type', 'PAYOUT')->where('status', 'COMPLETED')->sum('amount') ?? 0)
            : 0;
        if ($completedPayoutAmount <= 0) {
            $completedPayoutAmount = (float) (Agreement::whereHas('transaction', function ($q) {
                $q->whereIn('status', [TransactionStatus::COMPLETED, 'COMPLETED']);
            })->sum('seller_net') ?? 0);
        }

        // Pendapatan Fee Platform Tercatat (Hanya diakui dari transaksi yang sukses/selesai)
        $completedFeeRevenue = (float) (Agreement::whereHas('transaction', function ($q) {
            $q->whereIn('status', [TransactionStatus::COMPLETED, 'COMPLETED']);
        })->sum('fee_amount') ?? 0);
        $totalFeeRevenue = $completedFeeRevenue > 0
            ? $completedFeeRevenue
            : (float) (Agreement::sum('fee_amount') ?? 0);

        // Refund yang Diproses ke Buyer
        $refundProcessedAmount = Schema::hasTable('settlements')
            ? (float) (Settlement::where('settlement_type', 'REFUND')->where('status', 'COMPLETED')->sum('amount') ?? 0)
            : 0;
        if ($refundProcessedAmount <= 0) {
            $refundProcessedAmount = (float) (Agreement::whereHas('transaction', function ($q) {
                $q->whereIn('status', [TransactionStatus::REFUNDED, 'REFUNDED']);
            })->sum('buyer_total') ?? 0);
        }

        $activeEscrowVolume = $escrowHeldAmount > 0 ? $escrowHeldAmount : (float) (Agreement::whereHas('transaction', function ($q) {
            $q->whereIn('status', ['IN_ESCROW', 'PAYMENT_VERIFIED', 'DELIVERY_SUBMITTED', 'PAID', 'DELIVERED']);
        })->sum('buyer_total') ?? 0);

        $successRate = $totalTransactions > 0
            ? round(($completedTransactions / $totalTransactions) * 100, 1)
            : 100.0;

        // 3. Antrean Tindakan Admin (Action Queue - Urgensi & Deadline)
        $urgentPaymentDeadlines = Transaction::whereIn('status', [TransactionStatus::WAITING_PAYMENT, 'WAITING_PAYMENT'])
            ->whereNotNull('payment_due_at')
            ->with(['agreement.buyer', 'agreement.seller', 'listing'])
            ->orderBy('payment_due_at')
            ->take(4)
            ->get();

        $urgentDeliveryDeadlines = Transaction::whereIn('status', [TransactionStatus::PAID, 'PAID'])
            ->whereNotNull('delivery_due_at')
            ->with(['agreement.buyer', 'agreement.seller', 'listing'])
            ->orderBy('delivery_due_at')
            ->take(4)
            ->get();

        $urgentConfirmationDeadlines = Transaction::whereIn('status', [TransactionStatus::DELIVERED, 'DELIVERED'])
            ->whereNotNull('confirmation_due_at')
            ->with(['agreement.buyer', 'agreement.seller', 'listing'])
            ->orderBy('confirmation_due_at')
            ->take(4)
            ->get();

        $waitingPayoutActionList = Transaction::whereIn('status', [TransactionStatus::CONFIRMED, 'CONFIRMED'])
            ->with(['agreement.buyer', 'agreement.seller', 'settlement'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        $activeComplaints = Schema::hasTable('complaints')
            ? Complaint::whereIn('status', ['OPEN', 'REVIEWING'])
                ->with(['transaction.agreement.buyer', 'transaction.agreement.seller', 'raisedBy'])
                ->latest('created_at')
                ->take(5)
                ->get()
            : collect();

        $pendingAccountReports = Schema::hasTable('account_reports')
            ? AccountReport::whereIn('status', ['OPEN', 'REVIEWING'])
                ->with(['reportedUser', 'reporter', 'transaction'])
                ->latest('created_at')
                ->take(5)
                ->get()
            : collect();

        $totalActionQueueCount = $urgentPaymentDeadlines->count()
            + $urgentDeliveryDeadlines->count()
            + $urgentConfirmationDeadlines->count()
            + $waitingPayoutActionList->count()
            + $activeComplaints->count()
            + $pendingAccountReports->count();

        // 4. Metrik Master Data, Inventaris, & Komunitas
        $totalCategories = ProductCategory::count();
        $totalProducts = Product::count();
        $totalVariants = ProductVariant::count();
        $totalListings = Listing::count();
        $activeListings = Listing::where('status', 'ACTIVE')->count();

        $totalUsers = User::count();
        $activeUsers = User::where('account_status', 'ACTIVE')->count();
        $suspendedUsers = User::where('account_status', 'SUSPENDED')->count();
        $totalSellers = User::has('listings')->count();
        $totalForumPosts = ForumPost::count();

        $totalComplaints = Schema::hasTable('complaints') ? Complaint::count() : 0;
        $openComplaints = Schema::hasTable('complaints') ? Complaint::where('status', 'OPEN')->count() : 0;

        // 5. Data Stream Aktivitas Terkini (Activity Feed & Audit)
        $recentStatusHistories = Schema::hasTable('transaction_status_histories')
            ? TransactionStatusHistory::with(['transaction.agreement.buyer', 'transaction.agreement.seller', 'changedBy'])
                ->latest('changed_at')
                ->take(8)
                ->get()
            : collect();

        $recentTransactions = Transaction::with(['agreement.buyer', 'agreement.seller', 'listing'])
            ->latest('transaction_id')
            ->take(6)
            ->get();

        $recentListings = Listing::with(['seller', 'variant.product'])
            ->latest('listing_id')
            ->take(5)
            ->get();

        // 6. Dataset Analitik untuk 5 Chart
        $analyticsData = $this->getAnalyticsData($period);

        return view('admin.dashboard', [
            // Summary Cards 7 Metrik
            'activeEscrowTransactions' => $activeEscrowTransactions,
            'waitingPaymentTransactions' => $waitingPaymentTransactions,
            'waitingDeliveryTransactions' => $waitingDeliveryTransactions,
            'waitingConfirmationTransactions' => $waitingConfirmationTransactions,
            'waitingPayoutTransactions' => $waitingPayoutTransactions,
            'completedTransactions' => $completedTransactions,
            'disputedTransactions' => $disputedTransactions,
            'cancelledTransactions' => $cancelledTransactions,
            'failedPayoutsTransactions' => $failedPayoutsTransactions,
            'totalTransactions' => $totalTransactions,
            'inEscrowTransactions' => $inEscrowTransactions,
            'pendingPaymentTransactions' => $pendingPaymentTransactions,

            // Ringkasan Finansial Escrow vs Fee Platform
            'totalGrossVolume' => $totalGrossVolume,
            'escrowHeldAmount' => $escrowHeldAmount,
            'waitingPayoutAmount' => $waitingPayoutAmount,
            'completedPayoutAmount' => $completedPayoutAmount,
            'totalFeeRevenue' => $totalFeeRevenue,
            'refundProcessedAmount' => $refundProcessedAmount,
            'activeEscrowVolume' => $activeEscrowVolume,
            'successRate' => $successRate,

            // Antrean Tindakan Admin
            'urgentPaymentDeadlines' => $urgentPaymentDeadlines,
            'urgentDeliveryDeadlines' => $urgentDeliveryDeadlines,
            'urgentConfirmationDeadlines' => $urgentConfirmationDeadlines,
            'waitingPayoutActionList' => $waitingPayoutActionList,
            'activeComplaints' => $activeComplaints,
            'pendingAccountReports' => $pendingAccountReports,
            'totalActionQueueCount' => $totalActionQueueCount,

            // Master Data & Komunitas
            'totalCategories' => $totalCategories,
            'totalProducts' => $totalProducts,
            'totalVariants' => $totalVariants,
            'totalListings' => $totalListings,
            'activeListings' => $activeListings,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'suspendedUsers' => $suspendedUsers,
            'totalSellers' => $totalSellers,
            'totalForumPosts' => $totalForumPosts,
            'totalComplaints' => $totalComplaints,
            'openComplaints' => $openComplaints,

            // Stream Aktivitas & Listing
            'recentStatusHistories' => $recentStatusHistories,
            'recentTransactions' => $recentTransactions,
            'recentListings' => $recentListings,

            // Data Awal Chart Analitik
            'analyticsData' => $analyticsData,
            'currentPeriod' => $period,
        ]);
    }

    /**
     * Endpoint API JSON untuk data 5 chart analitik dengan filter periode dinamis.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->query('period', '30d');

        return response()->json($this->getAnalyticsData($period));
    }

    /**
     * Hitung agregasi data untuk 5 Chart Analitik berdasarkan rentang waktu.
     */
    private function getAnalyticsData(string $period): array
    {
        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            'all' => 365,
            default => 30,
        };

        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        // 1. Chart 1: Tren Transaksi & Nilai GMV (Time Series)
        $trendLabels = [];
        $transactionCounts = [];
        $gmvValues = [];

        // Kelompokkan per interval sesuai panjang hari
        $intervalDays = $days <= 14 ? 1 : ($days <= 60 ? 2 : 5);

        for ($date = clone $startDate; $date->lte($endDate); $date->addDays($intervalDays)) {
            $chunkStart = clone $date;
            $chunkEnd = (clone $date)->addDays($intervalDays - 1)->endOfDay();
            if ($chunkEnd->gt($endDate)) {
                $chunkEnd = clone $endDate;
            }

            $label = $days <= 14
                ? $chunkStart->translatedFormat('d M')
                : $chunkStart->translatedFormat('d M');

            $trendLabels[] = $label;

            $txCount = Transaction::whereBetween('created_at', [$chunkStart, $chunkEnd])->count();
            $gmvSum = (float) (Agreement::whereHas('transaction', function ($q) use ($chunkStart, $chunkEnd) {
                $q->whereBetween('created_at', [$chunkStart, $chunkEnd]);
            })->sum('buyer_total') ?? 0);

            $transactionCounts[] = $txCount;
            $gmvValues[] = $gmvSum;
        }

        // 2. Chart 2: Komposisi Status Transaksi (Donut)
        $statusCounts = [
            'Menunggu Bayar' => Transaction::whereIn('status', [TransactionStatus::WAITING_PAYMENT, 'WAITING_PAYMENT', 'PENDING_PAYMENT'])
                ->where('created_at', '>=', $startDate)->count(),
            'Penyerahan Produk' => Transaction::whereIn('status', [TransactionStatus::PAID, 'PAID'])
                ->where('created_at', '>=', $startDate)->count(),
            'Konfirmasi Buyer' => Transaction::whereIn('status', [TransactionStatus::DELIVERED, 'DELIVERED'])
                ->where('created_at', '>=', $startDate)->count(),
            'Siap Payout' => Transaction::whereIn('status', [TransactionStatus::CONFIRMED, 'CONFIRMED'])
                ->where('created_at', '>=', $startDate)->count(),
            'Selesai (Payout)' => Transaction::whereIn('status', [TransactionStatus::COMPLETED, 'COMPLETED'])
                ->where('created_at', '>=', $startDate)->count(),
            'Sengketa (Dispute)' => Transaction::whereIn('status', [TransactionStatus::DISPUTED, 'DISPUTED'])
                ->where('created_at', '>=', $startDate)->count(),
            'Dibatalkan / Refund' => Transaction::whereIn('status', [TransactionStatus::CANCELLED, TransactionStatus::REFUNDED, 'CANCELLED', 'REFUNDED'])
                ->where('created_at', '>=', $startDate)->count(),
        ];

        // 3. Chart 3: Performa Transaksi (Bar: Sukses vs Dibatalkan vs Bermasalah)
        $performanceCompleted = Transaction::whereIn('status', [TransactionStatus::COMPLETED, 'COMPLETED'])
            ->where('created_at', '>=', $startDate)->count();
        $performanceCancelled = Transaction::whereIn('status', [TransactionStatus::CANCELLED, TransactionStatus::REFUNDED, 'CANCELLED', 'REFUNDED'])
            ->where('created_at', '>=', $startDate)->count();
        $performanceDisputed = Transaction::whereIn('status', [TransactionStatus::DISPUTED, 'DISPUTED'])
            ->where('created_at', '>=', $startDate)->count();

        // 4. Chart 4: Tren Komplain & Resolusi
        $complaintsCreated = Schema::hasTable('complaints')
            ? Complaint::whereBetween('created_at', [$startDate, $endDate])->count()
            : 0;
        $complaintsResolved = Schema::hasTable('complaints')
            ? Complaint::where('status', 'RESOLVED')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count()
            : 0;

        // 5. Chart 5: Status Pencairan Dana (Payout)
        $payoutCompletedCount = Schema::hasTable('settlements')
            ? Settlement::where('settlement_type', 'PAYOUT')->where('status', 'COMPLETED')
                ->where('created_at', '>=', $startDate)->count()
            : 0;
        if ($payoutCompletedCount === 0) {
            $payoutCompletedCount = $performanceCompleted;
        }

        $payoutWaitingCount = Transaction::whereIn('status', [TransactionStatus::CONFIRMED, 'CONFIRMED'])
            ->where('created_at', '>=', $startDate)->count();

        $payoutFailedCount = Schema::hasTable('settlements')
            ? Settlement::where('settlement_type', 'PAYOUT')->where('status', 'FAILED')
                ->where('created_at', '>=', $startDate)->count()
            : 0;

        return [
            'period' => $period,
            'trend' => [
                'labels' => $trendLabels,
                'transactions' => $transactionCounts,
                'gmv' => $gmvValues,
            ],
            'statusComposition' => [
                'labels' => array_keys($statusCounts),
                'data' => array_values($statusCounts),
                'total' => array_sum($statusCounts),
            ],
            'performance' => [
                'labels' => ['Transaksi Selesai', 'Dibatalkan', 'Sengketa'],
                'data' => [$performanceCompleted, $performanceCancelled, $performanceDisputed],
            ],
            'complaints' => [
                'labels' => ['Komplain Masuk', 'Terselesaikan'],
                'data' => [$complaintsCreated, $complaintsResolved],
            ],
            'payouts' => [
                'labels' => ['Payout Berhasil', 'Menunggu Eksekusi', 'Gagal / Perlu Review'],
                'data' => [$payoutCompletedCount, $payoutWaitingCount, $payoutFailedCount],
            ],
        ];
    }
}
