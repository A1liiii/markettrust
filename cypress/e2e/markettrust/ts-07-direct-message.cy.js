describe('TS-07 — Direct Message & Lampiran', () => {
  const buyerUsername = 'alibuyer_ts07';
  const sellerUsername = 'aliseller_ts07';
  const password = 'Password123!';
  let conversationId;

  before(() => {
    cy.task(
      'execPhp',
      `
      $buyer = \\App\\Models\\User::updateOrCreate(
          ['username' => '${buyerUsername}'],
          [
              'email' => 'alibuyer_ts07@markettrust.test',
              'password_hash' => '${password}',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      $seller = \\App\\Models\\User::updateOrCreate(
          ['username' => '${sellerUsername}'],
          [
              'email' => 'aliseller_ts07@markettrust.test',
              'password_hash' => '${password}',
              'email_verified_at' => now(),
              'account_type' => 'USER',
              'account_status' => 'ACTIVE',
          ]
      );

      [$oneId, $twoId] = $buyer->user_id < $seller->user_id
          ? [$buyer->user_id, $seller->user_id]
          : [$seller->user_id, $buyer->user_id];

      \\App\\Models\\Conversation::where('participant_one_id', $oneId)
          ->where('participant_two_id', $twoId)
          ->delete();

      $conv = \\App\\Models\\Conversation::create([
          'participant_one_id' => $oneId,
          'participant_two_id' => $twoId,
          'created_at' => now(),
      ]);

      echo 'CONV_ID:' . $conv->conversation_id;
      `
    ).then((output) => {
      const match = output.match(/CONV_ID:(\d+)/);
      expect(match).to.not.be.null;
      conversationId = match[1];
    });
  });

  beforeEach(() => {
    cy.loginByForm(buyerUsername, password);
  });

  it('TC-05-04: Kirim Pesan Teks dan Polling Sinkronisasi DM (Pesan lawan muncul otomatis tanpa reload halaman)', () => {
    cy.visit(`/conversations/${conversationId}`);
    cy.contains(`@${sellerUsername}`).should('be.visible');

    // 1. Buyer mengirim pesan teks melalui form UI
    cy.get('#message-content').type('Halo Kak Seller, apakah akun masih ready hari ini?');
    cy.get('#submit-message-btn').click();

    cy.get('#message-list').should('contain', 'Halo Kak Seller, apakah akun masih ready hari ini?');
    cy.get('#message-list').should('contain', 'Terkirim');

    // 2. Simulasikan Seller membaca pesan & membalas dari sisi lawan saat Buyer tetap di halaman tanpa reload
    cy.task(
      'execPhp',
      `
      $seller = \\App\\Models\\User::where('username', '${sellerUsername}')->first();
      $conv = \\App\\Models\\Conversation::findOrFail(${conversationId});
      $service = app(\\App\\Services\\ConversationService::class);
      $service->markMessagesAsRead($conv, $seller);
      $service->sendMessage($conv, $seller, 'Halo Kak Buyer! Stok sangat ready, silakan lanjut.');
      echo 'SELLER_REPLIED';
      `
    ).should('contain', 'SELLER_REPLIED');

    // Tunggu polling otomatis (interval 3.5 detik di UI) menampilkan pesan baru tanpa reload halaman
    cy.get('#message-list', { timeout: 8000 }).should(
      'contain',
      'Halo Kak Buyer! Stok sangat ready, silakan lanjut.'
    );
    cy.get('#message-list').should('contain', 'Dibaca');
  });

  it('TC-05-05: Validasi Ketat Lampiran Berkas DM (Format terlarang, ukuran > 5 MB ditolak, dan gambar valid berhasil diunggah)', () => {
    cy.visit(`/conversations/${conversationId}`);

    // Variasi 1: Format berkas terlarang (.zip) -> ditolak dengan peringatan di UI
    cy.get('#attachment-input').selectFile(
      {
        contents: Cypress.Buffer.from('PK\x03\x04dummyzipcontent'),
        fileName: 'berkas_terlarang.zip',
        mimeType: 'application/zip',
      },
      { force: true }
    );
    cy.get('#attachment-error-alert').should('be.visible');
    cy.get('#attachment-error-message').should(
      'contain',
      'Format file lampiran harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.'
    );

    // Variasi 2: Berkas gambar melebihi batas 5 MB (5.5 MB) -> ditolak dengan peringatan ukuran
    const oversizedBuffer = Cypress.Buffer.alloc(5.5 * 1024 * 1024, 'a');
    cy.get('#attachment-input').selectFile(
      {
        contents: oversizedBuffer,
        fileName: 'gambar_terlalu_besar.png',
        mimeType: 'image/png',
      },
      { force: true }
    );
    cy.get('#attachment-error-alert').should('be.visible');
    cy.get('#attachment-error-message').should('contain', 'Ukuran file lampiran maksimal 5 MB.');

    // Variasi 3: Berkas gambar valid (.png <= 5 MB) -> pratinjau tampil dan berhasil terkirim
    const validPngBase64 =
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    cy.get('#attachment-input').selectFile(
      {
        contents: Cypress.Buffer.from(validPngBase64, 'base64'),
        fileName: 'bukti-pembayaran-sah.png',
        mimeType: 'image/png',
      },
      { force: true }
    );

    cy.get('#attachment-preview-badge').should('be.visible');
    cy.get('#attachment-preview-name').should('contain', 'bukti-pembayaran-sah.png');

    cy.get('#message-content').clear().type('Ini lampiran gambar bukti yang valid.');
    cy.get('#submit-message-btn').click();

    cy.get('#message-list').should('contain', 'Ini lampiran gambar bukti yang valid.');
    cy.get('#message-list').should('contain', 'bukti-pembayaran-sah.png');
    cy.get('#message-list').should('contain', 'Unduh');
  });
});
