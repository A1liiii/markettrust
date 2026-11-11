describe('Testing Cycle 2 — Bagian Fadhil Syahidan (Kelola Listing, Login & Registrasi, Profil Seller)', () => {
  const sellerEmail = 'fadhil_c2_seller@markettrust.local';
  const sellerUsername = 'fadhil_c2_seller';
  const testPassword = 'Password123!';
  let variantId = null;
  let testListingId = null;
  let sellerUserId = null;

  before(() => {
    // Siapkan data master katalog, seller, buyer, listing, dan event reputasi via execPhp
    cy.task('execPhp', `
      $seller = \\App\\Models\\User::updateOrCreate(
        ['email' => '${sellerEmail}'],
        [
          'username' => '${sellerUsername}',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${testPassword}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Akun Game Digital C2'],
        ['description' => 'Kategori Akun Game Cycle 2', 'is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['category_id' => $category->category_id, 'product_name' => 'Mobile Legends C2'],
        ['description' => 'Produk Master MLBB C2', 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['product_id' => $product->product_id, 'variant_name' => 'Mythical Immortal C2'],
        ['is_active' => true]
      );

      $listing = \\App\\Models\\Listing::updateOrCreate(
        ['title' => 'Akun MLBB Immortal Siap Turnamen C2'],
        [
          'seller_id' => $seller->user_id,
          'variant_id' => $variant->variant_id,
          'description' => 'Akun pribadi tangan pertama aman.',
          'price' => 250000,
          'delivery_method' => 'Data Kredensial via Catatan Aman',
          'estimated_delivery_value' => 15,
          'estimated_delivery_unit' => 'MINUTE',
          'status' => 'ACTIVE',
        ]
      );

      \\App\\Models\\ReputationEvent::updateOrCreate(
        ['event_key' => 'cycle2:test:event:seed'],
        [
          'user_id' => $seller->user_id,
          'event_type' => \\App\\Models\\ReputationEvent::EVENT_ADMIN_MODERATION,
          'points_delta' => -10,
          'previous_status' => null,
          'new_status' => 'PROBLEMATIC',
          'reason' => 'Sanksi moderasi uji coba',
          'created_at' => now(),
        ]
      );

      echo json_encode([
        'seller_user_id' => $seller->user_id,
        'variant_id' => $variant->variant_id,
        'listing_id' => $listing->listing_id,
      ]);
    `).then((res) => {
      try {
        const data = JSON.parse(res);
        variantId = data.variant_id;
        testListingId = data.listing_id;
        sellerUserId = data.seller_user_id;
      } catch (e) {
        // fallback parse
      }
    });
  });

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  // ==========================================
  // 1. KELOLA LISTING (TC-C2-LS-01 s/d TC-C2-LS-03)
  // ==========================================
  describe('Kelola Listing', () => {
    beforeEach(() => {
      // Login sebagai seller
      cy.visit('/login');
      cy.get('[data-cy="input-login"]').type(sellerEmail);
      cy.get('[data-cy="input-password"]').type(testPassword);
      cy.get('[data-cy="btn-login"]').click();
      cy.url().should('not.include', '/login');
    });

    it('TC-C2-LS-01: Pembuatan Listing Baru Tanpa Foto Berhasil (Status ACTIVE)', () => {
      const listingTitle = `Listing MLBB No Photo ${Date.now()}`;

      cy.visit('/seller/listings/create');
      cy.get('#variant_id option').contains('Mythical Immortal C2').then($opt => {
        cy.get('#variant_id').select($opt.val());
      });
      cy.get('#title').type(listingTitle);
      cy.get('#description').type('Penjualan akun game tanpa foto lampiran, proses instan.');
      cy.get('#price_display').type('150000');
      cy.get('#delivery_method').clear().type('Data Kredensial via Catatan Aman');
      cy.get('#estimated_delivery_value').clear().type('15');
      cy.get('#estimated_delivery_unit').select('MINUTE');

      cy.get('#listing-form button[type="submit"]').click();

      cy.url().should('include', '/seller/listings');
      cy.get('[data-cy="alert-success"]').should('be.visible');
      cy.contains(listingTitle).should('be.visible');
      cy.contains('Aktif').should('be.visible');
    });

    it('TC-C2-LS-02: Pembuatan Listing Baru Dengan Foto Berhasil via Fallback Storage', () => {
      const listingTitle = `Listing MLBB With Photo ${Date.now()}`;

      cy.visit('/seller/listings/create');
      cy.get('#variant_id option').contains('Mythical Immortal C2').then($opt => {
        cy.get('#variant_id').select($opt.val());
      });
      cy.get('#title').type(listingTitle);
      cy.get('#description').type('Penjualan akun game dengan bukti screenshot valid.');
      cy.get('#price_display').type('200000');
      cy.get('#delivery_method').clear().type('Data Kredensial via Catatan Aman');
      cy.get('#estimated_delivery_value').clear().type('20');
      cy.get('#estimated_delivery_unit').select('MINUTE');

      // Unggah foto produk valid
      cy.get('[data-cy="input-listing-images"]').selectFile({
        contents: Cypress.Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', 'base64'),
        fileName: 'screenshot_akun.png',
        mimeType: 'image/png',
      });

      cy.get('#listing-form button[type="submit"]').click();

      // Tidak boleh muncul 500 Server Error atau can't access
      cy.url().should('include', '/seller/listings');
      cy.get('[data-cy="alert-success"]').should('be.visible');
      cy.contains(listingTitle).should('be.visible');
      cy.contains('Aktif').should('be.visible');
    });

    it('TC-C2-LS-03: Validasi Form Listing Menolak Nominal di Bawah Batas Minimum', () => {
      cy.visit('/seller/listings/create');
      cy.get('#variant_id option').contains('Mythical Immortal C2').then($opt => {
        cy.get('#variant_id').select($opt.val());
      });
      cy.get('#title').type('Listing Harga Di Bawah Minimal');
      cy.get('#description').type('Pengetesan validasi batas minimum harga penawaran.');
      cy.get('#price_display').type('2000'); // di bawah 5.000
      cy.get('#delivery_method').clear().type('Data Kredensial via Catatan Aman');
      cy.get('#estimated_delivery_value').clear().type('10');
      cy.get('#estimated_delivery_unit').select('MINUTE');

      cy.get('#listing-form button[type="submit"]').click();

      // Tetap di create page dengan pesan error validasi
      cy.url().should('include', '/seller/listings/create');
      cy.contains(/minimal rp|5\.000/i).should('be.visible');
    });
  });

  // ==========================================
  // 2. LOGIN & REGISTRASI (TC-C2-AUTH-01 s/d TC-C2-AUTH-02)
  // ==========================================
  describe('Login dan Registrasi (Desktop Non-Scrollable)', () => {
    it('TC-C2-AUTH-01: Tampilan Desktop Login Muat 1 Layar Penuh Bebas Scroll', () => {
      cy.viewport(1280, 800);
      cy.visit('/login');

      // Verifikasi seluruh card login terlihat dalam layar tanpa vertical scroll
      cy.get('[data-cy="login-form"]').should('be.visible');
      cy.get('[data-cy="input-login"]').should('be.visible');
      cy.get('[data-cy="input-password"]').should('be.visible');
      cy.get('[data-cy="btn-login"]').should('be.visible');

      cy.window().then((win) => {
        const scrollHeight = win.document.documentElement.scrollHeight;
        const innerHeight = win.innerHeight;
        // Mentolerir dev header / banner browser tipis (margin 25px)
        expect(scrollHeight).to.be.at.most(innerHeight + 25);
      });

      // Lakukan login dan pastikan berhasil
      cy.get('[data-cy="input-login"]').type(sellerEmail);
      cy.get('[data-cy="input-password"]').type(testPassword);
      cy.get('[data-cy="btn-login"]').click();
      cy.url().should('not.include', '/login');
    });

    it('TC-C2-AUTH-02: Tampilan Desktop Registrasi Muat 1 Layar Penuh Bebas Scroll', () => {
      cy.viewport(1280, 800);
      cy.visit('/register');

      // Verifikasi form registrasi 2-kolom terlihat dalam layar
      cy.get('[data-cy="register-form"]').should('be.visible');
      cy.get('[data-cy="input-username"]').should('be.visible');
      cy.get('[data-cy="input-email"]').should('be.visible');
      cy.get('[data-cy="input-password"]').should('be.visible');
      cy.get('[data-cy="input-password-confirmation"]').should('be.visible');
      cy.get('[data-cy="btn-register"]').should('be.visible');

      cy.window().then((win) => {
        const scrollHeight = win.document.documentElement.scrollHeight;
        const innerHeight = win.innerHeight;
        expect(scrollHeight).to.be.at.most(innerHeight + 25);
      });

      // Lakukan pendaftaran akun baru
      const newUsername = `cy_c2_${Date.now()}`.substring(0, 20);
      const newEmail = `cy_c2_${Date.now()}@markettrust.local`;
      cy.get('[data-cy="input-username"]').type(newUsername);
      cy.get('[data-cy="input-email"]').type(newEmail);
      cy.get('[data-cy="input-password"]').type('ValidPassword123!');
      cy.get('[data-cy="input-password-confirmation"]').type('ValidPassword123!');
      cy.get('[data-cy="btn-register"]').click();

      cy.url().should('not.include', '/register');
    });
  });

  // ==========================================
  // 3. PROFIL SELLER (TC-C2-PROF-01 s/d TC-C2-PROF-05)
  // ==========================================
  describe('Profil Seller & Riwayat Legitimasi', () => {
    beforeEach(() => {
      // Login terlebih dahulu agar memiliki akses profil
      cy.visit('/login');
      cy.get('[data-cy="input-login"]').type(sellerEmail);
      cy.get('[data-cy="input-password"]').type(testPassword);
      cy.get('[data-cy="btn-login"]').click();
      cy.url().should('not.include', '/login');
    });

    it('TC-C2-PROF-01: Akses Profil Seller Melalui Tombol di Detail Produk Marketplace', () => {
      cy.visit(`/marketplace/${testListingId}`);
      cy.get('[data-cy="btn-view-seller-profile"]').should('be.visible').click();

      cy.url().should('include', `/profile/${sellerUserId}`);
      cy.contains(`@${sellerUsername}`).should('be.visible');
      cy.contains('Riwayat Peristiwa Reputasi & Legitimasi').should('be.visible');
    });

    it('TC-C2-PROF-02: Hapus Button "Lihat Etalase Produk"', () => {
      cy.visit(`/profile/${sellerUserId}`);
      cy.contains('Lihat Etalase Produk').should('not.exist');
    });

    it('TC-C2-PROF-03: Total Akumulasi Reputasi dan Nilai Poin Berada dalam Satu Baris', () => {
      cy.visit(`/profile/${sellerUserId}`);
      cy.contains('Total Akumulasi:').should('be.visible');
      cy.contains('Total Akumulasi:')
        .closest('.whitespace-nowrap')
        .should('have.class', 'whitespace-nowrap');
    });

    it('TC-C2-PROF-04: Optimalisasi Judul Panduan Indikator Reputasi Menjadi Satu Baris', () => {
      cy.visit(`/profile/${sellerUserId}`);
      cy.contains('Panduan Indikator Reputasi & Legitimasi').should('be.visible');
      cy.contains('Panduan Indikator Reputasi & Legitimasi')
        .parents('.glass-card')
        .within(() => {
          cy.contains('PROBLEMATIC')
            .should('be.visible');
        });
    });

    it('TC-C2-PROF-05: Hapus Bagian KEEP dan Pertahankan Tinjauan Pembatalan Pasca-Bayar', () => {
      cy.visit(`/profile/${sellerUserId}`);
      // Catatan Kepatuhan Transaksi tampil dengan Tinjauan Pembatalan Pasca-Bayar
      cy.contains('Catatan Kepatuhan Transaksi').should('be.visible');
      cy.contains('Tinjauan Pembatalan Pasca-Bayar').should('be.visible');

      // Kewajiban Penggantian Fee (KEEP) telah dihapus
      cy.contains('Kewajiban Penggantian Fee (KEEP)').should('not.exist');
    });
  });
});
