describe('TS-02: Login, Logout, dan Google Auth', () => {
  const timestamp = Date.now();
  const testUser = {
    username: `login_user_${timestamp}`,
    email: `login_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  before(() => {
    // Daftarkan akun valid untuk pengujian login
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(testUser.username);
    cy.get('[data-cy="input-email"]').type(testUser.email);
    cy.get('[data-cy="input-password"]').type(testUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(testUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Verifikasi berhasil terdaftar dan logout untuk persiapan test login
    cy.url().should('eq', `${Cypress.config().baseUrl}/`);
    cy.get('[data-cy="btn-logout"]').click();
  });

  beforeEach(() => {
    cy.visit('/login');
  });

  describe('TC-02-01: Login lokal valid', () => {
    it('TC-02-01 [Dataset 1: Login dengan Email]: sesi dibuat dan dialihkan ke home', () => {
      cy.get('[data-cy="input-login"]').type(testUser.email);
      cy.get('[data-cy="input-password"]').type(testUser.password);
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('eq', `${Cypress.config().baseUrl}/`);
      cy.get('[data-cy="alert-success"]')
        .should('be.visible')
        .and('contain', 'Selamat datang kembali');

      cy.get('[data-cy="nav-user-profile"]')
        .should('contain', testUser.username);

      cy.get('[data-cy="btn-logout"]').click();
    });

    it('TC-02-01 [Dataset 2: Login dengan Username]: sesi dibuat dan dialihkan ke home', () => {
      cy.get('[data-cy="input-login"]').type(testUser.username);
      cy.get('[data-cy="input-password"]').type(testUser.password);
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('eq', `${Cypress.config().baseUrl}/`);
      cy.get('[data-cy="alert-success"]')
        .should('be.visible')
        .and('contain', 'Selamat datang kembali');

      cy.get('[data-cy="nav-user-profile"]')
        .should('contain', testUser.username);

      cy.get('[data-cy="btn-logout"]').click();
    });
  });

  describe('TC-02-02: Login tidak valid', () => {
    it('TC-02-02 [Dataset 1: Password Salah]: login ditolak dan sesi tidak dibuat', () => {
      cy.get('[data-cy="input-login"]').type(testUser.email);
      cy.get('[data-cy="input-password"]').type('WrongPassword999!');
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('include', '/login');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Email/username atau password salah.');
    });

    it('TC-02-02 [Dataset 2: Email Tidak Terdaftar]: login ditolak dan sesi tidak dibuat', () => {
      cy.get('[data-cy="input-login"]').type(`nonexistent_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('include', '/login');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Email/username atau password salah.');
    });

    it('TC-02-02 [Dataset 3: Username Tidak Terdaftar]: login ditolak dan sesi tidak dibuat', () => {
      cy.get('[data-cy="input-login"]').type(`nonexistent_user_${Date.now()}`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('include', '/login');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Email/username atau password salah.');
    });
  });

  describe('TC-02-09: Logout', () => {
    it('TC-02-09: Sesi berakhir setelah logout dan URL privat tidak dapat diakses', () => {
      // Login terlebih dahulu
      cy.get('[data-cy="input-login"]').type(testUser.username);
      cy.get('[data-cy="input-password"]').type(testUser.password);
      cy.get('[data-cy="btn-login"]').click();

      cy.url().should('eq', `${Cypress.config().baseUrl}/`);

      // Lakukan logout
      cy.get('[data-cy="btn-logout"]').click();

      // Coba akses URL privat yang memerlukan autentikasi
      cy.visit('/profile');
      cy.url().should('include', '/login');
    });
  });

  describe('TC-02-04 & TC-02-07: Google OAuth Link & Callback Error Handling', () => {
    it('TC-02-04: Tombol Masuk dengan Google mengarahkan ke rute auth Google', () => {
      cy.get('a[href*="/auth/google"]').should('exist');
    });

    it('TC-02-07: Callback OAuth dengan state tidak valid atau error dialihkan dengan penanganan aman', () => {
      cy.visit('/auth/google/callback?error=access_denied', { failOnStatusCode: false });
      // Sistem harus mengarahkan kembali ke login dan tidak membuat sesi palsu
      cy.url().should('include', '/login');
    });
  });
});
