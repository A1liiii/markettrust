describe('TS-22: Notifikasi & Deduplikasi Event Pengguna', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const userA = {
    username: `notif_a_${timestamp}`,
    email: `notif_a_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const userB = {
    username: `notif_b_${timestamp}`,
    email: `notif_b_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const userC = {
    username: `notif_c_${timestamp}`,
    email: `notif_c_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let notifAId;

  before(() => {
    // 1. Registrasi User A
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userA.username);
    cy.get('[data-cy="input-email"]').type(userA.email);
    cy.get('[data-cy="input-password"]').type(userA.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userA.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Buat notifikasi untuk User A via execPhp
    cy.task('execPhp', `
      $u = \\App\\Models\\User::where('username', '${userA.username}')->first();
      $n = \\App\\Models\\Notification::create([
        'user_id' => $u->user_id,
        'event_type' => 'TRANSACTION_CREATED',
        'message' => 'Pesanan baru #TRX-9988 untuk lisensi telah dibuat.',
        'read_at' => null,
        'created_at' => now(),
      ]);
      echo $n->notification_id;
    `).then((output) => {
      notifAId = output.trim();
    });

    // Logout User A
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 2. Registrasi User B
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userB.username);
    cy.get('[data-cy="input-email"]').type(userB.email);
    cy.get('[data-cy="input-password"]').type(userB.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userB.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout User B
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 3. Registrasi User C (akun kosong tanpa notifikasi)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userC.username);
    cy.get('[data-cy="input-email"]').type(userC.email);
    cy.get('[data-cy="input-password"]').type(userC.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userC.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout User C
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-22-01: Penerima dan event - notifikasi tampil dengan benar pada penerima yang berhak', () => {
    // Login sebagai User A
    cy.visit('/login');
    cy.get('#login').type(userA.username);
    cy.get('#password').type(userA.password);
    cy.get('button[type="submit"]').contains('Masuk').click();

    // Verifikasi ikon bell di navbar memiliki indikator unread
    cy.get('[data-cy="nav-notifications"]').should('be.visible');
    cy.get('[data-cy="nav-unread-badge"]').should('be.visible').and('contain', '1');

    // Buka pusat notifikasi
    cy.get('[data-cy="nav-notifications"]').click();
    cy.url().should('include', '/notifications');
    cy.get('[data-cy="notifications-title"]').should('be.visible');

    // Verifikasi konten notifikasi milik User A
    cy.contains('Pesanan baru #TRX-9988 untuk lisensi telah dibuat.').should('be.visible');
    cy.get('[data-cy="badge-unread"]').should('be.visible');

    // Logout User A
    cy.get('[data-cy="btn-logout"]').click();

    // Login sebagai User B dan pastikan tidak melihat notifikasi milik User A
    cy.visit('/login');
    cy.get('#login').type(userB.username);
    cy.get('#password').type(userB.password);
    cy.get('button[type="submit"]').contains('Masuk').click();

    cy.visit('/notifications');
    cy.contains('Pesanan baru #TRX-9988 untuk lisensi telah dibuat.').should('not.exist');

    // Logout User B
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-22-02: Status baca - read_at dicatat saat dipilih dan status berubah menjadi dibaca', () => {
    // Login kembali sebagai User A
    cy.visit('/login');
    cy.get('#login').type(userA.username);
    cy.get('#password').type(userA.password);
    cy.get('button[type="submit"]').contains('Masuk').click();

    cy.visit('/notifications');
    cy.get(`[data-cy="btn-read-${notifAId}"]`).click();

    // Verifikasi read_at tercatat dan badge unread hilang
    cy.visit('/notifications');
    cy.get('[data-cy="badge-unread"]').should('not.exist');
    cy.contains('Dibaca pada').should('be.visible');

    // Logout User A
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-22-03: Tanpa notifikasi - akun tanpa notifikasi menampilkan keadaan kosong yang informatif', () => {
    // Login sebagai User C (tanpa notifikasi)
    cy.visit('/login');
    cy.get('#login').type(userC.username);
    cy.get('#password').type(userC.password);
    cy.get('button[type="submit"]').contains('Masuk').click();

    cy.visit('/notifications');
    cy.get('[data-cy="empty-notifications"]').should('be.visible');
    cy.contains('Belum Ada Notifikasi').should('be.visible');
    cy.contains('0 pemberitahuan').should('be.visible');

    // Logout User C
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-22-04: Akses pihak lain - ditolak (403) saat mengakses ID notifikasi milik akun lain', () => {
    // Login sebagai User B
    cy.visit('/login');
    cy.get('#login').type(userB.username);
    cy.get('#password').type(userB.password);
    cy.get('button[type="submit"]').contains('Masuk').click();

    // Coba buka notifikasi milik User A secara langsung
    cy.request({
      url: `/notifications/${notifAId}/read`,
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(403);
    });

    // Logout User B
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-22-05: Event ulang - deduplikasi mencegah notifikasi bisnis ganda akibat retry', () => {
    // Eksekusi pemanggilan event ganda via NotificationService
    cy.task('execPhp', `
      $u = \\App\\Models\\User::where('username', '${userA.username}')->first();
      $svc = app(\\App\\Contracts\\Services\\NotificationServiceInterface::class);

      // Panggilan 1
      $svc->notify($u->user_id, 1001, \\App\\Enums\\NotificationEventType::SELLER_DELIVERY_WARNING, 'Peringatan batas waktu penyerahan');

      // Panggilan 2 (retry)
      $svc->notify($u->user_id, 1001, \\App\\Enums\\NotificationEventType::SELLER_DELIVERY_WARNING, 'Peringatan batas waktu penyerahan');

      $count = \\App\\Models\\Notification::where('user_id', $u->user_id)
        ->where('transaction_id', 1001)
        ->where('event_type', 'SELLER_DELIVERY_WARNING')
        ->count();

      echo "COUNT:" . $count;
    `).then((output) => {
      expect(output).to.include('COUNT:1');
    });
  });
});
