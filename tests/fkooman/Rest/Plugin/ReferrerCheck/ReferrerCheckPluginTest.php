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

namespace fkooman\Rest\Plugin\ReferrerCheck;

use fkooman\Http\Request;
use PHPUnit_Framework_TestCase;

class ReferrerCheckPluginTest extends PHPUnit_Framework_TestCase
{
    public function testGoodPost()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => 'http://www.example.org/bar/index.php/',
            'HTTP_ACCEPT' => 'text/html',
        );
        $request = new Request($srv);
        $rcp = new ReferrerCheckPlugin();
        $this->assertNull($rcp->execute($request, array()));
    }

    public function testGet()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/html',
        );
        $request = new Request($srv);
        $rcp = new ReferrerCheckPlugin();
        $this->assertNull($rcp->execute($request, array()));
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER header missing
     */
    public function testCheckPostNoReferrer()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'POST',
            'HTTP_ACCEPT' => 'text/html',
        );
        $request = new Request($srv);
        $rcp = new ReferrerCheckPlugin();
        $rcp->execute($request, array());
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER has unexpected value
     */
    public function testCheckPostWrongReferrer()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => 'foo=bar',
            'REQUEST_URI' => '/bar/index.php?foo=bar',
            'SCRIPT_NAME' => '/bar/index.php',
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => 'http://www.attacker.org/foo',
            'HTTP_ACCEPT' => 'text/html',
        );
        $request = new Request($srv);
        $rcp = new ReferrerCheckPlugin();
        $rcp->execute($request, array());
    }
}
