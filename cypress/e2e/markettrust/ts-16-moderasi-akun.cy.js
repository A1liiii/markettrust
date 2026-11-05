describe('TS-16 — Pelaporan Akun & Moderasi User', () => {
  beforeEach(() => {
    cy.task('execPhp', `
      use App\\Models\\User;
      use App\\Models\\AccountReport;
      use Illuminate\\Support\\Facades\\Hash;

      $admin = User::firstOrCreate(
        ['email' => 'admin_mod@markettrust.test'],
        [
          'username' => 'admin_moderator',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'ADMIN',
          'account_status' => 'ACTIVE',
        ]
      );

      $reporter = User::firstOrCreate(
        ['email' => 'reporter_user@markettrust.test'],
        [
          'username' => 'reporter_user',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $targetUser = User::firstOrCreate(
        ['email' => 'target_mod@markettrust.test'],
        [
          'username' => 'target_suspect',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $freshTarget = User::firstOrCreate(
        ['email' => 'target_fresh@markettrust.test'],
        [
          'username' => 'target_fresh',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      // Bersihkan laporan aktif terdahulu agar tidak duplikat
      AccountReport::where('reporter_id', $reporter->user_id)->where('reported_user_id', $freshTarget->user_id)->delete();

      // Buat laporan untuk target_suspect yang siap ditinjau admin
      $report = AccountReport::create([
        'reporter_id' => $reporter->user_id,
        'reported_user_id' => $targetUser->user_id,
        'source' => AccountReport::SOURCE_USER,
        'reason' => 'Pengguna dicurigai melakukan aktivitas mencurigakan dan penipuan transaksi.',
        'status' => AccountReport::STATUS_OPEN,
      ]);

      echo json_encode([
        'report_id' => $report->report_id,
        'target_user_id' => $targetUser->user_id,
        'fresh_target_id' => $freshTarget->user_id,
        'reporter_username' => $reporter->username,
        'admin_username' => $admin->username,
      ]);
    `).as('seedData');
  });

  it('TC-14-01 — Pengguna Melaporkan Akun Lawan Transaksi (UC-30)', function () {
    const data = JSON.parse(this.seedData);
    cy.loginByForm('reporter_user', 'password123');
    cy.visit(`/profile/${data.fresh_target_id}`);

    // Klik tombol laporkan akun
    cy.get('[data-cy="btn-report-account"]').should('be.visible').click();
    cy.get('#account-report-modal').should('be.visible');

    // Isi alasan pelaporan
    cy.get('#report-reason').clear().type('Pengguna ini terindikasi mengirimkan akun palsu pada transaksi.');
    cy.get('#account-report-modal form').submit();

    // Verifikasi pesan sukses
    cy.contains('berhasil diajukan').should('be.visible');
  });

  it('TC-14-02 — Admin Meninjau Kasus Pelaporan dan Menutup Laporan Palsu (DISMISSED)', function () {
    const data = JSON.parse(this.seedData);
    cy.loginByForm('admin_moderator', 'password123');
    cy.visit('/admin/users');

    cy.get(`[data-cy="btn-review-case-${data.report_id}"]`).should('be.visible').click();
    cy.get('#admin-case-modal').should('be.visible');

    // Tunggu konten termuat
    cy.get('#case-modal-content').should('not.contain', 'Memuat berkas perkara');

    // Isi catatan penolakan dan submit form dismiss
    cy.get('form[action*="/dismiss"] input[name="review_note"]')
      .clear()
      .type('Setelah ditinjau, bukti tidak mencukupi dan tuduhan tidak terbukti.');
    cy.get('[data-cy="btn-action-dismiss"]').click();

    // Verifikasi kembali ke halaman admin dengan alert sukses dismiss
    cy.contains('DISMISSED').should('be.visible');
  });

  it('TC-14-03 — Admin Menjatuhkan Sanksi dan Mengubah Status Reputasi Menjadi PROBLEMATIC', function () {
    // Siapkan laporan baru yang masih OPEN
    cy.task('execPhp', `
      use App\\Models\\AccountReport;
      use App\\Models\\User;

      $reporter = User::where('username', 'reporter_user')->first();
      $target = User::where('username', 'target_suspect')->first();

      $report = AccountReport::create([
        'reporter_id' => $reporter->user_id,
        'reported_user_id' => $target->user_id,
        'source' => AccountReport::SOURCE_USER,
        'reason' => 'Pengguna terbukti melakukan pelanggaran berat.',
        'status' => AccountReport::STATUS_OPEN,
      ]);

      echo json_encode(['report_id' => $report->report_id]);
    `).then((res) => {
      const reportId = JSON.parse(res).report_id;
      cy.loginByForm('admin_moderator', 'password123');
      cy.visit('/admin/users');

      cy.get(`[data-cy="btn-review-case-${reportId}"]`).should('be.visible').click();
      cy.get('#admin-case-modal').should('be.visible');

      // Tunggu konten termuat
      cy.get('#case-modal-content').should('not.contain', 'Memuat berkas perkara');

      // Isi form keputusan sanksi
      cy.get('form[action*="/resolve"] select[name="account_status"]').select('SUSPENDED');
      cy.get('form[action*="/resolve"] select[name="new_reputation_status"]').select('PROBLEMATIC');
      cy.get('form[action*="/resolve"] input[name="points_delta"]').clear().type('-25');
      cy.get('form[action*="/resolve"] input[name="review_note"]')
        .clear()
        .type('Pelanggaran berat terbukti. Akun disuspend dan status ditandai bermasalah.');
      cy.get('[data-cy="btn-action-resolve"]').click();

      // Verifikasi alert sukses keputusan moderasi
      cy.contains('berhasil ditetapkan').should('be.visible');
    });
  });
});
