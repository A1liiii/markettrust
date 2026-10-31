<?php

namespace Tests\Feature;

use App\Models\AccountReport;
use App\Models\EvidenceFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountReportAndModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Pengguna tidak dapat melaporkan akun miliknya sendiri.
     */
    public function test_user_cannot_report_their_own_account(): void
    {
        $user = User::factory()->create([
            'username' => 'selfreporter',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post(route('reports.accounts.store'), [
            'reported_user_id' => $user->user_id,
            'reason' => 'Mencoba melaporkan diri sendiri untuk menguji validasi sistem.',
        ]);

        $response->assertSessionHasErrors('reported_user_id');
        $this->assertDatabaseCount('account_reports', 0);
    }

    /**
     * Pengguna dapat melaporkan akun pengguna lain dengan alasan dan bukti pendukung.
     * Pelaporan awal TIDAK otomatis mengurangi poin reputasi atau memberikan sanksi pada terlapor.
     */
    public function test_user_can_report_account_with_reason_and_evidence_without_automatic_sanctions(): void
    {
        $reporter = User::factory()->create([
            'username' => 'buyer_pelapor',
            'account_status' => 'ACTIVE',
        ]);

        $reportedUser = User::factory()->create([
            'username' => 'seller_terlapor',
            'account_status' => 'ACTIVE',
        ]);

        $initialReputationPoints = $reportedUser->reputation_points;
        $initialReputationStatus = $reportedUser->reputation_status;

        $evidenceFile = UploadedFile::fake()->create('bukti_pelanggaran.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Pengguna ini tidak menyerahkan akun game yang dijanjikan setelah tenggat waktu berakhir.',
            'evidence' => $evidenceFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan record laporan tersimpan dengan status OPEN dan source USER
        $this->assertDatabaseHas('account_reports', [
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'status' => 'OPEN',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $report = AccountReport::first();
        $this->assertNotNull($report);
        $this->assertEquals(AccountReport::STATUS_OPEN, $report->status);

        // Pastikan berkas bukti pendukung tersimpan pada evidence_files
        $this->assertDatabaseHas('evidence_files', [
            'uploaded_by' => $reporter->user_id,
            'report_id' => $report->report_id,
            'file_name' => 'bukti_pelanggaran.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $evidence = EvidenceFile::where('report_id', $report->report_id)->first();
        Storage::disk('public')->assertExists($evidence->file_url);

        // VERIFIKASI KRUSIAL: Terlapor TIDAK mengalami sanksi, pengurangan reputasi, atau peristiwa reputasi otomatis
        $reportedUser->refresh();
        $this->assertEquals('ACTIVE', $reportedUser->account_status);
        $this->assertEquals($initialReputationPoints, $reportedUser->reputation_points);
        $this->assertEquals($initialReputationStatus, $reportedUser->reputation_status);
        $this->assertDatabaseCount('reputation_events', 0);
    }

    /**
     * Mencegah duplikasi laporan aktif yang identik oleh pelapor yang sama terhadap terlapor yang sama.
     */
    public function test_user_cannot_create_duplicate_active_report_for_same_account(): void
    {
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();

        // 1. Laporan pertama berstatus OPEN
        AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'reason' => 'Laporan pertama yang masih dalam status OPEN.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Mencoba membuat laporan kedua saat laporan pertama masih OPEN.',
        ]);

        $response->assertSessionHasErrors('reported_user_id');
        $this->assertDatabaseCount('account_reports', 1);

        // 2. Ubah status laporan pertama menjadi REVIEWING
        AccountReport::first()->update(['status' => 'REVIEWING']);

        $responseReviewing = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Mencoba membuat laporan kedua saat laporan pertama masih REVIEWING.',
        ]);

        $responseReviewing->assertSessionHasErrors('reported_user_id');
        $this->assertDatabaseCount('account_reports', 1);
    }

    /**
     * Pengguna dapat membuat laporan baru apabila laporan sebelumnya telah selesai (DISMISSED atau RESOLVED).
     */
    public function test_user_can_create_new_report_once_previous_report_is_closed(): void
    {
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();

        // Laporan pertama telah berstatus DISMISSED
        AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'reason' => 'Laporan lama yang telah ditutup.',
            'status' => 'DISMISSED',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($reporter)->post(route('reports.accounts.store'), [
            'reported_user_id' => $reportedUser->user_id,
            'reason' => 'Laporan baru karena terdapat pelanggaran baru pada transaksi berikutnya.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('account_reports', 2);
    }

    /**
     * Pengguna biasa dilarang mengakses antrean moderasi dan tindakan admin (403 Forbidden).
     */
    public function test_regular_user_cannot_access_admin_moderation_queue_and_actions(): void
    {
        $user = User::factory()->create(['account_type' => 'USER']);
        $report = AccountReport::create([
            'reported_user_id' => $user->user_id,
            'reporter_id' => null,
            'source' => 'SYSTEM',
            'reason' => 'Indikasi akun bot oleh sistem.',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.reports.review', $report), ['review_note' => 'Catatan'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.reports.dismiss', $report), ['review_note' => 'Catatan'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.reports.resolve', $report), ['review_note' => 'Catatan'])->assertForbidden();
    }

    /**
     * Admin dapat melihat antrean moderasi laporan akun dan data investigasi kasus.
     */
    public function test_admin_can_view_moderation_queue_and_case_details(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create(['username' => 'pelapor_sah']);
        $reportedUser = User::factory()->create(['username' => 'terlapor_curang']);

        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'reason' => 'Pelanggaran pengiriman kredensial palsu pada pesanan.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Manajemen Pengguna & Moderasi Akun');
        $response->assertSee('@pelapor_sah');
        $response->assertSee('@terlapor_curang');
        $response->assertSee('Menunggu Tinjauan');

        // Uji endpoint detail show / API context
        $detailResponse = $this->actingAs($admin)->getJson(route('admin.users.reports.show', $report));
        $detailResponse->assertOk();
        $detailResponse->assertJsonStructure([
            'report' => ['report_id', 'reason', 'status'],
            'context' => ['user', 'cancellations', 'keep_obligations', 'reputation_events'],
        ]);
    }

    /**
     * Admin dapat menandai laporan menjadi REVIEWING (meminta bukti tambahan / investigasi) dan mencatat jejak audit.
     */
    public function test_admin_can_mark_report_as_reviewing_and_creates_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $reportedUser = User::factory()->create(['account_status' => 'ACTIVE']);
        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => null,
            'source' => 'USER',
            'reason' => 'Dugaan aktivitas tidak wajar pada akun.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.reports.review', $report), [
            'review_note' => 'Meminta rekaman video atau tangkapan layar transaksi gateway tambahan.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(AccountReport::STATUS_REVIEWING, $report->status);
        $this->assertEquals($admin->user_id, $report->reviewed_by);
        $this->assertEquals('Meminta rekaman video atau tangkapan layar transaksi gateway tambahan.', $report->review_note);
        $this->assertNotNull($report->reviewed_at);

        // Jejak audit tersimpan
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_REVIEWING',
            'entity_type' => 'account_reports',
            'entity_id' => $report->report_id,
        ]);

        // Terlapor belum dikenakan penalti
        $reportedUser->refresh();
        $this->assertEquals('ACTIVE', $reportedUser->account_status);
        $this->assertDatabaseCount('reputation_events', 0);
    }

    /**
     * Admin dapat menolak laporan (DISMISSED) jika tidak terbukti tanpa sanksi bagi terlapor.
     */
    public function test_admin_can_dismiss_report_without_sanction_and_creates_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $reportedUser = User::factory()->create(['account_status' => 'ACTIVE']);
        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => null,
            'source' => 'USER',
            'reason' => 'Laporan tanpa dasar yang jelas.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.reports.dismiss', $report), [
            'review_note' => 'Bukti penyerahan penjual terverifikasi valid dan tidak ditemukan pelanggaran.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(AccountReport::STATUS_DISMISSED, $report->status);
        $this->assertEquals($admin->user_id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);

        // Jejak audit
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_DISMISSED',
            'entity_type' => 'account_reports',
            'entity_id' => $report->report_id,
        ]);

        // Tanpa sanksi
        $reportedUser->refresh();
        $this->assertEquals('ACTIVE', $reportedUser->account_status);
        $this->assertDatabaseCount('reputation_events', 0);
    }

    /**
     * Admin dapat memutuskan moderasi (RESOLVED) dengan sanksi reputasi dan penangguhan akun (SUSPENDED).
     */
    public function test_admin_can_resolve_report_with_reputation_penalty_and_suspension(): void
    {
        $admin = User::factory()->admin()->create();
        $reportedUser = User::factory()->create([
            'username' => 'penipu_digital',
            'account_status' => 'ACTIVE',
        ]);

        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => null,
            'source' => 'USER',
            'reason' => 'Terbukti mengirimkan malware pada link penyerahan hasil.',
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.reports.resolve', $report), [
            'account_status' => 'SUSPENDED',
            'new_reputation_status' => 'PROBLEMATIC',
            'points_delta' => -25,
            'review_note' => 'Akun ditangguhkan karena pelanggaran keamanan berat dan diberi status reputasi PROBLEMATIC.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(AccountReport::STATUS_RESOLVED, $report->status);
        $this->assertEquals($admin->user_id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);

        // Status akun terlapor berubah menjadi SUSPENDED
        $reportedUser->refresh();
        $this->assertEquals('SUSPENDED', $reportedUser->account_status);
        $this->assertEquals('PROBLEMATIC', $reportedUser->reputation_status);
        $this->assertEquals(-25, $reportedUser->reputation_points);

        // Peristiwa reputasi tercatat dengan event_key unik
        $expectedEventKey = "account_report:{$report->report_id}:resolution";
        $this->assertDatabaseHas('reputation_events', [
            'user_id' => $reportedUser->user_id,
            'event_type' => 'ADMIN_MODERATION',
            'points_delta' => -25,
            'new_status' => 'PROBLEMATIC',
            'report_id' => $report->report_id,
            'actor_id' => $admin->user_id,
            'event_key' => $expectedEventKey,
        ]);

        // Audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->user_id,
            'action' => 'ACCOUNT_REPORT_RESOLVED',
            'entity_type' => 'account_reports',
            'entity_id' => $report->report_id,
        ]);
    }

    /**
     * Keputusan moderasi yang diulang tidak menggandakan reputation_event (idempotensi event_key).
     */
    public function test_repeating_resolve_action_is_idempotent_and_does_not_duplicate_reputation_event(): void
    {
        $admin = User::factory()->admin()->create();
        $reportedUser = User::factory()->create([
            'account_status' => 'ACTIVE',
        ]);

        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => null,
            'source' => 'USER',
            'reason' => 'Uji idempotensi moderasi admin.',
            'status' => 'OPEN',
        ]);

        // Eksekusi resolve pertama
        $this->actingAs($admin)->post(route('admin.users.reports.resolve', $report), [
            'account_status' => 'SUSPENDED',
            'new_reputation_status' => 'PROBLEMATIC',
            'points_delta' => -10,
            'review_note' => 'Keputusan pertama.',
        ]);

        $this->assertDatabaseCount('reputation_events', 1);

        // Eksekusi resolve kedua (pengulangan / retry)
        $this->actingAs($admin)->post(route('admin.users.reports.resolve', $report), [
            'account_status' => 'SUSPENDED',
            'new_reputation_status' => 'PROBLEMATIC',
            'points_delta' => -10,
            'review_note' => 'Keputusan kedua yang diulang.',
        ]);

        // Verifikasi penting: reputation_events TETAP 1 (tidak berganda)
        $this->assertDatabaseCount('reputation_events', 1);
        $reportedUser->refresh();
        $this->assertEquals(-10, $reportedUser->reputation_points);
    }

    /**
     * Hak akses berkas bukti: hanya admin dan pelapor yang berhak mengunduh bukti privat laporan.
     */
    public function test_evidence_access_authorization(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $otherUser = User::factory()->create();
        $reportedUser = User::factory()->create();

        $report = AccountReport::create([
            'reported_user_id' => $reportedUser->user_id,
            'reporter_id' => $reporter->user_id,
            'source' => 'USER',
            'reason' => 'Uji hak akses bukti.',
            'status' => 'OPEN',
        ]);

        $path = UploadedFile::fake()->image('bukti.png')->store('evidence/reports', 'public');
        $evidence = EvidenceFile::create([
            'uploaded_by' => $reporter->user_id,
            'report_id' => $report->report_id,
            'file_name' => 'bukti.png',
            'file_url' => $path,
            'mime_type' => 'image/png',
            'uploaded_at' => now(),
        ]);

        // 1. Pelapor dapat mengunduh bukti
        $this->actingAs($reporter)
            ->get(route('reports.accounts.evidence', ['report' => $report, 'evidence' => $evidence]))
            ->assertOk();

        // 2. Admin dapat mengunduh bukti
        $this->actingAs($admin)
            ->get(route('reports.accounts.evidence', ['report' => $report, 'evidence' => $evidence]))
            ->assertOk();

        // 3. Pihak ketiga yang tidak berwenang dilarang mengunduh (403 Forbidden)
        $this->actingAs($otherUser)
            ->get(route('reports.accounts.evidence', ['report' => $report, 'evidence' => $evidence]))
            ->assertForbidden();
    }
}
