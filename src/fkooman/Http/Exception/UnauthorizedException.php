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

namespace fkooman\Http\Exception;

use fkooman\Http\Response;

class UnauthorizedException extends HttpException
{
    /** @var array */
    private $authScheme;

    public function __construct($message, $description, $code = 0, Exception $previous = null)
    {
        $this->authScheme = array();
        parent::__construct($message, $description, 401, $previous);
    }

    public function addScheme($scheme, array $authParams = array())
    {
        if (!array_key_exists('realm', $authParams)) {
            $authParams['realm'] = 'Protected Resource';
        }
        $this->authScheme[$scheme] = $authParams;
    }

    private function addHeader(Response $response)
    {
        foreach ($this->authScheme as $k => $v) {
            $response->addHeader(
                'WWW-Authenticate',
                sprintf('%s %s', $k, self::authParamsToString($v))
            );
        }

        return $response;
    }

    public function getJsonResponse()
    {
        return $this->addHeader(parent::getJsonResponse());
    }

    public function getFormResponse()
    {
        return $this->addHeader(parent::getFormResponse());
    }

    public function getHtmlResponse()
    {
        return $this->addHeader(parent::getHtmlResponse());
    }

    public static function authParamsToString(array $authParams)
    {
        $a = array();
        foreach ($authParams as $k => $v) {
            if (is_string($k) && is_string($v) && 0 < strlen($k) && 0 < strlen($v)) {
                $a[] = sprintf('%s="%s"', $k, $v);
            }
        }

        return implode(',', $a);
    }
}
