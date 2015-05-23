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

class Response
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    private $statusCodes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    public function __construct($statusCode = 200, $contentType = 'text/html;charset=UTF-8')
    {
        $this->setStatusCode($statusCode);
        $this->headers = array(
            'Content-Type' => $contentType
        );
        $this->body = '';
    }

    public function setStatusCode($code)
    {
        if (!is_int($code) || !array_key_exists($code, $this->statusCodes)) {
            throw new InvalidArgumentException('invalid status code');
        }
        $this->statusCode = $code;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getStatusReason()
    {
        return $this->statusCodes[$this->statusCode];
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }
    }

    public function setHeader($headerKey, $headerValue)
    {
        $foundHeaderKey = $this->getHeaderKey($headerKey);
        if (null === $foundHeaderKey) {
            $this->headers[$headerKey] = $headerValue;
        } else {
            $this->headers[$foundHeaderKey] = $headerValue;
        }
    }

    public function getHeader($headerKey)
    {
        $headerKey = $this->getHeaderKey($headerKey);

        return null !== $headerKey ? $this->headers[$headerKey] : null;
    }

    /**
     * Look for a header in a case insensitive way. It is possible to have a
     * header key 'Content-type' or a header key 'Content-Type', these should
     * be treated the same.
     *
     * @param headerName the name of the header to search for
     * @returns The name of the header as it was set (original case)
     *
     */
    private function getHeaderKey($headerKey)
    {
        $headerKeys = array_keys($this->headers);
        $keyPositionInArray = array_search(strtolower($headerKey), array_map('strtolower', $headerKeys));

        return false !== $keyPositionInArray ? $headerKeys[$keyPositionInArray] : null;
    }

    public function getHeaders($formatted = false)
    {
        return $this->headers;
    }

    public function send()
    {
        header(sprintf('HTTP/1.1 %s %s', $this->getStatusCode(), $this->getStatusReason()));
        foreach ($this->getHeaders() as $k => $v) {
            header($k.': '.$v);
        }
        echo $this->body;
    }
}
