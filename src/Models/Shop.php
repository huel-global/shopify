<?php
namespace Huelify\Shopify\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class Shop extends Model {
    protected $table = 'shops';

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    // public function login() {
        // \Log::info('[Huelify] Try starting a session ' . json_encode($this->user));
        // \Log::info('[Huelify] Try validating a session ' . json_encode(\Auth::validateCredentials($this->user)));
        // \Auth::login($this->user);
        // \Log::info('log in ID: ' . $this->user->id);
        // \Auth::loginUsingId(5);
        // $credentials = $this->user->only('email');
    // }

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
