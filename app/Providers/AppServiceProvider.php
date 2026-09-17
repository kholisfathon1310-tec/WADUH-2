<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // Proyek ini memakai Bootstrap 5 (bukan Tailwind, yang merupakan default Laravel) —
        // tanpa ini, ->links() merender markup Tailwind yang tidak bergaya sama sekali.
        Paginator::useBootstrapFive();
    }
}
