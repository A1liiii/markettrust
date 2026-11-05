describe('TS-14 — Pembatalan & Denda KEEP', () => {
  const buyerUsername = 'alibuyer_ts14';
  const sellerUsername = 'aliseller_ts14';
  const adminUsername = 'aliadmin_ts14';
  const password = 'Password123!';
  let txPrePayId;
  let txPostPayId;

  before(() => {
    cy.task(
      'execPhp',
      `
      $buyer = \\App\\Models\\User::updateOrCreate(
          ['username' => '${buyerUsername}'],
          [
              'email' => 'alibuyer_ts14@markettrust.test',
              'password_hash' => '${password}',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      $seller = \\App\\Models\\User::updateOrCreate(
          ['username' => '${sellerUsername}'],
          [
              'email' => 'aliseller_ts14@markettrust.test',
              'password_hash' => '${password}',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      $admin = \\App\\Models\\User::updateOrCreate(
          ['username' => '${adminUsername}'],
          [
              'email' => 'aliadmin_ts14@markettrust.test',
              'password_hash' => '${password}',
              'email_verified_at' => now(),
              'account_type' => 'ADMIN',
              'account_status' => 'ACTIVE',
          ]
      );

      $cat = \\App\\Models\\ProductCategory::updateOrCreate(
          ['category_name' => 'Lisensi Software TS14'],
          ['is_active' => true]
      );

      $prod = \\App\\Models\\Product::updateOrCreate(
          ['category_id' => $cat->category_id, 'product_name' => 'JetBrains Pack TS14'],
          ['is_active' => true]
      );

      $var = \\App\\Models\\ProductVariant::updateOrCreate(
          ['product_id' => $prod->product_id, 'variant_name' => '1 Tahun Resmi'],
          ['is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $var->variant_id,
          'title' => 'JetBrains All Products 1 Tahun TS14',
          'description' => 'Lisensi resmi aktivasi akun pribadi',
          'price' => 100000,
          'delivery_method' => 'DIRECT_CHAT',
          'estimated_delivery_value' => 30,
          'estimated_delivery_unit' => 'MINUTE',
          'status' => 'ACTIVE',
      ]);

      // 1. Transaksi WAITING_PAYMENT untuk TC-12-01
      $agr1 = \\App\\Models\\Agreement::create([
          'created_by' => $buyer->user_id,
          'buyer_id' => $buyer->user_id,
          'seller_id' => $seller->user_id,
          'variant_id' => $var->variant_id,
          'item_title' => $listing->title,
          'item_description' => $listing->description,
          'quantity' => 1,
          'base_price' => 100000,
          'fee_type' => 'EXCLUDE',
          'fee_amount' => 5000,
          'buyer_total' => 105000,
          'seller_net' => 100000,
          'delivery_method' => 'DIRECT_CHAT',
          'delivery_within_minutes' => 30,
          'revision_no' => 1,
          'status' => 'FINALIZED',
          'buyer_accepted_at' => now(),
          'seller_accepted_at' => now(),
          'finalized_at' => now(),
      ]);

      $tx1 = \\App\\Models\\Transaction::create([
          'agreement_id' => $agr1->agreement_id,
          'source_type' => 'MARKETPLACE',
          'listing_id' => $listing->listing_id,
          'status' => 'WAITING_PAYMENT',
          'payment_due_at' => now()->addHour(),
      ]);

      // 2. Transaksi PAID untuk TC-12-02 & TC-12-03
      $agr2 = \\App\\Models\\Agreement::create([
          'created_by' => $buyer->user_id,
          'buyer_id' => $buyer->user_id,
          'seller_id' => $seller->user_id,
          'variant_id' => $var->variant_id,
          'item_title' => 'JetBrains Post-Payment TS14',
          'item_description' => 'Pesanan sudah dibayar via BCA VA',
          'quantity' => 1,
          'base_price' => 100000,
          'fee_type' => 'EXCLUDE',
          'fee_amount' => 5000,
          'buyer_total' => 105000,
          'seller_net' => 100000,
          'delivery_method' => 'DIRECT_CHAT',
          'delivery_within_minutes' => 30,
          'revision_no' => 1,
          'status' => 'FINALIZED',
          'buyer_accepted_at' => now(),
          'seller_accepted_at' => now(),
          'finalized_at' => now(),
      ]);

      $tx2 = \\App\\Models\\Transaction::create([
          'agreement_id' => $agr2->agreement_id,
          'source_type' => 'MARKETPLACE',
          'listing_id' => $listing->listing_id,
          'status' => 'PAID',
          'payment_due_at' => now()->addHour(),
          'delivery_due_at' => now()->addHour(),
      ]);

      \\App\\Models\\Payment::create([
          'transaction_id' => $tx2->transaction_id,
          'gateway_order_id' => 'MT-CYPRESS-PAID-' . $tx2->transaction_id,
          'payment_channel' => 'bca_va',
          'amount' => 105000,
          'gateway_fee_amount' => 4000,
          'status' => 'PAID',
          'gateway_status' => 'settlement',
          'paid_at' => now()->subMinutes(5),
      ]);

      echo 'TX_IDS:' . $tx1->transaction_id . ',' . $tx2->transaction_id;
      `
    ).then((output) => {
      const match = output.match(/TX_IDS:(\d+),(\d+)/);
      expect(match).to.not.be.null;
      txPrePayId = match[1];
      txPostPayId = match[2];
    });
  });

  it('TC-12-01: Pembatalan Transaksi Pre-Payment oleh Buyer (Sebelum Bayar) langsung mengubah status menjadi CANCELLED', () => {
    cy.loginByForm(buyerUsername, password);
    cy.visit(`/transactions/${txPrePayId}`);

    cy.get('[data-cy="btn-open-cancel-modal"]').should('be.visible').click();
    cy.get('#modal-cancel-transaction').should('be.visible');
    cy.get('#cancel-reason').type('Salah memilih durasi paket langganan.');
    cy.contains('#modal-cancel-transaction button[type="submit"]', 'Ya, Batalkan Pesanan').click();

    cy.get('[data-cy="cancelled-transaction-banner"]').should('be.visible');
    cy.get('[data-cy="cancelled-transaction-banner"]').should('contain', 'Pra-Pembayaran');
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dibatalkan');
  });

  it('TC-12-02: Pengajuan Pembatalan Post-Payment Sebelum Dikirim (Status PAID) wajib mengisi alasan dan masuk antrean Admin', () => {
    cy.loginByForm(buyerUsername, password);
    cy.visit(`/transactions/${txPostPayId}`);

    cy.get('[data-cy="btn-open-cancel-modal"]').should('be.visible').click();
    cy.get('#modal-cancel-transaction').should('be.visible');
    cy.get('#cancel-reason').type('Penjual mengabarkan bahwa stok lisensi sedang habis.');
    cy.contains('#modal-cancel-transaction button[type="submit"]', 'Kirim Pengajuan Pembatalan').click();

    cy.get('[data-cy="pending-cancellation-banner"]').should('be.visible');
    cy.get('[data-cy="pending-cancellation-banner"]').should(
      'contain',
      'Pengajuan Pembatalan Sedang Ditinjau Admin'
    );
    cy.get('[data-cy="pending-cancellation-banner"]').should(
      'contain',
      'Penjual mengabarkan bahwa stok lisensi sedang habis.'
    );
  });

  it('TC-12-03: Admin Meninjau Pembatalan dan Menetapkan Seller Bersalah (Menerbitkan denda KEEP Rp 4.000 berstatus OPEN)', () => {
    cy.loginByForm(adminUsername, password);
    cy.visit('/admin/transactions');

    cy.contains('Antrean Pengajuan Pembatalan Pasca-Bayar').should('be.visible');
    cy.contains('tr', `#TX-${txPostPayId}`).within(() => {
      cy.contains('button', 'Tinjau Keputusan').click();
    });

    cy.get('#modal-review-cancellation').should('be.visible');
    cy.get('#modal-review-cancellation input[name="decision"][value="APPROVED"]').check({ force: true });
    cy.get('#responsible_party').select('SELLER');
    cy.get('#decision_note').type('Disetujui karena penjual kehabisan stok pasca pembayaran.');
    cy.contains('#modal-review-cancellation button[type="submit"]', 'Simpan Keputusan Final').click();

    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.get('[data-cy="section-keep-obligations"]').within(() => {
      cy.contains('tr', sellerUsername).should('be.visible').and('contain', 'Rp 4.000').and('contain', 'OPEN');
    });
  });
});
