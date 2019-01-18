<?php

namespace Huelify\Shopify\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class ViewMiddleware {
    public function __construct() {
    }

    public function handle($request, Closure $next) {
        // rewrite auth
        \Auth::loginUsingId(5);
        $user = \Auth::user();
        \Log::info('[Huelify] Middleware user session ' . json_encode(\Auth::user()));
        \Log::info('[Huelify] Middleware user info ' . json_encode($user));
        if ($user) {
            \Log::info('[Huelify] User var ' . json_encode($user));
            view()->share('user', $user);
            view()->share('shop', $user->shop);
        }

        return $next($request);
    }
}
