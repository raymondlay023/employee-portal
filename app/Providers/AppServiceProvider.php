<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Authorization\Roles;

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
        // Implicitly grant Admin role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Roles::ADMIN) ? true : null;
        });
    }
}
