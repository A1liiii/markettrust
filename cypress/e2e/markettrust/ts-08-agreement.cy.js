describe('TS-08 — Kesepakatan Forum (Agreement)', () => {
  beforeEach(() => {
    cy.task('execPhp', `
      use App\\Models\\User;
      use App\\Models\\ProductCategory;
      use App\\Models\\Product;
      use App\\Models\\ProductVariant;
      use App\\Models\\ForumPost;
      use App\\Models\\Conversation;
      use App\\Models\\Agreement;
      use Illuminate\\Support\\Facades\\Hash;

      $seller = User::firstOrCreate(
        ['email' => 'seller_agr@markettrust.test'],
        [
          'username' => 'agr_seller',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $buyer = User::firstOrCreate(
        ['email' => 'buyer_agr@markettrust.test'],
        [
          'username' => 'agr_buyer',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $category = ProductCategory::firstOrCreate(
        ['category_name' => 'Akun Game'],
        ['is_active' => true]
      );

      $product = Product::firstOrCreate(
        ['product_name' => 'Mobile Legends', 'category_id' => $category->category_id],
        ['is_active' => true]
      );

      $variant = ProductVariant::firstOrCreate(
        ['variant_name' => 'Mythic Glory 100+ Stars', 'product_id' => $product->product_id],
        ['is_active' => true]
      );

      $post = ForumPost::firstOrCreate(
        ['title' => 'WTS: Akun MLBB Spesial Agreement Test'],
        [
          'author_id' => $seller->user_id,
          'category_code' => 'WTS',
          'variant_id' => $variant->variant_id,
          'content' => 'Item kesepakatan uji DM',
          'price_offer' => 100000,
          'status' => 'ACTIVE',
        ]
      );

      $conversation = Conversation::firstOrCreate([
        'participant_one_id' => min($buyer->user_id, $seller->user_id),
        'participant_two_id' => max($buyer->user_id, $seller->user_id),
        'post_id' => $post->post_id,
      ]);

      // Buat draft kesepakatan Rev 1 diajukan oleh buyer
      $agreement = Agreement::create([
        'created_by' => $buyer->user_id,
        'buyer_id' => $buyer->user_id,
        'seller_id' => $seller->user_id,
        'variant_id' => $variant->variant_id,
        'item_title' => 'Akun MLBB All Skin Collector Final',
        'item_description' => 'Akun siap serah terima dalam waktu 60 menit',
        'quantity' => 1,
        'base_price' => 100000,
        'fee_type' => 'EXCLUDE',
        'fee_amount' => 3000,
        'buyer_total' => 103000,
        'seller_net' => 100000,
        'delivery_method' => 'DIRECT_CHAT',
        'delivery_within_minutes' => 60,
        'revision_no' => 1,
        'status' => 'DRAFT',
        'buyer_accepted_at' => now(),
        'seller_accepted_at' => null,
      ]);

      $conversation->update(['agreement_id' => $agreement->agreement_id]);

      echo json_encode([
        'conversation_id' => $conversation->conversation_id,
        'variant_id' => $variant->variant_id,
        'buyer_username' => $buyer->username,
        'seller_username' => $seller->username,
      ]);
    `).as('seedData');
  });

  it('TC-06-01 — Pembuatan Usulan Kesepakatan (Agreement Draft) dari DM', function () {
    // Siapkan percakapan kosong tanpa agreement
    cy.task('execPhp', `
      use App\\Models\\Conversation;
      $c = Conversation::find(${JSON.parse(this.seedData).conversation_id});
      $c->update(['agreement_id' => null]);
      echo json_encode(['conversation_id' => $c->conversation_id]);
    `);

    const data = JSON.parse(this.seedData);
    cy.loginByForm('agr_buyer', 'password123');
    cy.visit(`/conversations/${data.conversation_id}`);

    cy.get('#btn-open-propose-modal').should('be.visible').click();
    cy.get('#modal-propose-agreement').should('be.visible');
    cy.get('#propose-variant').select(String(data.variant_id));
    cy.get('#propose-item-title').clear().type('Akun MLBB All Skin Collector Final');
    cy.get('#propose-item-desc').clear().type('Akun siap serah terima dalam waktu 60 menit');
    cy.get('#propose-base-price').clear().type('100000');
    cy.get('#modal-propose-agreement form').submit();

    // Verifikasi lembar kesepakatan muncul dengan nomor revisi
    cy.contains('Lembar Kesepakatan Transaksi').should('be.visible');
    cy.contains('Rev #1').should('be.visible');
  });

  it('TC-06-02 — Revisi Usulan Kesepakatan Menaikkan Nomor Revisi', function () {
    const data = JSON.parse(this.seedData);
    // Seller meninjau usulan Rev #1 dan melakukan revisi harga
    cy.loginByForm('agr_seller', 'password123');
    cy.visit(`/conversations/${data.conversation_id}`);

    cy.get('#btn-open-revise-modal').should('be.visible').click();
    cy.get('#modal-revise-agreement').should('be.visible');
    cy.get('#revise-base-price').clear().type('120000');
    cy.get('#modal-revise-agreement form').submit();

    // Verifikasi revisi terupdate ke Rev #2
    cy.contains('Lembar Kesepakatan Transaksi').should('be.visible');
    cy.contains('Rev #2').should('be.visible');
  });

  it('TC-06-03 — Penolakan Usulan Kesepakatan (Reject Agreement)', function () {
    const data = JSON.parse(this.seedData);
    // Seller menolak usulan kesepakatan
    cy.loginByForm('agr_seller', 'password123');
    cy.visit(`/conversations/${data.conversation_id}`);

    cy.get('#btn-open-reject-modal').should('be.visible').click();
    cy.get('#modal-reject-agreement').should('be.visible');
    cy.get('#rejection-reason').clear().type('Penawaran harga belum cocok, saya tolak dahulu.');
    cy.get('#modal-reject-agreement form').submit();

    // Verifikasi status REJECTED
    cy.contains('REJECTED').should('be.visible');
  });

  it('TC-06-04 — Persetujuan Kesepakatan Menghasilkan Transaksi WAITING_PAYMENT', function () {
    const data = JSON.parse(this.seedData);
    // Seller login dan menyetujui kesepakatan Rev 1
    cy.loginByForm('agr_seller', 'password123');
    cy.visit(`/conversations/${data.conversation_id}`);

    cy.get('#btn-open-accept-modal').should('be.visible').click();
    cy.get('#modal-accept-agreement').should('be.visible');
    cy.get('#modal-accept-agreement form').submit();

    // Verifikasi berhasil membentuk transaksi dan dialihkan ke detail transaksi
    cy.url().should('include', '/transactions/');
    cy.contains('Menunggu Pembayaran').should('be.visible');
  });
});
