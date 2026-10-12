<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreListingRequest;
use App\Http\Requests\Seller\UpdateListingRequest;
use App\Models\Listing;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListingController extends Controller
{
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

        return view('seller.listings.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Simpan penawaran listing baru ke database.
     */
    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['seller_id'] = $request->user()->user_id;

        Listing::create($data);

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

        $listing->update($request->validated());

        return redirect()->route('seller.listings.index')
            ->with('success', "Penawaran listing '{$listing->title}' berhasil diperbarui.");
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
