describe('TS-20: Refund Simulatif dari Komplain & Pembatalan Pasca-Bayar (MT-A07)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const admin = {
    username: `admin_ref_${timestamp}`,
    email: `admin_ref_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const seller = {
    username: `seller_ref_${timestamp}`,
    email: `seller_ref_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const buyer = {
    username: `buyer_ref_${timestamp}`,
    email: `buyer_ref_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  let txCancelRefundId;
  let txComplaintRefundId;

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

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Refund MT-A07'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Refund MT-A07'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian Refund MT-A07'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Pengujian Refund MT-A07',
        'description' => 'Lisensi pengujian refund simulatif',
        'price' => 1000000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 30,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      // 1. Transaksi dengan Pembatalan Pasca-Bayar APPROVED karena kesalahan SELLER (timbul KEEP Rp 12.000 pada Seller)
      // Buyer tetap menerima refund penuh sebesar buyer_total (Rp 1.010.000)
      $agCancel = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Pesanan Batal Pasca-Bayar #1',
        'item_description' => 'Pembatalan disetujui admin',
        'quantity' => 1,
        'base_price' => 1000000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 10000.00,
        'buyer_total' => 1010000.00,
        'seller_net' => 1000000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Refund dari pembatalan',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txCancel = \\App\\Models\\Transaction::create([
        'agreement_id' => $agCancel->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::CANCELLED,
        'payment_due_at' => now()->subHour(),
      ]);

      \\App\\Models\\Payment::create([
        'transaction_id' => $txCancel->transaction_id,
        'gateway_order_id' => 'ORD-REF-1-' . $txCancel->transaction_id,
        'payment_channel' => 'qris',
        'amount' => 1010000.00,
        'gateway_fee_amount' => 4000.00,
        'status' => 'PAID',
        'gateway_status' => 'settlement',
        'paid_at' => now()->subMinutes(50),
      ]);

      $cancellation = \\App\\Models\\TransactionCancellation::create([
        'transaction_id' => $txCancel->transaction_id,
        'requested_by' => $sellerUser->user_id,
        'stage' => 'AFTER_PAYMENT_BEFORE_DELIVERY',
        'reason' => 'Seller gagal mengirimkan lisensi',
        'status' => 'APPROVED',
        'responsible_party' => 'SELLER',
        'reviewed_by' => $adminUser->user_id,
        'reviewed_at' => now()->subMinutes(20),
      ]);

      \\App\\Models\\KeepObligation::create([
        'cancellation_id' => $cancellation->cancellation_id,
        'seller_id' => $sellerUser->user_id,
        'payment_channel_snapshot' => 'qris',
        'calculation_rule' => 'OTHER_TRIPLE_GATEWAY_FEE',
        'fee_basis_amount' => 4000.00,
        'multiplier' => 3,
        'amount' => 12000.00,
        'remaining_amount' => 12000.00,
        'status' => 'OPEN',
      ]);

      // 2. Transaksi dengan Keputusan Komplain RESOLVED = REFUND
      $agComplaint = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Pesanan Komplain Refund #2',
        'item_description' => 'Keputusan komplain REFUND',
        'quantity' => 1,
        'base_price' => 250000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5753.00,
        'buyer_total' => 255753.00,
        'seller_net' => 250000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Refund dari komplain',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $txComplaint = \\App\\Models\\Transaction::create([
        'agreement_id' => $agComplaint->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::DISPUTED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(40),
      ]);

      \\App\\Models\\Payment::create([
        'transaction_id' => $txComplaint->transaction_id,
        'gateway_order_id' => 'ORD-REF-2-' . $txComplaint->transaction_id,
        'payment_channel' => 'bank_transfer',
        'amount' => 255753.00,
        'gateway_fee_amount' => 4000.00,
        'status' => 'PAID',
        'gateway_status' => 'settlement',
        'paid_at' => now()->subMinutes(80),
      ]);

      \\App\\Models\\Complaint::create([
        'transaction_id' => $txComplaint->transaction_id,
        'raised_by' => $buyerUser->user_id,
        'reason' => 'Kode lisensi sudah pernah dipakai',
        'status' => 'RESOLVED',
        'decision' => 'REFUND',
        'decision_reason' => 'Bukti komplain pembeli valid',
        'decided_by' => $adminUser->user_id,
        'decided_at' => now()->subMinutes(10),
      ]);

      echo json_encode([
        'txCancel' => $txCancel->transaction_id,
        'txComplaint' => $txComplaint->transaction_id,
      ]);
    `;

    const encoded = btoa(phpCode);
    cy.task('execPhp', `eval(base64_decode('${encoded}'));`).then((output) => {
      const parsed = JSON.parse(output.trim());
      txCancelRefundId = parsed.txCancel;
      txComplaintRefundId = parsed.txComplaint;
    });
  });

  it('TC-20-01: Admin mencatat Refund simulatif dari pembatalan pasca-bayar (KEEP kesalahan Seller tidak memotong refund Buyer) dan mengubah status menjadi REFUNDED', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').clear().type(admin.email);
    cy.get('[data-cy="input-password"]').clear().type(admin.password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit('/admin/transactions');

    cy.get(`[data-cy="refund-row-${txCancelRefundId}"]`).should('be.visible');
    cy.get(`[data-cy="refund-source-${txCancelRefundId}"]`).should('contain', 'Pembatalan Pasca-Bayar (UC-28)');
    cy.get(`[data-cy="refund-amount-${txCancelRefundId}"]`).should('contain', 'Rp 1.010.000');

    cy.get(`[data-cy="btn-open-refund-modal-${txCancelRefundId}"]`).click();
    cy.get('[data-cy="modal-record-refund"]').should('be.visible');
    cy.get('[data-cy="select-refund-type"]').select('BANK');
    cy.get('[data-cy="input-refund-provider"]').clear().type('BCA');
    cy.get('[data-cy="input-refund-number"]').clear().type('5544332211');
    cy.get('[data-cy="input-refund-holder"]').clear().type('Buyer Refund BCA');
    cy.get('[data-cy="input-refund-reference"]').clear().type('SIM-REFUND-E2E-001');
    cy.get('[data-cy="btn-submit-refund"]').click();

    cy.get('[data-cy="alert-success"]').should('be.visible').and('contain', 'Rp 1.010.000');
    cy.get(`[data-cy="tx-status-${txCancelRefundId}"]`).should('contain', 'Dana Dikembalikan (Refund)');
  });

  it('TC-20-01 (Komplain): Admin mencatat Refund simulatif dari keputusan komplain REFUND', () => {
    cy.visit('/admin/transactions');

    cy.get(`[data-cy="refund-row-${txComplaintRefundId}"]`).should('be.visible');
    cy.get(`[data-cy="refund-source-${txComplaintRefundId}"]`).should('contain', 'Keputusan Komplain (UC-18)');
    cy.get(`[data-cy="refund-amount-${txComplaintRefundId}"]`).should('contain', 'Rp 255.753');

    cy.get(`[data-cy="btn-open-refund-modal-${txComplaintRefundId}"]`).click();
    cy.get('[data-cy="select-refund-type"]').select('EWALLET');
    cy.get('[data-cy="input-refund-provider"]').clear().type('GoPay');
    cy.get('[data-cy="input-refund-number"]').clear().type('081299887766');
    cy.get('[data-cy="input-refund-holder"]').clear().type('Buyer GoPay');
    cy.get('[data-cy="input-refund-reference"]').clear().type('SIM-REFUND-E2E-002');
    cy.get('[data-cy="btn-submit-refund"]').click();

    cy.get('[data-cy="alert-success"]').should('be.visible').and('contain', 'Rp 255.753');
    cy.get(`[data-cy="tx-status-${txComplaintRefundId}"]`).should('contain', 'Dana Dikembalikan (Refund)');
  });
});
