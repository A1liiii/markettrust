<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Conversation\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use PHPUnit\Framework\TestCase;

class DirectMessageRulesTest extends TestCase
{
    /**
     * TC-05-04: Aturan urutan kanonik peserta percakapan dan pengecekan partisipan di ConversationService.
     */
    public function test_tc_05_04_conversation_participant_and_canonical_ordering_rules(): void
    {
        $service = new ConversationService;

        // Urutan kanonik selalu menempatkan user_id lebih kecil di indeks pertama
        $this->assertSame([5, 12], $service->getCanonicalParticipants(12, 5));
        $this->assertSame([5, 12], $service->getCanonicalParticipants(5, 12));

        $conversation = new Conversation([
            'participant_one_id' => 5,
            'participant_two_id' => 12,
        ]);

        $userOne = new User;
        $userOne->user_id = 5;

        $userTwo = new User;
        $userTwo->user_id = 12;

        $outsider = new User;
        $outsider->user_id = 99;

        $this->assertTrue($service->isParticipant($conversation, $userOne));
        $this->assertTrue($service->isParticipant($conversation, $userTwo));
        $this->assertFalse($service->isParticipant($conversation, $outsider));
    }

    /**
     * TC-05-05: Validasi aturan lampiran berkas DM dan batas ukuran pada SendMessageRequest (UC-08 & KNF-04).
     */
    public function test_tc_05_05_send_message_request_attachment_validation_rules(): void
    {
        $request = new SendMessageRequest;
        $rules = $request->rules();
        $messages = $request->messages();

        $this->assertArrayHasKey('content', $rules);
        $this->assertContains('max:5000', $rules['content']);
        $this->assertContains('required_without:attachment', $rules['content']);

        $this->assertArrayHasKey('attachment', $rules);
        $this->assertContains('file', $rules['attachment']);
        $this->assertContains('max:5120', $rules['attachment']);
        $this->assertContains('mimes:jpg,jpeg,png,webp,pdf', $rules['attachment']);

        $this->assertSame('Ukuran file lampiran maksimal 5 MB.', $messages['attachment.max']);
        $this->assertSame(
            'Format file lampiran harus berupa gambar (JPG, JPEG, PNG, WEBP) atau PDF.',
            $messages['attachment.mimes']
        );
    }

    /**
     * TC-05-05: Helper deteksi tipe media dan pemformatan ukuran berkas pada model Message.
     */
    public function test_tc_05_05_message_model_attachment_metadata_helpers(): void
    {
        $textOnly = new Message([
            'content' => 'Halo tanpa lampiran',
            'attachment_path' => null,
        ]);
        $this->assertFalse($textOnly->hasAttachment());
        $this->assertSame('', $textOnly->formattedAttachmentSize());

        $imageMsg = new Message([
            'attachment_path' => 'dm_attachments/bukti.png',
            'attachment_name' => 'bukti.png',
            'attachment_mime' => 'image/png',
            'attachment_size' => 204800, // 200 KB
        ]);
        $this->assertTrue($imageMsg->hasAttachment());
        $this->assertTrue($imageMsg->isImage());
        $this->assertFalse($imageMsg->isPdf());
        $this->assertSame('200 KB', $imageMsg->formattedAttachmentSize());

        $pdfMsg = new Message([
            'attachment_path' => 'dm_attachments/panduan.pdf',
            'attachment_name' => 'panduan.pdf',
            'attachment_mime' => 'application/pdf',
            'attachment_size' => 2621440, // 2.5 MB
        ]);
        $this->assertTrue($pdfMsg->isPdf());
        $this->assertFalse($pdfMsg->isImage());
        $this->assertSame('2.5 MB', $pdfMsg->formattedAttachmentSize());
    }
}
