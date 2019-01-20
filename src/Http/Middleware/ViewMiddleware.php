<?php

namespace Huelify\Shopify\Http\Middleware;

use Closure;

class ViewMiddleware {
    public function __construct() {
    }

    public function handle($request, Closure $next) {
        $user = \Auth::user();
        if ($user) {
            view()->share('user', $user);
            view()->share('shop', $user->shop);
        }

        return $next($request);
    }
}
