<?php

namespace Tests\Feature\Forum;

use App\Models\ForumPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForumPostTest extends TestCase
{
    use RefreshDatabase;

    protected function createVariant(string $name = 'Skin Epic'): ProductVariant
    {
        $category = ProductCategory::create([
            'category_name' => 'Mobile Legends',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Akun MLBB',
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->product_id,
            'variant_name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_guest_can_view_forum_index_and_filtered_categories(): void
    {
        $author = User::factory()->create();

        $postGeneral = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Diskusi Komunitas Pertama',
            'content' => 'Halo semua anggota MarketTrust!',
            'status' => 'ACTIVE',
        ]);

        $postWtb = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'WTB',
            'title' => 'Cari Akun Murah',
            'content' => 'Butuh akun rank Mythic segera.',
            'status' => 'ACTIVE',
        ]);

        // 1. View all active posts
        $response = $this->get('/forum');
        $response->assertStatus(200);
        $response->assertSee('Diskusi Komunitas Pertama');
        $response->assertSee('Cari Akun Murah');

        // 2. View filtered category WTB
        $responseFiltered = $this->get('/forum?category=WTB');
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('Cari Akun Murah');
        $responseFiltered->assertDontSee('Diskusi Komunitas Pertama');
    }

    public function test_guest_can_view_active_forum_post_detail(): void
    {
        $author = User::factory()->create([
            'username' => 'penjual_handal',
        ]);

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Tips Aman Bertransaksi Rekber',
            'content' => 'Gunakan rekening bersama dan selalu periksa kesepakatan.',
            'status' => 'ACTIVE',
        ]);

        $response = $this->get(route('forum.show', $post->post_id));
        $response->assertStatus(200);
        $response->assertSee('Tips Aman Bertransaksi Rekber');
        $response->assertSee('penjual_handal');
    }

    public function test_guest_cannot_access_create_post_page(): void
    {
        $response = $this->get('/forum/create');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_general_post_without_variant(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/forum', [
            'category_code' => 'GENERAL',
            'title' => 'Pertanyaan Seputar Fee Platform',
            'content' => 'Bagaimana simulasi perhitungan fee untuk nominal 50.000?',
        ]);

        $post = ForumPost::where('title', 'Pertanyaan Seputar Fee Platform')->first();
        $this->assertNotNull($post);

        $response->assertRedirect(route('forum.show', $post->post_id));
        $this->assertDatabaseHas('forum_posts', [
            'author_id' => $user->user_id,
            'category_code' => 'GENERAL',
            'variant_id' => null,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_authenticated_user_can_create_wtb_post_with_variant_and_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $variant = $this->createVariant('Akun Mythic Immortal');

        $image1 = UploadedFile::fake()->image('bukti1.jpg', 600, 400);
        $image2 = UploadedFile::fake()->image('bukti2.png', 600, 400);

        $response = $this->actingAs($user)->post('/forum', [
            'category_code' => 'WTB',
            'variant_id' => $variant->variant_id,
            'title' => 'WTB Akun Mythic Immortal Siap Rekber',
            'content' => 'Budget 500rb, payment melalui gateway MarketTrust.',
            'price_offer' => 500000,
            'images' => [$image1, $image2],
        ]);

        $post = ForumPost::where('title', 'WTB Akun Mythic Immortal Siap Rekber')->first();
        $this->assertNotNull($post);

        $response->assertRedirect(route('forum.show', $post->post_id));
        $this->assertEquals($variant->variant_id, $post->variant_id);
        $this->assertCount(2, $post->image_urls);

        // Verify storage files exist
        foreach ($post->image_urls as $url) {
            $path = str_replace('/storage/', '', $url);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_create_post_fails_if_wtb_has_no_variant(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/forum', [
            'category_code' => 'WTB',
            'title' => 'WTB Tanpa Varian',
            'content' => 'Mencari sesuatu tapi tidak memilih varian.',
        ]);

        $response->assertSessionHasErrors(['variant_id']);
        $this->assertDatabaseMissing('forum_posts', [
            'title' => 'WTB Tanpa Varian',
        ]);
    }

    public function test_create_post_fails_if_images_exceed_four_files_or_exceed_3mb(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // 1. Exceeds 4 files
        $responseExcess = $this->actingAs($user)->post('/forum', [
            'category_code' => 'GENERAL',
            'title' => 'Post Terlalu Banyak Foto',
            'content' => 'Mengunggah 5 foto sekaligus.',
            'images' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
                UploadedFile::fake()->image('4.jpg'),
                UploadedFile::fake()->image('5.jpg'),
            ],
        ]);
        $responseExcess->assertSessionHasErrors(['images']);

        // 2. Exceeds 3MB (e.g. 3500KB)
        $responseLarge = $this->actingAs($user)->post('/forum', [
            'category_code' => 'GENERAL',
            'title' => 'Post Foto Terlalu Besar',
            'content' => 'Mengunggah foto melebihi 3MB.',
            'images' => [
                UploadedFile::fake()->create('heavy.jpg', 3500, 'image/jpeg'),
            ],
        ]);
        $responseLarge->assertSessionHasErrors(['images.0']);
    }

    public function test_author_can_update_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Judul Lama',
            'content' => 'Konten lama',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($author)->put(route('forum.update', $post->post_id), [
            'category_code' => 'GENERAL',
            'title' => 'Judul Baru yang Diedit',
            'content' => 'Konten baru yang sudah diperbarui.',
        ]);

        $response->assertRedirect(route('forum.show', $post->post_id));
        $this->assertDatabaseHas('forum_posts', [
            'post_id' => $post->post_id,
            'title' => 'Judul Baru yang Diedit',
        ]);
    }

    public function test_non_author_cannot_update_or_close_others_post(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Postingan Milik Author',
            'content' => 'Hanya author yang berhak edit.',
            'status' => 'ACTIVE',
        ]);

        // Attempt edit
        $responseEdit = $this->actingAs($otherUser)->get(route('forum.edit', $post->post_id));
        $responseEdit->assertStatus(403);

        // Attempt update
        $responseUpdate = $this->actingAs($otherUser)->put(route('forum.update', $post->post_id), [
            'category_code' => 'GENERAL',
            'title' => 'Bajak Postingan',
            'content' => 'Saya bukan author.',
        ]);
        $responseUpdate->assertStatus(403);

        // Attempt close
        $responseClose = $this->actingAs($otherUser)->post(route('forum.close', $post->post_id));
        $responseClose->assertStatus(403);
    }

    public function test_author_can_close_their_post_so_it_becomes_closed(): void
    {
        $author = User::factory()->create();
        $post = ForumPost::create([
            'author_id' => $author->user_id,
            'category_code' => 'GENERAL',
            'title' => 'Item Sudah Terjual',
            'content' => 'Terima kasih, item sudah laku.',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($author)->post(route('forum.close', $post->post_id));
        $response->assertRedirect(route('forum.show', $post->post_id));

        $this->assertDatabaseHas('forum_posts', [
            'post_id' => $post->post_id,
            'status' => 'CLOSED',
        ]);
    }
}
