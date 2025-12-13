<?php

namespace Inmoflow\PremiumGallery;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PremiumGalleryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'premium-gallery');

        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/premium-gallery'),
        ], 'premium-gallery-assets');

        // Register Assets
        FilamentAsset::register([
            Css::make('premium-gallery', __DIR__ . '/../resources/css/premium-gallery.css'),
        ], 'inmoflow/premium-gallery');

        // Register Routes
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => ['web', 'auth'], // Using web+auth for simplicity as per current web.php
            'as' => 'api.media.',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    public function register()
    {
        //
    }
}
