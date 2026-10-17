<?php

namespace App\Http\Controllers\Seller;

use App\Contracts\Services\MarketplaceServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreListingRequest;
use App\Http\Requests\Seller\UpdateListingRequest;
use App\Models\Listing;
use App\Models\ProductCategory;
use App\Services\SupabaseStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function __construct(
        protected SupabaseStorageService $storageService,
    ) {}

    /**
     * Tampilkan daftar listing milik seller yang sedang login.
     */
    public function index(Request $request): View
    {
        $sellerId = $request->user()->user_id;

        // Metrik Ringkasan Khusus Seller ini
        $totalListings = Listing::where('seller_id', $sellerId)->count();
        $activeListings = Listing::where('seller_id', $sellerId)->where('status', 'ACTIVE')->count();
        $draftListings = Listing::where('seller_id', $sellerId)->where('status', 'DRAFT')->count();
        $closedListings = Listing::where('seller_id', $sellerId)->where('status', 'CLOSED')->count();

        // Query listing dengan filter
        $query = Listing::with(['variant.product.category'])
            ->where('seller_id', $sellerId)
            ->latest('listing_id');

        $statusFilter = $request->query('status');
        if (in_array($statusFilter, ['ACTIVE', 'DRAFT', 'CLOSED'], true)) {
            $query->where('status', $statusFilter);
        }

        $search = $request->query('q');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('variant', function ($vq) use ($search) {
                        $vq->where('variant_name', 'like', "%{$search}%")
                            ->orWhereHas('product', fn ($pq) => $pq->where('product_name', 'like', "%{$search}%"));
                    });
            });
        }

        $listings = $query->paginate(12)->withQueryString();

        return view('seller.listings.index', [
            'listings' => $listings,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'totalListings' => $totalListings,
            'activeListings' => $activeListings,
            'draftListings' => $draftListings,
            'closedListings' => $closedListings,
        ]);
    }

    /**
     * Tampilkan formulir untuk membuat listing baru.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Listing::class);

        // Ambil kategori beserta produk dan varian yang semuanya aktif
        $categories = ProductCategory::with(['products' => function ($pq) {
            $pq->where('is_active', true)->with(['variants' => function ($vq) {
                $vq->where('is_active', true)->orderBy('variant_name');
            }])->orderBy('product_name');
        }])
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $submissionToken = (string) Str::uuid();

        return view('seller.listings.create', [
            'categories' => $categories,
            'submissionToken' => $submissionToken,
        ]);
    }

    /**
     * Simpan penawaran listing baru ke database dengan proteksi submit ganda (idempotency).
     */
    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sellerId = $request->user()->user_id;
        $data['seller_id'] = $sellerId;

        $token = $request->input('submission_token');

        // 1. Idempotency Token Guard: Mencegah form instance yang sama diproses ganda
        if ($token) {
            $tokenCacheKey = "listing_token_processed_{$sellerId}_{$token}";
            if (Cache::has($tokenCacheKey)) {
                return redirect()->route('seller.listings.index')
                    ->with('success', 'Penawaran listing baru berhasil dipublikasikan.');
            }
        }

        // 2. Atomic Lock Per Seller & Payload Hash: Mencegah request ganda serentak
        $payloadSignature = md5(json_encode([
            $data['variant_id'] ?? null,
            $data['title'] ?? null,
            $data['price'] ?? null,
            $data['delivery_method'] ?? null,
            $data['estimated_delivery_value'] ?? null,
            $data['estimated_delivery_unit'] ?? null,
            $data['status'] ?? null,
        ]));
        $lockKey = "seller_listing_store_{$sellerId}_{$payloadSignature}";

        $lock = Cache::lock($lockKey, 5);

        if (! $lock->get()) {
            return redirect()->route('seller.listings.index')
                ->with('success', 'Penawaran listing baru berhasil dipublikasikan.');
        }

        $newUploadedUrls = [];

        try {
            if ($token && Cache::has($tokenCacheKey)) {
                return redirect()->route('seller.listings.index')
                    ->with('success', 'Penawaran listing baru berhasil dipublikasikan.');
            }

            // Unggah gambar jika ada
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file instanceof UploadedFile && count($newUploadedUrls) < 4) {
                        $newUploadedUrls[] = $this->storageService->uploadFile($file);
                    }
                }
            }

            // Hapus field non-database sebelum penyimpanan
            unset($data['submission_token'], $data['images']);
            $data['image_urls'] = ! empty($newUploadedUrls) ? array_values($newUploadedUrls) : null;

            Listing::create($data);

            if ($token) {
                Cache::put($tokenCacheKey, true, now()->addMinutes(5));
            }
        } catch (\Throwable $e) {
            // Rollback gambar yang sudah terunggah jika gagal simpan
            foreach ($newUploadedUrls as $url) {
                $this->storageService->deleteFile($url);
            }

            throw $e;
        } finally {
            optional($lock)->release();
        }

        return redirect()->route('seller.listings.index')
            ->with('success', 'Penawaran listing baru berhasil dipublikasikan.');
    }

    /**
     * Tampilkan formulir untuk mengedit listing milik seller.
     */
    public function edit(Request $request, Listing $listing): View
    {
        Gate::authorize('update', $listing);

        $categories = ProductCategory::with(['products' => function ($pq) {
            $pq->where('is_active', true)->with(['variants' => function ($vq) {
                $vq->orderBy('variant_name');
            }])->orderBy('product_name');
        }])
            ->where('is_active', true)
            ->orderBy('category_name')
            ->get();

        $listing->load(['variant.product.category']);

        return view('seller.listings.edit', [
            'listing' => $listing,
            'categories' => $categories,
        ]);
    }

    /**
     * Perbarui data penawaran listing milik seller.
     */
    public function update(UpdateListingRequest $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $data = $request->validated();
        $existingImages = $listing->all_image_urls;

        // 1. Gambar lama yang secara eksplisit dipertahankan
        $keepImages = $request->input('keep_images', []);
        $finalImages = [];
        if (is_array($keepImages)) {
            foreach ($keepImages as $url) {
                if (in_array($url, $existingImages, true)) {
                    $finalImages[] = $url;
                }
            }
        }

        // 2. Unggah gambar baru
        $newUploadedUrls = [];
        if ($request->hasFile('images')) {
            try {
                foreach ($request->file('images') as $file) {
                    if ($file instanceof UploadedFile && (count($finalImages) + count($newUploadedUrls)) < 4) {
                        $newUploadedUrls[] = $this->storageService->uploadFile($file);
                    }
                }
            } catch (\Throwable $e) {
                // Rollback gambar baru yang baru terunggah jika error
                foreach ($newUploadedUrls as $url) {
                    $this->storageService->deleteFile($url);
                }
                throw $e;
            }
        }

        // 3. Hapus gambar yang dibuang dari storage setelah upload baru sukses
        $discardedImages = array_diff($existingImages, $finalImages);
        foreach ($discardedImages as $discardedUrl) {
            $this->storageService->deleteFile($discardedUrl);
        }

        $allImages = array_merge($finalImages, $newUploadedUrls);

        unset($data['images'], $data['keep_images']);
        $data['image_urls'] = ! empty($allImages) ? array_values($allImages) : null;

        $listing->update($data);

        return redirect()->route('seller.listings.index')
            ->with('success', "Penawaran listing '{$listing->title}' berhasil diperbarui.");
    }

    /**
     * Tampilkan pratinjau listing privat milik seller.
     */
    public function preview(Request $request, Listing $listing): View
    {
        Gate::authorize('preview', $listing);

        $listing->load(['seller', 'variant.product.category']);

        $marketStats = app(MarketplaceServiceInterface::class)
            ->getProductMarketStats($listing->variant_id);

        return view('marketplace.show', [
            'listing' => $listing,
            'marketStats' => $marketStats,
            'isPreview' => true,
        ]);
    }

    /**
     * Alihkan status listing antara AKTIF dan DITUTUP.
     */
    public function toggleStatus(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $newStatus = $listing->status === 'ACTIVE' ? 'CLOSED' : 'ACTIVE';
        $listing->update(['status' => $newStatus]);

        $label = $newStatus === 'ACTIVE' ? 'diaktifkan kembali' : 'ditutup';

        return redirect()->route('seller.listings.index')
            ->with('success', "Listing '{$listing->title}' berhasil {$label}.");
    }
}
