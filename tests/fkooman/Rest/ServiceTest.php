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

require_once __DIR__.'/TestPlugin.php';

use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\TestPlugin;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $r = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/index.php/',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $s = new Service();
        $s->get(
            '/',
            function (Request $request) {
                return 'foo';
            }
        );
        $response = $s->run($r);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                'foo',
            ),
            $response->toArray()
        );
    }

    public function testDeleteOverride()
    {
        $r = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/index.php/foo',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/foo',
                'REQUEST_METHOD' => 'POST',
            ),
            array(
                '_METHOD' => 'DELETE',
            )
        );

        $s = new Service();
        $s->delete(
            '/foo',
            function (Request $request) {
                return 'deleted';
            }
        );
        $response = $s->run($r);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                'deleted',
            ),
            $response->toArray()
        );
    }

    public function testMethodNotAllowed()
    {
        $r = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/index.php/foo',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/foo',
                'REQUEST_METHOD' => 'DELETE',
            )
        );
        $s = new Service();
        $s->get(
            '/',
            function (Request $request) {
                return 'foo';
            }
        );
        $response = $s->run($r);
        $this->assertSame(
            array(
                'HTTP/1.1 405 Method Not Allowed',
                'Content-Type: application/json',
                'Allow: GET,HEAD',
                '',
                '{"error":"method DELETE not supported"}',
            ),
            $response->toArray()
        );
    }

    public function testNotFound()
    {
        $r = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/index.php/foo',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/foo',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $s = new Service();
        $s->get(
            '/',
            function (Request $request) {
                return 'foo';
            }
        );
        $response = $s->run($r);
        $this->assertSame(
            array(
                'HTTP/1.1 404 Not Found',
                'Content-Type: application/json',
                '',
                '{"error":"url not found","error_description":"\/index.php\/foo"}',
            ),
            $response->toArray()
        );
    }

    /**
     * Test how stuff goes if a plugin returns an object that needs to be
     * matched not to the object itself, but to an interface implemented by
     * the object.
     */
    public function testPluginInterfaceCallbackMatch()
    {
        $stubFoo = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('StubFoo')->getMock();
        $stubFoo->method('execute')->willReturn('foo');

        $stubPlugin = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('StubPlugin')->getMock();
        $stubPlugin->method('execute')->willReturn($stubFoo);

        $service = new Service();
        $service->getPluginRegistry()->registerDefaultPlugin($stubPlugin);

        $service->get(
            '/',
            function (Request $request, ServicePluginInterface $i) {
                return $i->execute($request, array());
            }
        );

        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'REQUEST_METHOD' => 'GET',
            )
        );

        $response = $service->run($request);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                'foo',
            ),
            $response->toArray()
        );
    }

    // plugins need to be initiated
    public function testInit()
    {
        $testPlugin = new TestPlugin();

        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/index.php/foo',
                'SCRIPT_NAME' => '/index.php',
                'PATH_INFO' => '/foo',
                'REQUEST_METHOD' => 'GET',
            )
        );

        $service = new Service();
        $service->getPluginRegistry()->registerDefaultPlugin($testPlugin);
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                'foo',
            ),
            $service->run($request)->toArray()
        );
    }
}
