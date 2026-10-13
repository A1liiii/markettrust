<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function createForumPost(User $author): ForumPost
    {
        return ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'WTS',
            'title' => 'WTS Akun Genshin AR 60 Sultan',
            'content' => 'Nego santai lewat DM.',
            'price_offer' => 750000.00,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * TC-DM-01: Pesan valid terkirim, pesan kosong tanpa lampiran ditolak.
     */
    public function test_message_validation_rejects_empty_message_without_attachment(): void
    {
        $userA = User::factory()->create(['username' => 'alice']);
        $userB = User::factory()->create(['username' => 'bob']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        // Pesan kosong tanpa file harus ditolak validasi
        $emptyResponse = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['content' => '   ']
        );
        $emptyResponse->assertSessionHasErrors(['content']);

        // Pesan valid terkirim
        $validResponse = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['content' => 'Halo Alice, apakah harga bisa nego?']
        );
        $validResponse->assertRedirect(route('conversations.show', $conversation->conversation_id));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $userB->user_id,
            'content' => 'Halo Alice, apakah harga bisa nego?',
        ]);
    }

    /**
     * TC-DM-02 & TC-DM-05: Buka DM langsung dari postingan forum tanpa duplikasi.
     */
    public function test_opening_dm_from_forum_post_reuses_existing_conversation_without_duplicates(): void
    {
        $author = User::factory()->create(['username' => 'seller_forum']);
        $buyer = User::factory()->create(['username' => 'buyer_forum']);
        $post = $this->createForumPost($author);

        // Inisiasi pertama via endpoint quick / JSON
        $res1 = $this->actingAs($buyer)->postJson(route('conversations.from-post.quick', $post->post_id));
        $res1->assertStatus(200);
        $convId1 = $res1->json('conversation_id');

        // Inisiasi kedua untuk postingan yang sama oleh user yang sama
        $res2 = $this->actingAs($buyer)->postJson(route('conversations.from-post.quick', $post->post_id));
        $res2->assertStatus(200);
        $convId2 = $res2->json('conversation_id');

        $this->assertEquals($convId1, $convId2);
        $this->assertEquals(1, Conversation::where('post_id', $post->post_id)->count());
    }

    /**
     * TC-DM-02: Penulis postingan dilarang membuka percakapan dengan postingannya sendiri.
     */
    public function test_author_cannot_open_dm_with_own_forum_post(): void
    {
        $author = User::factory()->create(['username' => 'owner_post']);
        $post = $this->createForumPost($author);

        $response = $this->actingAs($author)->postJson(route('conversations.from-post.quick', $post->post_id));
        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Kebutuhan Tambahan: Lampiran dokumen disimpan di storage privat.
     */
    public function test_upload_valid_attachment_stores_file_in_private_storage(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create(['username' => 'charlie']);
        $userB = User::factory()->create(['username' => 'dave']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        $dummyPdf = UploadedFile::fake()->create('bukti_spesifikasi.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            [
                'content' => 'Ini dokumen spesifikasi akun.',
                'attachment' => $dummyPdf,
            ]
        );

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $message = Message::where('conversation_id', $conversation->conversation_id)->latest('sent_at')->first();
        $this->assertNotNull($message);
        $this->assertEquals('bukti_spesifikasi.pdf', $message->attachment_name);
        $this->assertNotEmpty($message->attachment_path);

        // Pastikan tersimpan di disk local (privat)
        Storage::disk('local')->assertExists($message->attachment_path);
    }

    /**
     * Kebutuhan Tambahan: Pengiriman pesan HANYA dengan lampiran tanpa teks diperbolehkan.
     */
    public function test_message_can_be_sent_with_attachment_only(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create(['username' => 'elena']);
        $userB = User::factory()->create(['username' => 'frank']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        $dummyZip = UploadedFile::fake()->create('arsip_dokumen.zip', 500, 'application/zip');

        $response = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            [
                'attachment' => $dummyZip,
            ]
        );

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'attachment_name' => 'arsip_dokumen.zip',
            'content' => null,
        ]);
    }

    /**
     * Kebutuhan Tambahan: Peserta sah dapat mengunduh lampiran, pihak ketiga ditolak HTTP 403.
     */
    public function test_authorized_participants_can_download_attachment_and_third_party_gets_403(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create(['username' => 'gina']);
        $userB = User::factory()->create(['username' => 'harry']);
        $intruder = User::factory()->create(['username' => 'intruder_hacker']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        $file = UploadedFile::fake()->create('faktur_pembelian.pdf', 300, 'application/pdf');
        $msg = $service->sendMessage($conversation, $userB, 'Faktur pembelian', $file);

        $downloadRoute = route('conversations.messages.attachment', [$conversation->conversation_id, $msg->message_id]);

        // 1. Pengirim (User B) dapat mengunduh
        $this->actingAs($userB)->get($downloadRoute)->assertStatus(200);

        // 2. Penerima (User A) dapat mengunduh
        $this->actingAs($userA)->get($downloadRoute)->assertStatus(200);

        // 3. Pihak ketiga (Intruder) DITOLAK dengan status 403 Forbidden
        $this->actingAs($intruder)->get($downloadRoute)->assertStatus(403);
    }

    /**
     * Kebutuhan Tambahan: File tidak valid (executable / oversize > 5MB) ditolak validasi.
     */
    public function test_invalid_attachment_type_or_oversize_is_rejected(): void
    {
        Storage::fake('local');

        $userA = User::factory()->create(['username' => 'ian']);
        $userB = User::factory()->create(['username' => 'jack']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        // File executable terlarang .exe
        $exeFile = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        $resExe = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['attachment' => $exeFile]
        );
        $resExe->assertSessionHasErrors(['attachment']);

        // File terlalu besar (> 15MB)
        $oversizedFile = UploadedFile::fake()->create('big_file.pdf', 20000, 'application/pdf');
        $resBig = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['attachment' => $oversizedFile]
        );
        $resBig->assertSessionHasErrors(['attachment']);
    }

    /**
     * TC-DM-04: Endpoint sync mengembalikan status dibaca realtime & pesan baru tanpa reload.
     */
    public function test_sync_endpoint_returns_read_receipts_and_updates_status(): void
    {
        $userA = User::factory()->create(['username' => 'karen']);
        $userB = User::factory()->create(['username' => 'leo']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        // Leo mengirim pesan ke Karen
        $msg1 = $service->sendMessage($conversation, $userB, 'Halo Karen!');
        $this->assertNull($msg1->read_at);

        // Leo melakukan sync: pesan miliknya masih unread
        $syncLeo = $this->actingAs($userB)->getJson(route('conversations.sync', $conversation->conversation_id));
        $syncLeo->assertStatus(200);
        $this->assertEmpty($syncLeo->json('my_read_message_ids'));

        // Karen membuka / sync percakapan: otomatis menandai pesan Leo sebagai terbaca
        $this->actingAs($userA)->getJson(route('conversations.sync', $conversation->conversation_id));
        $msg1->refresh();
        $this->assertNotNull($msg1->read_at);

        // Leo melakukan sync kembali tanpa reload: my_read_message_ids sekarang memuat message_id msg1
        $syncLeoUpdated = $this->actingAs($userB)->getJson(route('conversations.sync', $conversation->conversation_id));
        $syncLeoUpdated->assertStatus(200);
        $this->assertContains($msg1->message_id, $syncLeoUpdated->json('my_read_message_ids'));
    }

    /**
     * Akses sync oleh pihak ketiga ditolak HTTP 403.
     */
    public function test_sync_endpoint_rejected_for_non_participant(): void
    {
        $userA = User::factory()->create(['username' => 'mike']);
        $userB = User::factory()->create(['username' => 'nina']);
        $intruder = User::factory()->create(['username' => 'oscar']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        $this->actingAs($intruder)->getJson(route('conversations.sync', $conversation->conversation_id))
            ->assertStatus(403);
    }

    /**
     * Global Drawer: Endpoint inbox mengembalikan daftar percakapan dalam format JSON.
     */
    public function test_inbox_returns_json_payload_when_requested_via_ajax(): void
    {
        $userA = User::factory()->create(['username' => 'paul']);
        $userB = User::factory()->create(['username' => 'quinn']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);
        $service->sendMessage($conversation, $userA, 'Pesan pertama di inbox');

        $response = $this->actingAs($userB)->getJson(route('conversations.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'conversations' => [
                '*' => [
                    'conversation_id',
                    'other_user' => ['user_id', 'username'],
                    'context_type',
                    'last_message' => ['content'],
                    'unread_count',
                ],
            ],
            'total_unread',
        ]);
        $this->assertEquals(1, count($response->json('conversations')));
        $this->assertEquals(1, $response->json('total_unread'));
    }

    /**
     * Global Drawer: Endpoint show thread mengembalikan pesan obrolan dalam format JSON.
     */
    public function test_thread_returns_json_payload_when_requested_via_ajax(): void
    {
        $userA = User::factory()->create(['username' => 'rachel']);
        $userB = User::factory()->create(['username' => 'sam']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);
        $service->sendMessage($conversation, $userA, 'Halo Sam!');

        $response = $this->actingAs($userB)->getJson(route('conversations.show', $conversation->conversation_id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'conversation_id',
            'other_user' => ['user_id', 'username'],
            'messages' => [
                '*' => [
                    'message_id',
                    'sender_id',
                    'content',
                    'sent_at',
                    'is_read',
                ],
            ],
            'send_url',
            'sync_url',
        ]);
    }

    /**
     * Tamu dilarang mengakses endpoint JSON obrolan.
     */
    public function test_guest_cannot_access_inbox_or_thread_json(): void
    {
        $this->getJson(route('conversations.index'))->assertStatus(401);
    }

    /**
     * Lampiran media (gambar, video, PDF) mengembalikan metadata preview dan mendukung streaming inline.
     */
    public function test_attachment_preview_and_inline_streaming(): void
    {
        Storage::fake('local');
        $userA = User::factory()->create(['username' => 'tina']);
        $userB = User::factory()->create(['username' => 'uma']);
        $post = $this->createForumPost($userA);

        $service = app(ConversationService::class);
        $conversation = $service->startFromPost($userB, $post);

        $imageFile = UploadedFile::fake()->create('screenshot.png', 500, 'image/png');
        $res = $this->actingAs($userB)->postJson(
            route('conversations.messages.send', $conversation->conversation_id),
            [
                'content' => 'Ini lampiran foto',
                'attachment' => $imageFile,
            ]
        );
        $res->assertStatus(200);
        $messageData = $res->json('message');
        $this->assertTrue($messageData['has_attachment']);
        $this->assertTrue($messageData['is_image']);
        $this->assertFalse($messageData['is_video']);
        $this->assertFalse($messageData['is_pdf']);
        $this->assertNotNull($messageData['preview_url']);

        // Test inline download response
        $message = Message::find($messageData['message_id']);
        $streamRes = $this->actingAs($userA)->get(
            route('conversations.messages.attachment', [$conversation->conversation_id, $message->message_id, 'inline' => 1])
        );
        $streamRes->assertStatus(200);
        $streamRes->assertHeader('Content-Type', 'image/png');
    }
}
