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

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function testResponse()
    {
        $r = new Response();
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('text/html;charset=UTF-8', $r->getHeader('Content-Type'));
        $this->assertEquals('', $r->getBody());
        $this->assertNull($r->getHeader('Foo'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage invalid status code
     */
    public function testInvalidCode()
    {
        $h = new Response(999);
    }

    public function testSetBody()
    {
        $r = new Response();
        $r->setBody('<em>Foo</em>');
        $this->assertEquals('<em>Foo</em>', $r->getBody());
    }

    public function testGetStatusCodeAndReason()
    {
        $r = new Response(404);
        $this->assertEquals(404, $r->getStatusCode());
        $this->assertEquals('Not Found', $r->getStatusReason());
    }

    public function testSetGetHeader()
    {
        $r = new Response();
        $r->setHeader('Foo', 'Bar');
        $this->assertEquals('Bar', $r->getHeader('Foo'));
    }

    public function testGetHeaders()
    {
        $r = new Response();
        $r->setHeader('Foo', 'Bar');
        $this->assertEquals(
            array(
                'Foo' => 'Bar',
                'Content-Type' => 'text/html;charset=UTF-8',
            ),
            $r->getHeaders()
        );
    }

    public function testSetHeaders()
    {
        $r = new Response();
        $r->setHeaders(
            array(
                'Foo' => 'Bar',
                'Bar' => 'Baz',
            )
        );
        $this->assertEquals(
            array(
                'Foo' => 'Bar',
                'Bar' => 'Baz',
                'Content-Type' => 'text/html;charset=UTF-8',
            ),
            $r->getHeaders()
        );
    }

    public function testUpdateExistingHeader()
    {
        $r = new Response();
        $r->setHeader('CONTENT-TYPE', 'application/json');
        $this->assertEquals(
            array(
                'Content-Type' => 'application/json',
            ),
            $r->getHeaders()
        );
    }

    public function testSendResponse()
    {
        $this->expectOutputString('Hello World!');

        $r = new Response();
        $r->setHeader('Foo', 'Bar');
        $r->setBody('Hello World!');
        $r->send();

        $this->assertEquals(
            array(
                'Content-Type: text/html;charset=UTF-8',
                'Foo: Bar',
            ),
            xdebug_get_headers()
        );
    }
}
