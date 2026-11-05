describe('TS-06 — Forum Diskusi', () => {
  beforeEach(() => {
    // Siapkan data user uji via PHP task
    cy.task('execPhp', `
      use App\\Models\\User;
      use App\\Models\\ProductCategory;
      use App\\Models\\Product;
      use App\\Models\\ProductVariant;
      use App\\Models\\ForumPost;
      use Illuminate\\Support\\Facades\\Hash;

      $author = User::firstOrCreate(
        ['email' => 'author_forum@markettrust.test'],
        [
          'username' => 'forum_author',
          'password_hash' => Hash::make('password123'),
          'email_verified_at' => now(),
          'account_type' => 'USER',
          'account_status' => 'ACTIVE',
        ]
      );

      $buyer = User::firstOrCreate(
        ['email' => 'buyer_forum@markettrust.test'],
        [
          'username' => 'forum_buyer',
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
        ['title' => 'WTS: Akun MLBB All Skin Collector Terawat'],
        [
          'author_id' => $author->user_id,
          'category_code' => 'WTS',
          'variant_id' => $variant->variant_id,
          'content' => 'Akun pribadi tangan pertama aman 100% email bisa diganti',
          'price_offer' => 350000,
          'status' => 'ACTIVE',
        ]
      );

      echo json_encode([
        'post_id' => $post->post_id,
        'author_username' => $author->username,
        'buyer_username' => $buyer->username,
      ]);
    `).as('seedData');
  });

  it('TC-05-01 — Pembuatan Postingan Forum Jual/Beli', () => {
    cy.loginByForm('forum_author', 'password123');
    cy.visit('/forum/create');

    // Buat postingan Kategori GENERAL
    cy.get('#btn-cat-GENERAL').click();
    cy.get('#category_code').should('have.value', 'GENERAL');
    cy.get('input[name="title"]').clear().type('Diskusi Pengalaman Bertransaksi di Forum MarketTrust');
    cy.get('textarea[name="content"]').clear().type('Forum ini sangat membantu untuk bernegosiasi secara aman dan transparan.');
    cy.get('#submit-btn').click();

    // Verifikasi diarahkan ke halaman detail postingan baru
    cy.url().should('include', '/forum/');
    cy.contains('Postingan forum berhasil dipublikasikan!').should('be.visible');
    cy.contains('Diskusi Pengalaman Bertransaksi di Forum MarketTrust').should('be.visible');
  });

  it('TC-05-02 — Interaksi Komentar dan Like pada Postingan Forum', function () {
    const data = JSON.parse(this.seedData);
    cy.loginByForm('forum_buyer', 'password123');
    cy.visit(`/forum/${data.post_id}`);

    // Tambah komentar
    const commentText = 'Halo gan, apakah akun ini masih ready untuk dinego?';
    cy.get('#comment-content').type(commentText);
    cy.get('#comment-submit-btn').click();

    // Verifikasi komentar muncul di container
    cy.get('#comments-container').should('contain', commentText);
  });

  it('TC-05-03 — Tombol Negosiasi / Ajukan Penawaran dari Forum Mengarah ke DM', function () {
    const data = JSON.parse(this.seedData);
    // Login sebagai buyer (non-author)
    cy.loginByForm('forum_buyer', 'password123');
    cy.visit(`/forum/${data.post_id}`);

    // Tombol negosiasi harus tersedia untuk non-author
    cy.get('#forum-negotiate-form button[type="submit"]').should('be.visible').click();

    // Mengarah ke URL dengan parameter open_dm dan menampilkan alert sukses
    cy.url().should('include', 'open_dm=');
    cy.contains('berhasil diinisiasi').should('be.visible');
  });
});
