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

class MethodNotAllowedException extends HttpException
{
    /** @var array */
    private $allowedMethods;

    public function __construct($requestMethod, array $allowedMethods, $code = 0, Exception $previous = null)
    {
        $this->allowedMethods = $allowedMethods;
        parent::__construct(sprintf('method %s not supported', $requestMethod), null, 405, $previous);
    }

    private function addHeader(Response $response)
    {
        if (0 !== count($this->allowedMethods)) {
            $response->setHeader(
                'Allow',
                implode(
                    ',',
                    $this->allowedMethods
                )
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
}
