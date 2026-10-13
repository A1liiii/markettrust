<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected string $supabaseUrl;

    protected ?string $supabaseKey;

    protected string $defaultBucket;

    public function __construct()
    {
        $this->supabaseUrl = rtrim(config('services.supabase.url', env('SUPABASE_URL', 'https://abxvoixyidljckqmsjuw.supabase.co')), '/');
        $this->supabaseKey = config('services.supabase.key', env('SUPABASE_KEY', env('SUPABASE_ANON_KEY', null)));
        $this->defaultBucket = config('services.supabase.bucket', env('SUPABASE_BUCKET', 'forum_images'));
    }

    /**
     * Upload an uploaded file to Supabase Storage or fallback to local disk.
     */
    public function uploadFile(UploadedFile $file, ?string $bucket = null): string
    {
        $targetBucket = $bucket ?: $this->defaultBucket;

        // If Supabase credentials are not provided (e.g. local offline unit test), fallback to public disk
        if (empty($this->supabaseKey)) {
            $path = $file->store($targetBucket, 'public');

            return Storage::url($path);
        }

        try {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::random(40).'.'.$extension;
            $uploadUrl = "{$this->supabaseUrl}/storage/v1/object/{$targetBucket}/{$filename}";

            $response = Http::withHeaders([
                'apikey' => $this->supabaseKey,
                'Authorization' => 'Bearer '.$this->supabaseKey,
            ])->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $filename,
                ['Content-Type' => $file->getMimeType() ?: 'application/octet-stream']
            )->post($uploadUrl);

            if ($response->successful()) {
                return "{$this->supabaseUrl}/storage/v1/object/public/{$targetBucket}/{$filename}";
            }

            Log::warning('Supabase storage upload failed, falling back to local storage', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Exception during Supabase storage upload', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to local public disk
        $path = $file->store($targetBucket, 'public');

        return Storage::url($path);
    }

    /**
     * Delete a file from Supabase storage or local disk.
     */
    public function deleteFile(string $url, ?string $bucket = null): bool
    {
        $targetBucket = $bucket ?: $this->defaultBucket;

        if (str_contains($url, $this->supabaseUrl) && ! empty($this->supabaseKey)) {
            try {
                $filename = basename(parse_url($url, PHP_URL_PATH));
                $deleteUrl = "{$this->supabaseUrl}/storage/v1/object/{$targetBucket}/{$filename}";

                $response = Http::withHeaders([
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer '.$this->supabaseKey,
                ])->delete($deleteUrl);

                return $response->successful();
            } catch (\Throwable $e) {
                Log::warning('Failed deleting file from Supabase storage: '.$e->getMessage());

                return false;
            }
        }

        // Local storage delete
        $relativePath = Str::after($url, '/storage/');
        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->delete($relativePath);
        }

        return false;
    }
}
