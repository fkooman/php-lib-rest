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

require_once dirname(dirname(__DIR__)).'/Test/TestRequest.php';

use fkooman\Rest\Test\TestRequest;
use PHPUnit_Framework_TestCase;

class ReferrerCheckPluginTest extends PHPUnit_Framework_TestCase
{
    public function testGoodPost()
    {
        $request = TestRequest::post(
            'http://www.example.org/bar/index.php?foo=bar',
            [
                'HTTP_REFERER' => 'http://www.example.org/bar/index.php/',
                'HTTP_ACCEPT' => 'text/html',
            ]
        );

        $rcp = new ReferrerCheckPlugin();
        $this->assertNull($rcp->execute($request, array()));
    }

    public function testGet()
    {
        $request = TestRequest::get(
            'http://www.example.org/bar/index.php?foo=bar',
            [
                'HTTP_ACCEPT' => 'text/html',
            ]
        );

        $rcp = new ReferrerCheckPlugin();
        $this->assertNull($rcp->execute($request, array()));
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER header missing
     */
    public function testCheckPostNoReferrer()
    {
        $request = TestRequest::post(
            'http://www.example.org/bar/index.php?foo=bar',
            [
                'HTTP_ACCEPT' => 'text/html',
            ]
        );

        $rcp = new ReferrerCheckPlugin();
        $rcp->execute($request, array());
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER has unexpected value
     */
    public function testCheckPostWrongReferrer()
    {
        $request = TestRequest::post(
            'http://www.example.org/bar/index.php?foo=bar',
            [
                'HTTP_REFERER' => 'http://www.attacker.org/foo',
                'HTTP_ACCEPT' => 'text/html',
            ]
        );

        $rcp = new ReferrerCheckPlugin();
        $rcp->execute($request, array());
    }
}
