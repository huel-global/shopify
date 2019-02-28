<?php
namespace Huel\Shopify\Providers;

use Illuminate\Support\ServiceProvider;

use Huel\Shopify\EASDK;


class EASDKServiceProvider extends ServiceProvider {
    public function register() {
        $this->publishes([
            __DIR__.'/../database/' => database_path('migrations')
        ]);

        $this->app->bind('ShopifyEASDK', function($app) {
            return new EASDK;
        });

        $this->app->bind(Huel\Shopify\EASDK::class, function($app) {
            return new EASDK;
        });

    }

    public function boot() {

    }
}
