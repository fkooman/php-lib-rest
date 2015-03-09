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

use fkooman\Http\IncomingRequest;

class IncomingRequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testNoServer()
    {
        $i = new IncomingRequest();
    }

    public function testGetRequest()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php'
        );
        $i = new IncomingRequest();
        $this->assertEquals('www.example.org', $i->getServerName());
        $this->assertEquals('80', $i->getServerPort());
        $this->assertEquals('/foo', $i->getRequestUri());
        $this->assertEquals('GET', $i->getRequestMethod());
        $this->assertEquals('/index.php', $i->getScriptName());
    }

    public function testPostRequest()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => '/index.php'
        );
        $_POST = array(
            'foo' => 'bar'
        );
        $i = new IncomingRequest();
        $this->assertEquals('POST', $i->getRequestMethod());
        $this->assertEquals(array('foo' => 'bar'), $i->getPost());
    }

    public function testNoPostRequest()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => '/index.php'
        );
        $i = new IncomingRequest();
        $this->assertEquals('POST', $i->getRequestMethod());
        $this->assertEquals(array(), $i->getPost());
    }


    public function testGetRequestAuth()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar'
        );
        $i = new IncomingRequest();
        $this->assertEquals('www.example.org', $i->getServerName());
        $this->assertEquals('80', $i->getServerPort());
        $this->assertEquals('/foo', $i->getRequestUri());
        $this->assertEquals('GET', $i->getRequestMethod());
        $this->assertEquals('/index.php', $i->getScriptName());
        $this->assertEquals('foo', $i->getPhpAuthUser());
        $this->assertEquals('bar', $i->getPhpAuthPw());
    }

    public function testGetRequestPathInfo()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'PATH_INFO' => '/foo',
        );
        $i = new IncomingRequest();
        $this->assertEquals('www.example.org', $i->getServerName());
        $this->assertEquals('80', $i->getServerPort());
        $this->assertEquals('/foo', $i->getRequestUri());
        $this->assertEquals('GET', $i->getRequestMethod());
        $this->assertEquals('/index.php', $i->getScriptName());
        $this->assertEquals('/foo', $i->getPathInfo());
    }

    public function testIsNotHttps()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
        );
        $i = new IncomingRequest();
        $this->assertEquals('www.example.org', $i->getServerName());
        $this->assertEquals('80', $i->getServerPort());
        $this->assertEquals('/foo', $i->getRequestUri());
        $this->assertEquals('GET', $i->getRequestMethod());
        $this->assertEquals('/index.php', $i->getScriptName());
        $this->assertFalse($i->isHttps());
    }

    public function testIsHttps()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 443,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'HTTPS' => 'on'
        );
        $i = new IncomingRequest();
        $this->assertEquals('www.example.org', $i->getServerName());
        $this->assertEquals('443', $i->getServerPort());
        $this->assertEquals('/foo', $i->getRequestUri());
        $this->assertEquals('GET', $i->getRequestMethod());
        $this->assertEquals('/index.php', $i->getScriptName());
        $this->assertTrue($i->isHttps());
    }

    public function testHeaders()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:36.0) Gecko/20100101 Firefox/36.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        );
        $i = new IncomingRequest();
        $this->assertEquals(
            array(
                'USER_AGENT' => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:36.0) Gecko/20100101 Firefox/36.0',
                'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ),
            $i->getHeaders()
        );
    }

    public function testRoot()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php'
        );
        $i = new IncomingRequest();
        $this->assertEquals('/', $i->getRoot());
    }

    public function testPathRoot()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php'
        );
        $i = new IncomingRequest();
        $this->assertEquals('/bar/', $i->getRoot());
    }

    public function testMustIncludePort()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php'
        );
        $i = new IncomingRequest();
        $this->assertFalse($i->mustIncludePort());
    }

    public function testMustIncludePortHttps()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
            'HTTPS' => 'on'
        );
        $i = new IncomingRequest();
        $this->assertTrue($i->mustIncludePort());
    }

    public function testMustNotIncludePortHttps()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 443,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
            'HTTPS' => 'on'
        );
        $i = new IncomingRequest();
        $this->assertFalse($i->mustIncludePort());
    }

    public function testProxyHttps()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        );
        $i = new IncomingRequest();
        $this->assertTrue($i->isHttps());
    }

    public function testAbsoluteUri()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
        );
        $i = new IncomingRequest();
        $this->assertEquals('http://www.example.org/bar/foo', $i->getAbsoluteUri());
    }

    public function testAbsoluteUriHttpsProxyAltPort()
    {
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        );
        $i = new IncomingRequest();
        $this->assertEquals('https://www.example.org:8080/bar/foo', $i->getAbsoluteUri());
    }

    public function testApacheHeaders()
    {
        if (!function_exists('apache_request_headers')) {
            function apache_request_headers()
            {
                return array('FOO' => 'Bar');
            }
        }
        $_SERVER = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 8080,
            'REQUEST_URI' => '/bar/foo',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/bar/index.php',
        );
        $i = new IncomingRequest();
        $this->assertEquals(array('FOO' => 'Bar'), $i->getHeaders());
    }
}
