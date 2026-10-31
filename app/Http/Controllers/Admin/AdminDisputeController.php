<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\ComplaintServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideDisputeRequest;
use App\Http\Requests\Admin\RequestDisputeEvidenceRequest;
use App\Models\Complaint;
use App\Models\TransactionDeadlineEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminDisputeController extends Controller
{
    public function __construct(
        protected ComplaintServiceInterface $complaintService
    ) {}

    /**
     * Tampilkan daftar seluruh komplain dan sengketa transaksi (UC-18 / KF-09).
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'ALL');
        $search = trim((string) $request->query('search', ''));

        $query = Complaint::with([
            'transaction.agreement.buyer',
            'transaction.agreement.seller',
            'raisedBy',
            'responses',
            'settlement',
        ])->latest('created_at');

        if ($statusFilter !== 'ALL' && in_array($statusFilter, ['OPEN', 'REVIEWING', 'RESOLVED'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('transaction_id', (int) $search);
                }
                $q->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('raisedBy', fn ($uq) => $uq->where('username', 'like', "%{$search}%"))
                    ->orWhereHas('transaction.agreement', fn ($aq) => $aq->where('item_title', 'like', "%{$search}%"));
            });
        }

        $complaints = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Complaint::count(),
            'open' => Complaint::where('status', 'OPEN')->count(),
            'reviewing' => Complaint::where('status', 'REVIEWING')->count(),
            'resolved' => Complaint::where('status', 'RESOLVED')->count(),
        ];

        return view('admin.disputes.index', compact('complaints', 'stats', 'statusFilter', 'search'));
    }

    /**
     * Tampilkan detail investigasi komplain dan bukti lengkap (UC-18 / KF-09).
     */
    public function show(Complaint $complaint): View
    {
        $complaint->load([
            'transaction.agreement.buyer',
            'transaction.agreement.seller',
            'transaction.agreement.variant',
            'transaction.payment',
            'transaction.evidenceFiles.uploadedBy',
            'transaction.statusHistories.changedBy',
            'raisedBy',
            'evidenceFiles.uploadedBy',
            'responses.responder',
            'responses.evidenceFiles.uploadedBy',
            'settlement.recordedBy',
            'decidedBy',
        ]);

        $deadlineEvents = TransactionDeadlineEvent::where('transaction_id', $complaint->transaction_id)
            ->with('grantedBy')
            ->orderBy('created_at', 'asc')
            ->get();

        $sellerDeadline = $deadlineEvents
            ->where('event_type', 'SELLER_COMPLAINT_RESPONSE_DUE')
            ->last();

        $adminDeadline = $deadlineEvents
            ->where('event_type', 'ADMIN_DISPUTE_DECISION_DUE')
            ->last();

        return view('admin.disputes.show', compact('complaint', 'deadlineEvents', 'sellerDeadline', 'adminDeadline'));
    }

    /**
     * Admin meminta informasi atau bukti tambahan ke para pihak (UC-18).
     */
    public function requestEvidence(RequestDisputeEvidenceRequest $request, Complaint $complaint): RedirectResponse
    {
        try {
            $this->complaintService->requestAdditionalEvidence(
                $complaint,
                $request->user(),
                $request->validated('reason'),
                (int) $request->validated('extension_hours', 24)
            );

            return redirect()
                ->route('admin.disputes.show', $complaint)
                ->with('success', 'Permintaan bukti tambahan dan perpanjangan waktu berhasil dikirimkan.');
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.disputes.show', $complaint)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Admin menetapkan keputusan resolusi sengketa (PAYOUT / REFUND) (UC-18 / KF-10).
     */
    public function decide(DecideDisputeRequest $request, Complaint $complaint): RedirectResponse
    {
        try {
            $this->complaintService->resolveComplaint(
                $complaint,
                $request->user(),
                $request->validated('decision'),
                $request->validated('decision_reason')
            );

            return redirect()
                ->route('admin.disputes.show', $complaint)
                ->with('success', 'Keputusan sengketa berhasil ditetapkan dan instruksi penyelesaian finansial (Settlement) telah disiapkan.');
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.disputes.show', $complaint)
                ->with('error', $e->getMessage());
        }
    }
}

