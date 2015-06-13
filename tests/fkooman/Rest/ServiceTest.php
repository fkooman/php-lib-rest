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
        $this->assertEquals(
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
        $this->assertEquals(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                'deleted',
            ),
            $response->toArray()
        );
    }

    /**
     * @expectedException fkooman\Http\Exception\MethodNotAllowedException
     * @expectedExceptionMessage method DELETE not supported
     */
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
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
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
    }

    public function testSetPluginRegistry()
    {
        $s = new Service();
        $s->setPluginRegistry(new PluginRegistry());
    }
}
