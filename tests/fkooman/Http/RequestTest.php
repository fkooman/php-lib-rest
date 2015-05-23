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

namespace fkooman\Http;

use fkooman\Http\Request;
use PHPUnit_Framework_TestCase;

class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => '*/*'
        );
        $r = new Request($srv);
        $this->assertEquals('http://www.example.org/bar/index.php', $r->getUrl()->getRootUrl());
        $this->assertEquals('*/*', $r->getHeader('Accept'));
        $this->assertEquals('GET', $r->getMethod());
    }

    public function testPost()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'POST',
            'HTTP_ACCEPT' => '*/*',
            'CONTENT_LENGTH' => 15,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        );

        $post = array(
            'foo' => 'bar',
            'bar' => 'baz'
        );

        $r = new Request($srv, $post);
        $this->assertEquals('http://www.example.org/bar/index.php', $r->getUrl()->getRootUrl());
        $this->assertEquals('*/*', $r->getHeader('Accept'));
        $this->assertEquals('POST', $r->getMethod());
        $this->assertEquals('application/x-www-form-urlencoded', $r->getHeader('Content-Type'));
        $this->assertEquals(15, $r->getHeader('Content-Length'));
        $this->assertEquals('bar', $r->getPostParameter('foo'));
        $this->assertNull($r->getPostParameter('xyz'));
        $this->assertNull($r->getHeader('Foo'));
        $this->assertEquals('*/*', $r->getHeader('HTTP_Accept'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage missing key "REQUEST_METHOD"
     */
    public function testMissingRequestMethod()
    {
        $r = new Request();
    }

    public function testInput()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => '*/*'
        );
        $r = new Request($srv);
        $this->assertEmpty($r->getBody());
    }

    public function testMethodOverride()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/index.php',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'GET',
        );
        $r = new Request($srv);
        $this->assertEquals('http://www.example.org/index.php', $r->getUrl()->getRootUrl());
        $this->assertEquals('GET', $r->getMethod());
        $r->setMethod('POST');
        $this->assertEquals('POST', $r->getMethod());
    }
}
