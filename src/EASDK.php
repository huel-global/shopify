<?php
namespace Huel\Shopify;

class EASDK {
    private $api;

    public function __construct() {
        $api = app('ShopifyAPI');
    }

    public function getAPI() {
        return $this->api;
    }

    public function setAPI($api) {
        $this->api = $api;
    }

    public function hostedRedirect($shopDomain, $url) {
        return '<!DOCTYPE html><html><head></head><body><script type="text/javascript"> if (window.top == window.self) { window.top.location.href = "'.$url.'"; } else { message = JSON.stringify({ message: "Shopify.API.remoteRedirect", data: { location: "'.$url.'" } }); window.parent.postMessage(message, "https://'.$shopDomain.'"); } </script></body></html>';
    }
}
