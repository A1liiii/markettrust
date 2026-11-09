<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * Upload an uploaded file to Supabase Storage with automatic fallback to local public disk.
     *
     * @throws RuntimeException
     */
    public function uploadFile(UploadedFile $file, ?string $bucket = null): string
    {
        $targetBucket = $bucket ?: $this->defaultBucket;

        // 1. Coba unggah ke Supabase Storage jika kredensial tersedia
        if (! empty($this->supabaseUrl) && ! empty($this->supabaseKey)) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
            $filename = Str::uuid()->toString().'.'.$extension;
            $uploadUrl = "{$this->supabaseUrl}/storage/v1/object/{$targetBucket}/{$filename}";

            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $contents = file_get_contents($file->getRealPath());

            if ($contents !== false) {
                try {
                    $response = Http::withHeaders($this->getHeaders())
                        ->withBody($contents, $mimeType)
                        ->timeout(8)
                        ->post($uploadUrl);

                    if ($response->successful()) {
                        return "{$this->supabaseUrl}/storage/v1/object/public/{$targetBucket}/{$filename}";
                    }

                    Log::warning('Supabase storage upload unsuccessful, falling back to local public disk', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Exception during Supabase storage upload, falling back to local public disk', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 2. Fallback tangguh ke penyimpanan lokal disk public Laravel
        try {
            $folder = $bucket ?: 'listings';
            $path = $file->store($folder, 'public');

            return Storage::url($path);
        } catch (\Throwable $localErr) {
            Log::error('Local storage upload failed: '.$localErr->getMessage());

            throw new RuntimeException('Gagal mengunggah gambar ke sistem penyimpanan: '.$localErr->getMessage(), 0, $localErr);
        }
    }

    /**
     * Delete a file from Supabase storage or local disk.
     */
    public function deleteFile(string $url, ?string $bucket = null): bool
    {
        // 1. Hapus jika URL berasal dari local storage
        if (str_starts_with($url, '/storage/') || str_contains($url, '/storage/')) {
            $localPath = preg_replace('#^.*?/storage/#', '', $url);
            if (! empty($localPath)) {
                return Storage::disk('public')->delete($localPath);
            }
        }

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
