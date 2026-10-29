describe('TS-15: Konfirmasi Buyer & Auto-Confirm (MT-A04)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `seller_conf_${timestamp}`,
    email: `seller_conf_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buyer_conf_${timestamp}`,
    email: `buyer_conf_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let transactionId;

  before(() => {
    // 1. Registrasi Seller
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout Seller
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 2. Registrasi Buyer
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(buyer.username);
    cy.get('[data-cy="input-email"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="input-password-confirmation"]').type(buyer.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout Buyer
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 3. Buat transaksi DELIVERED via tinker
    cy.task('execPhp', `
      $buyerUser = \\App\\Models\\User::where('username', '${buyer.username}')->first();
      $sellerUser = \\App\\Models\\User::where('username', '${seller.username}')->first();

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital Test'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi E2E Confirmation'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian E2E Lisensi Conf'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Software Digital Confirmation E2E',
        'description' => 'Lisensi untuk konfirmasi buyer',
        'price' => 150000.00,
        'delivery_method' => 'MANUAL',
        'estimated_delivery_value' => 60,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      $agreement = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => $listing->title,
        'item_description' => $listing->description,
        'quantity' => 1,
        'base_price' => 150000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 155000.00,
        'seller_net' => 150000.00,
        'delivery_method' => 'MANUAL',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Kesepakatan pengujian konfirmasi',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx = \\App\\Models\\Transaction::create([
        'agreement_id' => $agreement->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::DELIVERED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(10),
        'confirmation_due_at' => now()->addMinutes(50),
        'delivery_note' => 'Kunci lisensi aktif: CONFIRM-TEST-TOKEN-12345. Silakan periksa akses Anda.',
        'result_link' => 'https://example.com/buyer-access',
      ]);

      echo $tx->transaction_id;
    `).then((output) => {
      transactionId = output.trim();
    });
  });

  it('TC-15-01: Buyer melihat formulir aksi konfirmasi penerimaan pada transaksi DELIVERED', () => {
    // Login sebagai Buyer
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();

    // Kunjungi halaman transaksi saat sedang login sebagai Buyer
    cy.visit(`/transactions/${transactionId}`);

    // Pastikan badge status bernilai DELIVERED (Item Diserahkan)
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');

    // Pastikan card aksi konfirmasi tampil
    cy.get('[data-cy="buyer-confirmation-action-card"]').should('be.visible');
    cy.contains('Konfirmasi Hasil Pesanan').should('be.visible');
    cy.get('[data-cy="btn-confirm-delivery"]').should('be.visible');
    cy.get('[data-cy="confirmation-due-text"]').should('be.visible');
    cy.get('[data-cy="display-delivery-note"]').should('contain', 'CONFIRM-TEST-TOKEN-12345');
  });

  it('TC-15-02: Buyer mengonfirmasi penerimaan dan status beralih ke CONFIRMED (Siap Payout)', () => {
    cy.visit(`/transactions/${transactionId}`);

    // Klik tombol konfirmasi (window:confirm secara otomatis bernilai true di Cypress)
    cy.get('[data-cy="btn-confirm-delivery"]').click();

    // Verifikasi pesan sukses
    cy.contains('Pesanan berhasil dikonfirmasi').should('be.visible');

    // Verifikasi badge status berubah menjadi CONFIRMED
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dikonfirmasi');

    // Verifikasi banner konfirmasi selesai tampil
    cy.get('[data-cy="confirmed-status-banner"]').should('be.visible');
    cy.get('[data-cy="confirmed-status-banner"]').should('contain', 'Pesanan Telah Dikonfirmasi');
    cy.get('[data-cy="confirmed-status-banner"]').should('contain', 'Siap Payout');

    // Tombol konfirmasi tidak boleh tampil lagi
    cy.get('[data-cy="buyer-confirmation-action-card"]').should('not.exist');
  });

  it('TC-15-03: Seller memeriksa transaksi dan melihat status pesanan telah dikonfirmasi', () => {
    // Logout Buyer
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // Login sebagai Seller
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="btn-login"]').click();

    // Buka halaman detail transaksi
    cy.visit(`/transactions/${transactionId}`);

    // Pastikan status Dikonfirmasi
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dikonfirmasi');
    cy.get('[data-cy="confirmed-status-banner"]').should('be.visible');
    cy.get('[data-cy="confirmed-status-banner"]').should('contain', 'Pesanan Telah Dikonfirmasi');
    cy.get('[data-cy="confirmed-status-banner"]').should('contain', 'Siap Payout');

    // Tombol aksi konfirmasi tidak boleh ada
    cy.get('[data-cy="buyer-confirmation-action-card"]').should('not.exist');
  });
});
