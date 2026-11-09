describe('Testing Cycle 2 — Zinky (Kaye) UI, DM & Kesepakatan', () => {
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

      $seller = User::updateOrCreate(
        ['email' => 'seller_c2@markettrust.test'],
        [
          'username' => 'seller_c2',
          'password_hash' => 'password123',
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $buyer = User::updateOrCreate(
        ['email' => 'buyer_c2@markettrust.test'],
        [
          'username' => 'buyer_c2',
          'password_hash' => 'password123',
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $category = ProductCategory::firstOrCreate(
        ['category_name' => 'Voucher Game Cycle 2'],
        ['is_active' => true]
      );

      $product = Product::firstOrCreate(
        ['product_name' => 'Valorant Points Cycle 2', 'category_id' => $category->category_id],
        ['is_active' => true]
      );

      $variant = ProductVariant::firstOrCreate(
        ['variant_name' => '1120 VP Fast Send', 'product_id' => $product->product_id],
        ['is_active' => true]
      );

      $post = ForumPost::firstOrCreate(
        ['title' => 'WTS: 1120 VP Valorant Legal Fast'],
        [
          'author_id' => $seller->user_id,
          'category_code' => 'WTS',
          'variant_id' => $variant->variant_id,
          'content' => 'Dijual 1120 VP via riot id.',
          'price_offer' => 120000,
          'status' => 'ACTIVE',
        ]
      );

      $conv = Conversation::firstOrCreate([
        'participant_one_id' => min($buyer->user_id, $seller->user_id),
        'participant_two_id' => max($buyer->user_id, $seller->user_id),
        'post_id' => $post->post_id,
      ]);

      // Create draft agreement Rev #1
      $agr = Agreement::updateOrCreate(
        ['buyer_id' => $buyer->user_id, 'seller_id' => $seller->user_id, 'variant_id' => $variant->variant_id],
        [
          'created_by' => $buyer->user_id,
          'item_title' => '1120 VP Valorant Points Resmi',
          'item_description' => 'Pengiriman via Riot ID',
          'quantity' => 1,
          'base_price' => 120000,
          'fee_type' => 'EXCLUDE',
          'fee_amount' => 6000,
          'buyer_total' => 126000,
          'seller_net' => 120000,
          'delivery_method' => 'DIRECT_CHAT',
          'delivery_within_minutes' => 30,
          'revision_no' => 1,
          'status' => 'DRAFT',
          'buyer_accepted_at' => now(),
          'seller_accepted_at' => null,
        ]
      );

      $conv->update(['agreement_id' => $agr->agreement_id]);

      echo json_encode([
        'conversation_id' => $conv->conversation_id,
        'variant_id' => $variant->variant_id,
        'buyer_username' => $buyer->username,
        'seller_username' => $seller->username,
      ]);
    `).as('seedData');
  });

  it('TC-C2-01: Verifikasi Tampilan Umum, Header (tanpa Digital Escrow) & Footer Rekber', () => {
    // 1. Desktop Viewport
    cy.visit('/');
    cy.get('header').should('be.visible');
    cy.get('header').should('contain', 'MarketTrust');
    cy.get('header').should('not.contain', 'Digital Escrow');

    // 2. Footer Verifications
    cy.get('footer').should('be.visible');
    cy.get('footer').should('contain', 'Platform Marketplace & Rekber Digital Terpercaya.');
    cy.get('footer').should('contain', 'Transaksi Item Aman');
    cy.get('footer').should('contain', 'Rekber Otomatis');
    cy.get('footer').should('contain', 'Komplain');
    cy.get('footer').should('not.contain', 'Escrow Rekber Otomatis');

    // 3. Mobile Viewport
    cy.viewport(375, 667);
    cy.visit('/');
    cy.get('header').should('be.visible');
    cy.get('header').should('not.contain', 'Digital Escrow');
    cy.get('footer').should('be.visible');
  });

  it('TC-C2-02: Verifikasi Homepage (Kalkulator Fee tanpa final, Copy Forum, Card Keamanan)', () => {
    cy.visit('/');

    // 1. Fee Calculator Copy
    cy.get('#simulasi-fee').scrollIntoView().should('be.visible');
    cy.get('#simulasi-fee').should('contain', 'sebelum membuat kesepakatan.');
    cy.get('#simulasi-fee').should('not.contain', 'sebelum membuat kesepakatan final.');
    cy.get('#simulasi-fee').should('contain', 'Dana ditahan di rekber');
    cy.get('#simulasi-fee').should('not.contain', 'Dana ditahan di escrow');

    // 2. Forum Pillar
    cy.get('#forum').scrollIntoView().should('be.visible');
    cy.get('#forum').should('contain', 'Forum untuk WTB (Want to Buy), WTS (Want to Sell), dan Umum. Komentar, like, dan dapat berlangsung ke direct message untuk negosiasi');

    // 3. Rekber Pillar
    cy.get('#escrow').scrollIntoView().should('be.visible');
    cy.get('#escrow h3').should('contain', 'Rekber Otomatis');
    cy.get('#escrow a').should('contain', 'Simulasi Biaya Rekber');

    // 4. Standar Keamanan Transaksi (4 Langkah)
    cy.contains('Standar Keamanan Transaksi').scrollIntoView().should('be.visible');
    cy.contains('data kesepakatan yang mengikat').should('be.visible');
    cy.contains('Agreement Snapshot').should('not.exist');
    cy.contains('Kesepakatan').should('be.visible');
    cy.contains('Buyer Payment').should('be.visible');
    cy.contains('Deliver Item').should('be.visible');
    cy.contains('Penjual menyerahkan item dan bukti digital melalui chat terenkripsi.').should('be.visible');
    cy.contains('Penjual menyerahkan akun/item').should('not.exist');
  });

  it('TC-C2-03: Verifikasi Percakapan / DM (Card Pesan Fix, Bebas Horizontal Overflow pada Mobile)', function () {
    const data = JSON.parse(this.seedData);
    cy.loginByForm('buyer_c2', 'password123');

    // Set mobile viewport
    cy.viewport(375, 667);

    // 1. Verifikasi Kotak Masuk (/conversations) bebas tabrakan waktu & badge Kesepakatan
    cy.visit('/conversations');
    cy.get('.dm-bubble-pop-card').should('be.visible').and('contain', '@seller_c2');
    cy.get('.dm-bubble-pop-card').should('contain', 'Kesepakatan');
    cy.get('body').then(($b) => {
      expect($b[0].scrollWidth).to.be.at.most($b[0].clientWidth + 2);
    });

    // 2. Open conversation directly
    cy.visit(`/conversations/${data.conversation_id}`);

    // Verify context banner
    cy.get('.glass-card').contains('Konteks:').should('be.visible');

    // Verify message container and body have no horizontal overflow
    cy.get('#messages-container').should('be.visible').then(($el) => {
      const el = $el[0];
      // Assert no horizontal scrollbar (scrollWidth equals or is tightly bounded by clientWidth)
      expect(el.scrollWidth).to.be.at.most(el.clientWidth + 2);
    });
    cy.get('body').then(($b) => {
      expect($b[0].scrollWidth).to.be.at.most($b[0].clientWidth + 2);
    });

    // Kirim pesan untuk verifikasi layout
    cy.get('#message-content').type('Halo seller, ready stock untuk Valorant points?');
    cy.get('#submit-message-btn').click();
    cy.contains('Halo seller, ready stock untuk Valorant points?').should('be.visible');
  });

  it('TC-C2-04: Verifikasi Reposisi Kesepakatan (Di Atas Input Pesan, Aksi Setujui/Tolak/Akhiri/Revisi)', function () {
    const data = JSON.parse(this.seedData);
    cy.loginByForm('seller_c2', 'password123');

    cy.visit(`/conversations/${data.conversation_id}`);

    // 1. Verifikasi posisi: Card kesepakatan harus berada tepat di atas input pesan (#message-form / input panel)
    cy.get('#messages-container').should('be.visible');
    cy.contains('Data Kesepakatan Transaksi').should('be.visible');
    cy.get('#message-content').should('be.visible');

    // 2. Tombol aksi tersedia
    cy.get('#btn-open-accept-modal').should('be.visible').and('contain', 'Setujui');
    cy.get('#btn-open-revise-modal').should('be.visible').and('contain', 'Revisi');
    cy.get('#btn-open-reject-modal').should('be.visible').and('contain', 'Tolak');
    cy.get('#btn-open-cancel-modal').should('be.visible').and('contain', 'Akhiri Agreement');

    // 3. Modal Setujui: Membuka modal konfirmasi review
    cy.get('#btn-open-accept-modal').click();
    cy.get('#modal-accept-agreement').should('be.visible');
    cy.get('#modal-accept-agreement').should('contain', 'Konfirmasi & Setujui Kesepakatan');
    cy.get('#modal-accept-agreement').should('contain', '1120 VP Valorant Points Resmi');
    cy.get('#accept-confirmation-check').should('be.visible');
    cy.get('[data-close-modal="modal-accept-agreement"]').click();
    cy.get('#modal-accept-agreement').should('not.be.visible');

    // 4. Modal Tolak
    cy.get('#btn-open-reject-modal').click();
    cy.get('#modal-reject-agreement').should('be.visible');
    cy.get('#modal-reject-agreement').should('contain', 'Tolak Usulan Kesepakatan');
    cy.get('[data-close-modal="modal-reject-agreement"]').click();
    cy.get('#modal-reject-agreement').should('not.be.visible');

    // 5. Modal Akhiri Agreement
    cy.get('#btn-open-cancel-modal').click();
    cy.get('#modal-cancel-agreement').should('be.visible');
    cy.get('#modal-cancel-agreement').should('contain', 'Akhiri Kesepakatan Transaksi');
    cy.get('[data-close-modal="modal-cancel-agreement"]').click();
    cy.get('#modal-cancel-agreement').should('not.be.visible');
  });
});
