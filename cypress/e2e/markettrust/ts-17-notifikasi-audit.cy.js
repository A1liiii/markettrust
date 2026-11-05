describe('TS-17 — Notifikasi & Jejak Audit', () => {
  beforeEach(() => {
    cy.task('execPhp', `
      use App\\Models\\User;
      use App\\Models\\Notification;
      use App\\Models\\AuditLog;
      use App\\Enums\\NotificationEventType;
      use Illuminate\\Support\\Facades\\Hash;

      $user = User::firstOrCreate(
        ['email' => 'user_notif@markettrust.test'],
        [
          'username' => 'user_notif',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $admin = User::firstOrCreate(
        ['email' => 'admin_audit@markettrust.test'],
        [
          'username' => 'admin_audit',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'ADMIN',
          'account_status' => 'ACTIVE',
        ]
      );

      // Bersihkan notifikasi user agar kondisi uji terisolasi
      Notification::where('user_id', $user->user_id)->delete();

      $n1 = Notification::create([
        'user_id' => $user->user_id,
        'event_type' => NotificationEventType::TRANSACTION_CREATED->value,
        'message' => 'Pesanan baru #MT-101 telah dibentuk',
        'read_at' => null,
        'created_at' => now()->subMinutes(10),
      ]);

      $n2 = Notification::create([
        'user_id' => $user->user_id,
        'event_type' => NotificationEventType::AGREEMENT_PROPOSED->value,
        'message' => 'Usulan kesepakatan baru dari penjual',
        'read_at' => null,
        'created_at' => now()->subMinutes(5),
      ]);

      $n3 = Notification::create([
        'user_id' => $user->user_id,
        'event_type' => NotificationEventType::PAYMENT_SUCCESS->value,
        'message' => 'Pembayaran pesanan #MT-100 sukses',
        'read_at' => now()->subHours(2),
        'created_at' => now()->subHours(3),
      ]);

      // Pastikan ada catatan audit log administratif
      AuditLog::firstOrCreate(
        ['action' => 'ACCOUNT_REPORT_RESOLVED', 'entity_type' => 'account_reports'],
        [
          'actor_id' => $admin->user_id,
          'entity_id' => 12,
          'old_value' => ['status' => 'OPEN'],
          'new_value' => ['status' => 'RESOLVED'],
          'created_at' => now()->subHour(),
        ]
      );

      AuditLog::firstOrCreate(
        ['action' => 'PAYOUT_TRANSFERRED', 'entity_type' => 'settlements'],
        [
          'actor_id' => $admin->user_id,
          'entity_id' => 88,
          'old_value' => ['status' => 'PENDING'],
          'new_value' => ['status' => 'TRANSFERRED'],
          'created_at' => now()->subMinutes(30),
        ]
      );

      echo json_encode([
        'user_id' => $user->user_id,
        'username' => $user->username,
        'admin_username' => $admin->username,
      ]);
    `).as('seedData');
  });

  it('TC-15-01 — Lonceng Notifikasi Navbar dan Halaman Notifikasi Pengguna', function () {
    cy.loginByForm('user_notif', 'password123');
    cy.visit('/');

    // 1. Verifikasi badge belum dibaca pada lonceng navbar
    cy.get('[data-cy="nav-notifications"]').should('be.visible');
    cy.get('[data-cy="nav-unread-badge"]').should('be.visible').and('contain.text', '2');

    // 2. Klik lonceng menuju Pusat Notifikasi
    cy.get('[data-cy="nav-notifications"]').click();
    cy.url().should('include', '/notifications');
    cy.get('[data-cy="notifications-title"]').should('be.visible').and('contain.text', 'Pusat Notifikasi');

    // 3. Verifikasi jumlah counter pada tab filter
    cy.get('[data-cy="filter-all"]').should('be.visible').and('contain.text', '3');
    cy.get('[data-cy="filter-unread"]').should('be.visible').and('contain.text', '2');
    cy.get('[data-cy="filter-read"]').should('be.visible').and('contain.text', '1');

    // 4. Filter tab belum dibaca
    cy.get('[data-cy="filter-unread"]').click();
    cy.url().should('include', 'filter=unread');
    cy.get('[data-cy="notification-list"]').should('be.visible');
    cy.get('[data-cy="badge-unread"]').should('have.length', 2);

    // 5. Filter tab sudah dibaca
    cy.get('[data-cy="filter-read"]').click();
    cy.url().should('include', 'filter=read');
    cy.get('[data-cy="notification-list"]').should('be.visible');
    cy.get('[data-cy="badge-unread"]').should('not.exist');
  });

  it('TC-15-02 — Fitur Tandai Semua Notifikasi Sudah Dibaca', function () {
    cy.loginByForm('user_notif', 'password123');
    cy.visit('/notifications');

    // Tombol tandai semua dibaca harus muncul saat ada unread
    cy.get('[data-cy="btn-mark-all-read"]').should('be.visible').click();

    // Verifikasi setelah submit
    cy.url().should('include', '/notifications');
    // Badge pada navbar hilang karena unread = 0
    cy.get('[data-cy="nav-unread-badge"]').should('not.exist');

    // Tab unread kini bernilai 0
    cy.get('[data-cy="filter-unread"]').should('contain.text', '0');
  });

  it('TC-15-03 — Audit Trail Admin untuk Tindakan Kritis Sistem', function () {
    // 1. Verifikasi proteksi: pengguna biasa dilarang membuka audit log
    cy.loginByForm('user_notif', 'password123');
    cy.request({
      url: '/admin/audit-logs',
      failOnStatusCode: false,
    }).then((res) => {
      expect([302, 403]).to.include(res.status);
    });

    // 2. Akses oleh Administrator (bersihkan sesi user sebelumnya)
    cy.clearCookies();
    cy.clearLocalStorage();
    cy.loginByForm('admin_audit', 'password123');
    cy.visit('/admin/audit-logs');

    cy.get('[data-cy="admin-audit-logs-page"]').should('be.visible');
    cy.get('[data-cy="audit-logs-title"]').should('contain.text', 'Audit Log');
    cy.get('[data-cy="stat-total-logs"]').should('be.visible');
    cy.get('[data-cy="audit-table-body"]').should('be.visible');

    // 3. Filter berdasarkan tipe aksi administratif
    cy.get('[data-cy="audit-action-filter"]').select('ACCOUNT_REPORT_RESOLVED');
    cy.get('[data-cy="btn-submit-filter"]').click();

    cy.url().should('include', 'action=ACCOUNT_REPORT_RESOLVED');
    cy.get('[data-cy="audit-table-body"]').should('contain.text', 'ACCOUNT_REPORT_RESOLVED');
  });
});

