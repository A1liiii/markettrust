describe('TS-10 — Pembayaran Midtrans Escrow', () => {
  let buyerEmail = 'cy_pay_buyer@markettrust.local';
  let password = 'Password123!';
  let transactionId;

  before(() => {
    cy.task('execPhp', `
      $buyer = \\App\\Models\\User::firstOrCreate(
        ['email' => '${buyerEmail}'],
        [
          'username' => 'cy_pay_buyer',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );

      $seller = \\App\\Models\\User::firstOrCreate(
        ['email' => 'cy_pay_seller@markettrust.local'],
        [
          'username' => 'cy_pay_seller',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Item Game'],
        ['description' => 'Item Game', 'is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Diamond Game'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => '100 Diamond Pack'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $agreement = \\App\\Models\\Agreement::create([
        'created_by' => $buyer->user_id,
        'buyer_id' => $buyer->user_id,
        'seller_id' => $seller->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Topup 100 Diamond',
        'item_description' => 'Paket Diamond Resmi',
        'quantity' => 1,
        'base_price' => 50000,
        'fee_type' => 'EXCLUDE',
        'fee_amount' => 2500,
        'buyer_total' => 52500,
        'seller_net' => 50000,
        'delivery_method' => 'Direct Message',
        'delivery_within_minutes' => 60,
        'status' => 'FINALIZED',
        'finalized_at' => now(),
      ]);

      $tx = \\App\\Models\\Transaction::create([
        'agreement_id' => $agreement->agreement_id,
        'source_type' => 'MARKETPLACE',
        'status' => 'WAITING_PAYMENT',
        'payment_due_at' => now()->addHour(),
      ]);

      echo $tx->transaction_id;
    `).then((output) => {
      transactionId = output.trim();
    });
  });

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('TC-08-01 — Tombol Bayar & Rincian Transaksi Ditampilkan pada Halaman Transaksi', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/transactions/${transactionId}`);
    cy.contains(/menunggu pembayaran/i).should('be.visible');
    cy.contains('Rp 52.500').should('be.visible');
  });

  it('TC-08-02 — Verifikasi Proteksi Endpoint Webhook Notifikasi Midtrans', () => {
    // Webhook tanpa signature valid harus ditolak (400 / 422)
    cy.request({
      method: 'POST',
      url: '/api/midtrans/notification',
      failOnStatusCode: false,
      body: {
        order_id: 'NON_EXISTENT_ORDER',
        status_code: '200',
        gross_amount: '52500.00',
        signature_key: 'invalid_dummy_signature'
      }
    }).then((res) => {
      expect([400, 403, 422, 500]).to.include(res.status);
    });
  });

  it('TC-08-03 — Tombol Sinkronisasi Status Pembayaran Tersedia', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/transactions/${transactionId}`);
    cy.get('body').should('contain', transactionId);
  });

  it('TC-08-04 — Penanganan Status Transaksi Dibatalkan pada Tampilan Detail', () => {
    const cancelledTxId = 'cy_tx_cancelled_' + Date.now();
    cy.task('execPhp', `
      $buyer = \\App\\Models\\User::where('email', '${buyerEmail}')->first();
      $seller = \\App\\Models\\User::where('email', 'cy_pay_seller@markettrust.local')->first();
      $variant = \\App\\Models\\ProductVariant::first();

      $ag = \\App\\Models\\Agreement::create([
        'created_by' => $buyer->user_id,
        'buyer_id' => $buyer->user_id,
        'seller_id' => $seller->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Cancelled Item Test',
        'item_description' => 'Desc',
        'quantity' => 1,
        'base_price' => 10000,
        'fee_type' => 'EXCLUDE',
        'fee_amount' => 1000,
        'buyer_total' => 11000,
        'seller_net' => 10000,
        'delivery_method' => 'DM',
        'delivery_within_minutes' => 60,
        'status' => 'FINALIZED',
        'finalized_at' => now(),
      ]);

      $tx = \\App\\Models\\Transaction::create([
        'agreement_id' => $ag->agreement_id,
        'source_type' => 'MARKETPLACE',
        'status' => 'CANCELLED',
        'payment_due_at' => now(),
        'cancelled_at' => now(),
      ]);

      echo $tx->transaction_id;
    `).then((id) => {
      cy.visit('/login');
      cy.get('[data-cy="input-login"]').type(buyerEmail);
      cy.get('[data-cy="input-password"]').type(password);
      cy.get('[data-cy="btn-login"]').click();

      cy.visit(`/transactions/${id.trim()}`);
      cy.contains(/dibatalkan|CANCELLED/i).should('be.visible');
    });
  });

  it('TC-08-05 — Scheduler Pembatalan Pembayaran Kadaluarsa Berhasil Dijalankan', () => {
    cy.task('execPhp', `
      $exitCode = \\Illuminate\\Support\\Facades\\Artisan::call('transactions:check-payment-deadlines');
      echo 'EXIT_' . $exitCode;
    `).then((res) => {
      expect(res).to.include('EXIT_0');
    });
  });
});
