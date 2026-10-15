describe('TS-32: Pengaturan Profil & Keamanan Pengguna', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const userA = {
    username: `prof_a_${timestamp}`,
    email: `prof_a_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const userB = {
    username: `prof_b_${timestamp}`,
    email: `prof_b_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const updatedUsername = `prof_a_upd_${timestamp}`;

  before(() => {
    // 1. Registrasi User B (sebagai pemegang username pembanding)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userB.username);
    cy.get('[data-cy="input-email"]').type(userB.email);
    cy.get('[data-cy="input-password"]').type(userB.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userB.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Logout User B dari halaman utama
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // 2. Registrasi User A (aktor utama pengujian profil)
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(userA.username);
    cy.get('[data-cy="input-email"]').type(userA.email);
    cy.get('[data-cy="input-password"]').type(userA.password);
    cy.get('[data-cy="input-password-confirmation"]').type(userA.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
  });

  it('TC-32-01: Edit profil valid - Mengubah username yang diizinkan dan tersimpan secara persisten', () => {
    cy.visit('/profile/edit');

    cy.get('#username').clear().type(updatedUsername);
    cy.get('button[type="submit"]').contains('Simpan Perubahan').click();

    // Verifikasi dialihkan ke /profile dengan flash message sukses
    cy.url().should('include', '/profile');
    cy.contains('Profil Anda berhasil diperbarui!').should('be.visible');
    cy.contains(`@${updatedUsername}`).should('be.visible');
  });

  it('TC-32-02: Username duplikat - Ditolak jika mencoba memakai username milik pengguna lain', () => {
    cy.visit('/profile/edit');

    cy.get('#username').clear().type(userB.username);
    cy.get('button[type="submit"]').contains('Simpan Perubahan').click();

    // Harus tetap di /profile/edit atau menampilkan pesan error validasi
    cy.contains(/sudah|telah digunakan|terdaftar/i).should('be.visible');
  });

  it('TC-32-03: Username tidak valid - Ditolak jika karakter di luar ketentuan regex atau batas panjang', () => {
    cy.visit('/profile/edit');

    // Uji karakter simbol terlarang
    cy.get('#username').clear().type('invalid*user!');
    cy.get('button[type="submit"]').contains('Simpan Perubahan').click();

    // Browser constraint validation (HTML5 pattern) atau server validation
    cy.get('#username:invalid').should('exist');
  });

  it('TC-32-04: Password kosong saat edit - Password lama tetap berlaku jika kolom password tidak diisi', () => {
    cy.visit('/profile/edit');

    // Kosongkan password baru dan konfirmasi
    cy.get('#password').should('be.empty');
    cy.get('#password_confirmation').should('be.empty');

    cy.get('button[type="submit"]').contains('Simpan Perubahan').click();
    cy.url().should('include', '/profile');

    // Logout dan uji login ulang dengan password lama
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(updatedUsername);
    cy.get('[data-cy="input-password"]').type(userA.password);
    cy.get('[data-cy="btn-login"]').click();

    // Berhasil masuk kembali
    cy.url().should('not.include', '/login');
  });

  it('TC-32-05: Konfirmasi password tidak cocok - Perubahan password ditolak saat konfirmasi berbeda', () => {
    cy.visit('/profile/edit');

    cy.get('#current_password').type(userA.password);
    cy.get('#password').type('NewPassword999!');
    cy.get('#password_confirmation').type('DifferentPassword999!');
    cy.get('button[type="submit"]').contains('Simpan Perubahan').click();

    // Ditolak dengan pesan validasi konfirmasi
    cy.contains(/konfirmasi|cocok|sesuai/i).should('be.visible');
  });

  it('TC-32-07: Profil pihak lain - Tamu (Guest) ditolak membuka halaman edit profil tanpa autentikasi', () => {
    // Logout terlebih dahulu
    cy.visit('/');
    cy.get('[data-cy="btn-logout"]').click();

    // Akses /profile/edit tanpa login
    cy.visit('/profile/edit');
    cy.url().should('include', '/login');
  });
});
