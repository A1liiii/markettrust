<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\SettlementServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\KeepObligation;
use App\Models\SettlementAttempt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class AdminSettlementController extends Controller
{
    public function __construct(
        protected SettlementServiceInterface $settlementService
    ) {}

    /**
     * Catat eksekusi Payout Simulatif (SUCCESS) atau percobaan gagal/belum pasti (FAILED / UNKNOWN).
     */
    public function recordPayout(Request $request, Transaction $transaction): RedirectResponse
    {
        $outcome = strtoupper(trim((string) $request->input('execution_outcome', 'SUCCESS')));

        if (in_array($outcome, [SettlementAttempt::STATUS_FAILED, SettlementAttempt::STATUS_UNKNOWN], true)) {
            $validated = $request->validate([
                'execution_outcome' => ['required', 'in:FAILED,UNKNOWN'],
                'failure_category' => ['required', 'in:SYSTEM,SELLER_DESTINATION,GATEWAY_UNKNOWN,NONE'],
                'failure_reason' => ['required', 'string', 'max:1000'],
                'follow_up_action' => ['nullable', 'string', 'max:1000'],
                'reference_no' => ['nullable', 'string', 'max:100'],
                'announce_disruption' => ['nullable', 'boolean'],
            ], [
                'failure_reason.required' => 'Penyebab kegagalan atau ketidakpastian payout wajib diisi.',
            ]);

            try {
                $attempt = $this->settlementService->recordFailedOrUnknownPayoutAttempt(
                    $transaction,
                    $request->user(),
                    [
                        'status' => $validated['execution_outcome'],
                        'failure_category' => $validated['failure_category'],
                        'failure_reason' => $validated['failure_reason'],
                        'follow_up_action' => $validated['follow_up_action'] ?? null,
                        'reference_no' => $validated['reference_no'] ?? null,
                        'announce_disruption' => $request->boolean('announce_disruption'),
                    ]
                );

                return redirect()
                    ->route('admin.transactions.index')
                    ->with('success', sprintf(
                        'Percobaan payout #%d (%s) untuk Transaksi #TX-%d berhasil dicatat tanpa memotong KEEP final.',
                        $attempt->attempt_no,
                        $attempt->status,
                        $transaction->transaction_id
                    ));
            } catch (InvalidArgumentException|AuthorizationException $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }

        $validated = $request->validate([
            'reference_no' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric'],
            'evidence_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'reference_no.required' => 'Nomor referensi bukti pencairan (payout) wajib diisi.',
            'evidence_file.mimes' => 'Bukti simulasi payout harus berformat gambar (JPG, PNG, WEBP) atau PDF.',
            'evidence_file.max' => 'Ukuran berkas bukti simulasi payout maksimal 5 MB.',
        ]);

        try {
            $settlement = $this->settlementService->recordSimulatedPayout(
                $transaction,
                $request->user(),
                $validated,
                $request->file('evidence_file')
            );

            return redirect()
                ->route('admin.transactions.index')
                ->with('success', sprintf(
                    'Payout simulatif Transaksi #TX-%d berhasil diselesaikan (Net: Rp %s, Potongan KEEP: Rp %s).',
                    $transaction->transaction_id,
                    number_format((float) $settlement->amount, 0, ',', '.'),
                    number_format((float) $settlement->keep_deducted_amount, 0, ',', '.')
                ));
        } catch (InvalidArgumentException|AuthorizationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Validasi & sinkronkan ulang tujuan rekening payout aktif Seller ke settlement yang tertunda (UC-29B).
     */
    public function syncDestination(Request $request, Transaction $transaction): RedirectResponse
    {
        try {
            $settlement = $this->settlementService->prepareOrSyncPayoutSettlement($transaction, $request->user());

            return redirect()
                ->route('admin.transactions.index')
                ->with('success', sprintf(
                    'Tujuan payout aktif Penjual (%s - %s) berhasil divalidasi dan disinkronkan ke Settlement #STL-%d.',
                    $settlement->destination_type,
                    $settlement->destination_holder,
                    $settlement->settlement_id
                ));
        } catch (InvalidArgumentException|AuthorizationException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Catat penyelesaian Refund Simulatif kepada Pembeli (UC-20).
     */
    public function recordRefund(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'destination_type' => ['required', 'string', 'in:BANK,EWALLET,QRIS'],
            'destination_provider' => ['nullable', 'string', 'max:30'],
            'destination_number' => ['nullable', 'string', 'max:100'],
            'destination_holder' => ['required', 'string', 'max:100'],
            'destination_qris_image_url' => ['nullable', 'string', 'max:500'],
            'reference_no' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric'],
            'evidence_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'destination_type.required' => 'Metode pengembalian dana (BANK, EWALLET, atau QRIS) wajib dipilih.',
            'destination_holder.required' => 'Nama pemilik rekening/tujuan refund Pembeli wajib diisi.',
            'reference_no.required' => 'Nomor referensi bukti refund wajib diisi.',
            'evidence_file.mimes' => 'Bukti simulasi refund harus berformat gambar (JPG, PNG, WEBP) atau PDF.',
            'evidence_file.max' => 'Ukuran berkas bukti simulasi refund maksimal 5 MB.',
        ]);

        try {
            $settlement = $this->settlementService->recordSimulatedRefund(
                $transaction,
                $request->user(),
                $validated,
                $request->file('evidence_file')
            );

            return redirect()
                ->route('admin.transactions.index')
                ->with('success', sprintf(
                    'Refund simulatif Transaksi #TX-%d sebesar Rp %s kepada Pembeli berhasil diselesaikan.',
                    $transaction->transaction_id,
                    number_format((float) $settlement->amount, 0, ',', '.')
                ));
        } catch (InvalidArgumentException|AuthorizationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Kirim pengumuman sistem atas gangguan luas saluran payout (UC-29).
     */
    public function announceDisruption(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:250'],
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,transaction_id'],
        ], [
            'message.required' => 'Isi pesan pengumuman gangguan sistem wajib diisi.',
        ]);

        $relatedTx = ! empty($validated['transaction_id'])
            ? Transaction::find($validated['transaction_id'])
            : null;

        try {
            $count = $this->settlementService->announceSystemDisruption(
                $request->user(),
                $validated['message'],
                $relatedTx
            );

            return redirect()
                ->route('admin.transactions.index')
                ->with('success', "Pengumuman gangguan sistem payout berhasil dikirimkan ke {$count} pengguna terkait.");
        } catch (InvalidArgumentException|AuthorizationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan rincian ledger kewajiban KEEP milik seorang Seller dengan proteksi kerahasiaan (TC-34-06).
     */
    public function sellerKeepLedger(Request $request, User $seller): View|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer || (! $viewer->isAdmin() && (int) $viewer->user_id !== (int) $seller->user_id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat catatan kewajiban KEEP penjual lain.');
        }

        $obligations = KeepObligation::query()
            ->where('seller_id', $seller->user_id)
            ->with([
                'cancellation.transaction.agreement',
                'deductions.settlement.transaction',
            ])
            ->orderByDesc('keep_obligation_id')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'seller_id' => $seller->user_id,
                'seller_username' => $seller->username,
                'total_open_remaining' => (float) $obligations->where('status', KeepObligation::STATUS_OPEN)->sum('remaining_amount'),
                'obligations' => $obligations,
            ]);
        }

        return view('admin.transactions.seller-keep-ledger', [
            'seller' => $seller,
            'obligations' => $obligations,
            'totalOpenRemaining' => (float) $obligations->where('status', KeepObligation::STATUS_OPEN)->sum('remaining_amount'),
        ]);
    }
}
