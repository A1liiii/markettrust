<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    protected string $supabaseUrl;

    protected ?string $supabaseKey;

    protected string $defaultBucket;

    public function __construct()
    {
        $this->supabaseUrl = rtrim((string) config('services.supabase.url', ''), '/');
        $this->supabaseKey = config('services.supabase.secret_key');
        $this->defaultBucket = (string) config('services.supabase.bucket', 'forum_images');
    }

    /**
     * Upload an uploaded file to Supabase Storage.
     *
     * @throws RuntimeException
     */
    public function uploadFile(UploadedFile $file, ?string $bucket = null): string
    {
        if (empty($this->supabaseUrl) || empty($this->supabaseKey)) {
            throw new RuntimeException('Konfigurasi Supabase Storage (SUPABASE_URL / SUPABASE_SECRET_KEY) belum disetel.');
        }

        $targetBucket = $bucket ?: $this->defaultBucket;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.$extension;
        $uploadUrl = "{$this->supabaseUrl}/storage/v1/object/{$targetBucket}/{$filename}";

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Gagal membaca isi file yang diunggah.');
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withBody($contents, $mimeType)
                ->post($uploadUrl);

            if ($response->successful()) {
                return "{$this->supabaseUrl}/storage/v1/object/public/{$targetBucket}/{$filename}";
            }

            Log::error('Supabase storage upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $message = $response->json('message') ?: $response->body() ?: 'HTTP '.$response->status();

            throw new RuntimeException("Gagal mengunggah gambar ke Supabase Storage: {$message}");
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Exception during Supabase storage upload', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Terjadi kesalahan koneksi saat mengunggah gambar ke Supabase Storage: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a file from Supabase storage.
     */
    public function deleteFile(string $url, ?string $bucket = null): bool
    {
        $targetBucket = $bucket ?: $this->defaultBucket;

        if (empty($this->supabaseUrl) || empty($this->supabaseKey)) {
            return false;
        }

        $publicPrefix = "{$this->supabaseUrl}/storage/v1/object/public/{$targetBucket}/";

        if (! str_starts_with($url, $publicPrefix)) {
            return false;
        }

        $objectPath = ltrim(substr($url, strlen($publicPrefix)), '/');
        if ($objectPath === '') {
            return false;
        }

        try {
            $deleteUrl = "{$this->supabaseUrl}/storage/v1/object/{$targetBucket}/{$objectPath}";

            $response = Http::withHeaders($this->getHeaders())->delete($deleteUrl);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Failed deleting file from Supabase storage: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Build the request headers for Supabase Storage REST API.
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        $headers = [
            'apikey' => (string) $this->supabaseKey,
        ];

        // Secret keys starting with sb_secret_ are not JWTs, so they must not be sent as Bearer tokens.
        // Only attach Authorization Bearer if the key is explicitly a legacy JWT.
        if (! empty($this->supabaseKey) && ! str_starts_with($this->supabaseKey, 'sb_secret_') && substr_count($this->supabaseKey, '.') === 2) {
            $headers['Authorization'] = 'Bearer '.$this->supabaseKey;
        }

        return $headers;
    }
}
