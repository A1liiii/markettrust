describe('TS-04: Katalog Admin & Listing Marketplace', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const adminUser = {
    username: `adm_rio_${timestamp}`,
    email: `adm_rio_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const sellerUser = {
    username: `sel_rio_${timestamp}`,
    email: `sel_rio_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const categoryName = `Kategori QA ${timestamp}`;
  const productName = `Produk Game QA ${timestamp}`;
  const variantName = `Varian Tier QA ${timestamp}`;
  const listingTitle = `Listing Uji Rio ${timestamp}`;

  before(() => {
    // 1. Buat akun Admin via execPhp
    cy.task('execPhp', `
      \\App\\Models\\User::create([
        'username' => '${adminUser.username}',
        'email' => '${adminUser.email}',
        'password_hash' => bcrypt('${adminUser.password}'),
        'account_type' => 'ADMIN',
        'account_status' => 'ACTIVE',
      ]);
    `);

    // 2. Daftarkan akun Seller via UI register
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(sellerUser.username);
    cy.get('[data-cy="input-email"]').type(sellerUser.email);
    cy.get('[data-cy="input-password"]').type(sellerUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(sellerUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
    cy.url().should('eq', Cypress.config().baseUrl + '/');

    // Logout seller agar siap untuk pengujian admin
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');
  });

  it('TC-03-01: Admin Menambah Kategori, Produk, dan Varian Baru secara berjenjang', () => {
    // 1. Login sebagai Admin
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminUser.email);
    cy.get('[data-cy="input-password"]').type(adminUser.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('include', '/admin');
    cy.visit('/admin/catalog?tab=categories');

    // 2. Tambah Kategori Baru
    cy.get('button[onclick*="modal-add-category"]').first().click();
    cy.get('#modal-add-category').should('be.visible');
    cy.get('#modal-add-category input[name="category_name"]').type(categoryName);
    cy.get('#modal-add-category textarea[name="description"]').type('Deskripsi kategori pengujian otomatis TS-04');
    cy.get('#modal-add-category button[type="submit"]').click();

    // Verifikasi kategori muncul di tabel
    cy.contains(categoryName).should('be.visible');

    // 3. Tambah Produk Master
    cy.visit('/admin/catalog?tab=products');
    cy.get('button[onclick*="modal-add-product"]').first().click();
    cy.get('#modal-add-product').should('be.visible');

    // Pilih kategori yang baru dibuat
    cy.get('#modal-add-product select[name="category_id"]').select(categoryName);
    cy.get('#modal-add-product input[name="product_name"]').type(productName);
    cy.get('#modal-add-product textarea[name="description"]').type('Deskripsi produk master otomatis');
    cy.get('#modal-add-product button[type="submit"]').click();

    // Verifikasi produk muncul di tabel
    cy.contains(productName).should('be.visible');

    // 4. Tambah Varian Spesifikasi
    cy.visit('/admin/catalog?tab=variants');
    cy.get('button[onclick*="modal-add-variant"]').first().click();
    cy.get('#modal-add-variant').should('be.visible');

    // Pilih produk yang baru dibuat
    cy.get('#modal-add-variant select[name="product_id"] option').contains(productName).then($opt => {
      cy.get('#modal-add-variant select[name="product_id"]').select($opt.val());
    });
    cy.get('#modal-add-variant input[name="variant_name"]').type(variantName);
    cy.get('#modal-add-variant button[type="submit"]').click();

    // Verifikasi varian muncul di tabel
    cy.contains(variantName).should('be.visible');

    // Logout Admin
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');
  });

  it('TC-03-02: Seller Membuat Listing Marketplace Baru dengan Status ACTIVE (Catatan: Gap DRAFT tercatat)', () => {
    // 1. Login sebagai Seller
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(sellerUser.email);
    cy.get('[data-cy="input-password"]').type(sellerUser.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // 2. Buka halaman pembuatan listing
    cy.visit('/seller/listings/create');
    cy.url().should('include', '/seller/listings/create');

    // Pilih varian yang baru saja dibuat oleh Admin
    cy.get('#variant_id option').contains(variantName).then($opt => {
      cy.get('#variant_id').select($opt.val());
    });

    // Isi formulir listing
    cy.get('#title').type(listingTitle);
    cy.get('#description').type('Akun game dengan garansi resmi dan pengiriman instant.');
    cy.get('#price_display').type('150000');
    cy.get('#delivery_method').clear().type('Data Akun via Catatan Terenkripsi');
    cy.get('#estimated_delivery_value').clear().type('30');
    cy.get('#estimated_delivery_unit').select('MINUTE');

    // Submit pembuatan listing
    cy.get('#listing-form button[type="submit"]').click();

    // Verifikasi redirect ke index listing dan badge aktif terlihat
    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(listingTitle).should('be.visible');
    cy.contains('Aktif').should('be.visible');
  });

  it('TC-03-03: Toggle Status Listing Seller (ACTIVE <-> INACTIVE/CLOSED)', () => {
    cy.visit('/seller/listings');

    // 1. Tutup/Nonaktifkan listing yang berstatus aktif
    cy.get('table tbody tr').contains(listingTitle)
      .closest('tr')
      .find('form[action*="/toggle-status"] button')
      .click();

    // Verifikasi status beralih ke DITUTUP
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains('DITUTUP').should('be.visible');

    // 2. Aktifkan kembali listing
    cy.get('table tbody tr').contains(listingTitle)
      .closest('tr')
      .find('form[action*="/toggle-status"] button')
      .click();

    // Verifikasi status beralih kembali ke Aktif
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains('AKTIF').should('be.visible');
    cy.contains('Aktif').should('be.visible');
  });
});
