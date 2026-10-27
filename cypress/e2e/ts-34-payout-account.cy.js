describe('TS-34: Pengaturan Rekening Payout Seller (MT-A01)', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller = {
    username: `seller_${timestamp}`,
    email: `seller_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  before(() => {
    // Registrasi seller baru
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller.username);
    cy.get('[data-cy="input-email"]').type(seller.email);
    cy.get('[data-cy="input-password"]').type(seller.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
  });

  it('TC-34-01: Navigasi ke halaman Rekening Payout dari profil pengguna', () => {
    cy.visit('/profile');
    cy.get('[data-cy="btn-profile-payout-accounts"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Pengaturan Rekening Payout').should('be.visible');
    cy.contains('Belum Ada Rekening Payout').should('be.visible');
  });

  it('TC-34-02: Tambah rekening BANK baru dengan validasi dan jadikan utama', () => {
    cy.visit('/seller/payout-accounts/create');

    // Pilih jenis BANK (default)
    cy.get('#destination_provider').type('BCA');
    cy.get('#destination_number').type('1234567890');
    cy.get('#destination_holder').clear().type('Muhammad Kaye Seller');
    cy.get('[data-cy="btn-submit-payout"]').click();

    // Verifikasi kembali ke halaman index dengan notifikasi sukses
    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil ditambahkan!').should('be.visible');
    cy.contains('BCA').should('be.visible');
    cy.contains('Muhammad Kaye Seller').should('be.visible');
    cy.contains('TUJUAN UTAMA AKTIF').should('be.visible');
  });

  it('TC-34-03: Tambah rekening EWALLET dan lakukan pergantian rekening aktif secara atomik', () => {
    cy.visit('/seller/payout-accounts/create');

    // Pilih jenis EWALLET
    cy.get('#card-EWALLET').click();
    cy.get('#destination_provider').type('GoPay');
    cy.get('#destination_number').type('081234567890');
    cy.get('#destination_holder').clear().type('Kaye Ewallet Account');
    
    // Jangan centang aktifkan utama agar bisa dites pergantian tombol
    cy.get('[data-cy="checkbox-payout-active"]').uncheck();
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('GoPay').should('be.visible');
    cy.contains('Kaye Ewallet Account').should('be.visible');

    // Aktifkan GoPay sebagai tujuan utama
    cy.get('[data-cy="btn-activate-account"]').first().click();

    cy.contains('Rekening payout berhasil diaktifkan sebagai tujuan utama.').should('be.visible');
    cy.contains('TUJUAN UTAMA AKTIF').should('be.visible');
  });

  it('TC-34-04: Edit rekening payout eksisting', () => {
    cy.visit('/seller/payout-accounts');
    cy.get('[data-cy="btn-edit-account"]').first().click();

    cy.url().should('include', '/edit');
    cy.contains('Ubah Tujuan Payout').should('be.visible');

    cy.get('#destination_holder').clear().type('Muhammad Kaye Updated');
    cy.get('[data-cy="btn-submit-payout"]').click();

    cy.url().should('include', '/seller/payout-accounts');
    cy.contains('Rekening payout berhasil diperbarui!').should('be.visible');
    cy.contains('Muhammad Kaye Updated').should('be.visible');
  });
});
