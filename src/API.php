<?php
namespace Huel\Shopify;

class API {
    private $api_key = '';
    private $api_secret = '';
    private $shop_domain = '';
    private $access_token = '';


    private $ch;


    /**
     * Creates a new API instance. Data argument is passed onto setup().
     */
    public function __construct($data = null) {
        $this->setupFromConfig();
        if ($data) {
            $this->setup($data);
        }
    }

    /**
     * Resets setup back to configuration defaults.
     */
    public function setupFromConfig() {
        $this->api_key = config('huel_shopify.public_credentials.api_key');
        $this->api_secret = config('huel_shopify.public_credentials.api_secret');
        $this->shop_domain = config('huel_shopify.private_credentials.shop_domain');
        $this->access_token = config('huel_shopify.private_credentials.access_token');
    }




    /**
     * Overrides the default setup with the given settings.
     */
    public function setup($data) {
        if (isset($data['API_KEY'])) {
            $this->api_key = $data['API_KEY'];
        }
        if (isset($data['API_SECRET'])) {
            $this->api_secret = $data['API_SECRET'];
        }
        if (isset($data['SHOP_DOMAIN'])) {
            $match = null;
            preg_match('/(https?:\/\/)?([a-zA-Z0-9\-\.])+/', $data['SHOP_DOMAIN'], $match);
            $this->shop_domain = $match[0];
        }
        if (isset($data['ACCESS_TOKEN'])) {
            $this->access_token = $data['ACCESS_TOKEN'];
        }
    }




    /**
     * Verifies data returned by OAuth call
     */
    public function verifyRequest($data = NULL, $bypassTimeCheck = FALSE) 
    {
        $da = array();
        if (is_string($data)) {
            $each = explode('&', $data);
            foreach($each as $e) {
                list($key, $val) = explode('=', $e);
                $da[$key] = $val;
            }
        } elseif (is_array($data)) {
            $da = $data;
        } else {
            throw new \Exception('Data passed to verifyRequest() needs to be an array or URL-encoded string of key/value pairs.');
        }

        // Timestamp check; 1 hour tolerance
        if (!$bypassTimeCheck) {
            if (($da['timestamp'] - time() > 3600)) {
                throw new \Exception('Timestamp is greater than 1 hour old. To bypass this check, pass TRUE as the second argument to verifyRequest().');
            }
        }

        if (array_key_exists('hmac', $da)) {
            // HMAC Validation
            $queryString = http_build_query(array('code' => $da['code'], 'shop' => $da['shop'], 'timestamp' => $da['timestamp']));
            $match = $da['hmac'];
            $calculated = hash_hmac('sha256', $queryString, $this->api_secret);
        } else {
            $calculated = null;
        }

        return $calculated === $match;
    }

    /**
     * Calls API and returns OAuth Access Token, which will be needed for all future requests
     */
    public function getAccessToken($code = '') 
    {
        $reqdata = [
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret,
            'code' => $code
        ];
        $url = 'https://' . $this->shop_domain . '/admin/oauth/access_token';

        $data = $this->call('post', $url, $reqdata, [
            'verify_data' => false
        ]);
        return $data->access_token;
    }





    /**
     * Returns a string of the install URL for the app
     */
    public function installURL($redirect = null, $permissions = []) 
    {
        if (!$this->shop_domain) {
            throw new \Exception('Can\'t get install URL without knowing the shop domain! (use setup([\'SHOP_DOMAIN\' => ...]))');
        }

        // https://{shop}.myshopify.com/admin/oauth/authorize?client_id={api_key}&scope={scopes}&redirect_uri={redirect_uri}
        return 'https://'   . $this->shop_domain
                . '/admin/oauth/authorize?client_id=' . $this->api_key
                . '&scope=' . implode(',', $permissions)
                . ($redirect ? '&redirect_uri=' . urlencode($redirect) : '');
    }





    /**
     * Checks that data provided is in proper format
     * @example Removes http(s):// from SHOP_DOMAIN
     * @param string $key
     * @param string $value
     * @return string
     */
    private static function verifySetup($key = '', $value = '') 
    {
        $value = trim($value);
        switch ($key)
        {
            case 'SHOP_DOMAIN':
                preg_match('/(https?:\/\/)?([a-zA-Z0-9\-\.])+/', $value, $matched);
                return $matched[0];
                break;
            default:
                return $value;
        }
    }

    private static function stripSchemeAndHost($url) {
        $parsedUrl = parse_url($url);

        return sprintf(
            '%s%s%s',
            $parsedUrl['path'] ?? null,
            isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : null,
            isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : null,
        );
    }

    private static function parseLinksHeader(string $linkHeader) {
        $links = explode(', ', $linkHeader);
    
        $map = [];
        foreach ($links as $link) {
            $linkRegex = '/<(.*)>; rel="(.*)"/';
    
            $matches = [];
            preg_match($linkRegex, $link, $matches);
            
            if (count($matches) < 3) {
                continue;
            }

            $stripedUrl = self::stripSchemeAndHost($matches[1]);
            $parsedUrl = parse_url($matches[1]);

            $params = [];
            parse_str($parsedUrl['query'], $params); 

            $map[$matches[2]] = (object) [
                'url' => $stripedUrl,
                'params' => (object) $params,
            ];
        }
        return (object) $map;
    }


