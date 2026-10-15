describe('TS-01: Registrasi Akun', () => {
  const timestamp = Date.now();
  // Fixture user untuk pengujian duplikasi
  const existingUser = {
    username: `dup_user_${timestamp}`,
    email: `dup_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  before(() => {
    // Daftarkan akun acuan yang akan digunakan untuk pengujian identitas duplikat (TC-01-02)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(existingUser.username);
    cy.get('[data-cy="input-email"]').type(existingUser.email);
    cy.get('[data-cy="input-password"]').type(existingUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(existingUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Verifikasi akun acuan berhasil didaftarkan lalu logout
    cy.url().should('not.include', '/register');
    cy.get('[data-cy="btn-logout"]').click();
  });

  beforeEach(() => {
    cy.visit('/register');
  });

  it('TC-01-01: Registrasi valid - satu akun USER ACTIVE dibuat dan diarahkan ke halaman utama', () => {
    const validUser = {
      username: `valid_user_${Date.now()}`,
      email: `valid_${Date.now()}@markettrust.test`,
      password: 'SecurePassword123!',
      password_confirmation: 'SecurePassword123!'
    };

    cy.get('[data-cy="input-username"]').type(validUser.username);
    cy.get('[data-cy="input-email"]').type(validUser.email);
    cy.get('[data-cy="input-password"]').type(validUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(validUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Verifikasi pengguna dialihkan ke halaman utama dengan pesan sukses
    cy.url().should('eq', `${Cypress.config().baseUrl}/`);
    cy.get('[data-cy="alert-success"]')
      .should('be.visible')
      .and('contain', 'Pendaftaran berhasil!')
      .and('contain', validUser.username);

    // Verifikasi status akun pada navbar adalah ACTIVE
    cy.get('[data-cy="nav-user-profile"]')
      .should('be.visible')
      .and('contain', validUser.username)
      .and('contain', 'ACTIVE');

    // Bersihkan sesi dengan logout
    cy.get('[data-cy="btn-logout"]').click();
  });

  describe('TC-01-02: Identitas duplikat', () => {
    it('TC-01-02 [Dataset 1: Email Terdaftar]: Email sudah terdaftar, username unik -> pendaftaran ditolak dengan pesan jelas', () => {
      const duplicateEmailUser = {
        username: `unique_user_${Date.now()}`,
        email: existingUser.email, // Email duplikat dari akun acuan
        password: 'Password123!',
        password_confirmation: 'Password123!'
      };

      cy.get('[data-cy="input-username"]').type(duplicateEmailUser.username);
      cy.get('[data-cy="input-email"]').type(duplicateEmailUser.email);
      cy.get('[data-cy="input-password"]').type(duplicateEmailUser.password);
      cy.get('[data-cy="input-password-confirmation"]').type(duplicateEmailUser.password_confirmation);
      cy.get('[data-cy="btn-register"]').click();

      // Verifikasi tetap di halaman register dan menampilkan error email duplikat
      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Alamat email sudah terdaftar.');
    });

    it('TC-01-02 [Dataset 2: Username Terdaftar]: Username sudah terdaftar, email unik -> pendaftaran ditolak dengan pesan jelas', () => {
      const duplicateUsernameUser = {
        username: existingUser.username, // Username duplikat dari akun acuan
        email: `unique_${Date.now()}@markettrust.test`,
        password: 'Password123!',
        password_confirmation: 'Password123!'
      };

      cy.get('[data-cy="input-username"]').type(duplicateUsernameUser.username);
      cy.get('[data-cy="input-email"]').type(duplicateUsernameUser.email);
      cy.get('[data-cy="input-password"]').type(duplicateUsernameUser.password);
      cy.get('[data-cy="input-password-confirmation"]').type(duplicateUsernameUser.password_confirmation);
      cy.get('[data-cy="btn-register"]').click();

      // Verifikasi tetap di halaman register dan menampilkan error username duplikat
      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Username sudah digunakan oleh akun lain.');
    });
  });

  describe('TC-01-03: Kelengkapan dan format', () => {
    it('TC-01-03 [Dataset 1: Konfirmasi Password Mismatch]: konfirmasi berbeda ditolak', () => {
      cy.get('[data-cy="input-username"]').type(`user_${Date.now()}`);
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('DifferentPass999!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Konfirmasi password tidak cocok.');
    });

    it('TC-01-03 [Dataset 2: Password Kurang dari 8 Karakter]: ditolak dengan pesan min 8 karakter', () => {
      cy.get('[data-cy="input-username"]').type(`user_${Date.now()}`);
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('short');
      cy.get('[data-cy="input-password-confirmation"]').type('short');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Password minimal harus 8 karakter.');
    });
  });

  describe('TC-01-04: Batas username', () => {
    it('TC-01-04 [Dataset 1: Username 2 Karakter]: ditolak (minimal 3 karakter)', () => {
      cy.get('[data-cy="input-username"]').type('ab');
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('Password123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Username minimal harus 3 karakter.');
    });

    it('TC-01-04 [Dataset 2: Username 3 Karakter]: diterima', () => {
      const shortValid = `u${Date.now().toString().slice(-2)}`; // 3 karakter
      cy.get('[data-cy="input-username"]').type(shortValid);
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('Password123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('eq', `${Cypress.config().baseUrl}/`);
      cy.get('[data-cy="alert-success"]').should('be.visible');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('TC-01-04 [Dataset 3: Username 31 Karakter]: ditolak (maksimal 30 karakter)', () => {
      const longUsername = 'a'.repeat(31);
      cy.get('[data-cy="input-username"]').type(longUsername);
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('Password123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Username maksimal 30 karakter.');
    });
  });

  describe('TC-01-05: Pola dan normalisasi username', () => {
    it('TC-01-05 [Dataset 1: Simbol Spasi dan Karakter Terlarang]: ditolak', () => {
      cy.get('[data-cy="input-username"]').type('user!@#');
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('Password123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('include', '/register');
      cy.get('[data-cy="alert-error"]').should('be.visible');
      cy.get('[data-cy="error-message"]').should('contain', 'Username hanya boleh terdiri dari huruf kecil, angka, dan underscore (_).');
    });

    it('TC-01-05 [Dataset 2: Normalisasi Huruf Besar ke Kecil]: berhasil dinormalisasi dan disimpan', () => {
      const mixedUsername = `MixUser_${Date.now().toString().slice(-4)}`;
      cy.get('[data-cy="input-username"]').type(mixedUsername);
      cy.get('[data-cy="input-email"]').type(`user_${Date.now()}@markettrust.test`);
      cy.get('[data-cy="input-password"]').type('Password123!');
      cy.get('[data-cy="input-password-confirmation"]').type('Password123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('eq', `${Cypress.config().baseUrl}/`);
      // Username di navbar harus huruf kecil
      cy.get('[data-cy="nav-user-profile"]')
        .should('contain', mixedUsername.toLowerCase());
      cy.get('[data-cy="btn-logout"]').click();
    });
  });
});
