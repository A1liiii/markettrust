<?php

namespace Tests\Unit;

use App\DTOs\AgreementSnapshotDTO;
use App\DTOs\FeeCalculationResultDTO;
use App\Enums\AgreementStatus;
use App\Enums\TransactionStatus;
use App\Services\AgreementService;
use App\Services\ConversationService;
use App\Services\NotificationService;
use App\Services\TransactionService;
use Tests\TestCase;

class CoreDomainSetupTest extends TestCase
{
    public function test_enums_have_correct_values_and_labels(): void
    {
        $this->assertEquals('DRAFT', AgreementStatus::DRAFT->value);
        $this->assertEquals('FINALIZED', AgreementStatus::FINALIZED->value);
        $this->assertEquals('REJECTED', AgreementStatus::REJECTED->value);
        $this->assertEquals('CANCELLED', AgreementStatus::CANCELLED->value);

        $this->assertEquals('WAITING_PAYMENT', TransactionStatus::WAITING_PAYMENT->value);
        $this->assertEquals('PAID', TransactionStatus::PAID->value);
        $this->assertEquals('DELIVERED', TransactionStatus::DELIVERED->value);
        $this->assertEquals('CONFIRMED', TransactionStatus::CONFIRMED->value);
        $this->assertEquals('DISPUTED', TransactionStatus::DISPUTED->value);
        $this->assertEquals('COMPLETED', TransactionStatus::COMPLETED->value);
        $this->assertEquals('REFUNDED', TransactionStatus::REFUNDED->value);
        $this->assertEquals('CANCELLED', TransactionStatus::CANCELLED->value);
    }

    public function test_dtos_can_be_instantiated(): void
    {
        $feeDto = new FeeCalculationResultDTO(10000, 'EXCLUDE', 1000, 11000, 10000);
        $this->assertEquals(10000, $feeDto->basePrice);
        $this->assertEquals(1000, $feeDto->feeAmount);

        $array = $feeDto->toArray();
        $this->assertArrayHasKey('base_price', $array);
        $this->assertArrayHasKey('fee_amount', $array);
    }

    public function test_service_classes_exist_and_can_be_instantiated(): void
    {
        $this->assertInstanceOf(AgreementService::class, new AgreementService());
        $this->assertInstanceOf(TransactionService::class, new TransactionService());
        $this->assertInstanceOf(ConversationService::class, new ConversationService());
        $this->assertInstanceOf(NotificationService::class, new NotificationService());
    }
}
