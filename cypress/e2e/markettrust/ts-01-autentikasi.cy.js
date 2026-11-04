describe('TS-01 — Autentikasi & Registrasi', () => {
  beforeEach(() => {
    // Bersihkan sesi & cookies sebelum setiap test
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('TC-01-01 — Registrasi Akun Berhasil dengan Data Valid', () => {
    const timestamp = Date.now();
    const username = `cy_user_${timestamp}`.substring(0, 20);
    const email = `cy_${timestamp}@markettrust.local`;

    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(username);
    cy.get('[data-cy="input-email"]').type(email);
    cy.get('[data-cy="input-password"]').type('ValidPassword123!');
    cy.get('[data-cy="input-password-confirmation"]').type('ValidPassword123!');
    cy.get('[data-cy="btn-register"]').click();

    // Verifikasi pengalihan dan status login aktif
    cy.url().should('not.include', '/register');
    cy.contains(/selamat datang|pendaftaran berhasil/i).should('be.visible');
  });

  it('TC-01-02 — Registrasi Gagal Karena Validasi Input Tidak Sesuai', () => {
    // 1. Password mismatch & terlalu pendek
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type('mismatch_user');
    cy.get('[data-cy="input-email"]').type('mismatch@markettrust.local');
    cy.get('[data-cy="input-password"]').type('short');
    cy.get('[data-cy="input-password-confirmation"]').type('different');
    cy.get('[data-cy="btn-register"]').click();

    cy.contains(/minimal 8 karakter|tidak cocok/i).should('be.visible');
  });

  it('TC-01-03 — Login Berhasil Menggunakan Username Maupun Email', () => {
    const testPassword = 'Password123!';
    const userEmail = 'cy_login_tester@markettrust.local';
    const username = 'cy_login_tester';

    // Seed test user via execPhp
    cy.task('execPhp', `
      \\App\\Models\\User::updateOrCreate(
        ['email' => '${userEmail}'],
        [
          'username' => '${username}',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${testPassword}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );
    `);

    // Subtest 1: Login via Email
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(userEmail);
    cy.get('[data-cy="input-password"]').type(testPassword);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Logout & clear session
    cy.clearCookies();
    cy.clearLocalStorage();
    cy.visit('/login');

    // Subtest 2: Login via Username
    cy.get('[data-cy="input-login"]').type(username);
    cy.get('[data-cy="input-password"]').type(testPassword);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');
  });

  it('TC-01-04 — Login Akun Admin Mengarahkan Langsung ke Dashboard Admin', () => {
    const adminEmail = 'cy_admin@markettrust.local';
    const adminPassword = 'AdminPassword123!';

    cy.task('execPhp', `
      \\App\\Models\\User::updateOrCreate(
        ['email' => '${adminEmail}'],
        [
          'username' => 'cy_admin_user',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${adminPassword}'),
          'account_type' => 'ADMIN',
          'account_status' => 'ACTIVE'
        ]
      );
    `);

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminEmail);
    cy.get('[data-cy="input-password"]').type(adminPassword);
    cy.get('[data-cy="btn-login"]').click();

    cy.url().should('include', '/admin');
  });

  it('TC-01-05 — Penolakan Login untuk Akun Berstatus SUSPENDED', () => {
    const suspendedEmail = 'cy_suspended@markettrust.local';
    const password = 'SuspendedPassword123!';

    cy.task('execPhp', `
      \\App\\Models\\User::updateOrCreate(
        ['email' => '${suspendedEmail}'],
        [
          'username' => 'cy_suspended_user',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'SUSPENDED'
        ]
      );
    `);

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(suspendedEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.contains(/ditangguhkan|SUSPENDED/i).should('be.visible');
    cy.url().should('include', '/login');
  });

  it('TC-01-06 — Logout Sesi Pengguna', () => {
    const userEmail = 'cy_logout_tester@markettrust.local';
    const password = 'Password123!';

    cy.task('execPhp', `
      \\App\\Models\\User::updateOrCreate(
        ['email' => '${userEmail}'],
        [
          'username' => 'cy_logout_user',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );
    `);

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(userEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Trigger logout via UI button
    cy.get('[data-cy="btn-logout"]').click();
    cy.visit('/profile');
    cy.url().should('include', '/login');
  });
});

