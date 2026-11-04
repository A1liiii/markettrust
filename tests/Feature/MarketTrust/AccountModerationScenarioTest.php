<?php

namespace Tests\Feature\MarketTrust;

use App\Models\AccountReport;
use App\Models\AuditLog;
use App\Models\ReputationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountModerationScenarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-14-01 — Pengguna Melaporkan Akun Lawan Transaksi (UC-30)
     * Uji pelaporan akun pengguna oleh pelapor dengan alasan dan bukti,
     * status awal OPEN, penolakan melaporkan akun sendiri, dan penolakan laporan duplikat aktif.
     */
    public function test_tc_14_01_pengguna_melaporkan_akun_lawan_transaksi(): void
    {
        Storage::fake('public');

        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();

        // 1. Larangan melaporkan akun diri sendiri
        $selfReportRes = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reporter->user_id,
            'reason' => 'Mencoba melaporkan akun diri sendiri untuk pengujian',
        ]);
        $selfReportRes->assertSessionHasErrors(['reported_user_id']);

        // 2. Pelaporan sah dengan file bukti
        $fakeEvidence = UploadedFile::fake()->create('bukti_pelanggaran.png', 500, 'image/png');

        $validReportRes = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Terindikasi melakukan penipuan akun dan mengirim kredensial palsu.',
            'evidence' => $fakeEvidence,
        ]);

        $validReportRes->assertRedirect();
        $this->assertDatabaseHas('account_reports', [
            'reporter_id' => $reporter->user_id,
            'reported_user_id' => $reportedUser->user_id,
            'status' => AccountReport::STATUS_OPEN,
            'reason' => 'Terindikasi melakukan penipuan akun dan mengirim kredensial palsu.',
        ]);

        // Verifikasi file bukti tersimpan
        $report = AccountReport::where('reported_user_id', $reportedUser->user_id)->first();
        $this->assertNotNull($report);
        $this->assertCount(1, $report->evidenceFiles);

        // 3. Larangan duplikasi laporan saat laporan sebelumnya masih aktif (OPEN)
        $duplicateRes = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Laporan kedua dengan pihak yang sama saat laporan pertama belum selesai',
        ]);
        $duplicateRes->assertSessionHasErrors(['reported_user_id']);
    }

    /**
     * TC-14-02 — Admin Meninjau Kasus Pelaporan dan Menutup Laporan Palsu (DISMISSED)
     * Uji admin menolak laporan yang tidak terbukti (DISMISSED),
     * reputasi dan status akun terlapor tidak berubah (tanpa sanksi), dan jejak audit tercatat.
     */
    public function test_tc_14_02_admin_meninjau_laporan_dan_menutup_laporan_palsu_dismissed(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create([
            'account_status' => 'ACTIVE',
        ]);

        $report = AccountReport::create([
            'reporter_id' => $reporter->user_id,
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Tuduhan bahwa akun penjual tidak merespon pesan',
            'status' => AccountReport::STATUS_OPEN,
            'source' => AccountReport::SOURCE_USER,
        ]);

        // Non-admin mencoba dismiss -> ditolak 403
        $unauthorizedRes = $this->actingAs($reporter)
            ->post(route('admin.users.reports.dismiss', $report->report_id), [
                'review_note' => 'Bukan admin mencoba menutup kasus',
            ]);
        $unauthorizedRes->assertForbidden();

        // Admin menutup laporan palsu (DISMISSED)
        $dismissRes = $this->actingAs($admin)
            ->post(route('admin.users.reports.dismiss', $report->report_id), [
                'review_note' => 'Setelah diverifikasi, tuduhan tidak terbukti karena batas respon belum terlewati.',
            ]);

        $dismissRes->assertRedirect();

        $report->refresh();
        $this->assertSame(AccountReport::STATUS_DISMISSED, $report->status);
        $this->assertSame($admin->user_id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);

        // Terlapor TIDAK terkena sanksi (tetap ACTIVE, poin 0 karena tidak ada event)
        $reportedUser->refresh();
        $this->assertSame('ACTIVE', $reportedUser->account_status);
        $this->assertEquals(0, $reportedUser->reputation_points);

        // Jejak audit tercatat
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_DISMISSED',
            'entity_type' => 'account_reports',
            'entity_id' => $report->report_id,
        ]);
    }

    /**
     * TC-14-03 — Admin Menjatuhkan Sanksi dan Mengubah Status Reputasi Menjadi PROBLEMATIC
     * Uji admin memutuskan laporan (RESOLVED) dengan sanksi perubahan status akun SUSPENDED,
     * status reputasi PROBLEMATIC, penalti poin, ReputationEvent idempoten, dan AuditLog.
     */
    public function test_tc_14_03_admin_menjatuhkan_sanksi_dan_mengubah_reputasi_problematic(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create([
            'account_status' => 'ACTIVE',
        ]);

        $report = AccountReport::create([
            'reporter_id' => $reporter->user_id,
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Pengguna terbukti membagikan kredensial phising',
            'status' => AccountReport::STATUS_OPEN,
            'source' => AccountReport::SOURCE_USER,
        ]);

        // Admin menetapkan keputusan moderasi sanksi
        $resolveRes = $this->actingAs($admin)
            ->post(route('admin.users.reports.resolve', $report->report_id), [
                'review_note' => 'Terbukti melanggar ketentuan integritas. Akun disuspend dan ditandai bermasalah.',
                'account_status' => 'SUSPENDED',
                'new_reputation_status' => 'PROBLEMATIC',
                'points_delta' => -50,
            ]);

        $resolveRes->assertRedirect();

        $report->refresh();
        $this->assertSame(AccountReport::STATUS_RESOLVED, $report->status);
        $this->assertSame($admin->user_id, $report->reviewed_by);

        // Verifikasi akun terlapor berstatus SUSPENDED dan reputasi PROBLEMATIC
        $reportedUser->refresh();
        $this->assertSame('SUSPENDED', $reportedUser->account_status);
        $this->assertSame('PROBLEMATIC', $reportedUser->reputation_status);
        $this->assertEquals(-50, $reportedUser->reputation_points);

        // Verifikasi pencatatan ReputationEvent idempoten
        $this->assertDatabaseHas('reputation_events', [
            'user_id' => $reportedUser->user_id,
            'event_type' => ReputationEvent::EVENT_ADMIN_MODERATION,
            'new_status' => 'PROBLEMATIC',
            'points_delta' => -50,
            'event_key' => ReputationEvent::generateReportEventKey($report->report_id),
        ]);

        // Verifikasi AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_RESOLVED',
            'entity_type' => 'account_reports',
            'entity_id' => $report->report_id,
        ]);
    }
}
