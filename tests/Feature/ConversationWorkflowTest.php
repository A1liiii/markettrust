<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\ForumPost;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'category_name' => 'Game Accounts ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createProduct(?ProductCategory $category = null): Product
    {
        $category = $category ?: $this->createCategory();

        return Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createVariant(?Product $product = null): ProductVariant
    {
        $product = $product ?: $this->createProduct();

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => 'Mythic Account ' . uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createListing(User $seller): Listing
    {
        $variant = $this->createVariant();

        return Listing::create([
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'title' => 'Akun ML Mythic 100 Stars',
            'description' => 'Full skins, all heroes unlocked',
            'price' => 250000.00,
            'delivery_method' => 'INSTANT',
            'estimated_delivery_value' => 15,
            'estimated_delivery_unit' => 'MINUTE',
            'status' => 'ACTIVE',
        ]);
    }

    protected function createForumPost(User $author): ForumPost
    {
        return ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'WTS',
            'title' => 'WTS Akun Genshin AR 60',
            'content' => 'Siap nego santai via DM escrow.',
            'price_offer' => 500000.00,
            'status' => 'ACTIVE',
        ]);
    }

    protected function createAgreement(User $creator, User $buyer, User $seller): Agreement
    {
        $variant = $this->createVariant();

        return Agreement::create([
            'created_by' => $creator->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Item Agreement Test',
            'item_description' => 'Test agreement item description',
            'quantity' => 1,
            'base_price' => 100000.00,
            'fee_type' => 'EXCLUDE',
            'fee_amount' => 5000.00,
            'buyer_total' => 105000.00,
            'seller_net' => 100000.00,
            'delivery_method' => 'INSTANT',
            'delivery_within_minutes' => 30,
            'status' => 'DRAFT',
        ]);
    }

    /**
     * 1. Pembuatan DM dari Listing dengan konteks listing_id.
     */
    public function test_can_initiate_dm_from_listing_with_listing_context(): void
    {
        $seller = User::factory()->create(['username' => 'seller_user']);
        $buyer = User::factory()->create(['username' => 'buyer_user']);
        $listing = $this->createListing($seller);

        $response = $this->actingAs($buyer)->get(route('conversations.from-listing', $listing->listing_id));

        $conversation = Conversation::where('listing_id', $listing->listing_id)->first();
        $this->assertNotNull($conversation);

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $expectedOne = min($buyer->user_id, $seller->user_id);
        $expectedTwo = max($buyer->user_id, $seller->user_id);

        $this->assertEquals($expectedOne, $conversation->participant_one_id);
        $this->assertEquals($expectedTwo, $conversation->participant_two_id);
        $this->assertEquals($listing->listing_id, $conversation->listing_id);

        // Calling again does not duplicate the conversation
        $this->actingAs($buyer)->get(route('conversations.from-listing', $listing->listing_id));
        $this->assertEquals(1, Conversation::where('listing_id', $listing->listing_id)->count());
    }

    /**
     * Seller tidak dapat memulai percakapan dengan listing miliknya sendiri.
     */
    public function test_seller_cannot_initiate_dm_with_own_listing(): void
    {
        $seller = User::factory()->create(['username' => 'self_seller']);
        $listing = $this->createListing($seller);

        $response = $this->actingAs($seller)->get(route('conversations.from-listing', $listing->listing_id));

        $response->assertSessionHas('warning');
        $this->assertEquals(0, Conversation::where('listing_id', $listing->listing_id)->count());
    }

    /**
     * 2. Pembuatan DM dari Forum Post dengan konteks post_id.
     */
    public function test_can_initiate_dm_from_forum_post_with_post_context(): void
    {
        $author = User::factory()->create(['username' => 'author_user']);
        $reader = User::factory()->create(['username' => 'reader_user']);
        $post = $this->createForumPost($author);

        $response = $this->actingAs($reader)->get(route('conversations.from-post', $post->post_id));

        $conversation = Conversation::where('post_id', $post->post_id)->first();
        $this->assertNotNull($conversation);

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $expectedOne = min($reader->user_id, $author->user_id);
        $expectedTwo = max($reader->user_id, $author->user_id);

        $this->assertEquals($expectedOne, $conversation->participant_one_id);
        $this->assertEquals($expectedTwo, $conversation->participant_two_id);
        $this->assertEquals($post->post_id, $conversation->post_id);

        // Calling again does not duplicate the conversation
        $this->actingAs($reader)->get(route('conversations.from-post', $post->post_id));
        $this->assertEquals(1, Conversation::where('post_id', $post->post_id)->count());
    }

    /**
     * Author tidak dapat memulai percakapan dengan post miliknya sendiri.
     */
    public function test_author_cannot_initiate_dm_with_own_forum_post(): void
    {
        $author = User::factory()->create(['username' => 'self_author']);
        $post = $this->createForumPost($author);

        $response = $this->actingAs($author)->get(route('conversations.from-post', $post->post_id));

        $response->assertSessionHas('warning');
        $this->assertEquals(0, Conversation::where('post_id', $post->post_id)->count());
    }

    /**
     * 3. Akses percakapan oleh peserta yang sah.
     */
    public function test_authorized_participants_can_access_conversation(): void
    {
        $userA = User::factory()->create(['username' => 'user_a']);
        $userB = User::factory()->create(['username' => 'user_b']);

        $service = app(ConversationService::class);
        $listing = $this->createListing($userA);
        $conversation = $service->startFromListing($userB, $listing);

        // Participant B can access
        $responseB = $this->actingAs($userB)->get(route('conversations.show', $conversation->conversation_id));
        $responseB->assertStatus(200);
        $responseB->assertSee('user_a');

        // Participant A can access
        $responseA = $this->actingAs($userA)->get(route('conversations.show', $conversation->conversation_id));
        $responseA->assertStatus(200);
        $responseA->assertSee('user_b');
    }

    /**
     * 4. Penolakan akses pengguna di luar percakapan dengan HTTP 403.
     */
    public function test_non_participant_receives_http_403_when_accessing_conversation(): void
    {
        $userA = User::factory()->create(['username' => 'user_alpha']);
        $userB = User::factory()->create(['username' => 'user_beta']);
        $intruder = User::factory()->create(['username' => 'intruder_user']);

        $service = app(ConversationService::class);
        $listing = $this->createListing($userA);
        $conversation = $service->startFromListing($userB, $listing);

        // Intruder gets 403 on show
        $response = $this->actingAs($intruder)->get(route('conversations.show', $conversation->conversation_id));
        $response->assertStatus(403);

        // Intruder gets 403 on sending message
        $postResponse = $this->actingAs($intruder)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['content' => 'Hacked message!']
        );
        $postResponse->assertStatus(403);
    }

    /**
     * 5. Pengiriman pesan oleh peserta yang sah dan validasi form.
     */
    public function test_participant_can_send_message(): void
    {
        $userA = User::factory()->create(['username' => 'alice']);
        $userB = User::factory()->create(['username' => 'bob']);

        $service = app(ConversationService::class);
        $listing = $this->createListing($userA);
        $conversation = $service->startFromListing($userB, $listing);

        $response = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['content' => 'Halo apakah akun ini masih ada?']
        );

        $response->assertRedirect(route('conversations.show', $conversation->conversation_id));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $userB->user_id,
            'content' => 'Halo apakah akun ini masih ada?',
            'read_at' => null,
        ]);

        // Validation: empty message is rejected
        $emptyResponse = $this->actingAs($userB)->post(
            route('conversations.messages.send', $conversation->conversation_id),
            ['content' => '']
        );
        $emptyResponse->assertSessionHasErrors(['content']);
    }

    /**
     * 6. Pembaruan read_at secara otomatis oleh penerima pesan.
     */
    public function test_receiver_automatically_updates_read_at_when_viewing(): void
    {
        $userA = User::factory()->create(['username' => 'charlie']);
        $userB = User::factory()->create(['username' => 'dave']);

        $service = app(ConversationService::class);
        $listing = $this->createListing($userA);
        $conversation = $service->startFromListing($userB, $listing);

        // Dave (buyer) sends message to Charlie (seller)
        $msg = $service->sendMessage($conversation, $userB, 'Halo Charlie, saya mau beli.');
        $this->assertNull($msg->read_at);

        // When Dave (the sender) views the conversation, read_at remains null
        $this->actingAs($userB)->get(route('conversations.show', $conversation->conversation_id));
        $msg->refresh();
        $this->assertNull($msg->read_at);

        // When Charlie (the receiver) views the conversation, read_at is marked
        $this->actingAs($userA)->get(route('conversations.show', $conversation->conversation_id));
        $msg->refresh();
        $this->assertNotNull($msg->read_at);
    }

    /**
     * 7. Pencegahan duplikasi DM utama untuk agreement_id.
     */
    public function test_max_one_primary_dm_per_agreement_id_is_enforced(): void
    {
        $buyer = User::factory()->create(['username' => 'agreement_buyer']);
        $seller = User::factory()->create(['username' => 'agreement_seller']);
        $agreement = $this->createAgreement($buyer, $buyer, $seller);

        $service = app(ConversationService::class);

        // First creation
        $conv1 = $service->startWithAgreement($buyer, $seller, $agreement->agreement_id);
        $this->assertEquals($agreement->agreement_id, $conv1->agreement_id);

        // Second call returns existing conversation
        $conv2 = $service->startWithAgreement($buyer, $seller, $agreement->agreement_id);
        $this->assertEquals($conv1->conversation_id, $conv2->conversation_id);

        // Database count must be exactly 1
        $this->assertEquals(1, Conversation::where('agreement_id', $agreement->agreement_id)->count());
    }

    /**
     * Pengguna dapat melihat daftar inbox percakapan aktif miliknya.
     */
    public function test_user_can_view_conversations_inbox(): void
    {
        $userA = User::factory()->create(['username' => 'inbox_user_a']);
        $userB = User::factory()->create(['username' => 'inbox_user_b']);

        $service = app(ConversationService::class);
        $listing = $this->createListing($userA);
        $conversation = $service->startFromListing($userB, $listing);

        $service->sendMessage($conversation, $userA, 'Pesan pertama di inbox');

        $response = $this->actingAs($userB)->get(route('conversations.index'));
        $response->assertStatus(200);
        $response->assertSee('inbox_user_a');
        $response->assertSee('Pesan pertama di inbox');
    }

    /**
     * Tamu (unauthenticated) tidak dapat mengakses percakapan.
     */
    public function test_guest_cannot_access_conversations(): void
    {
        $response = $this->get(route('conversations.index'));
        $response->assertRedirect(route('login'));
    }
}
