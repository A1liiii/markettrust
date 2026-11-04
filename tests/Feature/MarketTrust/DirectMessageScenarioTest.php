<?php

namespace Tests\Feature\MarketTrust;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DirectMessageScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $seller;

    protected User $outsider;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create([
            'username' => 'alibuyer',
            'account_status' => 'ACTIVE',
        ]);

        $this->seller = User::factory()->create([
            'username' => 'aliseller',
            'account_status' => 'ACTIVE',
        ]);

        $this->outsider = User::factory()->create([
            'username' => 'alioutsider',
            'account_status' => 'ACTIVE',
        ]);

        [$oneId, $twoId] = $this->buyer->user_id < $this->seller->user_id
            ? [$this->buyer->user_id, $this->seller->user_id]
            : [$this->seller->user_id, $this->buyer->user_id];

        $this->conversation = Conversation::create([
            'participant_one_id' => $oneId,
            'participant_two_id' => $twoId,
            'created_at' => now(),
        ]);
    }

    /**
     * TC-05-04: Kirim Pesan Teks dan Polling Sinkronisasi DM (Muncul di akun lawan tanpa reload & read receipt).
     */
    public function test_tc_05_04_send_text_message_and_sync_polling_between_participants(): void
    {
        // 1. Buyer mengirim pesan teks via JSON/AJAX seperti di UI chat
        $sendResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'content' => 'Halo Kak, apakah stok akun Canva Pro 1 bulan masih ada?',
            ]);

        $sendResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.content', 'Halo Kak, apakah stok akun Canva Pro 1 bulan masih ada?')
            ->assertJsonPath('message.is_me', true)
            ->assertJsonPath('message.is_read', false);

        $messageId = (int) $sendResponse->json('message.message_id');
        $this->assertGreaterThan(0, $messageId);

        // Pastikan pesan tersimpan dengan read_at masih null
        $this->assertDatabaseHas('messages', [
            'message_id' => $messageId,
            'conversation_id' => $this->conversation->conversation_id,
            'sender_id' => $this->buyer->user_id,
            'read_at' => null,
        ]);

        // 2. Seller melakukan polling sinkronisasi (GET /conversations/{id}/sync?after_id=0) tanpa reload halaman
        $syncSellerResponse = $this->actingAs($this->seller)
            ->getJson(route('conversations.sync', [
                'conversation' => $this->conversation->conversation_id,
                'after_id' => 0,
            ]));

        $syncSellerResponse->assertOk()
            ->assertJsonCount(1, 'new_messages')
            ->assertJsonPath('new_messages.0.message_id', $messageId)
            ->assertJsonPath('new_messages.0.content', 'Halo Kak, apakah stok akun Canva Pro 1 bulan masih ada?')
            ->assertJsonPath('new_messages.0.is_me', false);

        // Setelah Seller melakukan sync, pesan otomatis ditandai sudah dibaca (read_at != null)
        $messageFresh = Message::findOrFail($messageId);
        $this->assertNotNull($messageFresh->read_at);

        // 3. Buyer melakukan polling sync dan mendapati pesan miliknya sudah berstatus dibaca oleh Seller
        $syncBuyerResponse = $this->actingAs($this->buyer)
            ->getJson(route('conversations.sync', [
                'conversation' => $this->conversation->conversation_id,
                'after_id' => $messageId,
            ]));

        $syncBuyerResponse->assertOk();
        $this->assertContains($messageId, $syncBuyerResponse->json('my_read_message_ids'));

        // 4. Pengguna luar (bukan peserta) ditolak aksesnya (HTTP 403)
        $this->actingAs($this->outsider)
            ->getJson(route('conversations.sync', $this->conversation))
            ->assertForbidden();
    }

    /**
     * TC-05-05: Validasi Ketat Lampiran Berkas DM (UC-08 & KNF-04).
     * Menguji semua variasi: format terlarang (.zip, .exe, .mp4), ukuran > 5 MB, serta gambar (.png) & dokumen (.pdf) valid.
     */
    public function test_tc_05_05_strict_dm_attachment_validation_all_variations(): void
    {
        Storage::fake('local');

        // Variasi 1: Pesan kosong tanpa teks dan tanpa lampiran -> Ditolak 422
        $emptyResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'content' => '',
            ]);
        $emptyResponse->assertStatus(422)
            ->assertJsonValidationErrors(['content']);

        // Variasi 2A: Format berkas terlarang (.zip) -> Ditolak 422
        $zipFile = UploadedFile::fake()->create('arsip_berbahaya.zip', 500, 'application/zip');
        $zipResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'content' => 'Kirim file zip',
                'attachment' => $zipFile,
            ]);
        $zipResponse->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);

        // Variasi 2B: Format berkas terlarang (.exe) -> Ditolak 422
        $exeFile = UploadedFile::fake()->create('installer.exe', 300, 'application/x-msdownload');
        $exeResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'attachment' => $exeFile,
            ]);
        $exeResponse->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);

        // Variasi 2C: Format berkas terlarang (.mp4) -> Ditolak 422
        $mp4File = UploadedFile::fake()->create('rekaman.mp4', 1024, 'video/mp4');
        $mp4Response = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'attachment' => $mp4File,
            ]);
        $mp4Response->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);

        // Variasi 3: Ukuran berkas melebihi 5 MB (6.000 KB / ~5.86 MB > batas 5120 KB) -> Ditolak 422
        $oversizedPng = UploadedFile::fake()->create('resolusi_tinggi.png', 6000, 'image/png');
        $oversizedResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'content' => 'File lebih dari 5MB',
                'attachment' => $oversizedPng,
            ]);
        $oversizedResponse->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);

        // Variasi 4A: Unggah Gambar Valid (.png <= 5 MB) -> Berhasil
        $validImage = UploadedFile::fake()->image('bukti_transfer.png', 600, 400)->size(1024);
        $validImgResponse = $this->actingAs($this->buyer)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'content' => 'Berikut tangkapan layar pembayaran',
                'attachment' => $validImage,
            ]);

        $validImgResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.has_attachment', true)
            ->assertJsonPath('message.attachment_name', 'bukti_transfer.png')
            ->assertJsonPath('message.is_image', true);

        $imgMsgId = (int) $validImgResponse->json('message.message_id');
        $imgMsg = Message::findOrFail($imgMsgId);
        Storage::disk('local')->assertExists($imgMsg->attachment_path);

        // Variasi 4B: Unggah Dokumen PDF Valid (<= 5 MB) tanpa teks pengantar -> Berhasil
        $validPdf = UploadedFile::fake()->create('invoice_kesepakatan.pdf', 1500, 'application/pdf');
        $validPdfResponse = $this->actingAs($this->seller)
            ->postJson(route('conversations.messages.send', $this->conversation), [
                'attachment' => $validPdf,
            ]);

        $validPdfResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.has_attachment', true)
            ->assertJsonPath('message.attachment_name', 'invoice_kesepakatan.pdf')
            ->assertJsonPath('message.is_pdf', true);

        // Variasi 5: Otorisasi Unduh Lampiran (Peserta diizinkan HTTP 200, Outsider ditolak HTTP 403)
        $this->actingAs($this->seller)
            ->get(route('conversations.messages.attachment', [$this->conversation, $imgMsg]))
            ->assertOk();

        $this->actingAs($this->outsider)
            ->get(route('conversations.messages.attachment', [$this->conversation, $imgMsg]))
            ->assertForbidden();
    }
}
