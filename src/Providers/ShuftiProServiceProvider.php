<?php

namespace Asciisd\KycShuftiPro\Providers;

use Asciisd\KycShuftiPro\Drivers\ShuftiProDriver;
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;
use Asciisd\KycShuftiPro\Services\ShuftiProDocumentService;
use Asciisd\KycShuftiPro\Services\ShuftiProWebhookService;
use Illuminate\Support\ServiceProvider;

class ShuftiProServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register ShuftiPro services
        $this->app->singleton(ShuftiProApiService::class);
        $this->app->singleton(ShuftiProDocumentService::class);
        $this->app->singleton(ShuftiProWebhookService::class);

        // Register ShuftiPro driver
        $this->app->singleton(ShuftiProDriver::class, function ($app) {
            return new ShuftiProDriver(
                $app->make(ShuftiProApiService::class),
                $app->make(ShuftiProDocumentService::class),
                $app->make(ShuftiProWebhookService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/shuftipro.php' => config_path('shuftipro.php'),
        ], 'shuftipro-config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/shuftipro.php',
            'shuftipro'
        );
    }
}
