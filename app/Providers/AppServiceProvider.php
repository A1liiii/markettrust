<?php

namespace App\Providers;

use App\Contracts\Services\AgreementServiceInterface;
use App\Contracts\Services\ConversationServiceInterface;
use App\Contracts\Services\FeeCalculationServiceInterface;
use App\Contracts\Services\MarketplaceServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\PayoutDestinationServiceInterface;
use App\Contracts\Services\SettlementServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\Models\Listing;
use App\Models\User;
use App\Policies\ListingPolicy;
use App\Services\AgreementService;
use App\Services\ConversationService;
use App\Services\FeeCalculationService;
use App\Services\MarketplaceService;
use App\Services\MidtransPaymentService;
use App\Services\NotificationService;
use App\Services\PayoutDestinationService;
use App\Services\SettlementService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(PaymentServiceInterface::class, MidtransPaymentService::class);
        $this->app->bind(FeeCalculationServiceInterface::class, FeeCalculationService::class);
        $this->app->bind(PayoutDestinationServiceInterface::class, PayoutDestinationService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
        $this->app->bind(AgreementServiceInterface::class, AgreementService::class);
        $this->app->bind(ConversationServiceInterface::class, ConversationService::class);
        $this->app->bind(MarketplaceServiceInterface::class, MarketplaceService::class);
        $this->app->bind(SettlementServiceInterface::class, SettlementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Listing::class, ListingPolicy::class);

        Gate::define('manage-catalog', function (User $user): bool {
            return $user->isAdmin() && $user->isActive();
        });
    }
}
