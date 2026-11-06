describe('TS-13: Komplain & Sengketa (Dispute)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `sel_dsp_${timestamp}`,
    email: `sel_dsp_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const buyer = {
    username: `buy_dsp_${timestamp}`,
    email: `buy_dsp_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const admin = {
    username: `adm_dsp_${timestamp}`,
    email: `adm_dsp_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let txPayoutId;
  let txRefundId;
  let complaintPayoutId;
  let complaintRefundId;

  before(() => {
    // 1. Buat User Admin via execPhp
    cy.task('execPhp', `
      \\App\\Models\\User::create([
        'username' => '${admin.username}',
        'email' => '${admin.email}',
        'password_hash' => bcrypt('${admin.password}'),
        'account_type' => 'ADMIN',
        'account_status' => 'ACTIVE',
      ]);
    `);

    // 2. Registrasi Seller
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // 3. Registrasi Buyer
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(buyer.username);
    cy.get('[data-cy="input-email"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="input-password-confirmation"]').type(buyer.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // 4. Buat 2 Transaksi DELIVERED via execPhp (satu untuk alur PAYOUT, satu untuk alur REFUND)
    cy.task('execPhp', `
      $sellerUser = \\App\\Models\\User::where('email', '${seller.email}')->first();
      $buyerUser = \\App\\Models\\User::where('email', '${buyer.email}')->first();

      $cat = \\App\\Models\\ProductCategory::firstOrCreate(['category_name' => 'Kategori Digital TS13'], ['is_active' => true]);
      $prod = \\App\\Models\\Product::firstOrCreate(['product_name' => 'Produk Lisensi TS13'], ['category_id' => $cat->category_id, 'is_active' => true]);
      $var = \\App\\Models\\ProductVariant::firstOrCreate(['variant_name' => 'Varian Lisensi TS13'], ['product_id' => $prod->product_id, 'is_active' => true]);

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $sellerUser->user_id,
        'variant_id' => $var->variant_id,
        'title' => 'Lisensi Game Digital TS13',
        'description' => 'Lisensi resmi pengujian komplain sengketa',
        'price' => 200000.00,
        'delivery_method' => 'INSTANT',
        'estimated_delivery_value' => 60,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE',
      ]);

      $createTx = function($note) use ($buyerUser, $sellerUser, $listing, $var) {
        $agreement = \\App\\Models\\Agreement::create([
          'created_by' => $buyerUser->user_id,
          'buyer_id' => $buyerUser->user_id,
          'seller_id' => $sellerUser->user_id,
          'variant_id' => $var->variant_id,
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
          'agreement_note' => $note,
          'revision_no' => 1,
          'status' => \\App\\Enums\\AgreementStatus::FINALIZED,
          'buyer_accepted_at' => now()->subHours(2),
          'seller_accepted_at' => now()->subHours(2),
          'finalized_at' => now()->subHours(2),
        ]);

        return \\App\\Models\\Transaction::create([
          'agreement_id' => $agreement->agreement_id,
          'source_type' => \\App\\Enums\\TransactionSourceType::MARKETPLACE,
          'listing_id' => $listing->listing_id,
          'status' => \\App\\Enums\\TransactionStatus::DELIVERED,
          'payment_due_at' => now()->subHours(2),
          'delivery_due_at' => now()->subHour(),
          'delivered_at' => now()->subMinutes(15),
          'confirmation_due_at' => now()->addMinutes(45),
          'delivery_note' => 'Lisensi telah dikirim: TEST-KEY-DISPUTE-123',
        ]);
      };

      $tx1 = $createTx('Kesepakatan Payout Sengketa');
      $tx2 = $createTx('Kesepakatan Refund Sengketa');

      echo $tx1->transaction_id . ',' . $tx2->transaction_id;
    `).then((output) => {
      const parts = output.trim().split(',');
      txPayoutId = parts[0];
      txRefundId = parts[1];
    });
  });

  it('TC-11-01: Pengajuan Komplain Sah oleh Buyer saat status DELIVERED (UC-16)', () => {
    // Login sebagai Buyer
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyer.email);
    cy.get('[data-cy="input-password"]').type(buyer.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Buka transaksi payout
    cy.visit(`/transactions/${txPayoutId}`);
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Item Diserahkan');
    cy.get('[data-cy="buyer-complaint-cta-card"]').should('be.visible');

    // Buka modal komplain
    cy.get('[data-cy="btn-open-complaint-modal"]').click();
    cy.get('#complaint-modal').should('be.visible');

    // Isi alasan komplain
    cy.get('[data-cy="input-complaint-reason"]').type('Kunci lisensi tidak valid saat diaktivasi di portal resmi.');

    // Upload berkas bukti
    cy.get('[data-cy="input-complaint-evidence"]').selectFile({
      contents: Cypress.Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', 'base64'),
      fileName: 'bukti_komplain_buyer.png',
      mimeType: 'image/png',
    });

    // Kirim komplain
    cy.get('[data-cy="btn-submit-complaint"]').click();

    // Verifikasi pesan sukses dan status menjadi Dalam Sengketa
    cy.contains('Komplain resmi berhasil diajukan').should('be.visible');
    cy.get('[data-cy="transaction-status-badge"]').should('contain', 'Dalam Sengketa');
    cy.get('[data-cy="dispute-workflow-card"]').should('be.visible');

    // Ambil complaint_id dari database
    cy.task('execPhp', `
      $c = \\App\\Models\\Complaint::where('transaction_id', ${txPayoutId})->first();
      echo $c->complaint_id;
    `).then((id) => {
      complaintPayoutId = id.trim();
    });
  });

  it('TC-11-02: Pencegahan Duplikasi Komplain (Maksimal 1 Komplain per Transaksi)', () => {
    // Pada transaksi yang sudah disengketakan, CTA ajukan komplain tidak lagi muncul
    cy.visit(`/transactions/${txPayoutId}`);
    cy.get('[data-cy="buyer-complaint-cta-card"]').should('not.exist');
    cy.get('[data-cy="btn-open-complaint-modal"]').should('not.exist');
  });

  it('TC-11-03: Seller Mengirimkan Tanggapan dan Bukti Sanggahan (UC-17)', () => {
    // Logout Buyer
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // Login sebagai Seller
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Buka transaksi yang disengketakan
    cy.visit(`/transactions/${txPayoutId}`);
    cy.get('[data-cy="form-seller-respond"]').should('be.visible');

    // Isi tanggapan seller
    cy.get('[data-cy="input-response-text"]').type('Serial key sudah diuji dan valid. Pengguna mungkin salah memasukkan karakter O dengan angka 0.');

    // Upload berkas bukti penjual
    cy.get('[data-cy="input-response-evidence"]').selectFile({
      contents: Cypress.Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', 'base64'),
      fileName: 'bukti_uji_seller.png',
      mimeType: 'image/png',
    });

    // Kirim tanggapan
    cy.get('[data-cy="btn-submit-response"]').click();

    // Verifikasi tanggapan tampil
    cy.contains('Tanggapan komplain berhasil dikirimkan').should('be.visible');
    cy.contains('Serial key sudah diuji dan valid').should('be.visible');
  });

  it('TC-11-04: Admin Meminta Bukti Tambahan (Status REVIEWING)', () => {
    // Logout Seller
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();
    cy.get('[data-cy="nav-login"]').should('be.visible');

    // Login sebagai Admin
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(admin.email);
    cy.get('[data-cy="input-password"]').type(admin.password);
    cy.get('[data-cy="btn-login"]').click();
    cy.url().should('not.include', '/login');

    // Kunjungi halaman detail sengketa admin
    cy.visit(`/admin/disputes/${complaintPayoutId}`);
    cy.contains('Investigasi Sengketa Transaksi').should('be.visible');

    // Kirim permintaan bukti tambahan
    cy.get('[data-cy="form-request-evidence"]').should('be.visible');
    cy.get('[data-cy="form-request-evidence"] textarea[name="reason"]').type('Mohon kedua pihak melampirkan log aktivasi lengkap dari sistem terkait.');
    cy.get('[data-cy="btn-submit-request-evidence"]').click();

    // Verifikasi pesan sukses
    cy.contains('Permintaan bukti tambahan').should('be.visible');
  });

  it('TC-11-05: Keputusan Final Admin Memenangkan Seller (Putusan PAYOUT)', () => {
    cy.visit(`/admin/disputes/${complaintPayoutId}`);

    // Pilih opsi radio PAYOUT
    cy.get('[data-cy="radio-decision-payout"]').check({ force: true });
    cy.get('[data-cy="input-decision-reason"]').type('Berdasarkan investigasi admin, serial key terbukti sah dan telah diaktifkan oleh pembeli.');

    // Kirim putusan resmi
    cy.get('[data-cy="btn-submit-decision"]').click();

    // Verifikasi status sengketa resolved dan antrean payout terdaftar
    cy.contains('Keputusan sengketa berhasil ditetapkan').should('be.visible');
    cy.get('[data-cy="admin-resolved-summary-card"]').should('be.visible');
    cy.contains('PAYOUT').should('be.visible');

    // Verifikasi status transaksi di database menjadi CONFIRMED
    cy.task('execPhp', `
      $tx = \\App\\Models\\Transaction::find(${txPayoutId});
      echo $tx->status instanceof \\App\\Enums\\TransactionStatus ? $tx->status->value : $tx->status;
    `).then((status) => {
      expect(status.trim()).to.eq('CONFIRMED');
    });
  });

  it('TC-11-06: Keputusan Final Admin Memenangkan Buyer (Putusan REFUND)', () => {
    // 1. Buat komplain pada transaksi kedua via execPhp
    cy.task('execPhp', `
      $buyerUser = \\App\\Models\\User::where('email', '${buyer.email}')->first();
      $tx = \\App\\Models\\Transaction::find(${txRefundId});
      $tx->update(['status' => \\App\\Enums\\TransactionStatus::DISPUTED->value]);

      $c = \\App\\Models\\Complaint::create([
        'transaction_id' => $tx->transaction_id,
        'raised_by' => $buyerUser->user_id,
        'reason' => 'Barang rusak tidak dapat diperbaiki dan penjual menolak tanggung jawab.',
        'status' => 'OPEN',
      ]);
      echo $c->complaint_id;
    `).then((id) => {
      complaintRefundId = id.trim();

      // 2. Admin membuka sengketa kedua
      cy.visit(`/admin/disputes/${complaintRefundId}`);

      // Pilih opsi REFUND
      cy.get('[data-cy="radio-decision-refund"]').check({ force: true });
      cy.get('[data-cy="input-decision-reason"]').type('Penjual terbukti menyerahkan produk yang tidak sesuai dengan kesepakatan spesifikasi.');

      // Kirim putusan resmi
      cy.get('[data-cy="btn-submit-decision"]').click();

      // Verifikasi status sengketa resolved REFUND
      cy.contains('Keputusan sengketa berhasil ditetapkan').should('be.visible');
      cy.get('[data-cy="admin-resolved-summary-card"]').should('be.visible');
      cy.contains('REFUND').should('be.visible');

      // Verifikasi status transaksi di database menjadi REFUNDED
      cy.task('execPhp', `
        $tx = \\App\\Models\\Transaction::find(${txRefundId});
        echo $tx->status instanceof \\App\\Enums\\TransactionStatus ? $tx->status->value : $tx->status;
      `).then((status) => {
        expect(status.trim()).to.eq('REFUNDED');
      });
    });
  });
});
