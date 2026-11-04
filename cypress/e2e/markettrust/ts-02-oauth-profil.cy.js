describe('TS-02 — Google OAuth & Profil', () => {
  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('TC-02-01 (Turunan) — Tautan Login Google Tersedia di Form Login', () => {
    cy.visit('/login');
    cy.contains(/masuk dengan google/i).should('be.visible');
    cy.get('a[href*="auth/google"]').should('exist');
  });

  it('TC-02-02 (Turunan) — Inisiasi Pengalihan Otentikasi Google OAuth', () => {
    // Validasi endpoint /auth/google menginisiasi redirect 302 ke penyedia OAuth Google
    cy.request({
      url: '/auth/google',
      followRedirect: false
    }).then((response) => {
      expect(response.status).to.eq(302);
      expect(response.headers.location).to.include('accounts.google.com');
    });
  });

  it('TC-02-03 (Turunan) — Penolakan Akses Callback Google OAuth Tanpa Sesi Sah', () => {
    // Akses langsung callback tanpa state parameter valid dialihkan kembali ke login
    cy.visit('/auth/google/callback', { failOnStatusCode: false });
    cy.url().should('include', '/login');
  });

  it('TC-02-04 (Turunan) — Halaman Tautkan Akun Google dengan Akun Lokal', () => {
    // Kunjungi langsung halaman link Google (jika tanpa session pending akan redirect ke login)
    cy.visit('/auth/google/link', { failOnStatusCode: false });
    cy.url().should('include', '/login');
  });

  it('TC-02-05 (Turunan) — Halaman Pemilihan Username Registrasi Google Baru', () => {
    // Validasi proteksi akses form username Google tanpa sesi pending
    cy.visit('/auth/google/username', { failOnStatusCode: false });
    cy.url().should('include', '/login');
  });

  it('TC-02-06 (Turunan) — Pembaruan Profil Pengguna Melalui UI', () => {
    const userEmail = 'cy_profile_editor@markettrust.local';
    const password = 'Password123!';

    cy.task('execPhp', `
      \\App\\Models\\User::updateOrCreate(
        ['email' => '${userEmail}'],
        [
          'username' => 'cy_profile_editor',
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

    cy.visit('/profile/edit');
    cy.get('input[name="username"]').should('be.visible');
  });
});
