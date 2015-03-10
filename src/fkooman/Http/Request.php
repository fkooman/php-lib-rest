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

use InvalidArgumentException;
use RuntimeException;

class Request
{
    /** @var fkooman\Http\Uri */
    protected $requestUri;

    /** @var string */
    protected $requestMethod;

    /** @var array */
    protected $postParameters;

    /** @var array */
    protected $requestHeaders;

    /** @var string */
    protected $requestContent;

    /** @var string */
    protected $requestRoot;

    /** @var string */
    protected $requestPathInfo;

    /** @var string */
    protected $requestBasicAuthUser;

    /** @var string */
    protected $requestBasicAuthPass;

    public function __construct($requestUri, $requestMethod = 'GET')
    {
        $this->setRequestUri(
            new Uri(
                $requestUri
            )
        );
        $this->setRequestMethod($requestMethod);
        $this->requestHeaders = array();
        $this->requestContent = null;
        $this->requestRoot = null;
        $this->requestPathInfo = null;
        $this->requestBasicAuthUser = null;
        $this->requestBasicAuthPass = null;
    }

    public static function fromIncomingRequest(IncomingRequest $i)
    {
        $request = new static($i->getAbsoluteUri(), $i->getRequestMethod());
        $request->setHeaders($i->getHeaders());
        if ('POST' === $i->getRequestMethod()) {
            $request->setPostParameters($i->getPost());
        }
        $request->setContent($i->getBody());
        $request->setRoot($i->getRoot());
        $request->setPathInfo($i->getPathInfo());
        $request->setBasicAuthUser($i->getPhpAuthUser());
        $request->setBasicAuthPass($i->getPhpAuthPw());

        return $request;
    }

    public function setRequestUri(Uri $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    public function getRequestUri()
    {
        return $this->requestUri;
    }

    public function setRequestMethod($requestMethod)
    {
        $validRequestMethods = array(
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'HEAD',
            'OPTIONS',
            'PATCH'
        );

        if (!in_array($requestMethod, $validRequestMethods)) {
            throw new InvalidArgumentException('invalid or unsupported request method');
        }
        $this->requestMethod = $requestMethod;
    }

    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    public function getQueryParameters()
    {
        if (null === $this->requestUri->getQuery()) {
            return array();
        }
        $queryParameters = array();
        parse_str($this->requestUri->getQuery(), $queryParameters);

        return $queryParameters;
    }

    public function getQueryParameter($key)
    {
        $queryParameters = $this->getQueryParameters();
        if (array_key_exists($key, $queryParameters)) {
            if (is_string($queryParameters[$key]) && 0 !== strlen($queryParameters[$key])) {
                return $queryParameters[$key];
            }
        }

        return null;
    }

    public function setPostParameters(array $postParameters)
    {
        $this->postParameters = $postParameters;
    }

    public function getPostParameter($key)
    {
        $postParameters = $this->getPostParameters();
        if (null !== $postParameters) {
            if (array_key_exists($key, $postParameters)) {
                if (is_string($postParameters[$key]) && 0 !== strlen($postParameters[$key])) {
                    return $postParameters[$key];
                }
            }
        }

        return null;
    }

    public function getPostParameters()
    {
        return $this->postParameters;
    }

    public function setHeaders(array $requestHeaders)
    {
        foreach ($requestHeaders as $key => $value) {
            $key = self::normalizeHeaderKey($key);
            $this->requestHeaders[$key] = $value;
        }
    }

    public function getHeader($key)
    {
        $key = self::normalizeHeaderKey($key);

        if (array_key_exists($key, $this->requestHeaders)) {
            return $this->requestHeaders[$key];
        }

        return null;
    }

    public function getHeaders()
    {
        return $this->requestHeaders;
    }

    public function setContent($requestContent)
    {
        $this->requestContent = $requestContent;
    }

    public function getContent()
    {
        return $this->requestContent;
    }

    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    public function setRoot($root)
    {
        $this->requestRoot = $root;
    }

    public function getRoot()
    {
        return $this->requestRoot;
    }

    public function getAbsRoot()
    {
        $this->getRequestUri()->getBaseUri() . $this->getRoot();
    }

    public function setPathInfo($pathInfo)
    {
        $this->requestPathInfo = $pathInfo;
    }

    public function getPathInfo()
    {
        return $this->requestPathInfo;
    }

    public function setBasicAuthUser($basicAuthUser)
    {
        $this->requestBasicAuthUser = $basicAuthUser;
    }

    public function setBasicAuthPass($basicAuthPass)
    {
        $this->requestBasicAuthPass = $basicAuthPass;
    }

    public function getBasicAuthUser()
    {
        return $this->requestBasicAuthUser;
    }

    public function getBasicAuthPass()
    {
        return $this->requestBasicAuthPass;
    }

    private static function normalizeHeaderKey($key)
    {
        if (0 === strpos($key, 'HTTP_') || 0 === strpos($key, 'HTTP-')) {
            $key = substr($key, 5);
        }
        return strtoupper(str_replace('-', '_', $key));
    }
}
