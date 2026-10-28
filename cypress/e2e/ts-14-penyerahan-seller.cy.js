describe('TS-14: Penyerahan Hasil Resmi oleh Seller & Peringatan Tenggat (MT-A03)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `seller_del_${timestamp}`,
    email: `seller_del_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buyer_del_${timestamp}`,
    email: `buyer_del_${timestamp}@markettrust.test`,
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

    // 3. Buat transaksi PAID via tinker
    cy.task('execPhp', `
      $buyerUser = \\App\\Models\\User::where('username', '${buyer.username}')->first();
      $sellerUser = \\App\\Models\\User::where('username', '${seller.username}')->first();

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital Test'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi E2E'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian E2E Lisensi'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Software Digital Test E2E',
        'description' => 'Lisensi original untuk penyerahan seller',
        'price' => 200000.00,
        'delivery_method' => 'INSTANT',
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
        'base_price' => 200000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 205000.00,
        'seller_net' => 200000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Kesepakatan pengujian E2E',
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

      echo $tx->transaction_id;
    `).then((output) => {
      transactionId = output.trim();
    });
  });

  it('TC-14-01: Seller melihat formulir penyerahan hasil pada transaksi PAID', () => {
    // Login sebagai Seller
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="btn-login"]').click();

    // Buka halaman detail transaksi
    cy.visit(`/transactions/${transactionId}`);

    // Pastikan kartu form penyerahan tampil
    cy.get('[data-cy="seller-delivery-form-card"]').should('be.visible');
    cy.contains('Form Penyerahan Hasil Resmi').should('be.visible');
    cy.contains('Khusus Penjual').should('be.visible');
    cy.get('[data-cy="input-delivery-note"]').should('be.visible');
    cy.get('[data-cy="input-result-link"]').should('be.visible');
    cy.get('[data-cy="input-evidence-file"]').should('be.visible');
    cy.get('[data-cy="btn-submit-delivery"]').should('be.visible');
  });

  it('TC-14-02: Seller menyerahkan hasil resmi pesanan dan status beralih ke DELIVERED', () => {
    cy.visit(`/transactions/${transactionId}`);

    // Isi formulir penyerahan
    cy.get('[data-cy="input-delivery-note"]').type('Kunci Lisensi: MT-PRO-E2E-KEY-2026\nSilakan aktivasi pada portal resmi.');
    cy.get('[data-cy="input-result-link"]').type('https://example.com/assets/download-key');

    // Submit penyerahan
    cy.get('[data-cy="btn-submit-delivery"]').click();

    // Verifikasi notifikasi sukses dan status transaksi
    cy.contains('Hasil resmi transaksi berhasil diserahkan').should('be.visible');
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');

    // Verifikasi panel ringkasan hasil tampil
    cy.get('[data-cy="delivered-summary-card"]').should('be.visible');
    cy.get('[data-cy="display-delivery-note"]').should('contain', 'MT-PRO-E2E-KEY-2026');
    cy.get('[data-cy="confirmation-due-text"]').should('be.visible');
    cy.get('[data-cy="link-result-external"]').should('be.visible');
  });

  it('TC-14-03: Buyer melihat hasil resmi yang diserahkan dan batas waktu konfirmasi 1 jam', () => {
    // Logout Seller
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // Login sebagai Buyer
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();

    // Buka transaksi
    cy.visit(`/transactions/${transactionId}`);

    // Buyer melihat panel hasil penyerahan
    cy.get('[data-cy="delivered-summary-card"]').should('be.visible');
    cy.get('[data-cy="display-delivery-note"]').should('contain', 'MT-PRO-E2E-KEY-2026');
    cy.get('[data-cy="confirmation-due-text"]').should('be.visible');

    // Formulir input seller tidak boleh tampil untuk Buyer
    cy.get('[data-cy="seller-delivery-form-card"]').should('not.exist');
  });
});
