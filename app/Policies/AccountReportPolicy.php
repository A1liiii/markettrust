<?php

namespace App\Policies;

use App\Models\AccountReport;
use App\Models\EvidenceFile;
use App\Models\User;

class AccountReportPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat laporan.
     */
    public function view(User $user, AccountReport $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $user->user_id === (int) $report->reporter_id;
    }

    /**
     * Tentukan apakah pengguna berhak mengunduh/melihat bukti pendukung laporan privat.
     */
    public function downloadEvidence(User $user, AccountReport $report, ?EvidenceFile $evidence = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Pelapor berhak mengunduh bukti laporannya
        if ((int) $user->user_id === (int) $report->reporter_id) {
            return true;
        }

        // Jika laporan terikat ke transaksi, pihak transaksi terkait juga diizinkan jika relevan
        if ($report->transaction_id && $report->transaction && $report->transaction->agreement) {
            $agreement = $report->transaction->agreement;
            if ((int) $user->user_id === (int) $agreement->buyer_id || (int) $user->user_id === (int) $agreement->seller_id) {
                return true;
            }
        }

        return false;
    }
}
