<?php

/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace fkooman\Http;

use RuntimeException;
use InvalidArgumentException;

/**
 * Url Class.
 *
 * Helper class to easily determine the request URL. It has a number of
 * important functions:
 *
 * - Determine the REST route (i.e.: PATH_INFO) that should be executed. There
 *   are a number of scenarios we want to support. For example of the requested
 *   route is /foo/bar the request could look like any of these:
 *   - http://www.example.org/app/index.php/foo/bar (in folder 'app'
 *   - http://www.example.org/app/foo/bar (URL rewriting to index.php)
 *   - http://www.example.org/index.php/foo/bar (in the root)
 *   - http://www.example.org/foo/bar (in the root with rewriting)
 *
 *   We want to make it possible to figure out the (relative and absolute) path
 *   of the application, if it runs in the root we want to return '/', if it
 *   runs in a folder we want to return /app/.
 *
 *   In essence, we need to detect whether or not URL rewriting is active and
 *   whether or not the application is located in the root or in a folder.
 *   Various server variables give different results for different web servers
 *   and configurations and also for different PHP versions and whether or not
 *   php-fpm is used. This all needs to be made consisent.
 *
 * - Make the query parameters easily available.
 *
 * - Make it possible to reconstruct the full request URL from the server
 *   variables.
 */
class Url
{
    /** @var array */
    private $srv;

    /**
     * Create the Url object.
     *
     * @param array $srv the server variables, typically $_SERVER
     */
    public function __construct(array $srv)
    {
        $requiredKeys = array(
            'SERVER_NAME',
            'SERVER_PORT',
            'REQUEST_URI',
            'QUERY_STRING',
        );
        $optionalKeys = array(
            'PATH_INFO',
            'HTTPS',
            'HTTP_X_FORWARDED_PROTO',
        );

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $srv)) {
                throw new RuntimeException(sprintf('missing key "%s"', $key));
            }
            // we only want ASCII to avoid the need for mb_* functions
            if (false === mb_check_encoding($srv[$key], 'US-ASCII')) {
                // not ASCII
                throw new InvalidArgumentException('non ASCII characters detected');
            }
            $this->srv[$key] = $srv[$key];
        }
        foreach ($optionalKeys as $key) {
            if (!array_key_exists($key, $srv)) {
                $this->srv[$key] = null;
            } else {
                $this->srv[$key] = $srv[$key];
            }
        }
    }

    /**
     * Get the URL scheme.
     *
     * @return string the URL scheme
     */
    public function getScheme()
    {
        $h = $this->srv['HTTPS'];
        if (null !== $h && '' !== $h && 'off' !== $h) {
            return 'https';
        }
        $p = $this->srv['HTTP_X_FORWARDED_PROTO'];
        if ('https' === $p) {
            return 'https';
        }

        return 'http';
    }

    /**
     * Get the URL host.
     *
     * @return string the URL host
     */
    public function getHost()
    {
        return $this->srv['SERVER_NAME'];
    }

    /**
     * Get the URL port.
     *
     * @return int the URL port
     */
    public function getPort()
    {
        return intval($this->srv['SERVER_PORT']);
    }

    /**
     * Get the PATH_INFO of the request. This is the part after the actual
     * script name. So for example if the REQUEST_URI is '/index.php/foo' the
     * PATH_INFO is '/foo'.
     *
     * @return string the PATH_INFO or '/' if no PATH_INFO is available
     */
    public function getPathInfo()
    {
        // On CentOS 7 with PHP 5.4 PATH_INFO is null when rewriting is
        // enabled and you go to the root. On Fedora 22 with PHP 5.6 PATH_INFO
        // is '/' in the same scenario.
        if (null === $this->srv['PATH_INFO']) {
            return '/';
        }

        return $this->srv['PATH_INFO'];
    }

    /**
     * The query string.
     *
     * @return string the query string, or empty string if no query string
     *                is available
     */
    public function getQueryString()
    {
        return $this->srv['QUERY_STRING'];
    }

    /**
     * The query string as array.
     *
     * @return array the query string as array. The array will be empty if
     *               the query string is empty
     */
    public function getQueryStringAsArray()
    {
        if ('' === $this->getQueryString()) {
            return array();
        }
        $queryStringArray = array();
        parse_str($this->getQueryString(), $queryStringArray);

        return $queryStringArray;
    }

    /**
     * Return a specific query parameter value.
     *
     * @param string $key the query parameter key to get
     *
     * @return mixed the query parameter value if it is set, or null if the
     *               parameter is not available
     */
    public function getQueryParameter($key)
    {
        $queryStringArray = $this->getQueryStringAsArray();
        if (array_key_exists($key, $queryStringArray)) {
            return $queryStringArray[$key];
        }

        return;
    }

    /**
     * Get the REQUEST_URI without PATH_INFO and QUERY_STRING, taking server
     * rewriting in consideration.
     *
     * Example (without URL rewriting):
     * https://www.example.org/foo/index.php/bar?a=b will return:
     * '/foo/index.php'
     *
     * Example (with URL rewriting to index.php):
     * https://www.example.org/foo/bar?a=b will return:
     * '/foo'
     *
     * Example (with URL rewriting to index.php without sub folder):
     * https://www.example.org/bar?a=b will return:
     * ''
     */
    public function getRoot()
    {
        $r = $this->srv['REQUEST_URI'];
        $q = $this->srv['QUERY_STRING'];
        $p = $this->srv['PATH_INFO'];

        // remove query string from request uri if set
        if (0 !== strlen($q)) {
            $r = substr($r, 0, strlen($r) - strlen($q) - 1);
        }

        // remove path info from request uri if set
        if (null !== $p && 0 !== strlen($p)) {
            $r = substr($r, 0, strlen($r) - strlen($p));
        }

        // if path info is not set, remove the last path component, it is
        // probably the PHP script
        if (null === $p) {
            $r = substr($r, 0, strrpos($r, '/'));
        }

        if (0 === strlen($r) || strrpos($r, '/') !== strlen($r) - 1) {
            $r .= '/';
        }

        return $r;
    }

    /**
     * Get the root as a full URL.
     */
    public function getRootUrl()
    {
        return $this->getAuthority().$this->getRoot();
    }

    /**
     * Get the authority part of the URL. That is, the scheme, host and
     * optional port if it is not a standard port.
     *
     * @return string the authority part of the URL
     */
    public function getAuthority()
    {
        $s = $this->getScheme();
        $h = $this->getHost();
        $p = $this->getPort();

        // FIXME: we should also inspect the target port in case of HTTP
        // proxy to see if we need to specify a (different) port
        if ('https' === $s && 443 === $p || 'http' === $s && 80 === $p) {
            $authority = sprintf('%s://%s', $s, $h);
        } else {
            $authority = sprintf('%s://%s:%s', $s, $h, $p);
        }

        return $authority;
    }

    /**
     * Get the URL as a string.
     */
    public function toString()
    {
        return $this->getAuthority().$this->srv['REQUEST_URI'];
    }

    /**
     * Get the URL as a string if it is coerced to string.
     */
    public function __toString()
    {
        return $this->toString();
    }
}
