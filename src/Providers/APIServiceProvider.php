<?php
namespace Huelify\Shopify\Providers;

use Illuminate\Support\ServiceProvider;

use Huelify\Shopify\API;


class APIServiceProvider extends ServiceProvider {
    public function register() {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/huelify_shopify.php' => config_path('huelify_shopify.php')
        ]);

        $this->app->bind('ShopifyAPI', function($app) {
            return new API;
        });

        $this->app->bind(Huelify\Shopify\API::class, function($app) {
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
