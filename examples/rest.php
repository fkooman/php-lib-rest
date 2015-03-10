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

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Http\JsonResponse;
use fkooman\Rest\Service;
use fkooman\Http\Exception\BadRequestException;

try {
    $service = new Service();

    $service->get(
        '/hello/:str',
        function ($str) {
            $response = new JsonResponse();
            $response->setContent(
                array(
                    'type' => 'GET',
                    'response' => sprintf('hello %s', $str),
                )
            );

            return $response;
        }
    );

    $service->post(
        '/hello/:str',
        function ($str) {
            if ('foo' === $str) {
                throw new BadRequestException('you cannot say "foo!"');
            }
            $response = new JsonResponse();
            $response->setContent(
                array(
                    'type' => 'POST',
                    'response' => sprintf('hello %s', $str),
                )
            );

            return $response;
        }
    );

    $service->run()->sendResponse();
} catch (Exception $e) {
    Service::handleException($e)->sendResponse();
}
