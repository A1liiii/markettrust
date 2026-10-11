<?php

namespace Tests\Unit;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\ConversationServiceInterface;
use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\DTOs\PayoutDestinationDTO;
use App\Enums\AgreementStatus;
use App\Enums\FeeType;
use App\Enums\PayoutDestinationType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Services\AgreementService;
use App\Services\ConversationService;
use App\Services\FeeCalculationService;
use App\Services\NotificationService;
use App\Services\PayoutDestinationService;
use App\Services\TransactionService;
use Tests\TestCase;

class CoreDomainSetupTest extends TestCase
{
    /**
     * 1. Pilar Status: Verifikasi seluruh enum domain memiliki nilai dan label yang valid.
     */
    public function test_enums_have_correct_values_and_labels(): void
    {
        // AgreementStatus
        $this->assertEquals('DRAFT', AgreementStatus::DRAFT->value);
        $this->assertEquals('FINALIZED', AgreementStatus::FINALIZED->value);
        $this->assertEquals('REJECTED', AgreementStatus::REJECTED->value);
        $this->assertEquals('CANCELLED', AgreementStatus::CANCELLED->value);

        // TransactionStatus
        $this->assertEquals('WAITING_PAYMENT', TransactionStatus::WAITING_PAYMENT->value);
        $this->assertEquals('PAID', TransactionStatus::PAID->value);
        $this->assertEquals('DELIVERED', TransactionStatus::DELIVERED->value);
        $this->assertEquals('CONFIRMED', TransactionStatus::CONFIRMED->value);
        $this->assertEquals('DISPUTED', TransactionStatus::DISPUTED->value);
        $this->assertEquals('COMPLETED', TransactionStatus::COMPLETED->value);
        $this->assertEquals('REFUNDED', TransactionStatus::REFUNDED->value);
        $this->assertEquals('CANCELLED', TransactionStatus::CANCELLED->value);

        // FeeType & SourceType
        $this->assertEquals('INCLUDE', FeeType::INCLUDE->value);
        $this->assertEquals('EXCLUDE', FeeType::EXCLUDE->value);
        $this->assertEquals('MARKETPLACE', TransactionSourceType::MARKETPLACE->value);
        $this->assertEquals('FORUM', TransactionSourceType::FORUM->value);

        // PayoutDestinationType
        $this->assertEquals('BANK', PayoutDestinationType::BANK->value);
        $this->assertEquals('EWALLET', PayoutDestinationType::EWALLET->value);
        $this->assertEquals('QRIS', PayoutDestinationType::QRIS->value);
        $this->assertStringContainsString('Bank', PayoutDestinationType::BANK->label());
    }

    /**
     * 2. Pilar DTO: Verifikasi instansiasi DTO (Fee, AgreementSnapshot, PayoutDestination).
     */
    public function test_dtos_can_be_instantiated_and_serialized(): void
    {
        $feeDto = new FeeCalculationResultDTO(10000, 'EXCLUDE', 1000, 11000, 10000);
        $this->assertEquals(10000, $feeDto->basePrice);
        $this->assertEquals(1000, $feeDto->feeAmount);

        $feeArray = $feeDto->toArray();
        $this->assertArrayHasKey('base_price', $feeArray);
        $this->assertArrayHasKey('fee_amount', $feeArray);

        $payoutDto = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: 'Muhammad Zinky',
            destinationProvider: 'BCA',
            destinationNumber: '1234567890'
        );
        $this->assertEquals(PayoutDestinationType::BANK, $payoutDto->destinationType);
        $this->assertEquals('BCA', $payoutDto->destinationProvider);

