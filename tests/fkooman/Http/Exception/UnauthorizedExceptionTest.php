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

namespace fkooman\Http\Exception;

use PHPUnit_Framework_TestCase;

class UnauthorizedExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testUnauthorizeException()
    {
        $e = new UnauthorizedException("invalid credentials", 'invalid username or password', 'Basic', array('realm' => 'Foo'));
        $response = $e->getJsonResponse();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Foo"', $response->getHeader("WWW-Authenticate"));
        $this->assertEquals(
            array(
                'error' => 'invalid credentials',
                'error_description' => 'invalid username or password',
            ),
            $response->getContent()
        );
    }

    public function testAdditionalAuthParams()
    {
        $e = new UnauthorizedException(
            'invalid_token',
            'token is invalid or expired',
            'Bearer',
            array(
                'realm' => 'My OAuth Realm',
                'error' => 'invalid_token',
                'error_description' => 'token is invalid or expired',
            )
        );
        $response = $e->getJsonResponse();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(
            'Bearer realm="My OAuth Realm",error="invalid_token",error_description="token is invalid or expired"',
             $response->getHeader("WWW-Authenticate")
        );
        $this->assertEquals(
            array(
                'error' => 'invalid_token',
                'error_description' => 'token is invalid or expired',
            ),
            $response->getContent()
        );
    }
}
