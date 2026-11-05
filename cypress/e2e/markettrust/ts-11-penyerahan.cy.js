describe('TS-11: Penyerahan Produk Digital oleh Seller', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `sel_del_${timestamp}`,
    email: `sel_del_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buy_del_${timestamp}`,
    email: `buy_del_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let transactionId;
  let overdueTxId;

  before(() => {
    // 1. Registrasi Seller
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // 2. Registrasi Buyer
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(buyer.username);
    cy.get('[data-cy="input-email"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="input-password-confirmation"]').type(buyer.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // 3. Buat transaksi PAID via task execPhp
    cy.task('execPhp', `
      $sellerUser = \\App\\Models\\User::where('email', '${seller.email}')->first();
      $buyerUser = \\App\\Models\\User::where('email', '${buyer.email}')->first();

      $cat = \\App\\Models\\ProductCategory::firstOrCreate(['category_name' => 'Kategori Digital TS11'], ['is_active' => true]);
      $prod = \\App\\Models\\Product::firstOrCreate(['product_name' => 'Produk Lisensi TS11'], ['category_id' => $cat->category_id, 'is_active' => true]);
      $var = \\App\\Models\\ProductVariant::firstOrCreate(['variant_name' => 'Varian Lisensi TS11'], ['product_id' => $prod->product_id, 'is_active' => true]);

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $var->variant_id,
        'title' => 'Lisensi Software Desain E2E',
        'description' => 'Lisensi resmi original penyerahan',
        'price' => 175000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 60,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      $agreement = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $var->variant_id,
        'item_title' => $listing->title,
        'item_description' => $listing->description,
        'quantity' => 1,
        'base_price' => 175000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 180000.00,
        'seller_net' => 175000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Kesepakatan pengujian penyerahan E2E',
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
        'status' => \\App\\Enums\\TransactionStatus::PAID,
        'payment_due_at' => now()->subHour(),
        'delivery_due_at' => now()->addHour(),
      ]);

      // Transaksi kedua untuk pengujian keterlambatan scheduler (buat agreement baru terpisah)
      $agreementOverdue = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $var->variant_id,
        'item_title' => $listing->title . ' Overdue',
        'item_description' => $listing->description,
        'quantity' => 1,
        'base_price' => 175000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 180000.00,
        'seller_net' => 175000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Kesepakatan pengujian penyerahan overdue E2E',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now()->subHours(2),
        'seller_accepted_at' => now()->subHours(2),
        'finalized_at' => now()->subHours(2),
      ]);

      $txOverdue = \\App\\Models\\Transaction::create([
        'agreement_id' => $agreementOverdue->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::PAID,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subMinutes(15), // sudah jatuh tempo
      ]);

      echo $tx->transaction_id . ',' . $txOverdue->transaction_id;
    `).then((output) => {
      const parts = output.trim().split(',');
      transactionId = parts[0];
      overdueTxId = parts[1];
    });
  });

  it('TC-09-02: Seller Menyerahkan Pesanan Tanpa Bukti / Catatan ditolak validasi', () => {
    // Login sebagai Seller
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    cy.visit(`/transactions/${transactionId}`);
    cy.get('[data-cy="seller-delivery-form-card"]').should('be.visible');

    // Kosongkan form dan klik submit
    cy.get('[data-cy="input-delivery-note"]').clear();
    cy.get('[data-cy="btn-submit-delivery"]').click();

    // Verifikasi tetap di halaman form dan status tetap PAID (Dibayar)
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dibayar');
    cy.get('[data-cy="seller-delivery-form-card"]').should('be.visible');
  });

  it('TC-09-01: Penyerahan Hasil oleh Seller dengan Berkas Bukti Sah', () => {
    cy.visit(`/transactions/${transactionId}`);

    // Isi formulir penyerahan dengan data sah
    cy.get('[data-cy="input-delivery-note"]').type('Kunci Serial Lisensi: PRO-KEY-2026-X999\nPanduan unduh tersedia.');
    cy.get('[data-cy="input-result-link"]').type('https://example.com/assets/download-pkg');

    // Submit form penyerahan
    cy.get('[data-cy="btn-submit-delivery"]').click();

    // Verifikasi status transaksi beralih ke DELIVERED
    cy.contains('Hasil resmi transaksi berhasil diserahkan').should('be.visible');
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');

    // Verifikasi ringkasan penyerahan tampil
    cy.get('[data-cy="delivered-summary-card"]').should('be.visible');
    cy.get('[data-cy="display-delivery-note"]').should('contain', 'PRO-KEY-2026-X999');
    cy.get('[data-cy="confirmation-due-text"]').should('be.visible');
  });

  it('TC-09-03: Permintaan Perpanjangan Waktu Penyerahan oleh Seller (BLOCKED - Kebijakan Batas TBD)', () => {
    // Berdasarkan catatan penugasan: kebijakan durasi dan limit frekuensi perpanjangan belum final dalam dokumen spesifikasi.
    // Diuji ketersediaan fitur dasar dan dicatat status BLOCKED untuk kepatuhan dokumen.
    cy.visit(`/transactions/${transactionId}`);
    cy.log('TC-09-03 status: BLOCKED pada spesifikasi dokumen karena aturan batas frekuensi dan approval masih TBD.');
  });

  it('TC-09-04: Scheduler Peringatan Keterlambatan Penyerahan Seller (UC-14B)', () => {
    // Jalankan scheduler command untuk mendeteksi transaksi overdue
    cy.task('execPhp', `
      \\Illuminate\\Support\\Facades\\Artisan::call('transactions:check-delivery-deadlines');
      echo \\App\\Models\\Notification::where('transaction_id', ${overdueTxId})
        ->where('event_type', \\App\\Enums\\NotificationEventType::SELLER_DELIVERY_WARNING->value)
        ->count();
    `, { timeout: 120000 }).then((output) => {
      const notifCount = parseInt(output.trim(), 10);
      expect(notifCount, 'Peringatan keterlambatan penyerahan dikirimkan oleh scheduler').to.be.at.least(1);
    });

    // Buka halaman notifikasi seller
    cy.visit('/notifications');
    cy.contains('Peringatan Penyerahan').should('be.visible');
  });
});
