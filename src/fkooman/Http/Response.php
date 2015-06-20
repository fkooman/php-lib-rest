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

use InvalidArgumentException;

class Response
{
    /** @var int */
    protected $statusCode;

    /** @var array */
    protected $headers;

    /** @var string */
    protected $body;

    /**
     * Construct the Response.
     *
     * @param int    $statusCode  the HTTP status code
     * @param string $contentType the Content-Type header of the response
     */
    public function __construct($statusCode = 200, $contentType = 'text/html;charset=UTF-8')
    {
        if (false === self::codeToReason($statusCode)) {
            throw new InvalidArgumentException('invalid status code');
        }
        $this->statusCode = $statusCode;
        $this->headers = array(
            'Content-Type' => $contentType,
        );
        $this->body = '';
    }

    /**
     * Set the response body.
     *
     * @param string $body set the body value, also binary objects permitted.
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Set headers.
     *
     * @param array $headers the array of headers where the key is the header
     *                       name and the value is the header value
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    /**
     * Set a header. If it already exists the value is overwritten with the
     * new one.
     */
    public function setHeader($key, $value)
    {
        $key = str_replace(' ', '-', ucwords(strtolower(str_replace(array('_', '-'), ' ', $key))));
        $this->headers[$key] = $value;
    }

    /**
     * Add a header. If it already exists the value is appended to the existing
     * header using comma separation.
     *
     * @param string $key   the name of the header
     * @param string $value the value of the header
     */
    public function addHeader($key, $value)
    {
        $key = str_replace(' ', '-', ucwords(strtolower(str_replace(array('_', '-'), ' ', $key))));
        if (array_key_exists($key, $this->headers)) {
            $this->headers[$key] = sprintf('%s, %s', $this->headers[$key], $value);
        } else {
            $this->headers[$key] = $value;
        }
    }

    /**
     * Construct the response and send it out.
     */
    public function send()
    {
        header(
            sprintf(
                'HTTP/1.1 %s %s',
                $this->statusCode,
                self::codeToReason($this->statusCode)
            )
        );
        foreach ($this->headers as $k => $v) {
            header(
                sprintf('%s: %s', $k, $v)
            );
        }
        echo $this->body;
    }

    /**
     * Convert the full response to array for the purpose of unit testing.
     */
    public function toArray()
    {
        $output = array();
        $output[] = sprintf(
            'HTTP/1.1 %s %s',
            $this->statusCode,
            self::codeToReason($this->statusCode)
        );
        foreach ($this->headers as $k => $v) {
            $output[] = sprintf('%s: %s', $k, $v);
        }
        $output[] = '';
        $output[] = $this->body;

        return $output;
    }

    /**
     * Convert HTTP status code to "human readable" string.
     *
     * @param int $statusCode the HTTP status code
     */
    public static function codeToReason($statusCode)
    {
        $reasonList = array(
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
            505 => 'HTTP Version Not Supported',100 => 'Continue',
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

        if (!array_key_exists($statusCode, $reasonList)) {
            return false;
        }

        return $reasonList[$statusCode];
    }
}
