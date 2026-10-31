describe('MT-A11: Riwayat Transaksi & Audit Log Admin (UC-21 · KF-10)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const admin = {
    username: `adm_mta11_${timestamp}`,
    email: `adm_mta11_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const seller = {
    username: `seller_mta11_${timestamp}`,
    email: `seller_mta11_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const buyer = {
    username: `buyer_mta11_${timestamp}`,
    email: `buyer_mta11_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  const unrelatedUser = {
    username: `other_mta11_${timestamp}`,
    email: `other_mta11_${timestamp}@markettrust.test`,
    password: 'Password123!',
  };

  let txAutoConfirmId;
  let txManualConfirmId;
  let txCancelledKeepId;
  let cancelLogId;
  let autoConfirmLogId;
  let deadlineLogId;
  let payoutLogId;

  before(() => {
    // Siapkan data pengujian secara atomik via task execPhp
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

      $unrelatedUser = \\App\\Models\\User::create([
        'username' => '${unrelatedUser.username}',
        'email' => '${unrelatedUser.email}',
        'password_hash' => bcrypt('${unrelatedUser.password}'),
        'account_type' => 'USER',
        'account_status' => 'ACTIVE',
      ]);

      // Rekening Payout Seller
      \\App\\Models\\SellerPayoutAccount::create([
        'seller_id' => $sellerUser->user_id,
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '7788990011',
        'destination_holder' => 'Seller MT-A11 Test',
        'is_active' => true,
      ]);

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Kategori Digital MT-A11'],
        ['is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Produk Lisensi MT-A11'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Varian Standard MT-A11'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Lisensi Software MT-A11 E2E',
        'description' => 'Lisensi uji riwayat transaksi dan audit log',
        'price' => 100000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 30,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      // 1. Transaksi Auto-Confirm (CONFIRMED oleh sistem, ada deadline event & settlement payout)
      $ag1 = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Auto-Confirm E2E',
        'item_description' => 'Transaksi untuk uji auto-confirm dan settlement',
        'quantity' => 1,
        'base_price' => 100000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 105000.00,
        'seller_net' => 100000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji Auto-Confirm',
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
        'payment_due_at' => now()->subHours(3),
        'delivery_due_at' => now()->subHours(2),
        'delivered_at' => now()->subHours(2),
        'confirmation_due_at' => now()->subHours(1),
        'confirmed_at' => now()->subHours(1),
        'delivery_note' => 'Akses lisensi: E2E-AUTO-CONFIRM-999. Tautan aktif.',
        'result_link' => 'https://example.com/e2e-access',
      ]);

      \\App\\Models\\TransactionStatusHistory::create([
        'transaction_id' => $tx1->transaction_id,
        'from_status' => 'PAID',
        'to_status' => 'DELIVERED',
        'changed_by' => $sellerUser->user_id,
        'changed_at' => now()->subHours(2),
        'note' => 'Seller menyerahkan hasil pesanan',
      ]);

      \\App\\Models\\TransactionStatusHistory::create([
        'transaction_id' => $tx1->transaction_id,
        'from_status' => 'DELIVERED',
        'to_status' => 'CONFIRMED',
        'changed_by' => null, // Auto-confirm sistem
        'changed_at' => now()->subHours(1),
        'note' => 'Dikonfirmasi otomatis oleh sistem (grace period 30 menit berakhir)',
      ]);

      \\App\\Models\\TransactionDeadlineEvent::create([
        'transaction_id' => $tx1->transaction_id,
        'event_type' => 'BUYER_GRACE_PERIOD',
        'previous_deadline' => now()->subHours(1)->subMinutes(30),
        'new_deadline' => now()->subHours(1),
        'extension_minutes' => 30,
        'reason' => 'Masa sanggah 30 menit otomatis sebelum auto-confirm',
        'granted_by' => null,
        'created_at' => now()->subHours(1)->subMinutes(30),
      ]);

      \\App\\Models\\Settlement::create([
        'transaction_id' => $tx1->transaction_id,
        'settlement_type' => 'PAYOUT',
        'status' => 'COMPLETED',
        'gross_amount' => 100000.00,
        'keep_deducted_amount' => 0.00,
        'amount' => 100000.00,
        'destination_type' => 'BANK',
        'destination_provider' => 'BCA',
        'destination_number' => '7788990011',
        'destination_holder' => 'Seller MT-A11 Test',
        'reference_no' => 'TRF-PAYOUT-8899',
        'recorded_by' => $adminUser->user_id,
        'completed_at' => now()->subMinutes(30),
      ]);

      // 2. Transaksi Manual Confirm (CONFIRMED oleh pembeli)
      $ag2 = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Manual-Confirm E2E',
        'item_description' => 'Transaksi uji konfirmasi manual',
        'quantity' => 1,
        'base_price' => 100000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 105000.00,
        'seller_net' => 100000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji Manual Confirm',
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
        'status' => \\App\\Enums\\TransactionStatus::CONFIRMED,
        'payment_due_at' => now()->subHours(2),
        'delivery_due_at' => now()->subHour(),
        'delivered_at' => now()->subMinutes(40),
        'confirmed_at' => now()->subMinutes(10),
        'delivery_note' => 'Lisensi manual token: MANUAL-CONFIRM-TOKEN',
      ]);

      \\App\\Models\\TransactionStatusHistory::create([
        'transaction_id' => $tx2->transaction_id,
        'from_status' => 'DELIVERED',
        'to_status' => 'CONFIRMED',
        'changed_by' => $buyerUser->user_id,
        'changed_at' => now()->subMinutes(10),
        'note' => 'Pembeli mengonfirmasi pesanan sesuai',
      ]);

      // 3. Transaksi Batal + Komplain + Beban KEEP Penjual
      $ag3 = \\App\\Models\\Agreement::create([
        'created_by' => $buyerUser->user_id,
        'buyer_id' => $buyerUser->user_id,
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Lisensi Komplain Batal KEEP E2E',
        'item_description' => 'Transaksi uji komplain dan beban KEEP',
        'quantity' => 1,
        'base_price' => 150000.00,
        'fee_type' => \\App\\Enums\\FeeType::EXCLUDE,
        'fee_amount' => 5000.00,
        'buyer_total' => 155000.00,
        'seller_net' => 150000.00,
        'delivery_method' => 'INSTANT',
        'delivery_within_minutes' => 60,
        'agreement_note' => 'Uji KEEP',
        'revision_no' => 1,
        'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => now(),
        'finalized_at' => now(),
      ]);

      $tx3 = \\App\\Models\\Transaction::create([
        'agreement_id' => $ag3->agreement_id,
        'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
        'listing_id' => $listing->listing_id,
        'status' => \\App\\Enums\\TransactionStatus::CANCELLED,
        'payment_due_at' => now()->subHours(2),
      ]);

      $complaint = \\App\\Models\\Complaint::create([
        'transaction_id' => $tx3->transaction_id,
        'raised_by' => $buyerUser->user_id,
        'reason' => 'Lisensi tidak valid dan akun penjual tidak merespons.',
        'status' => 'RESOLVED',
        'decision' => 'REFUND',
        'decision_reason' => 'Mediasi memenangkan pembeli.',
        'decided_by' => $adminUser->user_id,
        'decided_at' => now()->subMinutes(30),
      ]);

      $cancellation = \\App\\Models\\TransactionCancellation::create([
        'transaction_id' => $tx3->transaction_id,
        'requested_by' => $buyerUser->user_id,
        'stage' => 'AFTER_PAYMENT_BEFORE_DELIVERY',
        'reason' => 'Pembatalan akibat lisensi cacat sesuai hasil mediasi',
        'status' => 'APPROVED',
        'responsible_party' => 'SELLER',
        'reviewed_by' => $adminUser->user_id,
        'decision_note' => 'Persetujuan pembatalan oleh admin',
        'reviewed_at' => now()->subMinutes(20),
      ]);

      $keep = \\App\\Models\\KeepObligation::create([
        'cancellation_id' => $cancellation->cancellation_id,
        'seller_id' => $sellerUser->user_id,
        'payment_channel_snapshot' => 'bank_transfer',
        'calculation_rule' => \\App\\Models\\KeepObligation::RULE_BANK_VA_FLAT,
        'fee_basis_amount' => 4000.00,
        'multiplier' => 3,
        'amount' => 12000.00,
        'remaining_amount' => 12000.00,
        'status' => \\App\\Models\\KeepObligation::STATUS_OPEN,
      ]);

      // 4. Catatan Audit Log
      $log1 = \\App\\Models\\AuditLog::create([
        'actor_id' => $adminUser->user_id,
        'action' => 'TRANSACTION_CANCEL_APPROVED',
        'entity_type' => 'cancellation',
        'entity_id' => $cancellation->cancellation_id,
        'old_value' => ['status' => 'PENDING'],
        'new_value' => ['status' => 'APPROVED', 'decision' => 'APPROVE_REFUND_FULL', 'responsible_party' => 'SELLER'],
        'created_at' => now()->subMinutes(20),
      ]);

      $log2 = \\App\\Models\\AuditLog::create([
        'actor_id' => null, // Sistem
        'action' => 'TRANSACTION_AUTO_CONFIRMED',
        'entity_type' => 'transaction',
        'entity_id' => $tx1->transaction_id,
        'old_value' => ['status' => 'DELIVERED'],
        'new_value' => ['status' => 'CONFIRMED', 'auto_confirmed' => true],
        'created_at' => now()->subHours(1),
      ]);

      $log3 = \\App\\Models\\AuditLog::create([
        'actor_id' => $sellerUser->user_id,
        'action' => 'DELIVERY_DEADLINE_EXTENDED',
        'entity_type' => 'transaction',
        'entity_id' => $tx1->transaction_id,
        'old_value' => ['delivery_due_at' => '2026-10-31 10:00:00'],
        'new_value' => ['delivery_due_at' => '2026-10-31 11:00:00', 'added_minutes' => 60],
        'created_at' => now()->subHours(2),
      ]);

      $log4 = \\App\\Models\\AuditLog::create([
        'actor_id' => $adminUser->user_id,
        'action' => 'RECORD_PAYOUT_COMPLETED',
        'entity_type' => 'settlement',
        'entity_id' => 8899,
        'old_value' => ['status' => 'READY'],
        'new_value' => ['status' => 'COMPLETED', 'transfer_reference' => 'TRF-PAYOUT-8899'],
        'created_at' => now()->subMinutes(15),
      ]);

      echo json_encode([
        'txAutoConfirmId' => $tx1->transaction_id,
        'txManualConfirmId' => $tx2->transaction_id,
        'txCancelledKeepId' => $tx3->transaction_id,
        'cancelLogId' => $log1->log_id,
        'autoConfirmLogId' => $log2->log_id,
        'deadlineLogId' => $log3->log_id,
        'payoutLogId' => $log4->log_id,
      ]);
    `;

    cy.task('execPhp', phpCode).then((output) => {
      const data = JSON.parse(output.trim());
      txAutoConfirmId = data.txAutoConfirmId;
      txManualConfirmId = data.txManualConfirmId;
      txCancelledKeepId = data.txCancelledKeepId;
      cancelLogId = data.cancelLogId;
      autoConfirmLogId = data.autoConfirmLogId;
      deadlineLogId = data.deadlineLogId;
      payoutLogId = data.payoutLogId;
    });
  });

  describe('Alur 1: Detail Transaksi & Audit Stepper (/transactions/{id})', () => {
    it('1.1. Buyer melihat stepper, riwayat status (Sistem Otomatis), lencana Auto-Confirm, masa tenggang, dan nomor rekening tersamar (KNF-06)', () => {
      // Login sebagai Buyer
      cy.loginByForm(buyer.username, buyer.password);

      // Kunjungi transaksi Auto-Confirm
      cy.visit(`/transactions/${txAutoConfirmId}`);

      // Stepper & Status Badge
      cy.get('[data-cy="transaction-title"]').should('contain', `Transaksi #${txAutoConfirmId}`);
      cy.get('[data-cy="transaction-stepper-card"]').should('be.visible');

      // Riwayat Perjalanan Transaksi menampilkan Sistem Otomatis
      cy.contains('Sistem Otomatis').should('be.visible');

      // Lencana Konfirmasi Otomatis oleh Sistem (Auto-Confirm)
      cy.get('[data-cy="confirmation-type-badge"]').should('contain', 'Sistem');
      cy.contains('Pesanan Dikonfirmasi Otomatis oleh Sistem (Auto-Confirm)').should('be.visible');

      // Riwayat Penyesuaian Tenggat Waktu (Deadline Events)
      cy.get('[data-cy="deadline-events-card"]').should('be.visible');
      cy.contains('Masa Tenggang Pembeli').should('be.visible');

      // Rincian Settlement Payout Penjual
      cy.get('[data-cy="settlement-detail-card"]').should('be.visible');
      cy.get('[data-cy="settlement-destination-number"]').should('be.visible');

      // KNF-06 Privasi: Nomor rekening penjual harus tersamar (berisi karakter bintang '*') untuk Buyer
      cy.get('[data-cy="settlement-destination-number"]').should('contain', '*');
      cy.get('[data-cy="settlement-destination-number"]').should('not.contain', '7788990011');

      // Logout Buyer
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('1.2. Seller melihat nomor rekening sendiri secara lengkap tanpa penyamaran pada rincian settlement', () => {
      // Login sebagai Seller
      cy.loginByForm(seller.username, seller.password);

      // Kunjungi transaksi Auto-Confirm
      cy.visit(`/transactions/${txAutoConfirmId}`);

      // Rincian Settlement Payout
      cy.get('[data-cy="settlement-detail-card"]').should('be.visible');

      // Seller pemilik rekening melihat nomor rekening lengkap
      cy.get('[data-cy="settlement-destination-number"]').should('contain', '7788990011');

      // Logout Seller
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('1.3. Buyer melihat lencana konfirmasi manual pada transaksi yang dikonfirmasi sendiri', () => {
      // Login sebagai Buyer
      cy.loginByForm(buyer.username, buyer.password);

      // Kunjungi transaksi konfirmasi manual
      cy.visit(`/transactions/${txManualConfirmId}`);

      // Lencana Konfirmasi Manual oleh Pembeli
      cy.get('[data-cy="confirmation-type-badge"]').should('contain', 'Manual');
      cy.contains('Pesanan Dikonfirmasi Manual oleh Pembeli').should('be.visible');

      // Logout Buyer
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('1.4. Komplain & Pembatalan: Rincian beban kewajiban KEEP seller tampil untuk Seller, tetapi tertutup untuk Buyer', () => {
      // 1. Periksa dari sisi Buyer
      cy.loginByForm(buyer.username, buyer.password);
      cy.visit(`/transactions/${txCancelledKeepId}`);

      // Detail komplain pembeli & alasan tampil
      cy.get('[data-cy="complaint-detail-card"]').should('be.visible');
      cy.get('[data-cy="complaint-reason-text"]').should('contain', 'Lisensi tidak valid');

      // Detail pembatalan tampil
      cy.get('[data-cy="cancellation-detail-card"]').should('be.visible');
      cy.get('[data-cy="responsible-party-badge"]').should('contain', 'Penjual (Seller)');

      // KNF-06 / Isolasi KEEP: Buyer TIDAK boleh melihat kartu beban KEEP internal penjual
      cy.get('[data-cy="seller-keep-obligation-card"]').should('not.exist');

      // Logout Buyer
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();

      // 2. Periksa dari sisi Seller
      cy.loginByForm(seller.username, seller.password);
      cy.visit(`/transactions/${txCancelledKeepId}`);

      // Seller melihat kartu beban kewajiban KEEP dirinya
      cy.get('[data-cy="seller-keep-obligation-card"]').should('be.visible');
      cy.get('[data-cy="keep-status-badge"]').should('contain', 'Belum Lunas (OPEN)');
      cy.get('[data-cy="keep-total-amount"]').should('contain', '12.000');

      // Logout Seller
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });
  });

  describe('Alur 2: Modul Jejak Audit Admin (/admin/audit-logs)', () => {
    it('2.1. Tamu (Guest) dialihkan ke login saat mencoba mengakses /admin/audit-logs', () => {
      cy.visit('/admin/audit-logs');
      cy.url().should('include', '/login');
    });

    it('2.2. Pengguna biasa (Non-Admin) ditolak dengan respon status 403 Forbidden', () => {
      // Login sebagai pengguna biasa
      cy.loginByForm(unrelatedUser.username, unrelatedUser.password);

      // Coba akses URL admin audit logs
      cy.request({
        url: '/admin/audit-logs',
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(403);
      });

      // Logout
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });

    it('2.3. Administrator berhasil mengakses /admin/audit-logs, melihat statistik, tabel log, filter, dan modal JSON payload', () => {
      // Login sebagai Administrator
      cy.loginByForm(admin.username, admin.password);

      // Kunjungi halaman audit logs
      cy.visit('/admin/audit-logs');
      cy.url().should('include', '/admin/audit-logs');

      // Verifikasi Judul & Breadcrumb
      cy.get('[data-cy="audit-logs-title"]').should('be.visible');
      cy.contains('Audit Log & Jejak Aktivitas Sistem').should('be.visible');

      // Verifikasi Kartu Statistik Ringkasan Cepat
      cy.contains('Total Log Tercatat').should('be.visible');
      cy.contains('Aksi Finansial').should('be.visible');
      cy.contains('Tinjauan Pembatalan').should('be.visible');
      cy.contains('Auto-Confirm & SLA').should('be.visible');

      // Verifikasi Baris-Baris Log Tampil di Tabel
      cy.get(`[data-cy="audit-log-row-${cancelLogId}"]`).should('be.visible');
      cy.get(`[data-cy="audit-log-row-${autoConfirmLogId}"]`).should('be.visible');

      // Uji Filter Berdasarkan Jenis Aksi (TRANSACTION_CANCEL_APPROVED)
      cy.get('[data-cy="audit-action-filter"]').select('TRANSACTION_CANCEL_APPROVED');
      cy.get('[data-cy="btn-submit-filter"]').click();

      // Log cancel harus tetap ada, log auto confirm tidak boleh ada di baris tabel
      cy.get(`[data-cy="audit-log-row-${cancelLogId}"]`).should('be.visible');
      cy.get(`[data-cy="audit-log-row-${autoConfirmLogId}"]`).should('not.exist');

      // Reset Filter: Pilih Semua Aksi
      cy.get('[data-cy="audit-action-filter"]').select('');
      cy.get('[data-cy="btn-submit-filter"]').click();
      cy.get(`[data-cy="audit-log-row-${autoConfirmLogId}"]`).should('be.visible');

      // Uji Modal Inspeksi Detail JSON Payload
      cy.get(`[data-cy="btn-inspect-payload-${cancelLogId}"]`).click();

      // Modal harus terbuka
      cy.get('[data-cy="audit-inspect-modal"]').should('not.have.class', 'hidden');
      cy.get('#modal-audit-action').should('contain', 'TRANSACTION_CANCEL_APPROVED');
      cy.get('#modal-audit-new').should('contain', 'APPROVE_REFUND_FULL');
      cy.get('#modal-audit-new').should('contain', 'SELLER');

      // Tutup modal
      cy.get('[data-cy="audit-inspect-modal"] button').contains('Tutup').click();
      cy.get('[data-cy="audit-inspect-modal"]').should('have.class', 'hidden');

      // Logout Admin
      cy.visit('/');
      cy.get('[data-cy="btn-logout"]').click();
    });
  });
});