        $payoutArray = $payoutDto->toArray();
        $this->assertEquals('BANK', $payoutArray['destination_type']);
        $this->assertEquals('Muhammad Zinky', $payoutArray['destination_holder']);
    }

    /**
     * 3. Pilar Kontrak: Verifikasi service implements kontrak dan terdaftar di Service Container.
     */
    public function test_service_contracts_are_bound_in_container_and_implement_interfaces(): void
    {
        $txService = app(TransactionServiceInterface::class);
        $this->assertInstanceOf(TransactionService::class, $txService);

        $feeService = app(FeeCalculationServiceInterface::class);
        $this->assertInstanceOf(FeeCalculationService::class, $feeService);

        $payoutService = app(PayoutDestinationServiceInterface::class);
        $this->assertInstanceOf(PayoutDestinationService::class, $payoutService);

        $notifService = app(NotificationServiceInterface::class);
        $this->assertInstanceOf(NotificationService::class, $notifService);

        $agreeService = app(AgreementServiceInterface::class);
        $this->assertInstanceOf(AgreementService::class, $agreeService);

        $convService = app(ConversationServiceInterface::class);
        $this->assertInstanceOf(ConversationService::class, $convService);
    }

    /**
     * 4. Pilar Fee: Verifikasi formula fee berjenjang sesuai tabel SKPL 2.5.2.
     */
    public function test_fee_calculation_service_matches_skpl_2_5_2_tiers(): void
    {
        $calculator = new FeeCalculationService();

        // Rentang 1: Rp5.000 - Rp10.000 (10% flat)
        $res1 = $calculator->calculate(10000, FeeType::EXCLUDE);
        $this->assertEquals(1000.0, $res1->feeAmount);
        $this->assertEquals(11000.0, $res1->buyerTotal);
        $this->assertEquals(10000.0, $res1->sellerNet);

        // Rentang 2: Rp10.001 - Rp50.000 (menurun bertahap 10% -> 5%)
        $res2 = $calculator->calculate(50000, FeeType::INCLUDE);
        $this->assertEquals(2500.0, $res2->feeAmount);
        $this->assertEquals(50000.0, $res2->buyerTotal);
        $this->assertEquals(47500.0, $res2->sellerNet);

        // Rentang 3: Rp50.001 - Rp100.000 (5% flat)
        $res3 = $calculator->calculate(100000, FeeType::EXCLUDE);
        $this->assertEquals(5000.0, $res3->feeAmount);
        $this->assertEquals(105000.0, $res3->buyerTotal);

        // Rentang 4: Rp100.001 - Rp499.000 (menurun bertahap 5% -> 1.4%)
        $res4 = $calculator->calculate(499000, FeeType::EXCLUDE);
        $this->assertEquals(7000.0, $res4->feeAmount);

        // Rentang 5: Rp499.001 - Rp1.000.000 (menurun bertahap 1.4% -> 1%)
        $res5 = $calculator->calculate(1000000, FeeType::EXCLUDE);
        $this->assertEquals(10000.0, $res5->feeAmount);

        // Di atas Rp1jt s/d < Rp10jt: Flat Rp10.000
        $res6 = $calculator->calculate(5000000, FeeType::EXCLUDE);
        $this->assertEquals(10000.0, $res6->feeAmount);

        // Rp10.000.000: Rp20.000
        $res7 = $calculator->calculate(10000000, FeeType::EXCLUDE);
        $this->assertEquals(20000.0, $res7->feeAmount);

        // Rp20.000.000: Rp30.000
        $res8 = $calculator->calculate(20000000, FeeType::EXCLUDE);
        $this->assertEquals(30000.0, $res8->feeAmount);
    }

    /**
     * 5. Pilar Tujuan Payout: Verifikasi validasi format tujuan payout rekening.
     */
    public function test_payout_destination_validation(): void
    {
        $service = new PayoutDestinationService();

        // Valid Bank
        $validBank = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: 'Muhammad Ali',
            destinationProvider: 'BCA',
            destinationNumber: '8880123456'
        );
        $this->assertTrue($service->validateDestination($validBank));

        // Invalid Bank (missing number)
        $invalidBank = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: 'Muhammad Ali',
            destinationProvider: 'BCA',
            destinationNumber: ''
        );
        $this->assertFalse($service->validateDestination($invalidBank));

        // Invalid (empty holder name)
        $invalidHolder = new PayoutDestinationDTO(
            destinationType: PayoutDestinationType::BANK,
            destinationHolder: '   ',
            destinationProvider: 'BCA',
            destinationNumber: '123'
        );
        $this->assertFalse($service->validateDestination($invalidHolder));
    }
}
