<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\StoreVariantRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Admin\UpdateVariantRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Tampilkan halaman utama kelola katalog master (kategori, produk, dan varian).
     */
    public function index(Request $request): View
    {
        $currentTab = $request->query('tab', 'categories');
        if (! in_array($currentTab, ['categories', 'products', 'variants'], true)) {
            $currentTab = 'categories';
        }

        $search = $request->query('q');

        // 1. Data Kategori
        $categoriesQuery = ProductCategory::withCount('products')->orderBy('category_name');
        if ($search && $currentTab === 'categories') {
            $categoriesQuery->where('category_name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }
        $categories = $categoriesQuery->paginate(15, ['*'], 'cat_page')->withQueryString();

        // 2. Data Produk
        $productsQuery = Product::with('category')->withCount('variants')->orderBy('product_name');
        if ($search && $currentTab === 'products') {
            $productsQuery->where('product_name', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($q) => $q->where('category_name', 'like', "%{$search}%"));
        }
        $products = $productsQuery->paginate(15, ['*'], 'prod_page')->withQueryString();

        // 3. Data Varian
        $variantsQuery = ProductVariant::with(['product.category'])->withCount('listings')->orderBy('variant_name');
        if ($search && $currentTab === 'variants') {
            $variantsQuery->where('variant_name', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($q) => $q->where('product_name', 'like', "%{$search}%"));
        }
        $variants = $variantsQuery->paginate(15, ['*'], 'var_page')->withQueryString();

        // Dropdown data untuk modal form
        $allActiveCategories = ProductCategory::where('is_active', true)->orderBy('category_name')->get();
        $allActiveProducts = Product::with('category')->where('is_active', true)->orderBy('product_name')->get();

        return view('admin.catalog.index', [
            'currentTab' => $currentTab,
            'search' => $search,
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'allActiveCategories' => $allActiveCategories,
            'allActiveProducts' => $allActiveProducts,
        ]);
    }

    /**
     * Simpan kategori produk baru.
     */
    public function storeCategory(StoreCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'categories'])
            ->with('success', 'Kategori produk baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data kategori produk.
     */
    public function updateCategory(UpdateCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'categories'])
            ->with('success', 'Data kategori produk berhasil diperbarui.');
    }

    /**
     * Ubah status aktif/nonaktif kategori produk.
     */
    public function toggleCategoryStatus(ProductCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $statusLabel = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.catalog.index', ['tab' => 'categories'])
            ->with('success', "Kategori '{$category->category_name}' berhasil {$statusLabel}.");
    }

    /**
     * Simpan produk master baru.
     */
    public function storeProduct(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'products'])
            ->with('success', 'Master produk baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data produk master.
     */
    public function updateProduct(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'products'])
            ->with('success', 'Data produk master berhasil diperbarui.');
    }

    /**
     * Ubah status aktif/nonaktif produk master.
     */
    public function toggleProductStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $statusLabel = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.catalog.index', ['tab' => 'products'])
            ->with('success', "Produk '{$product->product_name}' berhasil {$statusLabel}.");
    }

    /**
     * Simpan varian produk baru.
     */
    public function storeVariant(StoreVariantRequest $request): RedirectResponse
    {
        ProductVariant::create($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'variants'])
            ->with('success', 'Master varian produk baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data varian produk.
     */
    public function updateVariant(UpdateVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->validated());

        return redirect()->route('admin.catalog.index', ['tab' => 'variants'])
            ->with('success', 'Data varian produk berhasil diperbarui.');
    }

    /**
     * Ubah status aktif/nonaktif varian produk.
     */
    public function toggleVariantStatus(ProductVariant $variant): RedirectResponse
    {
        $variant->update(['is_active' => ! $variant->is_active]);

        $statusLabel = $variant->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.catalog.index', ['tab' => 'variants'])
            ->with('success', "Varian '{$variant->variant_name}' berhasil {$statusLabel}.");
    }
}
