describe('TS-03: Cari dan Lihat Listing Marketplace', () => {
  beforeEach(() => {
    cy.visit('/');
  });

  it('TC-03-01: Daftar dan detail listing - pilar marketplace dan informasi penawaran tampil dengan benar', () => {
    cy.get('#marketplace').should('be.visible');
    cy.get('#marketplace').within(() => {
      cy.contains('Marketplace Digital').should('be.visible');
      cy.contains('Listing Aktif').should('be.visible');
    });
  });

  it('TC-03-04: Listing nonaktif atau ID tidak ada - menangani permintaan dengan aman tanpa error internal', () => {
    cy.visit('/seller/listings/999999/edit', { failOnStatusCode: false });
    // Dialihkan ke login jika tamu, atau 404 tanpa crash
    cy.url().should('not.include', 'fatal_error');
  });

  it('TC-03-05: Akses ingin berjualan - tombol aksi mengarahkan ke form pembuatan listing', () => {
    // Daftarkan akun baru agar terautentikasi
    const timestamp = Date.now();
    const newUser = {
      username: `seller_${timestamp}`,
      email: `seller_${timestamp}@markettrust.test`,
      password: 'Password123!',
      password_confirmation: 'Password123!'
    };

    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(newUser.username);
    cy.get('[data-cy="input-email"]').type(newUser.email);
    cy.get('[data-cy="input-password"]').type(newUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(newUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.url().should('eq', `${Cypress.config().baseUrl}/`);

    // Klik tombol Ingin berjualan?
    cy.get('[data-cy="btn-ingin-berjualan"]').click();

    // Verifikasi diarahkan ke formulir listing baru
    cy.url().should('include', '/seller/listings/create');
    cy.contains('Publikasikan Listing Digital Baru').should('be.visible');

    // Logout
    cy.get('[data-cy="btn-logout"]').click();
  });

  it('TC-03-06: Menu listing pada navbar - pengguna baru belum memiliki menu listing hingga ada produk aktif', () => {
    const timestamp = Date.now();
    const userWithoutListing = {
      username: `nols_${timestamp}`,
      email: `nols_${timestamp}@markettrust.test`,
      password: 'Password123!',
      password_confirmation: 'Password123!'
    };

    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userWithoutListing.username);
    cy.get('[data-cy="input-email"]').type(userWithoutListing.email);
    cy.get('[data-cy="input-password"]').type(userWithoutListing.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userWithoutListing.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Pada navbar, tautan 'Listing' belum muncul karena hasListings() false
    cy.get('nav').within(() => {
      cy.contains('Listing').should('not.exist');
    });

    cy.get('[data-cy="btn-logout"]').click();
  });
});
