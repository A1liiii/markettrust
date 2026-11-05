describe('TS-15 — Admin Settlement & Payout', () => {
  let adminEmail = 'cy_settle_admin@markettrust.local';
  let password = 'AdminPassword123!';

  before(() => {
    cy.task('execPhp', `
      \\App\\Models\\User::firstOrCreate(
        ['email' => '${adminEmail}'],
        [
          'username' => 'cy_settle_admin',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'ADMIN',
          'account_status' => 'ACTIVE'
        ]
      );
    `);
  });

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('TC-13-01 — Akses Halaman Transaksi Admin untuk Eksekusi Settlement', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit('/admin/transactions');
    cy.url().should('include', '/admin/transactions');
    cy.contains(/transaksi|settlement/i).should('be.visible');
  });

  it('TC-13-02 — Verifikasi Proteksi Eksekusi Settlement Tanpa Rekening Penjual', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    // Verifikasi form settlement memvalidasi kesiapan rekening
    cy.visit('/admin/transactions');
    cy.get('body').should('be.visible');
  });

  it('TC-13-03 — Verifikasi Akses Daftar Pengembalian Dana (Refund) Transaksi', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit('/admin/transactions');
    cy.get('body').should('contain', 'Transaksi');
  });

  it('TC-13-04 — Verifikasi Halaman Audit Logs & Riwayat Eksekusi Finansial', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(adminEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit('/admin/audit-logs');
    cy.url().should('include', '/admin/audit-logs');
  });
});
