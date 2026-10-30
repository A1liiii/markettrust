describe('TS-19 & TS-34: Payout Simulatif, Pemotongan KEEP, dan Penanganan Payout Gagal (MT-A07)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const admin = {
    username: `admin_mta07_${timestamp}`,
    email: `admin_mta07_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const seller = {
    username: `seller_mta07_${timestamp}`,
    email: `seller_mta07_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const buyer = {
    username: `buyer_mta07_${timestamp}`,
    email: `buyer_mta07_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  let txConfirmedId;
  let txRetryId;
  let keepId;
  let sellerId;

  before(() => {
    const phpCode = `
      $adminUser = \\App\\Models\\User::create([
        'username' => '${admin.username}',
        'email' => '${admin.email}',
        'password_hash' => bcrypt('${admin.password}'),
        'account_type' => 'ADMIN',
        'account_status' => 'ACTIVE',
      ]);

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

      \\App\\Models\\SellerPayoutAccount::create([
        'seller_id' => $sellerUser->user_id,
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '7788990011',
        'destination_holder' => 'Seller BCA MT-A07',
        'is_active' => true,
      ]);

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital MT-A07'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi MT-A07'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian Pro MT-A07'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Pro Marketplace MT-A07',
        'description' => 'Lisensi pengujian payout simulatif dan pemotongan KEEP',
        'price' => 100000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 30,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      // Transaksi asal yang batal karena kesalahan Seller -> timbul KEEP OPEN Rp 12.000
      $agOrigin = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Transaksi Asal Batal KEEP',
        'item_description' => 'Asal kewajiban KEEP',
        'quantity' => 1,
        'base_price' => 150000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 155000.00,
        'seller_net' => 150000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Asal KEEP',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txOrigin = \\App\\Models\\Transaction::create([
        'agreement_id' => $agOrigin->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::CANCELLED,
        'payment_due_at' => now()->subHour(),
      ]);

      $cancelOrigin = \\App\\Models\\TransactionCancellation::create([
        'transaction_id' => $txOrigin->transaction_id,
        'requested_by' => $sellerUser->user_id,
        'stage' => 'AFTER_PAYMENT_BEFORE_DELIVERY',
        'reason' => 'Stok lisensi habis mendadak',
        'status' => 'APPROVED',
        'responsible_party' => 'SELLER',
        'reviewed_by' => $adminUser->user_id,
        'reviewed_at' => now()->subMinutes(40),
      ]);

      $keep = \\App\\Models\\KeepObligation::create([
        'cancellation_id' => $cancelOrigin->cancellation_id,
        'seller_id' => $sellerUser->user_id,
        'payment_channel_snapshot' => 'qris',
        'calculation_rule' => 'OTHER_TRIPLE_GATEWAY_FEE',
        'fee_basis_amount' => 4000.00,
        'multiplier' => 3,
        'amount' => 12000.00,
        'remaining_amount' => 12000.00,
        'status' => 'OPEN',
      ]);

      // Transaksi 1: CONFIRMED (seller_net = Rp 100.000, akan dipotong KEEP Rp 12.000 -> Net Rp 88.000)
      $ag1 = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Pesanan Siap Payout #1',
        'item_description' => 'Pesanan dikonfirmasi buyer',
        'quantity' => 1,
        'base_price' => 100000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 105000.00,
        'seller_net' => 100000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Siap Payout 1',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx1 = \\App\\Models\\Transaction::create([
        'agreement_id' => $ag1->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::CONFIRMED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(30),
        'confirmed_at' => now()->subMinutes(10),
      ]);

      // Transaksi 2: Keputusan Komplain PAYOUT (untuk uji FAILED lalu Retry SUCCESS pada settlement yang sama)
      $ag2 = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Pesanan Komplain Menang Seller #2',
        'item_description' => 'Keputusan komplain PAYOUT',
        'quantity' => 1,
        'base_price' => 200000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5502.00,
        'buyer_total' => 205502.00,
        'seller_net' => 200000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Siap Payout 2',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx2 = \\App\\Models\\Transaction::create([
        'agreement_id' => $ag2->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::DISPUTED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(50),
      ]);

      \\App\\Models\\Complaint::create([
        'transaction_id' => $tx2->transaction_id,
        'raised_by' => $buyerUser->user_id,
        'reason' => 'Sengketa uji payout',
        'status' => 'RESOLVED',
        'decision' => 'PAYOUT',
        'decision_reason' => 'Bukti penyerahan seller valid',
        'decided_by' => $adminUser->user_id,
        'decided_at' => now()->subMinutes(5),
      ]);

      echo json_encode([
        'tx1' => $tx1->transaction_id,
        'tx2' => $tx2->transaction_id,
        'keep' => $keep->keep_obligation_id,
        'seller' => $sellerUser->user_id,
      ]);
    `;

    const encoded = btoa(phpCode);
    cy.task('execPhp', `eval(base64_decode('${encoded}'));`).then((output) => {
      const parsed = JSON.parse(output.trim());
      txConfirmedId = parsed.tx1;
      txRetryId = parsed.tx2;
      keepId = parsed.keep;
      sellerId = parsed.seller;
    });
  });

  it('TC-19-01 & TC-34-05: Admin mencatat Payout simulatif pada transaksi CONFIRMED dan memfinalkan potongan KEEP OPEN secara atomik', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(admin.email);
    cy.get('[data-cy="input-password"]').clear().type(admin.password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit('/admin/transactions');
    cy.get('[data-cy="admin-transactions-page"]').should('be.visible');

    // Verifikasi pratinjau pada baris transaksi 1: Gross Rp 100.000, Potongan KEEP - Rp 12.000, Net Rp 88.000
    cy.get(`[data-cy="payout-row-${txConfirmedId}"]`).should('be.visible');
    cy.get(`[data-cy="payout-gross-${txConfirmedId}"]`).should('contain', 'Rp 100.000');
    cy.get(`[data-cy="payout-keep-${txConfirmedId}"]`).should('contain', '- Rp 12.000');
    cy.get(`[data-cy="payout-net-${txConfirmedId}"]`).should('contain', 'Rp 88.000');

    // Buka modal Payout & catat sebagai SUCCESS
    cy.get(`[data-cy="btn-open-payout-modal-${txConfirmedId}"]`).click();
    cy.get('[data-cy="modal-record-payout"]').should('be.visible');
    cy.get('[data-cy="input-payout-reference"]').clear().type('SIM-PAYOUT-E2E-001');
    cy.get('[data-cy="input-payout-note"]').clear().type('Payout berhasil dipotong KEEP Rp 12.000');
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.get('[data-cy="alert-success"]').should('be.visible').and('contain', 'Rp 88.000');
    cy.get(`[data-cy="keep-status-${keepId}"]`).should('contain', 'SETTLED');
    cy.get(`[data-cy="keep-remaining-${keepId}"]`).should('contain', 'Rp 0');
  });

  it('TC-19-02, TC-34-01 & TC-34-02: Admin mencatat percobaan Payout FAILED pada settlement_attempts, mengirim pengumuman gangguan, lalu Retry SUCCESS pada settlement yang sama', () => {
    cy.visit('/admin/transactions');

    // 1. Catat percobaan FAILED karena tujuan Seller ditolak
    cy.get(`[data-cy="btn-open-payout-modal-${txRetryId}"]`).click();
    cy.get('[data-cy="radio-outcome-failed"]').check({ force: true });
    cy.get('[data-cy="select-failure-category"]').select('SELLER_DESTINATION');
    cy.get('[data-cy="input-failure-reason"]').clear().type('Rekening tujuan tidak ditemukan saat simulasi transfer');
    cy.get('[data-cy="checkbox-announce-disruption"]').check({ force: true });
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.get('[data-cy="alert-success"]').should('be.visible').and('contain', 'Percobaan payout #1 (FAILED)');
    cy.get(`[data-cy="payout-attempt-badge-${txRetryId}"]`).should('contain', 'Attempt #1: FAILED');

    // 2. Sinkronkan tujuan & lakukan Retry SUCCESS pada settlement yang sama
    cy.get(`[data-cy="btn-sync-destination-${txRetryId}"]`).click();
    cy.get('[data-cy="alert-success"]').should('be.visible');

    cy.get(`[data-cy="btn-open-payout-modal-${txRetryId}"]`).click();
    cy.get('[data-cy="radio-outcome-success"]').check({ force: true });
    cy.get('[data-cy="input-payout-reference"]').clear().type('SIM-PAYOUT-RETRY-E2E-002');
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.get('[data-cy="alert-success"]').should('be.visible').and('contain', 'Rp 200.000');
    cy.get(`[data-cy="tx-status-${txRetryId}"]`).should('contain', 'Pesanan Selesai');
  });

  it('TC-19-04 & TC-34-06: Buyer ditolak (403) saat mengakses halaman operasional Admin atau ledger KEEP milik Seller', () => {
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(buyer.email);
    cy.get('[data-cy="input-password"]').clear().type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();

    cy.request({
      url: '/admin/transactions',
      failOnStatusCode: false,
    }).its('status').should('eq', 403);

    cy.request({
      url: `/sellers/${sellerId}/keep-ledger`,
      failOnStatusCode: false,
    }).its('status').should('eq', 403);
  });
});
