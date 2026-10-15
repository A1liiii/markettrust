describe('TS-06: Interaksi Forum Komunitas', () => {
  beforeEach(() => {
    cy.visit('/forum');
  });

  it('TC-06-01: Kategori dan detail - Kategori Umum, WTB, WTS dapat dijelajahi dengan benar', () => {
    cy.contains('Umum').should('be.visible').click();
    cy.url().should('include', 'category=GENERAL');

    cy.contains('WTB (Beli)').should('be.visible').click();
    cy.url().should('include', 'category=WTB');

    cy.contains('WTS (Jual)').should('be.visible').click();
    cy.url().should('include', 'category=WTS');
  });

  it('TC-06-02: Forum kosong / pencarian tanpa hasil - Keadaan kosong jelas ditampilkan tanpa error', () => {
    // Cari keyword yang pasti tidak ada
    cy.get('input[name="q"]').type('nonexistent_query_xyz_99999{enter}');
    // Sistem harus menampilkan pesan kosong tanpa error
    cy.contains('Tidak Ditemukan Postingan').should('be.visible');
  });

  it('TC-06-07: Interaksi tanpa sesi - Tamu yang belum login diarahkan ke halaman login jika mencoba membuat posting', () => {
    cy.contains('Masuk untuk Posting').click();
    cy.url().should('include', '/login');
  });

  it('TC-06-03 & TC-06-05: Pengguna login dapat melihat posting aktif dan tombol like/komentar tersedia', () => {
    // Daftarkan akun aktif baru
    const timestamp = Date.now();
    const forumUser = {
      username: `fuser_${timestamp}`,
      email: `fuser_${timestamp}@markettrust.test`,
      password: 'Password123!',
      password_confirmation: 'Password123!'
    };

    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(forumUser.username);
    cy.get('[data-cy="input-email"]').type(forumUser.email);
    cy.get('[data-cy="input-password"]').type(forumUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(forumUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.visit('/forum');
    cy.contains('Buat Postingan Baru').should('be.visible');

    // Jika ada postingan di feed, buka salah satu postingan
    cy.get('body').then(($body) => {
      const cards = $body.find('[id^="post-card-"]');
      if (cards.length > 0) {
        cy.wrap(cards.first()).click();
        cy.url().should('match', /\/forum\/\d+/);

        // Verifikasi keberadaan tombol like dan area komentar
        cy.get('#post-like-btn').should('exist');
        cy.get('#comment-content').should('exist');
      }
    });

    cy.get('[data-cy="btn-logout"]').click();
  });
});


