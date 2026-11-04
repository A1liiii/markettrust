describe('TS-03 — Rekening Payout Seller', () => {
  const sellerUsername = 'aliseller_ts03';
  const sellerEmail = 'aliseller_ts03@markettrust.test';
  const sellerPassword = 'Password123!';

  before(() => {
    cy.task(
      'execPhp',
      `
      $user = \\App\\Models\\User::updateOrCreate(
          ['username' => '${sellerUsername}'],
          [
              'email' => '${sellerEmail}',
              'password_hash' => '${sellerPassword}',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );
      \\App\\Models\\SellerPayoutAccount::where('seller_id', $user->user_id)->delete();
      echo 'SEEDED_TS03';
      `
    ).should('contain', 'SEEDED_TS03');
  });

  beforeEach(() => {
    cy.loginByForm(sellerUsername, sellerPassword);
  });

  it('TC-02-01: Penambahan Rekening Payout Tipe BANK berhasil disimpan dan menjadi tujuan utama aktif', () => {
    cy.visit('/seller/payout-accounts');
    cy.contains('Pengaturan Rekening Payout').should('be.visible');

    cy.get('[data-cy="btn-create-payout-account"]').click();
    cy.url().should('include', '/seller/payout-accounts/create');

    cy.get('#card-BANK').click();
    cy.get('[data-cy="input-payout-provider"]').clear().type('BCA');
    cy.get('[data-cy="input-payout-number"]').clear().type('8720192831');
    cy.get('[data-cy="input-payout-holder"]').clear().type('Muhammad Ali');
    cy.get('[data-cy="checkbox-payout-active"]').check({ force: true });
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil ditambahkan!').should('be.visible');
    cy.contains('TUJUAN UTAMA AKTIF').should('be.visible');
    cy.contains('BCA').should('be.visible');
    cy.contains('•••• •••• 2831').should('be.visible');
    cy.contains('Muhammad Ali').should('be.visible');
    cy.get('[data-cy="badge-account-active"]').should('have.length', 1);
  });

  it('TC-02-02: Aturan Atomik Hanya 1 Rekening Payout yang Aktif saat menambah EWALLET baru sebagai utama', () => {
    cy.visit('/seller/payout-accounts/create');

    cy.get('#card-EWALLET').click();
    cy.get('[data-cy="input-payout-provider"]').clear().type('GoPay');
    cy.get('[data-cy="input-payout-number"]').clear().type('081234567890');
    cy.get('[data-cy="input-payout-holder"]').clear().type('Muhammad Ali');
    cy.get('[data-cy="checkbox-payout-active"]').check({ force: true });
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil ditambahkan!').should('be.visible');

    // Hanya boleh ada tepat 1 badge Aktif di UI
    cy.get('[data-cy="badge-account-active"]').should('have.length', 1);
    cy.get('[data-cy="payout-account-card"]').should('have.length', 2);
    cy.contains('[data-cy="payout-account-card"]', 'GoPay').should('contain', 'Aktif');
    cy.contains('[data-cy="payout-account-card"]', 'BCA').should('contain', 'Nonaktif');

    // Verifikasi atomik di database: hanya 1 rekening is_active = true milik seller
    cy.task(
      'execPhp',
      `
      $user = \\App\\Models\\User::where('username', '${sellerUsername}')->first();
      $activeCount = \\App\\Models\\SellerPayoutAccount::where('seller_id', $user->user_id)->where('is_active', true)->count();
      echo 'ACTIVE_COUNT:' . $activeCount;
      `
    ).should('contain', 'ACTIVE_COUNT:1');
  });

  it('TC-02-03: Penambahan Rekening Payout Tipe QRIS Wajib Unggah Gambar (Validasi gagal tanpa gambar & berhasil dengan gambar valid)', () => {
    cy.visit('/seller/payout-accounts/create');

    // Subtest A: Submit QRIS tanpa unggah gambar -> ditolak validasi
    cy.get('#card-QRIS').click();
    cy.get('[data-cy="input-payout-provider"]').clear().type('QRIS Merchant');
    cy.get('[data-cy="input-payout-number"]').clear().type('ID1020304050');
    cy.get('[data-cy="input-payout-holder"]').clear().type('Toko Digital Ali');
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.contains('File gambar barcode QRIS wajib diunggah untuk tipe tujuan QRIS.').should('be.visible');

    // Subtest B: Unggah berkas PNG valid -> berhasil disimpan
    const validPngBase64 =
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    cy.get('#card-QRIS').click();
    cy.get('[data-cy="input-payout-qris-file"]').selectFile(
      {
        contents: Cypress.Buffer.from(validPngBase64, 'base64'),
        fileName: 'qris-toko-ali.png',
        mimeType: 'image/png',
      },
      { force: true }
    );
    cy.get('[data-cy="checkbox-payout-active"]').uncheck({ force: true });
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil ditambahkan!').should('be.visible');
    cy.contains('Lihat Gambar Barcode QRIS').should('be.visible');
  });

  it('TC-02-04: Seller Menghapus Rekening Payout Non-Aktif melalui modal konfirmasi tanpa mengganggu rekening utama aktif', () => {
    cy.visit('/seller/payout-accounts');
    cy.get('[data-cy="payout-account-card"]').should('have.length', 3);

    // Cari kartu BCA yang berstatus Nonaktif lalu klik tombol hapus
    cy.contains('[data-cy="payout-account-card"]', 'BCA').within(() => {
      cy.contains('Nonaktif').should('be.visible');
      cy.get('[data-cy="btn-delete-account"]').click();
    });

    cy.get('[data-cy="modal-delete-payout"]').should('be.visible');
    cy.get('#delete-modal-provider').should('contain', 'BCA');
    cy.get('[data-cy="btn-confirm-delete"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil dihapus.').should('be.visible');
    cy.get('[data-cy="payout-account-card"]').should('have.length', 2);
    cy.contains('[data-cy="payout-account-card"]', '•••• •••• 2831').should('not.exist');
    cy.contains('[data-cy="payout-account-card"]', 'BCA').should('not.exist');
    cy.contains('[data-cy="payout-account-card"]', 'GoPay').should('contain', 'Aktif');
  });
});
