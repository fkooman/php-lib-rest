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

require_once __DIR__.'/Test/TestModule.php';

use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Rest\Test\TestModule;

class ServiceModuleTest extends PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/foo',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/foo',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $service = new Service();
        $service->addModule(new TestModule('foo'));
        $service->addModule(new TestModule('bar'));

        $response = $service->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                'Content-Length: 3',
                '',
                'foo',
            ),
            $response->toArray()
        );
    }

    public function testBar()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/bar',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/bar',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $service = new Service();
        $service->addModule(new TestModule('foo'));
        $service->addModule(new TestModule('bar'));

        $response = $service->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                'Content-Length: 3',
                '',
                'bar',
            ),
            $response->toArray()
        );
    }
}
