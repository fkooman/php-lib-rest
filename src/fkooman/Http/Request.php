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

class Request
{
    /** @var array */
    private $srv;

    /** @var array */
    private $post;

    /** @var Url */
    private $url;

    /**
     * Contruct the Request object.
     *
     * @param array $srv  the server parameters, typically $_SERVER
     * @param array $post the server POST parameters, typically $_POST
     */
    public function __construct(array $srv = null, array $post = null)
    {
        if (null === $srv) {
            $srv = $_SERVER;
        }
        if (null === $post) {
            $post = $_POST;
        }

        $requiredKeys = array('REQUEST_METHOD');
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $srv)) {
                throw new RuntimeException(sprintf('missing key "%s"', $key));
            }
        }
        $this->srv = $srv;
        $this->post = $post;
        $this->url = new Url($srv);
    }

    /**
     * Get the Url object.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the POST parameters.
     *
     * @return array the key value pair POST parameters
     */
    public function getPostParameters()
    {
        return $this->post;
    }

    /**
     * Get a specific POST parameter.
     *
     * @param string $key the POST key parameter to retrieve.
     *
     * @return mixed the string value of the POST parameter key, or null if the
     *               key does not exist
     */
    public function getPostParameter($key)
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return;
    }

    /**
     * Set the HTTP method manually. Used for HTTP method override from
     * Service class to support _METHOD form override.
     *
     * @param string $method the HTTP method to switch to
     */
    public function setMethod($method)
    {
        $this->srv['REQUEST_METHOD'] = $method;
    }

    /**
     * Get the HTTP request method.
     *
     * @return string the HTTP method
     */
    public function getMethod()
    {
        return $this->srv['REQUEST_METHOD'];
    }

    /**
     * Get the value of the request header keyname.
     *
     * @param string $k the HTTP header keyname
     *
     * @return mixed the value of the header keyname as string or null if the
     *               header key is not set
     */
    public function getHeader($keyName)
    {
        $headers = $this->getHeaders();
        $keyName = self::normalizeHeaderKeyName($keyName);
        if (array_key_exists($keyName, $headers)) {
            return $headers[$keyName];
        }

        return;
    }

    /**
     * Get the HTTP headers.
     *
     * @return array the HTTP headers as a key-value array
     */
    public function getHeaders()
    {
        $headers = array();
        foreach ($this->srv as $k => $v) {
            $headers[self::normalizeHeaderKeyName($k)] = $v;
        }

        return $headers;
    }

    /**
     * Get the HTTP request body.
     *
     * @return string the HTTP body as a string, can also be binary.
     */
    public function getBody()
    {
        return @file_get_contents('php://input');
    }

    /**
     * Normalize a HTTP request header keyname. It will remove any HTTP_ or
     * HTTP- prefix, replace all underscores with dashes and capitalize the
     * first letter(s). For example:.
     *
     * HTTP_ACCEPT: Accept
     * CONTENT_TYPE: Content-Type
     *
     * @param string $keyName the keyname to normalize
     *
     * @return string the normalized keyname
     */
    public static function normalizeHeaderKeyName($keyName)
    {
        if (0 === stripos($keyName, 'HTTP_') || 0 === stripos($keyName, 'HTTP-')) {
            $keyName = substr($keyName, 5);
        }

        return str_replace(
            ' ',
            '-',
            ucwords(
                strtolower(
                    str_replace(
                        array('_', '-'),
                        ' ',
                        $keyName
                    )
                )
            )
        );
    }
}
