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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('/', $u->getPathInfo());
        $this->assertSame('http://www.example.org/bar/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
        $this->assertSame('https', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(443, $u->getPort());
        $this->assertSame('/', $u->getPathInfo());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('https://www.example.org/bar/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
        $this->assertSame('https://www.example.org/bar/index.php?foo=bar', $u->__toString());
        $this->assertSame('https://www.example.org/bar/index.php?foo=bar', $u->toString());
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(8080, $u->getPort());
        $this->assertSame('/', $u->getPathInfo());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('http://www.example.org:8080/bar/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/bar/index.php/', $u->getRoot());
        $this->assertSame('/def', $u->getPathInfo());
        $this->assertSame('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('/def', $u->getPathInfo());
        $this->assertSame('http://www.example.org/bar/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/', $u->getRoot());
        $this->assertSame('/def', $u->getPathInfo());
        $this->assertSame('http://www.example.org/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('/', $u->getPathInfo());
        $this->assertSame('http://www.example.org/bar/', $u->getRootUrl());
        $this->assertSame(array(), $u->getQueryStringAsArray());
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
        $this->assertSame('https', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(443, $u->getPort());
        $this->assertSame('/bar/', $u->getRoot());
        $this->assertSame('/', $u->getPathInfo());
        $this->assertSame('https://www.example.org/bar/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
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
        $this->assertSame('http', $u->getScheme());
        $this->assertSame('www.example.org', $u->getHost());
        $this->assertSame(80, $u->getPort());
        $this->assertSame('/bar/index.php/', $u->getRoot());
        $this->assertSame('/foo', $u->getPathInfo());
        $this->assertSame('http://www.example.org/bar/index.php/', $u->getRootUrl());
        $this->assertSame(array('foo' => 'bar'), $u->getQueryStringAsArray());
        $this->assertSame('bar', $u->getQueryParameter('foo'));
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
