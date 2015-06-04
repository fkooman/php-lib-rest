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
namespace fkooman\Http;

use PHPUnit_Framework_TestCase;

class UrlTest extends PHPUnit_Framework_TestCase
{
    public function testHttp()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertNull($u->getPathInfo());
        $this->assertEquals('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    public function testHttps()
    {
        $srv = array(
            'HTTPS' => 'on',
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '443',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('https', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(443, $u->getPort());
        $this->assertNull($u->getPathInfo());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertEquals('https://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
        $this->assertEquals('https://www.example.org/bar/index.php?foo=bar', $u->__toString());
        $this->assertEquals('https://www.example.org/bar/index.php?foo=bar', $u->toString());
    }

    public function testHttpNonStandardPort()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '8080',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(8080, $u->getPort());
        $this->assertNull($u->getPathInfo());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertEquals('http://www.example.org:8080/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    public function testHttpPathInfo()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'foo=bar',
            'PATH_INFO' => '/def',
            'REQUEST_URI' => '/bar/index.php/def?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertEquals('/def', $u->getPathInfo());
        $this->assertEquals('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    public function testHttpServerRewrite()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'foo=bar',
            'PATH_INFO' => '/def',
            'REQUEST_URI' => '/bar/def?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/bar/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertEquals('/def', $u->getPathInfo());
        $this->assertEquals('http://www.example.org/bar/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    public function testHttpServerRewriteRoot()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'foo=bar',
            'PATH_INFO' => '/def',
            'REQUEST_URI' => '/def?foo=bar',
            'SCRIPT_NAME' => '/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/', $u->getRoot());
        $this->assertEquals('/', $u->getRootFolder());
        $this->assertEquals('/def', $u->getPathInfo());
        $this->assertEquals('http://www.example.org/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    public function testEmptyQueryString()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/bar/index.php',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertNull($u->getPathInfo());
        $this->assertEquals('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array(), $u->getQueryArray());
        $this->assertNull($u->getQueryParameter('foo'));
    }

    public function testHttpsProxy()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
        $this->assertEquals('https', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(443, $u->getPort());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertNull($u->getPathInfo());
        $this->assertEquals('https://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage missing key "SERVER_NAME"
     */
    public function testMissingKey()
    {
        $u = new Url(array());
    }

    public function testScriptNameFix()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'foo=bar',
            'PATH_INFO' => '/foo',
            'REQUEST_URI' => '/bar/index.php/foo?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php/foo',  // mistakenly includes PATH_INFO
        );

        $u = new Url($srv);
        $this->assertEquals('http', $u->getScheme());
        $this->assertEquals('www.example.org', $u->getHost());
        $this->assertEquals(80, $u->getPort());
        $this->assertEquals('/bar/index.php/', $u->getRoot());
        $this->assertEquals('/bar/', $u->getRootFolder());
        $this->assertEquals('/foo', $u->getPathInfo());
        $this->assertEquals('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertEquals(array('foo' => 'bar'), $u->getQueryArray());
        $this->assertEquals('bar', $u->getQueryParameter('foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage non ASCII characters detected
     */
    public function testNonAsciiUrlPart()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => '80',
            'QUERY_STRING' => 'name=François',
            'PATH_INFO' => '/foo',
            'REQUEST_URI' => '/bar/index.php/foo?name=François',
            'SCRIPT_NAME' => '/bar/index.php',
        );

        $u = new Url($srv);
    }
}
