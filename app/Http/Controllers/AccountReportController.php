<?php

namespace App\Http\Controllers;

use App\Contracts\Services\AccountModerationServiceInterface;
use App\Http\Requests\AccountReport\CreateAccountReportRequest;
use App\Models\AccountReport;
use App\Models\EvidenceFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountReportController extends Controller
{
    public function __construct(
        protected AccountModerationServiceInterface $moderationService
    ) {}

    /**
     * Kirimkan laporan akun baru dari halaman profil atau detail transaksi (UC-30).
     */
    public function store(CreateAccountReportRequest $request): RedirectResponse
    {
        $report = $this->moderationService->createReport(
            $request->user(),
            $request->validated(),
            $request->file('evidence')
        );

        return redirect()->back()->with(
            'success',
            'Laporan akun #MT-REP-'.str_pad($report->report_id, 4, '0', STR_PAD_LEFT).' berhasil diajukan dan akan ditinjau oleh administrator.'
        );
    }

    /**
     * Unduh atau pratinjau bukti pendukung laporan privat dengan otorisasi ketat.
     */
    public function downloadEvidence(Request $request, AccountReport $report, EvidenceFile $evidence): BinaryFileResponse|StreamedResponse
    {
        Gate::authorize('downloadEvidence', [$report, $evidence]);

        if ((int) $evidence->report_id !== (int) $report->report_id) {
            abort(404, 'Berkas bukti tidak cocok dengan laporan akun yang diminta.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($evidence->file_url)) {
            abort(404, 'Berkas fisik bukti laporan tidak ditemukan pada penyimpanan.');
        }

        if ($request->boolean('inline')) {
            return response()->file(
                $disk->path($evidence->file_url),
                [
                    'Content-Type' => $evidence->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="'.addslashes($evidence->file_name).'"',
                ]
            );
        }

        return $disk->download($evidence->file_url, $evidence->file_name);
    }
}
