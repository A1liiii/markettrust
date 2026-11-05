<?php

namespace Tests\Unit\MarketTrust;

use App\Contracts\Services\FeeCalculationServiceInterface;
use App\DTOs\AgreementSnapshotDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Models\Agreement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AgreementRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji nilai-nilai enum AgreementStatus dan FeeType.
     */
    public function test_agreement_enums_values(): void
    {
        $this->assertSame('DRAFT', AgreementStatus::DRAFT->value);
        $this->assertSame('FINALIZED', AgreementStatus::FINALIZED->value);
        $this->assertSame('REJECTED', AgreementStatus::REJECTED->value);

        $this->assertSame('EXCLUDE', FeeType::EXCLUDE->value);
        $this->assertSame('INCLUDE', FeeType::INCLUDE->value);
    }

    /**
     * Uji kalkulasi fee mode EXCLUDE (Fee ditambahkan ke Buyer, Seller terima penuh).
     */
    public function test_fee_calculation_service_exclude_mode(): void
    {
        /** @var FeeCalculationServiceInterface $feeService */
        $feeService = app(FeeCalculationServiceInterface::class);

        $basePrice = 100000.0;
        $result = $feeService->calculate($basePrice, FeeType::EXCLUDE);

        $this->assertEquals($basePrice, $result->basePrice);
        $this->assertSame(FeeType::EXCLUDE, $result->feeType);
        $this->assertGreaterThan(0, $result->feeAmount);
        $this->assertEquals($basePrice + $result->feeAmount, $result->buyerTotal);
        $this->assertEquals($basePrice, $result->sellerNet);
    }

    /**
     * Uji kalkulasi fee mode INCLUDE (Buyer bayar pokok, Seller dipotong fee).
     */
    public function test_fee_calculation_service_include_mode(): void
    {
        /** @var FeeCalculationServiceInterface $feeService */
        $feeService = app(FeeCalculationServiceInterface::class);

        $basePrice = 100000.0;
        $result = $feeService->calculate($basePrice, FeeType::INCLUDE);

        $this->assertEquals($basePrice, $result->basePrice);
        $this->assertSame(FeeType::INCLUDE, $result->feeType);
        $this->assertGreaterThan(0, $result->feeAmount);
        $this->assertEquals($basePrice, $result->buyerTotal);
        $this->assertEquals($basePrice - $result->feeAmount, $result->sellerNet);
    }

    /**
     * Uji penolakan transaksi di bawah harga batas minimum Rp5.000.
     */
    public function test_fee_calculation_service_rejects_under_minimum(): void
    {
        /** @var FeeCalculationServiceInterface $feeService */
        $feeService = app(FeeCalculationServiceInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('di bawah batas minimum');

        $feeService->calculate(4000.0, FeeType::EXCLUDE);
    }

    /**
     * Uji pembentukan AgreementSnapshotDTO dari model Agreement.
     */
    public function test_agreement_snapshot_dto_creation(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->category_id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->product_id]);

        $agreement = Agreement::create([
            'created_by' => $buyer->user_id,
            'buyer_id' => $buyer->user_id,
            'seller_id' => $seller->user_id,
            'variant_id' => $variant->variant_id,
            'item_title' => 'Akun Developer Premium',
            'item_description' => 'Akun terverifikasi dengan billing aktif',
            'quantity' => 1,
            'base_price' => 150000.00,
            'fee_type' => FeeType::EXCLUDE->value,
            'fee_amount' => 4500.00,
            'buyer_total' => 154500.00,
            'seller_net' => 150000.00,
            'delivery_method' => 'DIRECT_TRANSFER',
            'delivery_within_minutes' => 60,
            'revision_no' => 1,
            'status' => AgreementStatus::FINALIZED->value,
            'finalized_at' => now(),
        ]);

        $snapshot = AgreementSnapshotDTO::fromModel($agreement);

        $this->assertInstanceOf(AgreementSnapshotDTO::class, $snapshot);
        $this->assertSame($agreement->agreement_id, $snapshot->agreementId);
        $this->assertEquals(150000.00, $snapshot->basePrice);
        $this->assertEquals(154500.00, $snapshot->buyerTotal);
        $this->assertEquals(150000.00, $snapshot->sellerNet);
        $this->assertSame('DIRECT_TRANSFER', $snapshot->deliveryMethod);
    }
}
