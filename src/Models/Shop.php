<?php
namespace Huel\Shopify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Shop extends Model {
    protected $table = 'shops';

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function login() {
        \Auth::login($this->user);
    }

    public function logout() {
        \Auth::logout();
    }

    public static function findByDomain($myshopifyDomain) {
        return Shop::where('shop_domain', $myshopifyDomain)->first();
    }

    public function getAPI() {
        $api = app('ShopifyAPI');

        $shopifyCredentials = [
            'SHOP_DOMAIN' => $this->shop_domain,
            'ACCESS_TOKEN' => $this->access_token
        ];

        //if the api key and shared secret is set in the shops table use the values for shopify api authentication (private app use)
        if (Schema::hasColumn('shops', 'api_key') && Schema::hasColumn('shops', 'shared_secret')) {
            $shopifyCredentials['API_KEY'] = $this->api_key;
            $shopifyCredentials['API_SECRET'] = $this->api_secret;
        }

        $api->setup($shopifyCredentials);

        return $api;
    }



    protected static function boot() {
        parent::boot();

        static::deleting(function($shop) {
             $shop->user()->delete();
        });
    }
}
