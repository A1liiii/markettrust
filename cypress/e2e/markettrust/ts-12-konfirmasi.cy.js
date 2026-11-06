describe('TS-12: Konfirmasi Penerimaan & Auto-Confirm', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `sel_cnf_${timestamp}`,
    email: `sel_cnf_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buy_cnf_${timestamp}`,
    email: `buy_cnf_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let manualTxId;
  let graceTxId;
  let autoConfirmTxId;

  before(() => {
    // 1. Registrasi Seller
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

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

    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // 3. Buat transaksi DELIVERED via execPhp
    cy.task('execPhp', `
      $sellerUser = \\App\\Models\\User::where('email', '${seller.email}')->first();
      $buyerUser = \\App\\Models\\User::where('email', '${buyer.email}')->first();

      $cat = \\App\\Models\\ProductCategory::firstOrCreate(['category_name' => 'Kategori Digital TS12'], ['is_active' => true]);
      $prod = \\App\\Models\\Product::firstOrCreate(['product_name' => 'Produk Lisensi TS12'], ['category_id' => $cat->category_id, 'is_active' => true]);
      $var = \\App\\Models\\ProductVariant::firstOrCreate(['variant_name' => 'Varian Lisensi TS12'], ['product_id' => $prod->product_id, 'is_active' => true]);

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $var->variant_id,
        'title' => 'Lisensi Software Cloud TS12',
        'description' => 'Lisensi resmi pengujian konfirmasi buyer',
        'price' => 125000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 60,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      $makeTx = function($deliveredAgoMinutes, $dueOffsetMinutes) use ($buyerUser, $sellerUser, $listing, $var) {
        $agreement = \\App\\Models\\Agreement::create([
          'created_by' => $buyerUser->user_id,
          'buyer_id' => $buyerUser->user_id,
          'seller_id' => $sellerUser->user_id,
          'variant_id' => $var->variant_id,
          'item_title' => $listing->title,
          'item_description' => $listing->description,
          'quantity' => 1,
          'base_price' => 125000.00,
          'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
          'fee_amount' => 5000.00,
          'buyer_total' => 130000.00,
          'seller_net' => 125000.00,
          'delivery_method' => 'INSTANT',
          'delivery_within_minutes' => 60,
          'agreement_note' => 'Kesepakatan pengujian konfirmasi',
          'revision_no' => 1,
          'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
          'buyer_accepted_at' => now()->subHours(3),
          'seller_accepted_at' => now()->subHours(3),
          'finalized_at' => now()->subHours(3),
        ]);

        return \\App\\Models\\Transaction::create([
          'agreement_id' => $agreement->agreement_id,
          'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
          'listing_id' => $listing->listing_id,
          'status' => \\App\\Enums\\TransactionStatus::DELIVERED,
          'payment_due_at' => now()->subHours(3),
          'delivery_due_at' => now()->subHours(2),
          'delivered_at' => now()->subMinutes($deliveredAgoMinutes),
          'confirmation_due_at' => now()->addMinutes($dueOffsetMinutes),
          'delivery_note' => 'Aktivasi lisensi telah dikirim.',
        ]);
      };

      $tx1 = $makeTx(10, 50); // Transaksi 1: DELIVERED normal (untuk konfirmasi manual)
      $tx2 = $makeTx(65, -5); // Transaksi 2: DELIVERED lewat 1 jam (untuk grace period)
      $tx3 = $makeTx(100, -10); // Transaksi 3: DELIVERED lewat masa tenggang (untuk auto-confirm)

      // Pastikan tx3 sudah tercatat BUYER_GRACE_PERIOD
      \\App\\Models\\TransactionDeadlineEvent::create([
        'transaction_id' => $tx3->transaction_id,
        'event_type' => 'BUYER_GRACE_PERIOD',
        'previous_deadline' => now()->subMinutes(40),
        'new_deadline' => now()->subMinutes(10),
        'extension_minutes' => 30,
        'is_flagged_for_admin' => false,
        'created_at' => now()->subMinutes(40),
      ]);

      echo $tx1->transaction_id . ',' . $tx2->transaction_id . ',' . $tx3->transaction_id;
    `).then((output) => {
      const parts = output.trim().split(',');
      manualTxId = parts[0];
      graceTxId = parts[1];
      autoConfirmTxId = parts[2];
    });
  });

  it('TC-10-01: Konfirmasi Penerimaan Manual oleh Buyer (Penyelesaian Normal)', () => {
    // Login sebagai Buyer
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Buka detail transaksi DELIVERED
    cy.visit(`/transactions/${manualTxId}`);
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');
    cy.get('[data-cy="buyer-confirmation-action-card"]').should('be.visible');

    // Klik tombol konfirmasi penerimaan
    cy.get('[data-cy="btn-confirm-delivery"]').click();
    cy.get('[data-cy="confirm-delivery-modal"]').should('be.visible');

    // Konfirmasi di modal
    cy.get('[data-cy="btn-modal-confirm-submit"]').click();

    // Verifikasi pesan sukses dan status menjadi Dikonfirmasi
    cy.contains('Pesanan berhasil dikonfirmasi').should('be.visible');
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dikonfirmasi');
    cy.get('[data-cy="confirmed-status-banner"]').should('be.visible');
    cy.get('[data-cy="confirmed-status-banner"]').should('contain', 'Siap Payout');
  });

  it('TC-10-02: Scheduler Memberikan Grace Period 30 Menit pada Pembeli Pasif (UC-15B Tahap 1)', () => {
    // Jalankan scheduler command untuk mengaktifkan masa tenggang
    cy.task('execPhp', `
      \\Illuminate\\Support\\Facades\\Artisan::call('transactions:check-confirmation-deadlines');
      $event = \\App\\Models\\TransactionDeadlineEvent::where('transaction_id', ${graceTxId})
        ->where('event_type', 'BUYER_GRACE_PERIOD')
        ->first();
      echo $event ? 'GRACE_OK' : 'GRACE_MISSING';
    `).then((res) => {
      expect(res.trim()).to.eq('GRACE_OK');
    });

    // Login sebagai Buyer dan buka halaman transaksi grace
    cy.visit(`/transactions/${graceTxId}`);
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');
    cy.contains('Masa Tenggang').should('be.visible');
  });

  it('TC-10-03: Scheduler Melakukan Auto-Confirm Sistem Setelah Grace Period Berakhir (UC-15B Tahap 2)', () => {
    // Jalankan scheduler command untuk mengeksekusi auto confirm
    cy.task('execPhp', `
      \\Illuminate\\Support\\Facades\\Artisan::call('transactions:check-confirmation-deadlines');
      $tx = \\App\\Models\\Transaction::find(${autoConfirmTxId});
      echo $tx->status instanceof \\App\\Enums\\TransactionStatus ? $tx->status->value : $tx->status;
    `).then((status) => {
      expect(status.trim()).to.eq('CONFIRMED');
    });

    // Kunjungi halaman transaksi untuk verifikasi banner auto-confirm
    cy.visit(`/transactions/${autoConfirmTxId}`);
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dikonfirmasi');
    cy.get('[data-cy="confirmed-status-banner"]').should('be.visible');
    cy.contains('Otomatis oleh Sistem').should('be.visible');
  });
});
