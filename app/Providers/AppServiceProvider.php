<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;

use Illuminate\Support\ServiceProvider;
use App\Services\PaymentService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
    {
        // ESTA LÍNEA ES VITAL
        Payment::observe(PaymentObserver::class);
    }
}