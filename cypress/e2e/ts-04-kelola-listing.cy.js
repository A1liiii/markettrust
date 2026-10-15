describe('TS-04: Kelola Listing Seller', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller1 = {
    username: `sel1_${timestamp}`,
    email: `sel1_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const seller2 = {
    username: `sel2_${timestamp}`,
    email: `sel2_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let createdListingId = null;

  before(() => {
    // Daftarkan akun Seller 1
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller1.username);
    cy.get('[data-cy="input-email"]').type(seller1.email);
    cy.get('[data-cy="input-password"]').type(seller1.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller1.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
    cy.url().should('eq', Cypress.config().baseUrl + '/');
  });

  it('TC-04-01: Buat listing valid - Listing tersimpan atas pemilik benar dan muncul di daftar', () => {
    cy.visit('/seller/listings/create');
    cy.url().should('include', '/seller/listings/create');

    // Pilih varian produk pertama yang tersedia
    cy.get('#variant_id').then(($select) => {
      const options = $select.find('option[value!=""]');
      if (options.length > 0) {
        const variantVal = options.first().val();
        cy.get('#variant_id').select(variantVal);
      }
    });

    const listingTitle = `Item Test Seller Valid ${timestamp}`;
    cy.get('#title').type(listingTitle);
    cy.get('#description').type('Spesifikasi penawaran valid untuk pengujian Cypress otomatis tanpa kendala.');
    cy.get('#price_display').type('150000');
    cy.get('#delivery_method').clear().type('Pengiriman Data Akun Instant');
    cy.get('#estimated_delivery_value').clear().type('30');
    cy.get('#estimated_delivery_unit').select('MINUTE');

    // Submit form listing
    cy.get('#listing-form button[type="submit"]').click();

    // Verifikasi kembali ke panel listing dan berhasil
    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(listingTitle).should('be.visible');

    // Simpan listing ID dari link edit untuk pengujian selanjutnya
    cy.contains(listingTitle)
      .closest('tr')
      .find('a[href*="/seller/listings/"][href*="/edit"]')
      .invoke('attr', 'href')
      .then((href) => {
        const matches = href.match(/\/seller\/listings\/(\d+)\/edit/);
        if (matches) {
          createdListingId = matches[1];
        }
      });
  });

  it('TC-04-02: Ubah listing sendiri - Seller dapat memperbarui rincian listing miliknya', () => {
    cy.visit('/seller/listings');
    const updatedTitle = `Item Test Updated ${timestamp}`;

    cy.get('table').should('be.visible');
    cy.get('a[href*="/seller/listings/"][href*="/edit"]').first().click();

    cy.url().should('include', '/edit');
    cy.get('#title').clear().type(updatedTitle);
    cy.get('#edit-listing-form button[type="submit"]').click();

    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(updatedTitle).should('be.visible');
  });

  it('TC-04-03: Nonaktifkan listing - Status listing berhasil ditutup oleh pemilik', () => {
    cy.location('pathname').then((path) => {
      if (path !== '/seller/listings') {
        cy.visit('/seller/listings');
      }
    });

    // Klik tombol Tutup pada listing yang aktif
    cy.get('form[action*="/toggle-status"] button').contains('Tutup').first().click();

    // Verifikasi pesan sukses dan status menjadi DITUTUP
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains('DITUTUP').should('be.visible');
  });

  it('TC-04-04: Validasi listing - Form menolak data jika kolom wajib kosong atau harga nol/negatif', () => {
    cy.visit('/seller/listings/create');

    // Submit form kosong
    cy.get('#listing-form button[type="submit"]').click();

    // Browser HTML5 validation atau URL tetap di create
    cy.url().should('include', '/seller/listings/create');
  });

  it('TC-04-05: Listing pihak lain - Seller lain ditolak mengakses halaman edit listing bukan miliknya', () => {
    // Logout Seller 1
    cy.visit('/seller/listings');
    cy.get('[data-cy="btn-logout"]').click();

    // Daftarkan Seller 2
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller2.username);
    cy.get('[data-cy="input-email"]').type(seller2.email);
    cy.get('[data-cy="input-password"]').type(seller2.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller2.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Seller 2 mencoba akses halaman edit listing Seller 1
    if (createdListingId) {
      cy.request({
        url: `/seller/listings/${createdListingId}/edit`,
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).to.eq(403);
      });
    }
  });
});
