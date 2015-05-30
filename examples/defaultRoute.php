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

$service = new Service();
$service->setDefaultRoute('/welcome');

$service->get(
    '/',
    function () {
        $response = new Response(200, 'text/plain');
        $response->setBody('root');

        return $response;
    }
);
$service->get(
    '/welcome',
    function () {
        $response = new Response(200, 'text/plain');
        $response->setBody('welcome');

        return $response;
    }
);

$service->run()->send();
