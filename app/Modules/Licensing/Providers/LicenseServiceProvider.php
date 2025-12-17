<?php

namespace App\Modules\Licensing\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Licensing\Services\LicenseService;

class LicenseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('license', function ($app) {
            return new LicenseService();
        });
    }

    public function boot()
    {
        // Optional: Middleware registration or Blade directives can go here
    }
}