    public function call($method = 'GET', $url = '/', $data = [], $options = []) {

        // Setup options
        $defaults = [
            'charset' => 'UTF-8',
            'headers' => array(),
            'fail_on_error' => TRUE,
            'return_array' => FALSE,
            'all_data' => FALSE,
            'verify_data' => TRUE,
            'ignore_response' => FALSE,
            'timeout' => 10000000
        ];
        $options = array_merge($defaults, $options);

        // Data -> GET Params
        $method = strtoupper($method);
        if ($method === 'GET' && $data) {
            if (!is_array($data)) {
                $data = json_decode($data);
                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new \Exception('Data is malformed. Provide an array OR json-encoded object/array.');
                }
            }

            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= http_build_query($data);
        }

        // Setup headers
        $defaultHeaders = [];
        $defaultHeaders[] = 'Content-Type: application/json; charset=' . $options['charset'];
        $defaultHeaders[] = 'Accept: application/json';

        if ($this->access_token) {
            $defaultHeaders[] = 'X-Shopify-Access-Token: ' . $this->access_token;
        }
        $headers = array_merge($defaultHeaders, $options['headers']);

        // Setup URL
        if ($options['verify_data']) {
            $url = 'https://' . $this->api_key . ':' . $this->access_token . '@' . $this->shop_domain . $url;
        }

        // Setup CURL
        if (!$this->ch) {
            $this->ch = curl_init();
        }
        $ch = $this->ch;

        $curlOpts = array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Shopify API Wrapper',
            CURLOPT_FAILONERROR => $options['fail_on_error'],
            CURLOPT_VERBOSE => $options['all_data'],
            CURLOPT_HEADER => 1,
            CURLOPT_NOSIGNAL => 0,
            CURLOPT_TIMEOUT_MS => $options['timeout'],
        );

        if (!$data || $curlOpts[CURLOPT_CUSTOMREQUEST] === 'GET') {
            $curlOpts[CURLOPT_POSTFIELDS] = '';
        } else {
            if (is_array($data)) {
                $curlOpts[CURLOPT_POSTFIELDS] = json_encode($data);
            } else {
                // Detect if already a JSON object
                json_decode($request['DATA']);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $curlOpts[CURLOPT_POSTFIELDS] = $data;
                } else {
                    throw new \Exception('Data is malformed. Provide an array OR json-encoded object/array.');
                }
            }
        }

        if ($options['ignore_response']) {
            $curlOpts[CURLOPT_WRITEFUNCTION] = function($curl, $input) {
                return 0;
            };
            $curlOpts[CURLOPT_RETURNTRANSFER] = null;
            $curlOpts[CURLOPT_TIMEOUT_MS] = 1;
            $curlOpts[CURLOPT_NOSIGNAL] = 1;
        }
        curl_setopt_array($ch, $curlOpts);

        // Make request

        $response = null;
        $headerSize = null;
        $result = null;
        $info = null;
        $returnError = null;

        $retry = true;
        while($retry) {
            $retry = false;

            $response = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $result = json_decode(substr($response, $headerSize), $options['return_array']);

            $info = array_filter(array_map('trim', explode("\n", substr($response, 0, $headerSize))));


            // Parse errors
            $returnError = [
                'number' => curl_errno($ch),
                'msg' =>  curl_error($ch)
            ];
            // curl_close($ch);

    	    if ($returnError['number'])
    	    {
                if ($returnError['msg'] == 'The requested URL returned error: 429 Too Many Requests') {
                    \Log::info('[Shopify]: Sleeping for 4 seconds (Too Many Requests)');
                    sleep(4);
                    $retry = true;
                } else if (!$options['ignore_response']) {
                    throw new \Exception('ERROR #' . $returnError['number'] . ': ' . $returnError['msg']);
                }
            }

            foreach ($info as $header) {
                if (strpos($header, 'X-Shopify-Shop-Api-Call-Limit:') === 0) {
                    $limit = explode(':', $header)[1];
                    $value = explode('/', trim($limit))[0];

                    if ($value > 35) {
                        \Log::info('[Shopify]: Sleeping for 1 (rate limit)');
                        sleep(1);
                    }
                }

                if (stripos($header, 'Link:') === 0) {
                    $matches = [];
                    preg_match('/.*: (.*)/', $header, $matches);
                    $linkHeaderValue = $matches[1];

                    $result->links = self::parseLinksHeader($linkHeaderValue);
                }
            }

        }

        // Parse extra info
        $returnInfo = null;
        if ($options['all_data']) {
            foreach($info as $k => $header)
            {
    	        if (strpos($header, 'HTTP/') > -1)
    	        {
                    $returnInfo['HTTP_CODE'] = $header;
                    continue;
                }
                list($key, $val) = explode(':', $header);
                $returnInfo[trim($key)] = trim($val);
            }
        }

        if ($options['all_data']) {
            if ($options['return_array']) {
                $result['_ERROR'] = $returnError;
                $result['_INFO'] = $returnInfo;
            } else {
                $result->_ERROR = $returnError;
                $result->_INFO = $returnInfo;
            }
            return $result;
        }
        return $result;
    }
}
