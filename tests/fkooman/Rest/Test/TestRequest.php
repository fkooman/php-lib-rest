<?php

namespace fkooman\Rest\Test;

use fkooman\Http\Request;

class TestRequest
{
    public static function get($url, array $hdrs = [])
    {
        return self::request($url, 'GET', $hdrs);
    }

    public static function post($url, array $hdrs = [], array $postBody = [])
    {
        return self::request($url, 'POST', $hdrs, $postBody);
    }

    public static function put($url, array $hdrs = [])
    {
        return self::request($url, 'PUT', $hdrs);
    }

    public static function delete($url, array $hdrs = [])
    {
        return self::request($url, 'DELETE', $hdrs);
    }

    public static function request($url, $method, array $hdrs = [], array $postBody = [])
    {
        // parse the URL
        $parsedUrl = parse_url($url);

        if (array_key_exists('port', $parsedUrl)) {
            $serverPort = $parsedUrl['port'];
        } else {
            if ('http' === $parsedUrl['scheme']) {
                $serverPort = 80;
            } elseif ('https' === $parsedUrl['scheme']) {
                $serverPort = 443;
            } else {
                // unsupported scheme
                $serverPort = false;
            }
        }

        if (array_key_exists('query', $parsedUrl)) {
            $requestUrl = $parsedUrl['path'].'?'.$parsedUrl['query'];
        } else {
            $requestUrl = $parsedUrl['path'];
        }

        $srv = [
            'REQUEST_SCHEME' => $parsedUrl['scheme'],
            'SERVER_NAME' => $parsedUrl['host'],
            'SERVER_PORT' => $serverPort,
            'REQUEST_URI' => $requestUrl,
            'REQUEST_METHOD' => $method,
        ];

        $srv = array_merge($srv, $hdrs);

        return new Request($srv, $postBody);
    }
}
