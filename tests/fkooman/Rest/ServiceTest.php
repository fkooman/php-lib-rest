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
namespace fkooman\Rest;

use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Rest\Plugin\ReferrerCheckPlugin;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/index.php/',
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/',
            'REQUEST_METHOD' => 'GET',
        );
        $r = new Request($srv);

        $s = new Service();
        $s->get(
            '/',
            function (Request $request) {
                $response = new Response();
                $response->setBody('foo');

                return $response;
            }
        );
        $response = $s->run($r);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foo', $response->getBody());
    }

    public function testDefaultPlugin()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/index.php/',
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/',
            'REQUEST_METHOD' => 'POST',
        );
        $r = new Request($srv);

        $s = new Service();
        $s->registerDefaultPlugin(new ReferrerCheckPlugin());
        $s->post(
            '/',
            function (Request $request) {
                $response = new Response();
                $response->setBody('foo');

                return $response;
            },
            array('fkooman\Rest\Plugin\ReferrerCheckPlugin' => array('enabled' => false))
        );
        $response = $s->run($r);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foo', $response->getBody());
    }
}
