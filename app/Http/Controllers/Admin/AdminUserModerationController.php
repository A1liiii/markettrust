<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\AccountModerationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveAccountReportRequest;
use App\Http\Requests\Admin\ReviewAccountReportRequest;
use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserModerationController extends Controller
{
    public function __construct(
        protected AccountModerationServiceInterface $moderationService
    ) {}

    /**
     * Tampilkan antrean peninjauan laporan akun dan direktori moderasi pengguna (UC-24).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = AccountReport::query()
            ->with([
                'reportedUser',
                'reporter',
                'transaction.agreement',
                'reviewer',
                'evidenceFiles',
            ])
            ->latest('report_id');

        if ($status && in_array($status, [AccountReport::STATUS_OPEN, AccountReport::STATUS_REVIEWING, AccountReport::STATUS_RESOLVED, AccountReport::STATUS_DISMISSED], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('reportedUser', fn ($sub) => $sub->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('reporter', fn ($sub) => $sub->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('account_status', 'ACTIVE')->count(),
            'suspended_users' => User::where('account_status', 'SUSPENDED')->count(),
            'pending_reports' => AccountReport::whereIn('status', [AccountReport::STATUS_OPEN, AccountReport::STATUS_REVIEWING])->count(),
            'open_reports' => AccountReport::where('status', AccountReport::STATUS_OPEN)->count(),
            'reviewing_reports' => AccountReport::where('status', AccountReport::STATUS_REVIEWING)->count(),
            'resolved_reports' => AccountReport::where('status', AccountReport::STATUS_RESOLVED)->count(),
            'dismissed_reports' => AccountReport::where('status', AccountReport::STATUS_DISMISSED)->count(),
        ];

        return view('admin.users.index', [
            'reports' => $reports,
            'stats' => $stats,
            'currentStatus' => $status,
            'search' => $search,
        ]);
    }

    /**
     * Ambil rincian detail investigasi kasus laporan beserta rekam jejak pengguna terlapor.
     */
    public function show(Request $request, AccountReport $report): View|JsonResponse
    {
        $report->loadMissing([
            'reportedUser',
            'reporter',
            'transaction.agreement.buyer',
            'transaction.agreement.seller',
            'reviewer',
            'evidenceFiles',
            'reputationEvents.actor',
        ]);

        $context = $this->moderationService->getUserModerationContext($report->reportedUser);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'report' => $report,
                'context' => $context,
            ]);
        }

        return view('admin.users.show', [
            'report' => $report,
            'context' => $context,
        ]);
    }

    /**
     * Ubah status laporan akun menjadi REVIEWING (meminta bukti tambahan / investigasi mendalam).
     */
    public function review(ReviewAccountReportRequest $request, AccountReport $report): RedirectResponse
    {
        $this->moderationService->reviewReport(
            $report,
            $request->user(),
            $request->input('review_note')
        );

        return redirect()->back()->with(
            'success',
            'Laporan #MT-REP-'.str_pad($report->report_id, 4, '0', STR_PAD_LEFT).' berhasil ditandai sedang diinvestigasi (REVIEWING).'
        );
    }

    /**
     * Tutup laporan yang tidak terbukti (DISMISSED) tanpa memberikan sanksi pada terlapor.
     */
    public function dismiss(ReviewAccountReportRequest $request, AccountReport $report): RedirectResponse
    {
        $this->moderationService->dismissReport(
            $report,
            $request->user(),
            $request->input('review_note')
        );

        return redirect()->back()->with(
            'success',
            'Laporan #MT-REP-'.str_pad($report->report_id, 4, '0', STR_PAD_LEFT).' telah ditolak/ditutup (DISMISSED) tanpa perubahan reputasi atau sanksi akun.'
        );
    }

    /**
     * Tetapkan keputusan moderasi final (RESOLVED) beserta sanksi reputasi / penangguhan akun.
     */
    public function resolve(ResolveAccountReportRequest $request, AccountReport $report): RedirectResponse
    {
        $this->moderationService->resolveReport(
            $report,
            $request->user(),
            $request->validated()
        );

        return redirect()->back()->with(
            'success',
            'Keputusan moderasi untuk laporan #MT-REP-'.str_pad($report->report_id, 4, '0', STR_PAD_LEFT).' berhasil ditetapkan, sanksi diterapkan secara idempoten, dan dicatat dalam audit log.'
        );
    }
}
