describe('TS-08: Direct Message (DM) & Negosiasi', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const userA = {
    username: `dma_${timestamp}`,
    email: `dma_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const userB = {
    username: `dmb_${timestamp}`,
    email: `dmb_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const outsiderUser = {
    username: `dmout_${timestamp}`,
    email: `dmout_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let targetPostUrl = null;
  let conversationUrl = null;
  let conversationId = null;

  before(() => {
    // 1. Daftarkan User A dan buat sebuah posting forum agar dapat diajak negosiasi oleh User B
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userA.username);
    cy.get('[data-cy="input-email"]').type(userA.email);
    cy.get('[data-cy="input-password"]').type(userA.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userA.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.visit('/forum/create');
    cy.get('#title').type(`Item Negosiasi User A ${timestamp}`);
    cy.get('#content').type(`Postingan forum untuk menguji fitur DM dan negosiasi Cypress.`);
    cy.get('#submit-btn').click();
    cy.url().should('match', /\/forum\/\d+/);

    cy.url().then((postUrl) => {
      targetPostUrl = postUrl;

      // 2. Logout User A
      cy.visit('/forum');
      cy.get('[data-cy="btn-logout"]').click();

      // 3. Daftarkan User B
      cy.visit('/register');
      cy.get('[data-cy="input-username"]').type(userB.username);
      cy.get('[data-cy="input-email"]').type(userB.email);
      cy.get('[data-cy="input-password"]').type(userB.password);
      cy.get('[data-cy="input-password-confirmation"]').type(userB.password_confirmation);
      cy.get('[data-cy="btn-register"]').click();
    });
  });

  it('TC-08-01: Inisiasi dari forum - Pembeli dapat memulai percakapan negosiasi dengan pemilik posting', () => {
    cy.visit(targetPostUrl);

    // Klik tombol negosiasi / kirim pesan dari form negosiasi forum
    cy.get('#forum-negotiate-form button[type="submit"]').click();

    // Verifikasi percakapan terinisiasi di daftar kotak masuk (/conversations)
    cy.visit('/conversations');
    cy.get('.dm-bubble-pop-card').first().should('be.visible').invoke('attr', 'href').then((href) => {
      conversationUrl = href;
      const matches = href.match(/\/conversations\/(\d+)/);
      if (matches) {
        conversationId = matches[1];
      }
      expect(conversationId).to.not.be.null;
    });
  });

  it('TC-08-02: Kirim dan terima pesan - Pengguna dapat mengirim pesan teks di thread percakapan', () => {
    expect(conversationUrl).to.not.be.null;
    cy.visit(conversationUrl);

    const testMessage = `Halo User A, apakah postingan ini masih bisa dinegosiasi? ${timestamp}`;
    cy.get('#message-content').type(testMessage);
    cy.get('#submit-message-btn').click();

    // Verifikasi pesan terkirim dan muncul di riwayat thread
    cy.contains(testMessage).should('be.visible');
  });

  it('TC-08-03: Pesan kosong - Form menolak pengiriman pesan jika teks kosong', () => {
    expect(conversationUrl).to.not.be.null;
    cy.visit(conversationUrl);

    // Submit tanpa isi teks
    cy.get('#submit-message-btn').click();

    // Textarea tetap kosong dan tidak ada bubble pesan kosong
    cy.get('#message-content').should('be.empty');
  });

  it('TC-08-06: Daftar chat mengambang - Widget obrolan mengambang dapat dibuka dan ditutup via tombol X', () => {
    cy.visit('/forum');

    // Buka floating launcher
    cy.get('#global-dm-launcher-btn').should('be.visible').click();
    cy.get('#global-dm-drawer').should('not.have.class', 'is-closed');

    // Tutup floating launcher via tombol close X
    cy.get('.global-dm-close-btn').first().click();
    cy.get('#global-dm-drawer').should('have.class', 'is-closed');
  });

  it('TC-08-04: Pembatasan akses percakapan - Pengguna pihak ketiga ditolak membuka thread percakapan', () => {
    // Logout User B
    cy.visit('/forum');
    cy.get('[data-cy="btn-logout"]').click();

    // Daftarkan User Outsider
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(outsiderUser.username);
    cy.get('[data-cy="input-email"]').type(outsiderUser.email);
    cy.get('[data-cy="input-password"]').type(outsiderUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(outsiderUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Akses percakapan orang lain
    expect(conversationId).to.not.be.null;
    cy.request({
      url: `/conversations/${conversationId}`,
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(403);
    });
  });
});
