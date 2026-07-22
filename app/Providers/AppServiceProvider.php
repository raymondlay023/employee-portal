<?php

namespace App\Providers;

use App\Authorization\Roles;
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
        // Implicitly grant Admin role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Roles::ADMIN) ? true : null;
        });
    }
}
