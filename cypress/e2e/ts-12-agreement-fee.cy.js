describe('TS-12: Rincian Agreement, Pemisahan Fee Platform, Gateway Aktual, dan KEEP (MT-A17)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `seller_mta17_${timestamp}`,
    email: `seller_mta17_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const buyer = {
    username: `buyer_mta17_${timestamp}`,
    email: `buyer_mta17_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const outsider = {
    username: `outsider_mta17_${timestamp}`,
    email: `outsider_mta17_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  let txMarketplaceId;
  let txForumExId;
  let txForumIncId;

  before(() => {
    const phpCode = `
      $sellerUser = \\App\\Models\\User::create([
        'username' => '${seller.username}',
        'email' => '${seller.email}',
        'password_hash' => bcrypt('${seller.password}'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      $buyerUser = \\App\\Models\\User::create([
        'username' => '${buyer.username}',
        'email' => '${buyer.email}',
        'password_hash' => bcrypt('${buyer.password}'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      $outsiderUser = \\App\\Models\\User::create([
        'username' => '${outsider.username}',
        'email' => '${outsider.email}',
        'password_hash' => bcrypt('${outsider.password}'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital MT-A17'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi MT-A17'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian Enterprise MT-A17'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Enterprise Marketplace MT-A17',
        'description' => 'Lisensi original pengujian pemisahan fee dan KEEP',
        'price' => 1000000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 60,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      $agMp = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => $listing->title,
        'item_description' => $listing->description,
        'quantity' => 1,
        'base_price' => 1000000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 10000.00,
        'buyer_total' => 1010000.00,
        'seller_net' => 1000000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Snapshot Marketplace MT-A17',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txMp = \\App\\Models\\Transaction::create([
        'agreement_id' => $agMp->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::PAID,
        'payment_due_at' => now()->subMinutes(10),
        'delivery_due_at' => now()->addMinutes(50),
      ]);

      \\App\\Models\\Payment::create([
        'transaction_id' => $txMp->transaction_id,
        'gateway_order_id' => 'TRX-MP-' . $txMp->transaction_id,
        'payment_channel' => 'qris',
        'amount' => 1010000.00,
        'gateway_fee_amount' => null,
        'status' => 'PAID',
        'gateway_status' => 'settlement',
        'paid_at' => now(),
      ]);

      $post = \\App\\Models\\ForumPost::create([
        'author_id' => $sellerUser->user_id,
        'category_code' => 'WTS',
        'variant_id' => $variant->variant_id,
        'title' => 'WTS Lisensi Forum MT-A17',
        'content' => 'Penawaran via forum MT-A17',
        'price_offer' => 1000000.00,
        'status' => 'ACTIVE',
      ]);

      $agForumEx = \\App\\Models\\Agreement::create([
        'created_by' => $sellerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Kesepakatan Forum EXCLUDE MT-A17',
        'item_description' => 'Rincian kesepakatan forum mode EXCLUDE',
        'quantity' => 1,
        'base_price' => 1000000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 10000.00,
        'buyer_total' => 1010000.00,
        'seller_net' => 1000000.00,
        'delivery_method' => 'DIRECT_CHAT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Snapshot Forum EX',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txForumEx = \\App\\Models\\Transaction::create([
        'agreement_id' => $agForumEx->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::FORUM,
        'post_id' => $post->post_id,
        'status' => \\App\\Enums\\TransactionStatus::CANCELLED,
        'payment_due_at' => now()->subHour(),
      ]);

      \\App\\Models\\Payment::create([
        'transaction_id' => $txForumEx->transaction_id,
        'gateway_order_id' => 'TRX-FEX-' . $txForumEx->transaction_id,
        'payment_channel' => 'gopay',
        'amount' => 1010000.00,
        'gateway_fee_amount' => 4000.00,
        'status' => 'PAID',
        'gateway_status' => 'settlement',
        'paid_at' => now()->subMinutes(40),
      ]);

      $cancelEx = \\App\\Models\\TransactionCancellation::create([
        'transaction_id' => $txForumEx->transaction_id,
        'requested_by' => $sellerUser->user_id,
        'stage' => 'AFTER_PAYMENT_BEFORE_DELIVERY',
        'reason' => 'Kendala stok mendadak pada seller',
        'status' => 'APPROVED',
        'responsible_party' => 'SELLER',
        'reviewed_at' => now()->subMinutes(20),
      ]);

      $keepEx = \\App\\Models\\KeepObligation::create([
        'cancellation_id' => $cancelEx->cancellation_id,
        'seller_id' => $sellerUser->user_id,
        'payment_channel_snapshot' => 'gopay',
        'calculation_rule' => 'OTHER_TRIPLE_GATEWAY_FEE',
        'fee_basis_amount' => 4000.00,
        'multiplier' => 3,
        'amount' => 12000.00,
        'remaining_amount' => 5000.00,
        'status' => 'OPEN',
      ]);

      $agForumInc = \\App\\Models\\Agreement::create([
        'created_by' => $sellerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Kesepakatan Forum INCLUDE MT-A17',
        'item_description' => 'Rincian kesepakatan forum mode INCLUDE',
        'quantity' => 1,
        'base_price' => 1000000.00,
        'fee_type' => \\App\\Enums\\FeeType::INCLUDE,
        'fee_amount' => 10000.00,
        'buyer_total' => 1000000.00,
        'seller_net' => 990000.00,
        'delivery_method' => 'DIRECT_CHAT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Snapshot Forum INC',
        'revision_no' => 2,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txForumInc = \\App\\Models\\Transaction::create([
        'agreement_id' => $agForumInc->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::FORUM,
        'post_id' => $post->post_id,
        'status' => \\App\\Enums\\TransactionStatus::COMPLETED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(50),
        'confirmed_at' => now()->subMinutes(30),
        'closed_at' => now()->subMinutes(5),
      ]);

      \\App\\Models\\Payment::create([
        'transaction_id' => $txForumInc->transaction_id,
        'gateway_order_id' => 'TRX-FINC-' . $txForumInc->transaction_id,
        'payment_channel' => 'bank_transfer',
        'amount' => 1000000.00,
        'gateway_fee_amount' => 4000.00,
        'status' => 'PAID',
        'gateway_status' => 'settlement',
        'paid_at' => now()->subMinutes(100),
      ]);

      $settlementInc = \\App\\Models\\Settlement::create([
        'transaction_id' => $txForumInc->transaction_id,
        'settlement_type' => 'PAYOUT',
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '8889990001',
        'destination_holder' => 'Seller MT-A17',
        'gross_amount' => 990000.00,
        'keep_deducted_amount' => 7000.00,
        'amount' => 983000.00,
        'status' => 'COMPLETED',
        'reference_no' => 'SIM-PAYOUT-MTA17',
        'completed_at' => now()->subMinutes(5),
      ]);

      \\App\\Models\\KeepDeduction::create([
        'keep_obligation_id' => $keepEx->keep_obligation_id,
        'settlement_id' => $settlementInc->settlement_id,
        'amount' => 7000.00,
        'deducted_at' => now()->subMinutes(5),
      ]);

      $listing->update(['price' => 3500000.00]);

      echo json_encode([
        'mp' => $txMp->transaction_id,
        'fex' => $txForumEx->transaction_id,
        'finc' => $txForumInc->transaction_id,
      ]);
    `;

    const encoded = btoa(phpCode);
    cy.task('execPhp', `eval(base64_decode('${encoded}'));`).then((output) => {
      const parsed = JSON.parse(output.trim());
      txMarketplaceId = parsed.mp;
      txForumExId = parsed.fex;
      txForumIncId = parsed.finc;
    });
  });

  it('TC-12-01 & TC-12-04: Buyer hanya melihat Harga Produk & Total Pembayaran (buyer_total) secara bersih tanpa rincian +fee atau seller_net', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(buyer.email);
    cy.get('[data-cy="input-password"]').clear().type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/transactions/${txMarketplaceId}`);

    cy.get('[data-cy="financial-detail-section"]').should('be.visible');
    cy.get('[data-cy="financial-buyer-card"]').should('be.visible');
    cy.get('[data-cy="buyer-product-price"]').should('contain', 'Rp 1.010.000');
    cy.get('[data-cy="snapshot-buyer-total"]').should('contain', 'Rp 1.010.000');

    // Harga listing baru Rp 3.500.000 maupun rincian +fee / seller_net tidak ditampilkan ke Buyer
    cy.get('[data-cy="financial-buyer-card"]').should('not.contain', 'Rp 3.500.000');
    cy.get('[data-cy="financial-buyer-card"]').should('not.contain', '+ Rp 10.000');
    cy.get('[data-cy="snapshot-seller-net"]').should('not.exist');
  });

  it('TC-12-02: Seller melihat rincian Marketplace (EXCLUDE Tetap, seller_net, biaya gateway belum terverifikasi bukan Rp 0) dan Forum EXCLUDE dengan KEEP OPEN parsial', () => {
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(seller.email);
    cy.get('[data-cy="input-password"]').clear().type(seller.password);
    cy.get('[data-cy="btn-login"]').click();

    // 1. Cek transaksi Marketplace sebagai Seller
    cy.visit(`/transactions/${txMarketplaceId}`);
    cy.get('[data-cy="financial-platform-card"]').should('be.visible');
    cy.get('[data-cy="snapshot-fee-mode"]').should('contain', 'EXCLUDE (Tetap)');
    cy.get('[data-cy="snapshot-base-price"]').should('contain', 'Rp 1.000.000');
    cy.get('[data-cy="snapshot-fee-amount"]').should('contain', '+ Rp 10.000');
    cy.get('[data-cy="snapshot-buyer-total"]').should('contain', 'Rp 1.010.000');
    cy.get('[data-cy="snapshot-seller-net"]').should('contain', 'Rp 1.000.000');
    cy.get('[data-cy="gateway-fee-unverified"]').should('contain', 'Menunggu verifikasi');
    cy.get('[data-cy="gateway-fee-amount"]').should('not.exist');

    // 2. Cek transaksi Forum EXCLUDE sebagai Seller
    cy.visit(`/transactions/${txForumExId}`);
    cy.get('[data-cy="snapshot-fee-mode"]').should('contain', 'EXCLUDE');
    cy.get('[data-cy="snapshot-buyer-total"]').should('contain', 'Rp 1.010.000');
    cy.get('[data-cy="snapshot-seller-net"]').should('contain', 'Rp 1.000.000');
    cy.get('[data-cy="gateway-fee-amount"]').should('contain', 'Rp 4.000');
    cy.get('[data-cy="keep-obligation-status"]').should('contain', 'OPEN (Parsial)');
    cy.get('[data-cy="keep-total-amount"]').should('contain', 'Rp 12.000');
    cy.get('[data-cy="keep-remaining-amount"]').should('contain', 'Rp 5.000');
  });

  it('TC-12-03: Seller melihat Forum INCLUDE (Seller Net Rp 990.000), pemisahan hak kotor vs potongan KEEP pada Settlement Payout, dan sisa KEEP aktif', () => {
    cy.visit(`/transactions/${txForumIncId}`);

    cy.get('[data-cy="snapshot-fee-mode"]').should('contain', 'INCLUDE');
    cy.get('[data-cy="snapshot-base-price"]').should('contain', 'Rp 1.000.000');
    cy.get('[data-cy="snapshot-fee-amount"]').should('contain', '- Rp 10.000');
    cy.get('[data-cy="snapshot-buyer-total"]').should('contain', 'Rp 1.000.000');
    cy.get('[data-cy="snapshot-seller-net"]').should('contain', 'Rp 990.000');

    // Biaya gateway aktual terverifikasi
    cy.get('[data-cy="gateway-fee-amount"]').should('contain', 'Rp 4.000');

    // Rincian Settlement memisahkan gross_amount (Rp 990.000), potongan KEEP (- Rp 7.000), dan net payout (Rp 983.000)
    cy.get('[data-cy="settlement-gross-amount"]').should('contain', 'Rp 990.000');
    cy.get('[data-cy="settlement-keep-deducted"]').should('contain', '- Rp 7.000');
    cy.get('[data-cy="settlement-net-amount"]').should('contain', 'Rp 983.000');
    cy.get('[data-cy="seller-keep-ledger-summary"]').should('be.visible');
    cy.get('[data-cy="seller-other-open-keep-total"]').should('contain', 'Rp 5.000');
  });

  it('TC-12-05: Akun non-pihak ditolak (403) saat mengakses halaman detail kesepakatan transaksi', () => {
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(outsider.email);
    cy.get('[data-cy="input-password"]').clear().type(outsider.password);
    cy.get('[data-cy="btn-login"]').click();

    cy.request({
      url: `/transactions/${txMarketplaceId}`,
      failOnStatusCode: false,
    }).its('status').should('eq', 403);
  });
});
