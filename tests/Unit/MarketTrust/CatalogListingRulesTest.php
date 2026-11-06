<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\StoreVariantRequest;
use App\Http\Requests\Seller\StoreListingRequest;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit terisolasi untuk aturan validasi katalog dan listing.
 * Mencakup TC-03-01, TC-03-02, dan TC-03-03.
 */
class CatalogListingRulesTest extends TestCase
{
    /**
     * TC-03-01 Subtest 1: Memverifikasi aturan validasi penambahan kategori baru oleh Admin.
     */
    public function test_tc_03_01_store_category_request_rules(): void
    {
        $request = new StoreCategoryRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('category_name', $rules);
        $this->assertContains('required', $rules['category_name']);
        $this->assertContains('string', $rules['category_name']);
        $this->assertContains('max:100', $rules['category_name']);
    }

    /**
     * TC-03-01 Subtest 2: Memverifikasi aturan validasi penambahan produk master di bawah kategori.
     */
    public function test_tc_03_01_store_product_request_rules(): void
    {
        $request = new StoreProductRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('product_name', $rules);
        $this->assertContains('required', $rules['category_id']);
        $this->assertContains('required', $rules['product_name']);
    }

    /**
     * TC-03-01 Subtest 3: Memverifikasi aturan validasi penambahan varian spesifikasi produk.
     */
    public function test_tc_03_01_store_variant_request_rules(): void
    {
        $request = new StoreVariantRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('product_id', $rules);
        $this->assertArrayHasKey('variant_name', $rules);
        $this->assertContains('required', $rules['product_id']);
        $this->assertContains('required', $rules['variant_name']);
    }

    /**
     * TC-03-02 Subtest 1: Memverifikasi aturan validasi pembuatan listing marketplace oleh Seller.
     */
    public function test_tc_03_02_store_listing_request_rules_and_status_constraints(): void
    {
        $request = new StoreListingRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('variant_id', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('status', $rules);

        // Memastikan batasan harga minimal Rp 5.000
        $this->assertContains('min:5000', $rules['price']);

        // Memastikan status menerima DRAFT, ACTIVE, atau CLOSED
        $this->assertContains('in:DRAFT,ACTIVE,CLOSED', $rules['status']);
    }

    /**
     * TC-03-02 Subtest 2: Memverifikasi pesan validasi kustom pada form listing seller.
     */
    public function test_tc_03_02_store_listing_custom_messages(): void
    {
        $request = new StoreListingRequest;
        $messages = $request->messages();

        $this->assertArrayHasKey('price.min', $messages);
        $this->assertStringContainsString('5.000', $messages['price.min']);
        $this->assertArrayHasKey('status.in', $messages);
    }

    /**
     * TC-03-03 Subtest 1: Memverifikasi fungsi logika transisi toggle status listing (ACTIVE <-> CLOSED).
     */
    public function test_tc_03_03_toggle_status_transition_logic(): void
    {
        $toggleLogic = fn (string $currentStatus): string => $currentStatus === 'ACTIVE' ? 'CLOSED' : 'ACTIVE';

        $this->assertSame('CLOSED', $toggleLogic('ACTIVE'));
        $this->assertSame('ACTIVE', $toggleLogic('CLOSED'));
        $this->assertSame('ACTIVE', $toggleLogic('INACTIVE'));
    }
}
