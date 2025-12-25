<?php

namespace Vega\FaceLogin;

use Illuminate\Support\ServiceProvider;

class FaceLoginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load Routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // Load Views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'face-login');

        // Load Migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Publish Assets (Models and JS)
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/face-login'),
        ], 'face-login-views');

        // Note: The face-api.js models should be placed in public/face-api-login/models
        // You can add a command or instructions to copy them.
    }
}
