<?php

/**
* Copyright 2013 François Kooman <fkooman@tuxed.net>
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
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getReason()
    {
        if (!array_key_exists($this->code, Response::$statusCodes)) {
            return null;
        }

        return Response::$statusCodes[$this->code];
    }

    public function getResponse($useJson = true)
    {
        if ($useJson) {
            $response = new JsonResponse($this->getCode());
            $response->setContent(
                array(
                    'code' => $this->getCode(),
                    'error' => $this->getReason(),
                    'error_description' => $this->getMessage(),
                )
            );
        } else {
            $response = new Response($this->getCode());
            $htmlData = sprintf(
                '<!DOCTYPE HTML><html><head><meta charset="utf-8"><title>%s %s</title></head><body><h1>%s</h1><p>%s</p></body></html>',
                $this->getCode(),
                $this->getReason(),
                $this->getReason(),
                $this->getMessage()
            );
            $response->setContent($htmlData);
        }

        return $response;
    }
}
