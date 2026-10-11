<?php

namespace App\Providers;

use App\Models\Listing;
use App\Models\User;
use App\Policies\ListingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
