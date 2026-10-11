<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tamu yang belum login tidak dapat mengakses halaman manajemen katalog.
     */
    public function test_guest_cannot_access_catalog_management(): void
    {
        $response = $this->get('/admin/catalog');

        $response->assertRedirect('/login');
    }

    /**
     * Pengguna dengan account_type USER tidak dapat mengakses halaman admin katalog.
     */
    public function test_regular_user_cannot_access_catalog_management(): void
    {
        $user = User::factory()->create([
            'account_type' => 'USER',
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->get('/admin/catalog');

        $response->assertForbidden();
    }

    /**
     * Admin dengan status SUSPENDED tidak dapat mengakses halaman admin katalog.
     */
    public function test_suspended_admin_cannot_access_catalog_management(): void
    {
        $admin = User::factory()->admin()->suspended()->create();

        $response = $this->actingAs($admin)->get('/admin/catalog');

        $response->assertForbidden();
    }

    /**
     * Admin aktif dapat mengakses halaman index katalog.
     */
    public function test_active_admin_can_view_catalog_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/catalog');

        $response->assertOk();
        $response->assertSee('Kelola Katalog Digital');
        $response->assertSee('Kategori Produk');
        $response->assertSee('Identitas Produk');
        $response->assertSee('Spesifikasi Varian');
    }

    /**
     * Admin dapat menambahkan kategori baru.
     */
    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/catalog/categories', [
            'category_name' => 'Akun Game Digital',
            'description' => 'Akun game berbagai publisher ternama',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('product_categories', [
            'category_name' => 'Akun Game Digital',
            'description' => 'Akun game berbagai publisher ternama',
            'is_active' => true,
        ]);
    }

    /**
     * Nama kategori yang duplikat ditolak oleh validasi.
     */
    public function test_duplicate_category_name_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        ProductCategory::factory()->create(['category_name' => 'Voucher Game']);

        $response = $this->actingAs($admin)->post('/admin/catalog/categories', [
            'category_name' => 'Voucher Game',
            'description' => 'Duplikat',
        ]);

        $response->assertSessionHasErrors('category_name');
    }

    /**
     * Admin dapat memperbarui kategori yang sudah ada.
     */
    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create(['category_name' => 'Nama Lama']);

        $response = $this->actingAs($admin)->put("/admin/catalog/categories/{$category->category_id}", [
            'category_name' => 'Nama Baru Diperbarui',
            'description' => 'Deskripsi baru',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('product_categories', [
            'category_id' => $category->category_id,
            'category_name' => 'Nama Baru Diperbarui',
        ]);
    }

    /**
     * Admin dapat men-toggle status aktif kategori.
     */
    public function test_admin_can_toggle_category_status(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch("/admin/catalog/categories/{$category->category_id}/toggle");

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'categories']));
        $this->assertDatabaseHas('product_categories', [
            'category_id' => $category->category_id,
            'is_active' => false,
        ]);
    }

    /**
     * Admin dapat membuat produk master baru di bawah kategori aktif.
     */
    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/catalog/products', [
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends: Bang Bang',
            'description' => 'Game MOBA 5v5 populer',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'category_id' => $category->category_id,
            'product_name' => 'Mobile Legends: Bang Bang',
            'is_active' => true,
        ]);
    }

    /**
     * Nama produk yang sama dalam kategori yang sama ditolak.
     */
    public function test_duplicate_product_name_in_same_category_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create();
        Product::factory()->create([
            'category_id' => $category->category_id,
            'product_name' => 'Valorant',
        ]);

        $response = $this->actingAs($admin)->post('/admin/catalog/products', [
            'category_id' => $category->category_id,
            'product_name' => 'Valorant',
        ]);

        $response->assertSessionHasErrors('product_name');
    }

    /**
     * Nama produk yang sama di kategori berbeda diperbolehkan.
     */
    public function test_same_product_name_in_different_category_is_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        $cat1 = ProductCategory::factory()->create();
        $cat2 = ProductCategory::factory()->create();

        Product::factory()->create([
            'category_id' => $cat1->category_id,
            'product_name' => 'Item Premium',
        ]);

        $response = $this->actingAs($admin)->post('/admin/catalog/products', [
            'category_id' => $cat2->category_id,
            'product_name' => 'Item Premium',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'category_id' => $cat2->category_id,
            'product_name' => 'Item Premium',
        ]);
    }

    /**
     * Admin dapat memperbarui produk master.
     */
    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['product_name' => 'Produk Awal']);

        $response = $this->actingAs($admin)->put("/admin/catalog/products/{$product->product_id}", [
            'category_id' => $product->category_id,
            'product_name' => 'Produk Hasil Update',
            'description' => 'Deskripsi produk terbarui',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'product_name' => 'Produk Hasil Update',
        ]);
    }

    /**
     * Admin dapat men-toggle status aktif produk.
     */
    public function test_admin_can_toggle_product_status(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch("/admin/catalog/products/{$product->product_id}/toggle");

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'products']));
        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'is_active' => false,
        ]);
    }

    /**
     * Admin dapat membuat varian baru di bawah produk master.
     */
    public function test_admin_can_create_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/catalog/variants', [
            'product_id' => $product->product_id,
            'variant_name' => 'Akun Rank Mythic Glory',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->product_id,
            'variant_name' => 'Akun Rank Mythic Glory',
            'is_active' => true,
        ]);
    }

    /**
     * Nama varian duplikat pada produk yang sama ditolak.
     */
    public function test_duplicate_variant_name_under_same_product_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->product_id,
            'variant_name' => '1000 Diamond',
        ]);

        $response = $this->actingAs($admin)->post('/admin/catalog/variants', [
            'product_id' => $product->product_id,
            'variant_name' => '1000 Diamond',
        ]);

        $response->assertSessionHasErrors('variant_name');
    }

    /**
     * Admin dapat memperbarui data varian.
     */
    public function test_admin_can_update_variant(): void
    {
        $admin = User::factory()->admin()->create();
        $variant = ProductVariant::factory()->create(['variant_name' => 'Varian Awal']);

        $response = $this->actingAs($admin)->put("/admin/catalog/variants/{$variant->variant_id}", [
            'product_id' => $variant->product_id,
            'variant_name' => 'Varian Baru Updated',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_variants', [
            'variant_id' => $variant->variant_id,
            'variant_name' => 'Varian Baru Updated',
        ]);
    }

    /**
     * Admin dapat men-toggle status aktif varian produk.
     */
    public function test_admin_can_toggle_variant_status(): void
    {
        $admin = User::factory()->admin()->create();
        $variant = ProductVariant::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch("/admin/catalog/variants/{$variant->variant_id}/toggle");

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'variants']));
        $this->assertDatabaseHas('product_variants', [
            'variant_id' => $variant->variant_id,
            'is_active' => false,
        ]);
    }
}
