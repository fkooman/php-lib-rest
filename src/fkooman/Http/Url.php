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
            'SCRIPT_NAME',
            'QUERY_STRING',  // always set, but '' if no query string present
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

        $this->fixScriptName();
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
        // is '/'
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
     * Return a specific query parameter value.
     *
     * @param string $key the query parameter key to get
     *
     * @return mixed the query parameter value if it is set, or null if the
     *               parameter is not available
     */
    public function getQueryParameter($key)
    {
        $qArray = $this->getQueryArray();
        if (array_key_exists($key, $qArray)) {
            return $qArray[$key];
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
        if (0 === strpos($this->srv['REQUEST_URI'], $this->srv['SCRIPT_NAME'])) {
            // no rewriting in the web server
            return $this->srv['SCRIPT_NAME'].'/';
        }
        // rewriting in the web server enabled
        $rootPath = dirname($this->srv['SCRIPT_NAME']);

        if ('/' === $rootPath) {
            return '/';
        }

        return $rootPath.'/';
    }

    /**
     * Get the folder of getRoot(). This is useful for referencing resources
     * like CSS and JS files independent on whether URL rewriting and/or
     * PATH_INFO is used.
     */
    public function getRootFolder()
    {
        $rootPath = dirname($this->srv['SCRIPT_NAME']);

        if ('/' === $rootPath) {
            return '/';
        }

        return $rootPath.'/';
    }

    /**
     * Get the root folder as a full URL.
     */
    public function getRootFolderUrl()
    {
        return $this->getAuthority().$this->getRootFolder();
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
     * Get the root as a full URL.
     */
    public function getRootUrl()
    {
        return $this->getAuthority().$this->getRoot();
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

    /**
     * PHP-FPM has a bug in combination with Apache where the SCRIPT_NAME
     * also includes the PATH_INFO. This is fixed in PHP >= 5.6 it seems.
     * Unfortunately CentOS 7 is affected by this issue.
     * See: https://bugs.php.net/bug.php?id=65641.
     */
    private function fixScriptName()
    {
        $pathInfo = $this->srv['PATH_INFO'];
        $scriptName = $this->srv['SCRIPT_NAME'];

        if (null !== $pathInfo) {
            // check if SCRIPT_NAME ends with PATH_INFO, if so, remove
            // PATH_INFO from SCRIPT_NAME and return that instead
            if (0 === strpos(strrev($scriptName), strrev($pathInfo))) {
                $scriptName = substr($scriptName, 0, strlen($scriptName) - strlen($pathInfo));
            }
        }
        $this->srv['SCRIPT_NAME'] = $scriptName;
    }
}
