describe('TS-35: Profil Publik dan Riwayat Legitimasi (MT-A12)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `sel_a12_${timestamp}`,
    email: `sel_a12_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buy_a12_${timestamp}`,
    email: `buy_a12_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let sellerId = null;
  let buyerId = null;
  let sellerListingId = null;

  before(() => {
    // 1. Registrasi akun Penjual (Seller)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Dapatkan user_id seller dan buatkan listing, reputation event (+15 LEGIT), serta rekening payout privat
    cy.task('execPhp', `
      $seller = \\App\\Models\\User::where('username', '${seller.username}')->first();
      $variant = \\App\\Models\\ProductVariant::first();
      if (! $variant) {
          $cat = \\App\\Models\\ProductCategory::firstOrCreate(['category_name' => 'Voucher', 'is_active' => true]);
          $prod = \\App\\Models\\Product::firstOrCreate(['category_id' => $cat->category_id, 'product_name' => 'Steam Wallet', 'is_active' => true]);
          $variant = \\App\\Models\\ProductVariant::firstOrCreate(['product_id' => $prod->product_id, 'variant_name' => '50K', 'is_active' => true]);
      }
      $listing = \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $variant->variant_id,
          'title' => 'Steam Wallet 50K Murah ${timestamp}',
          'description' => 'Fast delivery via chat',
          'price' => 50000,
          'delivery_method' => 'DM Voucher Code',
          'estimated_delivery_value' => 5,
          'estimated_delivery_unit' => 'MINUTE',
          'status' => 'ACTIVE',
      ]);
      \\App\\Models\\ReputationEvent::create([
          'user_id' => $seller->user_id,
          'event_type' => 'TRANSACTION_COMPLETED',
          'points_delta' => 15,
          'new_status' => 'LEGIT',
          'reason' => 'Transaksi sukses diselesaikan tepat waktu',
          'event_key' => 'cy_tx_' . uniqid(),
          'created_at' => now(),
      ]);
      \\App\\Models\\SellerPayoutAccount::create([
          'seller_id' => $seller->user_id,
          'destination_type' => 'BANK',
          'destination_provider' => 'BANK_BCA',
          'destination_number' => '1234567890',
          'destination_holder' => 'Seller Confidential Holder',
          'is_active' => true,
      ]);
      echo $seller->user_id . '|' . $listing->listing_id;
    `).then((output) => {
      const parts = output.trim().split('|');
      sellerId = parts[0];
      sellerListingId = parts[1];
    });

    // Logout Seller dari sesi
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 2. Registrasi akun Pembeli (Buyer) sebagai aktor pengamat profil publik
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(buyer.username);
    cy.get('[data-cy="input-email"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="input-password-confirmation"]').type(buyer.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.task('execPhp', `
      $buyer = \\App\\Models\\User::where('username', '${buyer.username}')->first();
      echo $buyer->user_id;
    `).then((output) => {
      buyerId = output.trim();
    });
  });

  it('TC-35-01: Navigasi dari detail listing marketplace ke profil publik penjual', () => {
    // Kunjungi halaman detail produk marketplace milik seller
    cy.visit(`/marketplace/${sellerListingId}`);
    cy.url().should('include', `/marketplace/${sellerListingId}`);

    // Pastikan kartu Informasi Penjual muncul dan menampilkan identitas seller
    cy.contains(`@${seller.username}`).should('be.visible');

    // Klik tombol aksi menuju profil publik penjual
    cy.get('[data-cy="btn-view-seller-profile"]').should('be.visible').click();

    // Verifikasi diarahkan ke rute profil publik penjual
    cy.url().should('include', `/profile/${sellerId}`);
    cy.contains(`@${seller.username}`).should('be.visible');
    cy.contains(`ID Pengguna: #MT-${String(sellerId).padStart(4, '0')}`).should('be.visible');
  });

  it('TC-35-02: Verifikasi badge status legitimasi, poin reputasi, dan tabel riwayat peristiwa', () => {
    cy.visit(`/profile/${sellerId}`);

    // 1. Verifikasi skor reputasi dan badge legitimasi LEGIT
    cy.contains('+15').should('be.visible');
    cy.contains('Poin').should('be.visible');
    cy.contains('Terverifikasi (LEGIT)').should('be.visible');

    // 2. Verifikasi Section Riwayat Peristiwa Reputasi & Legitimasi
    cy.get('#riwayat-reputasi').should('be.visible');
    cy.get('[data-cy="table-reputation-events"]').should('be.visible').within(() => {
      cy.contains('Transaksi Escrow Sukses').should('be.visible');
      cy.contains('+15 Poin').should('be.visible');
      cy.contains('LEGIT').should('be.visible');
      cy.contains('Transaksi sukses diselesaikan tepat waktu').should('be.visible');
    });

    // 3. Verifikasi Panduan Indikator Reputasi & Legitimasi
    cy.contains('Panduan Indikator Reputasi & Legitimasi').should('be.visible');
    cy.contains('LEGIT').should('be.visible');
    cy.contains('PROBLEMATIC').should('be.visible');
    cy.contains('UNREVIEWED').should('be.visible');
    cy.contains('Poin Dinamis').should('be.visible');

    // 4. Verifikasi Catatan Kepatuhan Transaksi & KEEP
    cy.contains('Catatan Kepatuhan Transaksi & KEEP').should('be.visible');
    cy.contains('Tinjauan Pembatalan Pasca-Bayar').should('be.visible');
    cy.contains('Kewajiban Penggantian Fee (KEEP)').should('be.visible');
  });

  it('TC-35-03: Perlindungan privasi data - Rekening payout dan keamanan akun privat tidak ditampilkan kepada pengguna lain', () => {
    cy.visit(`/profile/${sellerId}`);

    // Pastikan email privat seller tidak dibocorkan
    cy.contains(seller.email).should('not.exist');

    // Pastikan nomor rekening payout bank/e-wallet seller tidak tampil
    cy.contains('1234567890').should('not.exist');
    cy.contains('BANK_BCA').should('not.exist');
    cy.contains('Tujuan Payout Seller').should('not.exist');

    // Pastikan bagian keamanan autentikasi privat tidak muncul
    cy.contains('Keamanan Akun').should('not.exist');
    cy.contains('Koneksi Akun Google').should('not.exist');
    cy.contains('Kata Sandi (Password) Lokal').should('not.exist');

    // Pastikan tombol edit profil sendiri tidak muncul
    cy.contains('Ubah Foto & Password').should('not.exist');
    cy.get('[data-cy="btn-profile-payout-accounts"]').should('not.exist');

    // Pastikan tombol aksi interaksi publik untuk lawan transaksi tersedia
    cy.get('[data-cy="btn-profile-dm"]').should('be.visible');
  });

  it('TC-35-04: Verifikasi empty state pada profil pengguna yang belum memiliki catatan reputasi', () => {
    // Akses profil buyer (yang belum memiliki transaksi atau reputasi)
    cy.visit(`/profile/${buyerId}`);

    cy.contains(`@${buyer.username}`).should('be.visible');
    cy.contains('0').should('be.visible');
    cy.contains('Belum Ditinjau').should('be.visible');

    // Empty state edukatif harus tampil
    cy.get('[data-cy="empty-reputation-events"]').should('be.visible');
    cy.contains('Belum Ada Riwayat Peristiwa Reputasi').should('be.visible');

    // Tabel reputasi tidak boleh muncul saat riwayat kosong
    cy.get('[data-cy="table-reputation-events"]').should('not.exist');
  });
});
