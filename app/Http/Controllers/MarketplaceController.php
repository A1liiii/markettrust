<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MarketplaceServiceInterface;
use App\Http\Requests\Marketplace\MarketplaceSearchRequest;
use App\Models\Listing;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceServiceInterface $marketplaceService,
    ) {}

    /**
     * Tampilkan katalog pencarian dan filter listing aktif di Marketplace.
     */
    public function index(MarketplaceSearchRequest $request): View
    {
        $validated = $request->validated();
        $listings = $this->marketplaceService->searchListings($validated, 8);

        // Ambil pohon kategori -> produk -> varian aktif untuk dropdown filter
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->with(['products' => function ($pq) {
                $pq->where('is_active', true)
                    ->with(['variants' => function ($vq) {
                        $vq->where('is_active', true)->orderBy('variant_name');
                    }])
                    ->orderBy('product_name');
            }])
            ->orderBy('category_name')
            ->get();

        $totalActiveListings = Listing::query()->active()->count();

        return view('marketplace.index', [
            'listings' => $listings,
            'categories' => $categories,
            'filters' => $validated,
            'totalActiveListings' => $totalActiveListings,
        ]);
    }

    /**
     * Tampilkan halaman detail listing publik dan informasi pasar.
     */
    public function show(Request $request, Listing $listing): View
    {
        // Otorisasi: listing nonaktif hanya boleh dilihat oleh seller pemilik (preview)
        if ($listing->status !== 'ACTIVE') {
            if (! $request->user() || ! $request->user()->can('preview', $listing)) {
                abort(404, 'Listing tidak ditemukan atau sedang tidak aktif.');
            }
        }

        $listing->load(['seller', 'variant.product.category']);

        $period = (string) $request->query('period', '30d');
        $marketStats = $this->marketplaceService->getProductMarketStats($listing->variant_id, $period);

        return view('marketplace.show', [
            'listing' => $listing,
            'marketStats' => $marketStats,
            'isPreview' => $listing->status !== 'ACTIVE',
        ]);
    }
}
