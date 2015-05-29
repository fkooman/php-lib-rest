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

namespace fkooman\Rest;

use ErrorException;
use Exception;
use fkooman\Http\Request;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\InternalServerErrorException;

class ExceptionHandler
{
    public function __construct()
    {
        set_error_handler(
            function ($severity, $message, $file, $line) {
                if (!(error_reporting() & $severity)) {
                    // This error code is not included in error_reporting
                    return;
                }
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );
        // register global Exception handler
        set_exception_handler('fkooman\Rest\ExceptionHandler::handleException');
    }

    public static function handleException(Exception $e, $onlyLogServerErrors = true)
    {
        $request = new Request($_SERVER);

        if (!($e instanceof HttpException)) {
            $e = new InternalServerErrorException($e->getMessage());
        }

        if (!$onlyLogServerErrors || $onlyLogServerErrors && 500 === $e->getCode()) {
            error_log(
                sprintf(
                    'ERROR: "%s", DESCRIPTION: "%s", FILE: "%s", LINE: "%d"',
                    $e->getMessage(),
                    $e->getDescription(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
        }

        if (false !== strpos($request->getHeader('Accept'), 'text/html')) {
            return $e->getHtmlResponse();
        }
        if (false !== strpos($request->getHeader('Accept'), 'application/x-www-form-urlencoded')) {
            return $e->getFormResponse();
        }

        // by default we return JSON
        return $e->getJsonResponse();
    }
}
