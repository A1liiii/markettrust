describe('TS-07: Kelola Posting Forum', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const author = {
    username: `author_${timestamp}`,
    email: `author_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const otherUser = {
    username: `other_${timestamp}`,
    email: `other_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let createdPostUrl = null;

  before(() => {
    // Daftarkan akun author
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(author.username);
    cy.get('[data-cy="input-email"]').type(author.email);
    cy.get('[data-cy="input-password"]').type(author.password);
    cy.get('[data-cy="input-password-confirmation"]').type(author.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
    cy.url().should('eq', Cypress.config().baseUrl + '/');
  });

  it('TC-07-01: Posting semua kategori - Pengguna dapat mempublikasikan postingan forum umum', () => {
    cy.visit('/forum/create');
    cy.url().should('include', '/forum/create');

    const postTitle = `Diskusi Escrow MarketTrust ${timestamp}`;
    const postContent = `Ini adalah topik diskusi mengenai cara kerja transaksi escrow digital di MarketTrust agar aman dan terjamin.`;

    cy.get('#title').type(postTitle);
    cy.get('#content').type(postContent);
    cy.get('#submit-btn').click();

    // Verifikasi diarahkan ke detail postingan dengan flash alert success
    cy.url().should('match', /\/forum\/\d+/);
    cy.url().then((url) => {
      createdPostUrl = url;
    });

    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(postTitle).should('be.visible');
    cy.contains(postContent).should('be.visible');
    cy.contains('Diskusi Umum').should('be.visible');
    cy.contains('Aktif').should('be.visible');
  });

  it('TC-07-02: Ubah dan nonaktifkan - Penulis dapat mengedit postingan lalu menutupnya', () => {
    if (createdPostUrl) {
      cy.visit(createdPostUrl);
    }

    const updatedTitle = `Diskusi Escrow MarketTrust UPDATED ${timestamp}`;
    cy.contains('Edit').click();
    cy.url().should('include', '/edit');

    cy.get('#title').clear().type(updatedTitle);
    cy.get('form[action*="/forum/"] button[type="submit"]').contains('Simpan Perubahan').click();

    // Verifikasi pembaruan tersimpan
    cy.url().should('match', /\/forum\/\d+/);
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(updatedTitle).should('be.visible');

    // Tutup postingan via modal
    cy.contains('Tutup Postingan').click();
    cy.get('#close-post-modal').should('be.visible');
    cy.get('#close-post-modal button[type="submit"]').click();

    // Verifikasi status berubah menjadi Ditutup
    cy.get('[data-cy="alert-info"]').should('be.visible');
    cy.contains('Ditutup').should('be.visible');
  });

  it('TC-07-05: Kolom dan relasi salah - Form ditolak jika judul atau isi kosong', () => {
    cy.visit('/forum/create');
    cy.url().should('include', '/forum/create');

    // Coba submit tanpa mengisi judul atau konten
    cy.get('#submit-btn').click();

    // Form browser HTML5 validation menahan submit atau URL tetap di forum/create
    cy.url().should('include', '/forum/create');
  });
});
