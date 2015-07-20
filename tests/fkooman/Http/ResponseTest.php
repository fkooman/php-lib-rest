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
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                '',
            ),
            $r->toArray()
        );
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
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                '',
                '<em>Foo</em>',
            ),
            $r->toArray()
        );
#        $r->setBody('<em>Foo</em>');
#        $this->assertSame('<em>Foo</em>', $r->getBody());
    }

    public function testGetStatusCodeAndReason()
    {
        $r = new Response(404);
        $this->assertSame(
            array(
                'HTTP/1.1 404 Not Found',
                'Content-Type: text/html;charset=UTF-8',
                '',
                '',
            ),
            $r->toArray()
        );
#        $this->assertSame(404, $r->getStatusCode());
#        $this->assertSame('Not Found', $r->getStatusReason());
    }

    public function testSetGetHeader()
    {
        $r = new Response();
        $r->setHeader('Foo', 'Bar');
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                'Foo: Bar',
                '',
                '',
            ),
            $r->toArray()
        );

#        $this->assertSame('Bar', $r->getHeader('Foo'));
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
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html;charset=UTF-8',
                'Foo: Bar',
                'Bar: Baz',
                '',
                '',
            ),
            $r->toArray()
        );

#        $this->assertSame(
#            array(
#                'Foo' => 'Bar',
#                'Bar' => 'Baz',
#                'Content-Type' => 'text/html;charset=UTF-8',
#            ),
#            $r->getHeaders()
#        );
    }

    public function testUpdateExistingHeader()
    {
        $r = new Response();
        $r->setHeader('CONTENT-TYPE', 'application/json');
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: application/json',
                '',
                '',
            ),
            $r->toArray()
        );

#        $this->assertSame(
#            array(
#                'Content-Type' => 'application/json',
#            ),
#            $r->getHeaders()
#        );
    }

    public function testAddHeader()
    {
        $r = new Response(200, 'application/json');
        $r->setHeader('Link', '<https://example.org/micropub>; rel="micropub"');
        $r->addHeader('Link', '<https://example.net/micropub>; rel="micropub"');
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: application/json',
                'Link: <https://example.org/micropub>; rel="micropub", <https://example.net/micropub>; rel="micropub"',
                '',
                '',
            ),
            $r->toArray()
        );
    }

    public function testAddHeaderNonExisting()
    {
        $r = new Response(200, 'application/json');
        $r->addHeader('Link', '<https://example.net/micropub>; rel="micropub"');
        $this->assertSame(
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: application/json',
                'Link: <https://example.net/micropub>; rel="micropub"',
                '',
                '',
            ),
            $r->toArray()
        );
    }

#    public function testSendResponse()
#    {
#        $this->expectOutputString('Hello World!');

#        $r = new Response();
#        $r->setHeader('Foo', 'Bar');
#        $r->setBody('Hello World!');
#        $r->send();

#        $this->assertSame(
#            array(
#                'Content-Type: text/html;charset=UTF-8',
#                'Foo: Bar',
#            ),
#            xdebug_get_headers()
#        );
#    }
}
