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

class IncomingRequest
{
    public function __construct()
    {
        $requiredKeys = array(
            'SERVER_NAME',
            'SERVER_PORT',
            'REQUEST_URI',
            'REQUEST_METHOD',
            'SCRIPT_NAME'
        );
        foreach ($requiredKeys as $r) {
            if (!array_key_exists($r, $_SERVER) || empty($_SERVER[$r])) {
                throw new RuntimeException(
                    sprintf(
                        'missing environment variable "%s"',
                        $r
                    )
                );
            }
        }
    }

    public function getServerName()
    {
        return $_SERVER['SERVER_NAME'];
    }

    public function getServerPort()
    {
        return $_SERVER['SERVER_PORT'];
    }

    public function getRequestUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getScriptName()
    {
        return $_SERVER['SCRIPT_NAME'];
    }

    public function getPathInfo()
    {
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            return $_SERVER['PATH_INFO'];
        }

        return null;
    }

    public function isHttps()
    {
        if (array_key_exists('HTTPS', $_SERVER)) {
            if ('' !== $_SERVER['HTTPS'] && 'off' !== $_SERVER['HTTPS']) {
                return true;
            }
        }

        if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER)) {
            if ('https' === $_SERVER['HTTP_X_FORWARDED_PROTO']) {
                return true;
            }
        }

        return false;
    }

    public function getScheme()
    {
        return $this->isHttps() ? 'https://' : 'http://';
    }

    public function mustIncludePort()
    {
        if ($this->isHttps() && 443 == $this->getServerPort()) {
            return false;
        }
        if (!$this->isHttps() && 80 == $this->getServerPort()) {
            return false;
        }

        return true;
    }

    public function getAbsoluteUri()
    {
        if ($this->mustIncludePort()) {
            $fullRequestUri = sprintf(
                '%s%s:%s%s',
                $this->getScheme(),
                $this->getServerName(),
                $this->getServerPort(),
                $this->getRequestUri()
            );
        } else {
            $fullRequestUri = sprintf(
                '%s%s%s',
                $this->getScheme(),
                $this->getServerName(),
                $this->getRequestUri()
            );
        }

        return $fullRequestUri;
    }

    public function getPost()
    {
        return $_POST;
    }

    public function getBody()
    {
        return @file_get_contents('php://input');
    }

    public function getPhpAuthUser()
    {
        if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {
            return $_SERVER['PHP_AUTH_USER'];
        }

        return null;
    }

    public function getPhpAuthPw()
    {
        if (array_key_exists('PHP_AUTH_PW', $_SERVER)) {
            return $_SERVER['PHP_AUTH_PW'];
        }

        return null;
    }

    public function getRoot()
    {
        $scriptName = $this->getScriptName();

        return substr($scriptName, 0, strrpos($scriptName, '/') + 1);
    }

    public function getHeaders()
    {
        $requestHeaders = array();

        foreach ($_SERVER as $k => $v) {
            if (0 === strpos($k, 'HTTP_') || 0 === strpos($k, 'HTTP-')) {
                $k = substr($k, 5);
            }
            $k = strtoupper(str_replace('-', '_', $k));
            $requestHeaders[$k] = $v;
        }

        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            foreach ($apacheHeaders as $k => $v) {
                $k = strtoupper(
                    str_replace(
                        '-',
                        '_',
                        $k
                    )
                );
                if (!array_key_exists($k, $requestHeaders)) {
                    $requestHeaders[$k] = $v;
                }
            }
        }

        return $requestHeaders;
    }
}
