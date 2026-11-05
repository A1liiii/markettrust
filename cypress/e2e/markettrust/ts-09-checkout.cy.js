describe('TS-09 — Checkout Marketplace', () => {
  let buyerEmail = 'cy_checkout_buyer@markettrust.local';
  let password = 'Password123!';
  let listingId;

  before(() => {
    // Siapkan listing via execPhp
    cy.task('execPhp', `
      $buyer = \\App\\Models\\User::updateOrCreate(
        ['email' => '${buyerEmail}'],
        [
          'username' => 'cy_checkout_buyer',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );

      $seller = \\App\\Models\\User::firstOrCreate(
        ['email' => 'cy_checkout_seller@markettrust.local'],
        [
          'username' => 'cy_checkout_seller',
          'password_hash' => \\Illuminate\\Support\\Facades\\Hash::make('${password}'),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE'
        ]
      );

      $category = \\App\\Models\\ProductCategory::firstOrCreate(
        ['category_name' => 'Akun Game'],
        ['description' => 'Akun Game', 'is_active' => true]
      );

      $product = \\App\\Models\\Product::firstOrCreate(
        ['product_name' => 'Game RPG Online'],
        ['category_id' => $category->category_id, 'is_active' => true]
      );

      $variant = \\App\\Models\\ProductVariant::firstOrCreate(
        ['variant_name' => 'Server Asia Global'],
        ['product_id' => $product->product_id, 'is_active' => true]
      );

      $listing = \\App\\Models\\Listing::create([
        'seller_id' => $seller->user_id,
        'variant_id' => $variant->variant_id,
        'title' => 'Akun Premium Cypress Test',
        'description' => 'Deskripsi testing checkout Cypress',
        'price' => 50000,
        'delivery_method' => 'Kredensial via DM',
        'estimated_delivery_value' => 30,
        'estimated_delivery_unit' => 'MINUTE',
        'status' => 'ACTIVE'
      ]);

      echo $listing->listing_id;
    `).then((output) => {
      listingId = output.trim();
    });
  });

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  it('TC-07-01 — Halaman Review Checkout Menampilkan Pemisahan Rincian Harga Transparan', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/marketplace/${listingId}/checkout`);

    cy.get('[data-cy="checkout-financial-table"]').should('be.visible');
    cy.get('[data-cy="checkout-item-base-price"]').should('be.visible');
    cy.get('[data-cy="checkout-fee-amount"]').should('be.visible');
    cy.get('[data-cy="checkout-buyer-total"]').should('be.visible');
  });

  it('TC-07-02 — Peringatan Edukatif Saat Penjual Belum Mengatur Rekening Payout', () => {
    // Pastikan rekening payout seller dihapus sebelum pengujian
    cy.task('execPhp', `
      $seller = \\App\\Models\\User::where('email', 'cy_checkout_seller@markettrust.local')->first();
      if ($seller) {
        \\App\\Models\\SellerPayoutAccount::where('seller_id', $seller->user_id)->delete();
      }
    `);

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/marketplace/${listingId}/checkout`);
    cy.get('[data-cy="alert-seller-payout-missing"]').should('be.visible');
  });

  it('TC-07-03 — Indikator Siap Saat Penjual Sudah Memiliki Rekening Payout Aktif', () => {
    // Tambahkan rekening aktif untuk seller
    cy.task('execPhp', `
      $seller = \\App\\Models\\User::where('email', 'cy_checkout_seller@markettrust.local')->first();
      \\App\\Models\\SellerPayoutAccount::updateOrCreate(
        ['seller_id' => $seller->user_id],
        [
          'destination_type' => \\App\\Enums\\PayoutDestinationType::BANK,
          'destination_provider' => 'BCA',
          'destination_number' => '1234567890',
          'destination_holder' => 'Seller BCA Test',
          'is_active' => true
        ]
      );
    `);

    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/marketplace/${listingId}/checkout`);
    cy.get('[data-cy="seller-payout-ready"]').should('be.visible');
  });

  it('TC-07-04 — Konfirmasi Checkout Membuat Transaksi Escrow WAITING_PAYMENT', () => {
    cy.visit('/login');
    cy.get('[data-cy="input-login"]').type(buyerEmail);
    cy.get('[data-cy="input-password"]').type(password);
    cy.get('[data-cy="btn-login"]').click();

    cy.visit(`/marketplace/${listingId}/checkout`);
    cy.get('[data-cy="btn-confirm-checkout"]').click();

    cy.url().should('include', '/transactions/');
    cy.contains(/WAITING_PAYMENT|Menunggu Pembayaran/i).should('be.visible');
  });
});

