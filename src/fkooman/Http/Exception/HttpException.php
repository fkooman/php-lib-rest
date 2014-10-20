<?php

/**
* Copyright 2014 François Kooman <fkooman@tuxed.net>
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
use fkooman\Http\JsonResponse;
use Exception;

class HttpException extends Exception
{
    /** @var string */
    private $description;

    public function __construct($message, $description = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getJsonResponse()
    {
        $response = $this->getResponse(true);
        $responseData = array();
        $responseData['error'] = $this->getMessage();
        if (null !== $this->getDescription()) {
            $responseData['error_description'] = $this->getDescription();
        }
        $response->setContent($responseData);

        return $response;
    }

    public function getHtmlResponse()
    {
        $response = $this->getResponse(false);

        if (null !== $this->getDescription()) {
            $message = sprintf('%s (%s)', $this->getMessage(), $this->getDescription());
        } else {
            $message = $this->getMessage();
        }

        $htmlData = sprintf(
            '<!DOCTYPE HTML><html><head><meta charset="utf-8"><title>%s %s</title></head><body><h1>%s</h1><p>%s</p></body></html>',
            $this->getCode(),
            $response->getStatusReason(),
            $response->getStatusReason(),
            $message
        );
        $response->setContent($htmlData);

        return $response;
    }

    protected function getResponse($getJsonResponse)
    {
        if ($getJsonResponse) {
            return new JsonResponse($this->getCode());
        }

        return new Response($this->getCode());
    }
}
