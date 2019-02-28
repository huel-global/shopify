<?php
namespace Huel\Shopify\Models;

use Illuminate\Database\Eloquent\Model;

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
        $api->setup([
            'SHOP_DOMAIN' => $this->shop_domain,
            'ACCESS_TOKEN' => $this->access_token
        ]);
        return $api;
    }



    protected static function boot() {
        parent::boot();

        static::deleting(function($shop) {
             $shop->user()->delete();
        });
    }
}
