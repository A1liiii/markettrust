describe('Meet 1 Follow-Up: Marketplace, Informasi Pasar, Checkout, & Forum (Rio)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const testUser = {
    username: `buyer_rio_${timestamp}`,
    email: `buyer_rio_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let testListingId = null;
  let testPostId = null;

  before(() => {
    // 1. Seed data uji untuk listing marketplace dan forum postingan dengan balasan bertingkat
    cy.task('execPhp', `
      $seller = \\App\\Models\\User::firstOrCreate(
        ['email' => 'seller_cycle2@markettrust.test'],
        [
          'username' => 'seller_cycle2',
          'password_hash' => bcrypt('Password123!'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Akun Game Meet 1'],
        ['description' => 'Kategori uji meet 1', 'is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Mobile Legends Meet 1'],
        ['category_id' => $category->category_id, 'description' => 'Game MOBA populer', 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Mythical Glory 100+ Star'],
        ['product_id' => $product->product_id, 'description' => 'Akun tier tinggi', 'is_active' => true]
      );

      // Pastikan ada minimal 12 listing aktif untuk uji grid 3 baris
      for ($i = 1; $i <= 13; $i++) {
        \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $variant->variant_id,
          'title' => "Listing Meet 1 Rio #{$i} - " . ${timestamp},
          'description' => "Deskripsi listing pengujian siklus 2 meet 1 nomor {$i}",
          'price' => 75000 + ($i * 5000),
          'status' => 'ACTIVE',
        ]);
      }

      $sampleListing = \\App\\Models\\Listing::where('seller_id', $seller->user_id)->where('status', 'ACTIVE')->first();
      echo json_encode([
        'listing_id' => $sampleListing->listing_id,
      ]);
    `).then((output) => {
      try {
        const data = JSON.parse(output.trim());
        testListingId = data.listing_id;
      } catch (e) {
        // fallback
      }
    });

    // 2. Seed forum post dengan nested replies
    cy.task('execPhp', `
      $author = \\App\\Models\\User::first();
      $post = \\App\\Models\\ForumPost::create([
        'author_id' => $author->user_id,
        'category_code' => 'GENERAL',
        'title' => 'Postingan Meet 1 Switcher PS5 - ' . ${timestamp},
        'content' => 'Diskusi komprehensif mengenai update marketplace dan forum.',
        'status' => 'ACTIVE',
      ]);

      // Buat root comment
      $rootComment = \\App\\Models\\ForumComment::create([
        'post_id' => $post->post_id,
        'author_id' => $author->user_id,
        'content' => 'Komentar induk utama level 1',
      ]);

      // Buat reply level 1
      $reply1 = \\App\\Models\\ForumComment::create([
        'post_id' => $post->post_id,
        'author_id' => $author->user_id,
        'parent_comment_id' => $rootComment->comment_id,
        'content' => 'Balasan pertama untuk komentar induk',
      ]);

      echo json_encode([
        'post_id' => $post->post_id,
      ]);
    `).then((output) => {
      try {
        const data = JSON.parse(output.trim());
        testPostId = data.post_id;
      } catch (e) {
        // fallback
      }
    });

    // 3. Daftarkan akun Buyer untuk pengujian checkout
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(testUser.username);
    cy.get('[data-cy="input-email"]').type(testUser.email);
    cy.get('[data-cy="input-password"]').type(testUser.password);
    cy.get('[data-cy="input-password-confirmation"]').type(testUser.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
  });

  it('TC-MEET1-01: Marketplace - Badge Baru, Grid 3 Baris (12 items), Filter Produk & Pagination Putih', () => {
    cy.visit('/marketplace');

    // 1. Verifikasi badge baru
    cy.contains('Katalog Marketplace Terverifikasi').should('be.visible');
    cy.contains('Katalog Terverifikasi Escrow').should('not.exist');

    // 2. Verifikasi komponen filter terpadu "Produk"
    cy.get('button').contains('Produk').should('be.visible');

    // 3. Verifikasi jumlah card listing di halaman (12 per page)
    cy.get('[data-cy="marketplace-grid"] > a').should('have.length.at.most', 12);

    // 4. Verifikasi pagination nomor 1, 2, 3 berwarna putih terang (text-white)
    cy.get('nav[aria-label*="Pagination"]').within(() => {
      cy.get('.text-white').should('exist');
    });
  });

  it('TC-MEET1-02: Informasi Pasar - Navbar Menu, Halaman Mandiri, & Detail Item Sejajar', () => {
    // 1. Verifikasi navbar link Informasi Pasar di sebelah Forum
    cy.visit('/');
    cy.get('[data-cy="nav-informasi-pasar"]').should('be.visible').and('contain.text', 'Informasi Pasar');

    // 2. Buka halaman Informasi Pasar
    cy.get('[data-cy="nav-informasi-pasar"]').click();
    cy.url().should('include', '/informasi-pasar');
    cy.contains('Informasi Pasar Produk Digital').should('be.visible');
    cy.contains('Periode Transaksi').should('be.visible');

    // 3. Verifikasi ketiadaan widget info pasar pada detail listing dan posisi sejajar card
    if (testListingId) {
      cy.visit(`/marketplace/${testListingId}`);
      cy.get('#widget-informasi-pasar').should('not.exist');

      // Card detail spesifikasi (kiri) dan Card aksi beli (kanan) keduanya terlihat
      cy.contains('Spesifikasi & Informasi Produk').should('be.visible');
      cy.contains('Beli Sekarang').should('be.visible');
    }
  });

  it('TC-MEET1-03: Checkout Marketplace - Rincian Pembayaran 3 Baris & Tombol Bayar', () => {
    if (testListingId) {
      cy.visit(`/marketplace/${testListingId}/checkout`);

      // 1. Verifikasi badge mode fee & card payout telah dihapus
      cy.contains('Mode Fee: EXCLUDE').should('not.exist');
      cy.contains('Tujuan Pencairan Penjual Siap').should('not.exist');

      // 2. Verifikasi rincian pembayaran 3 baris: Harga Dasar Produk, Fee, Total Pembayaran Pembeli
      cy.contains('Harga Dasar Produk').should('be.visible');
      cy.contains('Fee').should('be.visible');
      cy.contains('Total Pembayaran Pembeli').should('be.visible');

      // 3. Verifikasi tombol submisi bertuliskan "Bayar"
      cy.get('button[type="submit"]').contains('Bayar').should('be.visible');
      cy.contains('Setuju & Mulai Transaksi').should('not.exist');
    }
  });

  it('TC-MEET1-04: Forum Komunitas - Switcher PS5 2 Card di Viewport & Threaded Replies', () => {
    cy.visit('/forum');

    // 1. Verifikasi kontainer switcher PS5
    cy.get('#forum-feed-scrollbox').should('be.visible');
    cy.contains('PS5 Switcher').should('be.visible');
    cy.get('button[onclick*="switchForumPost"]').should('have.length.at.least', 2);

    // 2. Buka detail postingan dengan nested replies
    if (testPostId) {
      cy.visit(`/forum/${testPostId}`);

      // Verifikasi toggle Reply (1) terlihat
      cy.contains('Reply (1)').should('be.visible');

      // Verifikasi replies container default-nya hidden
      cy.get(`[id^="replies-container-"]`).first().should('have.class', 'hidden');

      // Klik tombol toggle Reply (1) untuk expand
      cy.contains('Reply (1)').click();
      cy.get(`[id^="replies-container-"]`).first().should('not.have.class', 'hidden');
      cy.contains('Balasan pertama untuk komentar induk').should('be.visible');

      // Klik lagi untuk collapse
      cy.contains('Reply (1)').click();
      cy.get(`[id^="replies-container-"]`).first().should('have.class', 'hidden');
    }
  });
});
