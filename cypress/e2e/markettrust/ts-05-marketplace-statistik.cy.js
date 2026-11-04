describe('TS-05 — Marketplace & Statistik Pasar', () => {
  let listingCanva1MId;

  before(() => {
    cy.task(
      'execPhp',
      `
      \\Illuminate\\Support\\Facades\\DB::table('transactions')->delete();
      \\Illuminate\\Support\\Facades\\DB::table('agreements')->delete();
      \\Illuminate\\Support\\Facades\\DB::table('forum_posts')->delete();
      \\Illuminate\\Support\\Facades\\DB::table('listings')->delete();

      $seller = \\App\\Models\\User::updateOrCreate(
          ['username' => 'aliseller_ts05'],
          [
              'email' => 'aliseller_ts05@markettrust.test',
              'password_hash' => 'Password123!',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      $buyer = \\App\\Models\\User::updateOrCreate(
          ['username' => 'alibuyer_ts05'],
          [
              'email' => 'alibuyer_ts05@markettrust.test',
              'password_hash' => 'Password123!',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      $catA = \\App\\Models\\ProductCategory::updateOrCreate(
          ['category_name' => 'Desain & Produktivitas TS05'],
          ['description' => 'Kategori uji TS-05', 'is_active' => true]
      );

      $catB = \\App\\Models\\ProductCategory::updateOrCreate(
          ['category_name' => 'Hiburan & Streaming TS05'],
          ['description' => 'Kategori kedua uji TS-05', 'is_active' => true]
      );

      $prodCanva = \\App\\Models\\Product::updateOrCreate(
          ['category_id' => $catA->category_id, 'product_name' => 'Canva Pro TS05'],
          ['description' => 'Produk Canva uji TS-05', 'is_active' => true]
      );

      $prodNetflix = \\App\\Models\\Product::updateOrCreate(
          ['category_id' => $catB->category_id, 'product_name' => 'Netflix UHD TS05'],
          ['description' => 'Produk Netflix uji TS-05', 'is_active' => true]
      );

      $varCanva1M = \\App\\Models\\ProductVariant::updateOrCreate(
          ['product_id' => $prodCanva->product_id, 'variant_name' => '1 Bulan Private'],
          ['is_active' => true]
      );

      $varCanva1Y = \\App\\Models\\ProductVariant::updateOrCreate(
          ['product_id' => $prodCanva->product_id, 'variant_name' => '1 Tahun Private'],
          ['is_active' => true]
      );

      $varNetflix1M = \\App\\Models\\ProductVariant::updateOrCreate(
          ['product_id' => $prodNetflix->product_id, 'variant_name' => '1 Bulan 1 Profil'],
          ['is_active' => true]
      );

      $l1 = \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $varCanva1M->variant_id,
          'title' => 'Akun Canva 1 Bulan Murah Bergaransi',
          'description' => 'Akun private full garansi 30 hari',
          'price' => 20000,
          'delivery_method' => 'INSTANT',
          'estimated_delivery_value' => 15,
          'estimated_delivery_unit' => 'MINUTE',
          'status' => 'ACTIVE',
      ]);

      \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $varCanva1Y->variant_id,
          'title' => 'Akun Canva 1 Tahun Private Eksklusif',
          'description' => 'Akun resmi 1 tahun penuh',
          'price' => 150000,
          'delivery_method' => 'DIRECT_CHAT',
          'estimated_delivery_value' => 1,
          'estimated_delivery_unit' => 'HOUR',
          'status' => 'ACTIVE',
      ]);

      \\App\\Models\\Listing::create([
          'seller_id' => $seller->user_id,
          'variant_id' => $varNetflix1M->variant_id,
          'title' => 'Akun Netflix Premium 1 Bulan UHD',
          'description' => 'Streaming lancar 4K',
          'price' => 45000,
          'delivery_method' => 'INSTANT',
          'estimated_delivery_value' => 10,
          'estimated_delivery_unit' => 'MINUTE',
          'status' => 'ACTIVE',
      ]);

      \\App\\Models\\ForumPost::create([
          'author_id' => $buyer->user_id,
          'variant_id' => $varCanva1M->variant_id,
          'category_code' => 'WTB',
          'title' => 'WTB Canva Pro 1 Bulan Butuh Cepat',
          'content' => 'Dicari harga bersahabat',
          'expected_price' => 20000,
          'status' => 'ACTIVE',
      ]);

      \\App\\Models\\ForumPost::create([
          'author_id' => $seller->user_id,
          'variant_id' => $varCanva1M->variant_id,
          'category_code' => 'WTS',
          'title' => 'WTS Canva Pro 1 Bulan Ready',
          'content' => 'Stok banyak siap kirim',
          'expected_price' => 22000,
          'status' => 'ACTIVE',
      ]);

      $createTx = function ($variantId, $price, $status, $daysAgo) use ($buyer, $seller) {
          $ts = now()->subDays($daysAgo);
          $agr = \\App\\Models\\Agreement::create([
              'created_by' => $buyer->user_id,
              'buyer_id' => $buyer->user_id,
              'seller_id' => $seller->user_id,
              'variant_id' => $variantId,
              'item_title' => 'Item Statistik',
              'item_description' => 'Deskripsi item statistik',
              'quantity' => 1,
              'base_price' => $price,
              'fee_type' => 'EXCLUDE',
              'fee_amount' => 1000,
              'buyer_total' => $price + 1000,
              'seller_net' => $price,
              'delivery_method' => 'INSTANT',
              'delivery_within_minutes' => 15,
              'revision_no' => 1,
              'status' => 'FINALIZED',
              'buyer_accepted_at' => $ts,
              'seller_accepted_at' => $ts,
              'finalized_at' => $ts,
          ]);
          $tx = \\App\\Models\\Transaction::create([
              'agreement_id' => $agr->agreement_id,
              'source_type' => 'MARKETPLACE',
              'status' => $status,
              'payment_due_at' => now()->addHour(),
          ]);
          \\Illuminate\\Support\\Facades\\DB::table('transactions')
              ->where('transaction_id', $tx->transaction_id)
              ->update(['created_at' => $ts, 'updated_at' => $ts]);
      };

      $createTx($varCanva1M->variant_id, 20000, 'COMPLETED', 3);
      $createTx($varCanva1M->variant_id, 30000, 'COMPLETED', 20);
      $createTx($varCanva1M->variant_id, 40000, 'COMPLETED', 60);
      $createTx($varCanva1M->variant_id, 50000, 'COMPLETED', 120);
      $createTx($varCanva1M->variant_id, 99000, 'CANCELLED', 2);

      echo 'LISTING_ID:' . $l1->listing_id;
      `
    ).then((output) => {
      const match = output.match(/LISTING_ID:(\d+)/);
      expect(match).to.not.be.null;
      listingCanva1MId = match[1];
    });
  });

  it('TC-04-01: Pencarian kata kunci, filter kategori/varian, dan pengurutan harga pada katalog Marketplace', () => {
    cy.visit('/marketplace');
    cy.get('[data-cy="marketplace-grid"]').should('be.visible');

    // 1. Pencarian kata kunci "Canva"
    cy.get('[data-cy="input-search-marketplace"]').clear().type('Canva');
    cy.get('[data-cy="btn-submit-search"]').click();

    cy.contains('Akun Canva 1 Bulan Murah Bergaransi').should('be.visible');
    cy.contains('Akun Canva 1 Tahun Private Eksklusif').should('be.visible');
    cy.contains('Akun Netflix Premium 1 Bulan UHD').should('not.exist');

    // 2. Reset filter dan uji pengurutan harga terendah (price_asc)
    cy.get('[data-cy="btn-reset-filter"]').click();
    cy.get('[data-cy="select-sort"]').select('price_asc');
    cy.url().should('include', 'sort=price_asc');
    cy.get('[data-cy="marketplace-grid"] > a').first().should('contain', 'Akun Canva 1 Bulan Murah Bergaransi');

    // 3. Pengurutan harga tertinggi (price_desc)
    cy.get('[data-cy="select-sort"]').select('price_desc');
    cy.url().should('include', 'sort=price_desc');
    cy.get('[data-cy="marketplace-grid"] > a').first().should('contain', 'Akun Canva 1 Tahun Private Eksklusif');
  });

  it('TC-04-02: Filter Periode Statistik Pasar pada Detail Produk (UC-05) untuk 7d, 30d, 90d (Gap Docs), dan Semua Waktu', () => {
    cy.visit(`/marketplace/${listingCanva1MId}`);
    cy.contains('Informasi Pasar Produk').should('be.visible');

    // Default: 30 Hari Terakhir -> Demand = 1, Supply = 2, Avg = Rp 25.000 (2 transaksi selesai)
    cy.get('[data-cy="market-stat-period-label"]').should('contain', 'Periode: 30 Hari Terakhir');
    cy.get('[data-cy="market-stat-demand"]').should('contain', '1');
    cy.get('[data-cy="market-stat-supply"]').should('contain', '2');
    cy.get('[data-cy="market-stat-avg-price"]').should('contain', 'Rp 25.000');
    cy.get('[data-cy="market-stat-sample-count"]').should('contain', 'Berdasarkan 2 transaksi selesai');

    // Klik Filter 7 Hari -> Avg = Rp 20.000 (1 transaksi selesai)
    cy.get('[data-cy="market-stat-filter-7d"]').click();
    cy.url().should('include', 'period=7d');
    cy.get('[data-cy="market-stat-period-label"]').should('contain', 'Periode: 7 Hari Terakhir');
    cy.get('[data-cy="market-stat-avg-price"]').should('contain', 'Rp 20.000');
    cy.get('[data-cy="market-stat-sample-count"]').should('contain', 'Berdasarkan 1 transaksi selesai');

    // Klik Filter 90 Hari (Catatan Gap: tersedia di UI/Service) -> Avg = Rp 30.000 (3 transaksi selesai)
    cy.get('[data-cy="market-stat-filter-90d"]').click();
    cy.url().should('include', 'period=90d');
    cy.get('[data-cy="market-stat-period-label"]').should('contain', 'Periode: 90 Hari Terakhir');
    cy.get('[data-cy="market-stat-avg-price"]').should('contain', 'Rp 30.000');
    cy.get('[data-cy="market-stat-sample-count"]').should('contain', 'Berdasarkan 3 transaksi selesai');

    // Klik Filter Semua Waktu (all) -> Avg = Rp 35.000 (4 transaksi selesai, transaksi CANCELLED diabaikan)
    cy.get('[data-cy="market-stat-filter-all"]').click();
    cy.url().should('include', 'period=all');
    cy.get('[data-cy="market-stat-period-label"]').should('contain', 'Periode: Semua Waktu');
    cy.get('[data-cy="market-stat-avg-price"]').should('contain', 'Rp 35.000');
    cy.get('[data-cy="market-stat-sample-count"]').should('contain', 'Berdasarkan 4 transaksi selesai');
  });
});
