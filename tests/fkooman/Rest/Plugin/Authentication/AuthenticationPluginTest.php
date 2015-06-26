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

namespace fkooman\Rest\Plugin\Authentication;

use fkooman\Rest\Service;
use fkooman\Http\Request;
use PHPUnit_Framework_TestCase;
use fkooman\Http\Exception\UnauthorizedException;

class AuthenticationPluginTest extends PHPUnit_Framework_TestCase
{
    public function testNoAuthenticationAttemptWithTwoRegisteredMethods()
    {
        try {
            $service = new Service();
            $auth = new AuthenticationPlugin();

            $basic = $this->getMockBuilder('fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface')->getMock();
            $basic->method('isAttempt')->willReturn(false);
            $basic->method('getScheme')->willReturn('Basic');
            $basic->method('init')->willReturn(null);
            $basic->method('getAuthParams')->willReturn(array('realm' => 'Basic Foo'));

            $bearer = $this->getMockBuilder('fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface')->getMock();
            $bearer->method('isAttempt')->willReturn(false);
            $bearer->method('getScheme')->willReturn('Bearer');
            $bearer->method('init')->willReturn(null);
            $bearer->method('getAuthParams')->willReturn(array('realm' => 'Bearer Foo'));

            $auth->registerAuthenticationPlugin($basic);
            $auth->registerAuthenticationPlugin($bearer);
            $auth->init($service);

            $request = new Request(
                array(
                    'SERVER_NAME' => 'www.example.org',
                    'SERVER_PORT' => 80,
                    'QUERY_STRING' => '',
                    'REQUEST_URI' => '/',
                    'SCRIPT_NAME' => '/index.php',
                    'REQUEST_METHOD' => 'GET',
                )
            );

            $auth->execute($request, array());
            $this->assertTrue(false);
        } catch (UnauthorizedException $e) {
            $this->assertEquals(
                array(
                    'HTTP/1.1 401 Unauthorized',
                    'Content-Type: application/json',
                    'Www-Authenticate: Basic realm="Basic Foo", Bearer realm="Bearer Foo"',
                    '',
                    '{"error":"no_credentials","error_description":"credentials must be provided"}',
                ),
                $e->getJsonResponse()->toArray()
            );
        }
    }

    public function testNoAuthRequired()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
            )
        );
        $auth = new AuthenticationPlugin();
        $this->assertNull($auth->execute($request, array('requireAuth' => false)));
    }

    public function testAuthAttempt()
    {
        $request = new Request(
            array(
                'SERVER_NAME' => 'www.example.org',
                'SERVER_PORT' => 80,
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_METHOD' => 'GET',
            )
        );

        $auth = new AuthenticationPlugin();

        $basicUserInfo = $this->getMockBuilder('fkooman\Rest\Plugin\Authentication\UserInfoInterface')->getMock();
        $basicUserInfo->method('getUserId')->willReturn('foo');

        $basic = $this->getMockBuilder('fkooman\Rest\Plugin\Authentication\AuthenticationPluginInterface')->getMock();
        $basic->method('isAttempt')->willReturn(true);
        $basic->method('getScheme')->willReturn('Basic');
        $basic->method('getAuthParams')->willReturn(array('realm' => 'Basic Foo'));
        $basic->method('execute')->willReturn($basicUserInfo);

        $auth->registerAuthenticationPlugin($basic);

        $this->assertEquals('foo', $auth->execute($request, array())->getUserId());
    }
}
