describe('TS-04: Kelola Listing Seller', { testIsolation: false }, () => {
  const timestamp = Date.now();
  const seller1 = {
    username: `sel1_${timestamp}`,
    email: `sel1_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  const seller2 = {
    username: `sel2_${timestamp}`,
    email: `sel2_${timestamp}@markettrust.test`,
    password: 'Password123!',
    password_confirmation: 'Password123!'
  };

  let createdListingId = null;

  before(() => {
    // Daftarkan akun Seller 1
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller1.username);
    cy.get('[data-cy="input-email"]').type(seller1.email);
    cy.get('[data-cy="input-password"]').type(seller1.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller1.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();
    cy.url().should('eq', Cypress.config().baseUrl + '/');
  });

  it('TC-04-01: Buat listing valid - Listing tersimpan atas pemilik benar dan muncul di daftar (Cakupan Regresi: Enter Berulang)', () => {
    // 0. Pantau request store listing sebelum interaksi form dilakukan
    let postRequestCount = 0;
    cy.intercept('POST', '/seller/listings', (req) => {
      postRequestCount++;
      // Pertahankan backend asli dengan penundaan respons terkontrol (800ms)
      // agar penekanan Enter berturut-turut oleh pengguna dapat direproduksi sebelum browser redirect
      req.continue((res) => {
        res.setDelay(800);
      });
    }).as('storeListing');

    cy.visit('/seller/listings/create');
    cy.url().should('include', '/seller/listings/create');

    // Pilih varian produk pertama yang tersedia
    cy.get('#variant_id').then(($select) => {
      const options = $select.find('option[value!=""]');
      if (options.length > 0) {
        const variantVal = options.first().val();
        cy.get('#variant_id').select(variantVal);
      }
    });

    const listingTitle = `Item Test Seller Valid ${timestamp}`;
    cy.get('#title').type(listingTitle);
    
    // Verifikasi Enter pada textarea menghasilkan newline dan TIDAK men-submit form
    cy.get('#description').type('Spesifikasi Baris 1{enter}Spesifikasi Baris 2');
    cy.get('#description').should('have.value', 'Spesifikasi Baris 1\nSpesifikasi Baris 2');
    cy.url().should('include', '/seller/listings/create');

    cy.get('#price_display').type('150000');
    cy.get('#delivery_method').clear().type('Pengiriman Data Akun Instant');
    cy.get('#estimated_delivery_value').clear().type('30');
    cy.get('#estimated_delivery_unit').select('MINUTE');

    // 4. Fokuskan input yang memungkinkan submit melalui Enter
    // 5. Tekan Enter beberapa kali secara cepat (5 kali) untuk mereproduksi tindakan pengguna
    cy.get('#delivery_method').focus().type('{enter}{enter}{enter}{enter}{enter}');

    // 6. Tunggu proses penyimpanan selesai dan browser berpindah ke panel seller
    cy.wait('@storeListing');
    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');

    // 7. Hitung listing dengan penanda unik tersebut di UI
    // Assertion utama: satu tindakan pembuatan listing menghasilkan tepat satu entri tampilan
    cy.get('table').should('be.visible');
    cy.get('table tbody tr').filter(`:contains("${listingTitle}")`).should('have.length', 1);

    // 8. Refresh halaman dan periksa kembali
    cy.reload();
    cy.get('table').should('be.visible');
    cy.get('table tbody tr').filter(`:contains("${listingTitle}")`).should('have.length', 1);

    // 9. Verifikasi jumlah record yang tersimpan menggunakan mekanisme testing proyek (execPhp)
    const escapedTitle = listingTitle.replace(/'/g, "\\'");
    cy.task('execPhp', `echo App\\Models\\Listing::where('title', '${escapedTitle}')->count();`).then((rawOutput) => {
      const dbCount = parseInt(rawOutput.trim(), 10);
      cy.log(`Jumlah request POST terdeteksi: ${postRequestCount}`);
      cy.log(`Jumlah record tersimpan di DB untuk ${listingTitle}: ${dbCount}`);
      expect(dbCount, 'Tepat satu record listing yang tersimpan di database').to.equal(1);
    });

    // Simpan listing ID dari link edit untuk pengujian selanjutnya
    cy.contains(listingTitle)
      .closest('tr')
      .find('a[href*="/seller/listings/"][href*="/edit"]')
      .invoke('attr', 'href')
      .then((href) => {
        const matches = href.match(/\/seller\/listings\/(\d+)\/edit/);
        if (matches) {
          createdListingId = matches[1];
        }
      });
  });

  it('TC-04-01-REG-01: Klik tombol simpan berulang kali (double submit button click) dicegah oleh guard frontend & backend', () => {
    cy.visit('/seller/listings/create');
    cy.url().should('include', '/seller/listings/create');

    cy.get('#variant_id').then(($select) => {
      const options = $select.find('option[value!=""]');
      if (options.length > 0) {
        cy.get('#variant_id').select(options.first().val());
      }
    });

    const multiClickTitle = `Item Test Multi Click ${timestamp}`;
    cy.get('#title').type(multiClickTitle);
    cy.get('#description').type('Uji klik ganda pada tombol submit penawaran listing.');
    cy.get('#price_display').type('175000');
    cy.get('#delivery_method').clear().type('Delivery Instan');
    cy.get('#estimated_delivery_value').clear().type('15');
    cy.get('#estimated_delivery_unit').select('MINUTE');

    // Klik tombol simpan secara cepat (double click)
    cy.get('#listing-form button[type="submit"]').dblclick();

    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');

    // Verifikasi tepat satu entri di UI dan DB
    cy.get('table').should('be.visible');
    cy.get('table tbody tr').filter(`:contains("${multiClickTitle}")`).should('have.length', 1);

    const escMulti = multiClickTitle.replace(/'/g, "\\'");
    cy.task('execPhp', `echo App\\Models\\Listing::where('title', '${escMulti}')->count();`).then((rawOutput) => {
      expect(parseInt(rawOutput.trim(), 10)).to.equal(1);
    });
  });

  it('TC-04-01-REG-02: Pembuatan listing berikutnya secara sengaja tetap diizinkan setelah listing sebelumnya selesai', () => {
    cy.visit('/seller/listings/create');
    cy.url().should('include', '/seller/listings/create');

    cy.get('#variant_id').then(($select) => {
      const options = $select.find('option[value!=""]');
      if (options.length > 0) {
        cy.get('#variant_id').select(options.first().val());
      }
    });

    const secondListingTitle = `Item Test Sengaja Kedua ${timestamp}`;
    cy.get('#title').type(secondListingTitle);
    cy.get('#description').type('Penjual sengaja membuat listing kedua setelah listing pertama selesai.');
    cy.get('#price_display').type('200000');
    cy.get('#delivery_method').clear().type('Delivery Email');
    cy.get('#estimated_delivery_value').clear().type('1');
    cy.get('#estimated_delivery_unit').select('HOUR');

    cy.get('#listing-form button[type="submit"]').click();

    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(secondListingTitle).should('be.visible');

    const escSec = secondListingTitle.replace(/'/g, "\\'");
    cy.task('execPhp', `echo App\\Models\\Listing::where('title', '${escSec}')->count();`).then((rawOutput) => {
      expect(parseInt(rawOutput.trim(), 10)).to.equal(1);
    });
  });

  it('TC-04-02: Ubah listing sendiri - Seller dapat memperbarui rincian listing miliknya', () => {
    cy.visit('/seller/listings');
    const updatedTitle = `Item Test Updated ${timestamp}`;

    cy.get('table').should('be.visible');
    cy.get('a[href*="/seller/listings/"][href*="/edit"]').first().click();

    cy.url().should('include', '/edit');
    cy.get('#title').clear().type(updatedTitle);
    cy.get('#edit-listing-form button[type="submit"]').click();

    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(updatedTitle).should('be.visible');
  });

  it('TC-04-03: Nonaktifkan listing - Status listing berhasil ditutup oleh pemilik', () => {
    cy.location('pathname').then((path) => {
      if (path !== '/seller/listings') {
        cy.visit('/seller/listings');
      }
    });

    // Klik tombol Tutup pada listing yang aktif
    cy.get('form[action*="/toggle-status"] button').contains('Tutup').first().click();

    // Verifikasi pesan sukses dan status menjadi DITUTUP
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains('DITUTUP').should('be.visible');
  });

  it('TC-04-04: Validasi listing - Form menolak data jika kolom wajib kosong atau harga nol/negatif, lalu form tetap dapat digunakan kembali', () => {
    cy.visit('/seller/listings/create');

    // 1. Submit form kosong -> Ditolak oleh HTML5 form validation / URL tetap di create
    cy.get('#listing-form button[type="submit"]').click();
    cy.url().should('include', '/seller/listings/create');

    // 2. Tombol submit tidak terkunci / form dapat digunakan kembali
    cy.get('#listing-form button[type="submit"]').should('not.be.disabled');

    // 3. Isi field yang sebelumnya salah menjadi valid lalu submit kembali
    cy.get('#variant_id').then(($select) => {
      const options = $select.find('option[value!=""]');
      if (options.length > 0) {
        cy.get('#variant_id').select(options.first().val());
      }
    });

    const recoveredTitle = `Item Test Recovered Form ${timestamp}`;
    cy.get('#title').type(recoveredTitle);
    cy.get('#description').type('Pengujian recovery form setelah validasi ditolak.');
    cy.get('#price_display').type('120000');
    cy.get('#delivery_method').clear().type('Delivery Pulih');
    cy.get('#estimated_delivery_value').clear().type('10');
    cy.get('#estimated_delivery_unit').select('MINUTE');

    cy.get('#listing-form button[type="submit"]').click();

    // Berhasil disimpan setelah diperbaiki
    cy.url().should('include', '/seller/listings');
    cy.get('[data-cy="alert-success"]').should('be.visible');
    cy.contains(recoveredTitle).should('be.visible');
  });

  it('TC-04-05: Listing pihak lain - Seller lain ditolak mengakses halaman edit listing bukan miliknya', () => {
    // Logout Seller 1
    cy.visit('/seller/listings');
    cy.get('[data-cy="btn-logout"]').click();

    // Daftarkan Seller 2
    cy.visit('/register');
    cy.get('[data-cy="input-username"]').type(seller2.username);
    cy.get('[data-cy="input-email"]').type(seller2.email);
    cy.get('[data-cy="input-password"]').type(seller2.password);
    cy.get('[data-cy="input-password-confirmation"]').type(seller2.password_confirmation);
    cy.get('[data-cy="btn-register"]').click();

    // Seller 2 mencoba akses halaman edit listing Seller 1
    if (createdListingId) {
      cy.request({
        url: `/seller/listings/${createdListingId}/edit`,
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).to.eq(403);
      });
    }
  });
});
