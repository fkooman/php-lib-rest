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
require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Rest\Service;
use fkooman\Http\Response;
use fkooman\Http\Session;
use fkooman\Rest\ExceptionHandler;

ExceptionHandler::register();

$session = new Session('foo');
$service = new Service();
$service->get(
    '/',
    function () {
        $response = new Response(200, 'text/plain');
        $response->setBody('Welcome!');

        return $response;
    }
);
$service->get(
    '/:key',
    function ($key) use ($session) {
        $newCount = $session->has($key) ? $session->get($key) + 1 : 1;
        $session->set($key, $newCount);

        $response = new Response(200, 'text/plain');
        $response->setBody(
            sprintf('count: %d', $newCount)
        );

        return $response;
    }
);
$service->run()->send();
