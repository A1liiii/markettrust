<?php

namespace Tests\Feature\Forum;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForumSupabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.supabase.url' => 'https://mock-ref.supabase.co',
            'services.supabase.secret_key' => 'sb_secret_mock_key_987654321',
            'services.supabase.bucket' => 'forum_images',
        ]);

        Storage::fake('public');
    }

    protected function createVariant(): ProductVariant
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
            'variant_name' => 'Mythic Glory',
            'is_active' => true,
        ]);
    }

    public function test_upload_file_successfully_returns_public_supabase_url_in_forum_images_bucket(): void
    {
        Http::fake([
            'https://mock-ref.supabase.co/storage/v1/object/forum_images/*' => Http::response(['Key' => 'forum_images/mock.png'], 200),
        ]);

        $service = app(SupabaseStorageService::class);
        $file = UploadedFile::fake()->image('test_screenshot.png', 400, 300);

        $url = $service->uploadFile($file);

        $this->assertStringStartsWith('https://mock-ref.supabase.co/storage/v1/object/public/forum_images/', $url);
        $this->assertStringEndsWith('.png', $url);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_apikey_header_is_sent_and_authorization_bearer_is_omitted_for_sb_secret_key(): void
    {
        Http::fake([
            'https://mock-ref.supabase.co/storage/v1/object/forum_images/*' => Http::response(['Key' => 'forum_images/mock.jpg'], 200),
        ]);

        $service = app(SupabaseStorageService::class);
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $service->uploadFile($file);

        Http::assertSent(function (Request $request) {
            $hasCorrectApikey = $request->hasHeader('apikey', 'sb_secret_mock_key_987654321');
            $noBearerAuth = ! $request->hasHeader('Authorization');

            return $hasCorrectApikey && $noBearerAuth;
        });
    }

    public function test_upload_fails_when_configuration_is_missing_without_local_storage_fallback(): void
    {
        config([
            'services.supabase.secret_key' => null,
        ]);

        $service = app(SupabaseStorageService::class);
        $file = UploadedFile::fake()->image('test.jpg', 200, 200);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Konfigurasi Supabase Storage');

        try {
            $service->uploadFile($file);
        } finally {
            $this->assertEmpty(Storage::disk('public')->allFiles());
        }
    }

    public function test_post_creation_fails_when_supabase_returns_error_and_no_post_or_local_file_is_saved(): void
    {
        Http::fake([
            'https://mock-ref.supabase.co/storage/v1/object/forum_images/*' => Http::response([
                'statusCode' => '500',
                'error' => 'Internal Server Error',
                'message' => 'Storage backend error',
            ], 500),
        ]);

        $user = User::factory()->create();
        $variant = $this->createVariant();
        $file = UploadedFile::fake()->image('broken_upload.jpg', 300, 300);

        $response = $this->actingAs($user)->post('/forum', [
            'category_code' => 'WTB',
            'variant_id' => $variant->variant_id,
            'title' => 'WTB Akun Idaman',
            'content' => 'Mencari akun dengan spek lengkap.',
            'price_offer' => 250000,
            'images' => [$file],
        ]);

        $response->assertSessionHasErrors(['images']);
        $this->assertDatabaseMissing('forum_posts', [
            'title' => 'WTB Akun Idaman',
        ]);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_delete_file_targets_correct_supabase_endpoint_with_apikey(): void
    {
        Http::fake([
            'https://mock-ref.supabase.co/storage/v1/object/forum_images/*' => Http::response(['message' => 'Deleted'], 200),
        ]);

        $service = app(SupabaseStorageService::class);
        $fileUrl = 'https://mock-ref.supabase.co/storage/v1/object/public/forum_images/2026/03/sample-uuid.jpg';

        $deleted = $service->deleteFile($fileUrl);

        $this->assertTrue($deleted);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://mock-ref.supabase.co/storage/v1/object/forum_images/2026/03/sample-uuid.jpg'
                && $request->method() === 'DELETE'
                && $request->hasHeader('apikey', 'sb_secret_mock_key_987654321');
        });
    }

    public function test_delete_file_ignores_foreign_url_and_does_not_call_supabase(): void
    {
        Http::fake();

        $service = app(SupabaseStorageService::class);

        $foreignUrl = 'https://another-storage-service.com/images/sample.jpg';
        $resultForeign = $service->deleteFile($foreignUrl);

        $wrongBucketUrl = 'https://mock-ref.supabase.co/storage/v1/object/public/other_bucket/sample.jpg';
        $resultWrongBucket = $service->deleteFile($wrongBucketUrl);

        $this->assertFalse($resultForeign);
        $this->assertFalse($resultWrongBucket);
        Http::assertNothingSent();
    }
}
