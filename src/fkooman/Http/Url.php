<?php

/**
* Copyright 2015 François Kooman <fkooman@tuxed.net>
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

class Url
{
    /** @var array */
    private $srv;

    public function __construct(array $srv)
    {
        $requiredKeys = array(
            'SERVER_NAME',
            'SERVER_PORT',
            'REQUEST_URI',
            'SCRIPT_NAME',
            'QUERY_STRING'  // always set, but '' if no query string present
        );
        $optionalKeys = array(
            'PATH_INFO',
            'HTTPS',
            'HTTP_X_FORWARDED_PROTO'
        );

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $srv)) {
                throw new RuntimeException(sprintf('missing key "%s"', $key));
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

    public function getHost()
    {
        return $this->srv['SERVER_NAME'];
    }

    public function getPort()
    {
        return $this->srv['SERVER_PORT'];
    }

    public function getPathInfo()
    {
        return $this->srv['PATH_INFO'];
    }

    /**
     * The query string, or empty string if no query string was provided.
     */
    public function getQueryString()
    {
        return $this->srv['QUERY_STRING'];
    }

    /**
     * The query string as an array or empty array if no query string was
     * provided.
     */
    public function getQueryArray()
    {
        if ('' === $this->getQueryString()) {
            return array();
        }
        $qArray = array();
        parse_str($this->getQueryString(), $qArray);
        return $qArray;
    }

    /**
     * Return a specific query parameter value, empty string if the key was set
     * but has an empty value, null if the key did not exist.
     */
    public function getQueryParameter($key)
    {
        $qArray = $this->getQueryArray();
        if (array_key_exists($key, $qArray)) {
            return $qArray[$key];
        }
        return null;
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
        if (0 === strpos($this->srv['REQUEST_URI'], $this->srv['SCRIPT_NAME'])) {
            // no rewriting in the web server
            return $this->srv['SCRIPT_NAME'];
        }
        // rewriting in the web server enabled
        return dirname($this->srv['SCRIPT_NAME']);
    }

    public function getRootUri()
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
        return $authority . $this->getRoot();
    }
}
