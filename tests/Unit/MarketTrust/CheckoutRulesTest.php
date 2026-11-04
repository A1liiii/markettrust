<?php

namespace Tests\Unit\MarketTrust;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\DTOs\PayoutDestinationDTO;
use App\Enums\FeeType;
use App\Enums\PayoutDestinationType;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\FeeCalculationService;
use App\Services\MarketplaceService;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class CheckoutRulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * TC-07-01-U01: Formula kalkulasi fee EXCLUDE khusus Marketplace
     */
    public function test_tc_07_01_fee_calculation_for_marketplace_exclude(): void
    {
        $feeService = new FeeCalculationService;

        // Uji harga Rp10.000 (fee 10% = Rp1.000)
        $result = $feeService->calculateForMarketplace(10000.0);

        $this->assertEquals(10000.0, $result->basePrice);
        $this->assertEquals(FeeType::EXCLUDE, $result->feeType);
        $this->assertEquals(1000.0, $result->feeAmount);
        $this->assertEquals(11000.0, $result->buyerTotal);
        $this->assertEquals(10000.0, $result->sellerNet);
    }

    /**
     * TC-07-01-U02: Batas harga dasar minimum Rp5.000 ditolak jika kurang
     */
    public function test_tc_07_01_fee_calculation_rejects_below_minimum_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $feeService = new FeeCalculationService;
        $feeService->calculateForMarketplace(4999.0);
    }

    /**
     * TC-07-02-U01: Validasi rekening seller mengembalikan false jika tidak ada rekening aktif
     */
    public function test_tc_07_02_seller_payout_validation_returns_false_when_unconfigured(): void
    {
        $seller = new User(['user_id' => 10, 'username' => 'seller_unconfigured']);

        $payoutDestService = Mockery::mock(PayoutDestinationServiceInterface::class);
        $payoutDestService->shouldReceive('getDestinationForUser')
            ->with($seller)
            ->andReturn(null);

        $convService = Mockery::mock(ConversationService::class);
        $notifService = Mockery::mock(NotificationServiceInterface::class);
        $feeService = new FeeCalculationService;

        $marketplaceService = new MarketplaceService(
            $feeService,
            $notifService,
            $payoutDestService,
            $convService
        );

        $isAvailable = $marketplaceService->validateSellerPayoutAvailable($seller);
        $this->assertFalse($isAvailable);
    }

    /**
     * TC-07-03-U01: Validasi rekening seller mengembalikan true jika memiliki rekening aktif
     */
    public function test_tc_07_03_seller_payout_validation_returns_true_when_configured(): void
    {
        $seller = new User(['user_id' => 11, 'username' => 'seller_configured']);

        $destinationDTO = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: 'Seller Mandiri',
            destinationProvider: 'BCA',
            destinationNumber: '1234567890'
        );

        $payoutDestService = Mockery::mock(PayoutDestinationServiceInterface::class);
        $payoutDestService->shouldReceive('getDestinationForUser')
            ->with($seller)
            ->andReturn($destinationDTO);
        $payoutDestService->shouldReceive('validateDestination')
            ->with($destinationDTO)
            ->andReturn(true);

        $convService = Mockery::mock(ConversationService::class);
        $notifService = Mockery::mock(NotificationServiceInterface::class);
        $feeService = new FeeCalculationService;

        $marketplaceService = new MarketplaceService(
            $feeService,
            $notifService,
            $payoutDestService,
            $convService
        );

        $isAvailable = $marketplaceService->validateSellerPayoutAvailable($seller);
        $this->assertTrue($isAvailable);
    }
}
