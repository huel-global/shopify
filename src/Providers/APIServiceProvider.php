<?php
namespace Huel\Shopify\Providers;

use Illuminate\Support\ServiceProvider;

use Huel\Shopify\API;


class APIServiceProvider extends ServiceProvider {
    public function register() {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/huel_shopify.php' => config_path('huel_shopify.php')
        ]);

        $this->app->bind('ShopifyAPI', function($app) {
            return new API;
        });

        $this->app->bind(Huel\Shopify\API::class, function($app) {
            return new API;
        });

        $this->app->bind('App\Shop', function($app) {
            if (\Auth::user()) {
                return \Auth::user()->shop;
            }
        });
    }

    public function boot() {
        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes.php';
        }
    }
}
