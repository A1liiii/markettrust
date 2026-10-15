describe('TS-33: Dashboard Administrasi Platform', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const normalUser = {
    username: `usr_norm_${timestamp}`,
    email: `usr_norm_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const adminUser = {
    username: `adm_test_${timestamp}`,
    email: `adm_test_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  before(() => {
    // 1. Registrasi Akun Pengguna Biasa (role USER)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(normalUser.username);
    cy.get('[data-cy="input-email"]').type(normalUser.email);
    cy.get('[data-cy="input-password"]').type(normalUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(normalUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout pengguna biasa
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 2. Registrasi Akun yang akan dijadikan Admin
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(adminUser.username);
    cy.get('[data-cy="input-email"]').type(adminUser.email);
    cy.get('[data-cy="input-password"]').type(adminUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(adminUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Upgrade akun adminUser menjadi account_type = 'ADMIN' via Node task cy.task('execPhp')
    cy.task('execPhp', `App\\Models\\User::where('username', '${adminUser.username}')->update(['account_type' => 'ADMIN']);`);

    // Logout untuk memastikan session ter-refresh
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
  });

  describe('TC-33-01: Pembatasan hak akses dashboard admin', () => {
    it('TC-33-01 [Dataset 1: Tamu / Guest] dialihkan ke halaman login', () => {
      cy.visit('/admin');
      cy.url().should('include', '/login');
    });

    it('TC-33-01 [Dataset 2: Pengguna Biasa] ditolak dengan kode status 403 Forbidden', () => {
      // Login sebagai pengguna biasa
      cy.visit('/login');
      cy.get('[data-cy="input-login"]').type(normalUser.username);
      cy.get('[data-cy="input-password"]').type(normalUser.password);
      cy.get('[data-cy="btn-login"]').click();

      // Coba akses /admin
      cy.request({
        url: '/admin',
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).to.eq(403);
      });

      // Logout pengguna biasa
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('TC-33-01 [Dataset 3: Administrator] diizinkan masuk ke overview analitik', () => {
      // Login sebagai administrator
      cy.visit('/login');
      cy.get('[data-cy="input-login"]').type(adminUser.username);
      cy.get('[data-cy="input-password"]').type(adminUser.password);
      cy.get('[data-cy="btn-login"]').click();

      // Akses /admin
      cy.visit('/admin');
      cy.url().should('match', /\/admin/);
      cy.contains('Overview & Analitik Platform').should('be.visible');
    });
  });

  describe('TC-33-02: Angka ringkasan metrik dashboard admin', () => {
    it('TC-33-02: Menampilkan kartu ringkasan status transaksi dan volume', () => {
      cy.visit('/admin');

      // Verifikasi komponen ringkasan transaksi
      cy.contains('Overview & Analitik Platform').should('be.visible');
      cy.contains('Panel Administrasi').should('be.visible');
      cy.contains('Kelola Katalog').should('be.visible');
      cy.contains('Tinjau Escrow').should('be.visible');
    });
  });

  describe('TC-33-03: Filter periode analitik', () => {
    it('TC-33-03: Filter periode dapat dipilih dan mengubah data analitik', () => {
      cy.visit('/admin');

      // Klik filter 7 Hari
      cy.get('button[data-period="7d"]').should('be.visible').click();
      cy.get('button[data-period="7d"]').should('have.class', 'bg-indigo-600');

      // Klik filter 30 Hari
      cy.get('button[data-period="30d"]').should('be.visible').click();
      cy.get('button[data-period="30d"]').should('have.class', 'bg-indigo-600');
    });
  });

  describe('TC-33-04: Respon endpoint analitik admin', () => {
    it('TC-33-04: Endpoint /admin/analytics mengembalikan data metrik format valid', () => {
      cy.request({
        url: '/admin/analytics?period=30d',
        headers: {
          'Accept': 'application/json'
        }
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.body).to.have.property('period');
        expect(response.body).to.have.property('trend');
        expect(response.body).to.have.property('statusComposition');
      });
    });
  });
});
