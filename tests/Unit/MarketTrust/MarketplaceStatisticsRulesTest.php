<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Marketplace\MarketplaceSearchRequest;
use App\Models\Listing;
use PHPUnit\Framework\TestCase;

class MarketplaceStatisticsRulesTest extends TestCase
{
    /**
     * TC-04-01: Validasi aturan parameter pencarian, filter bertingkat, dan pengurutan di MarketplaceSearchRequest.
     */
    public function test_tc_04_01_marketplace_search_request_rules(): void
    {
        $request = new MarketplaceSearchRequest;

        $this->assertTrue($request->authorize());

        $rules = $request->rules();

        $this->assertArrayHasKey('q', $rules);
        $this->assertContains('max:100', $rules['q']);

        $this->assertArrayHasKey('category_id', $rules);
        $this->assertContains('integer', $rules['category_id']);

        $this->assertArrayHasKey('product_id', $rules);
        $this->assertContains('integer', $rules['product_id']);

        $this->assertArrayHasKey('variant_id', $rules);
        $this->assertContains('integer', $rules['variant_id']);

        $this->assertArrayHasKey('sort', $rules);
        $this->assertContains('in:latest,price_asc,price_desc', $rules['sort']);
    }

    /**
     * TC-04-01: Aturan format durasi estimasi penyerahan pada model Listing Marketplace.
     */
    public function test_tc_04_01_listing_estimated_delivery_formatting_and_minutes(): void
    {
        $listingMinute = new Listing([
            'estimated_delivery_value' => 30,
            'estimated_delivery_unit' => 'MINUTE',
        ]);
        $this->assertSame('30 Menit', $listingMinute->estimated_delivery_formatted);
        $this->assertSame(30, $listingMinute->estimated_minutes);

        $listingHour = new Listing([
            'estimated_delivery_value' => 2,
            'estimated_delivery_unit' => 'HOUR',
        ]);
        $this->assertSame('2 Jam', $listingHour->estimated_delivery_formatted);
        $this->assertSame(120, $listingHour->estimated_minutes);

        $listingDay = new Listing([
            'estimated_delivery_value' => 1,
            'estimated_delivery_unit' => 'DAY',
        ]);
        $this->assertSame('1 Hari', $listingDay->estimated_delivery_formatted);
        $this->assertSame(1440, $listingDay->estimated_minutes);
    }
}
