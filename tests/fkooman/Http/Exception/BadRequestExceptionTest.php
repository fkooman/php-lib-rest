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
namespace fkooman\Http\Exception;

use PHPUnit_Framework_TestCase;

class BadRequestExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testBadRequestException()
    {
        $e = new BadRequestException('foo');
        $this->assertEquals(400, $e->getCode());
        $this->assertEquals('foo', $e->getMessage());
        $this->assertEquals(
            array(
                'HTTP/1.1 400 Bad Request',
                'Content-Type: application/json',
                '',
                '{"error":"foo"}',
            ),
            $e->getJsonResponse()->toArray()
        );

        $this->assertEquals(
            array(
                'HTTP/1.1 400 Bad Request',
                'Content-Type: text/html;charset=UTF-8',
                '',
                '<!DOCTYPE HTML><html><head><meta charset="utf-8"><title>400 Bad Request</title></head><body><h1>Bad Request</h1><p>foo</p></body></html>',

            ),
            $e->getHtmlResponse()->toArray()
        );

#        $htmlResponse = $e->getHtmlResponse();
#        $this->assertEquals(400, $htmlResponse->getStatusCode());
#        $this->assertEquals('text/html;charset=UTF-8', $htmlResponse->getHeader('Content-Type'));
#        $this->assertEquals(
#            '<!DOCTYPE HTML><html><head><meta charset="utf-8"><title>400 Bad Request</title></head><body><h1>Bad Request</h1><p>foo</p></body></html>',
#            $htmlResponse->getBody()
#        );
    }
}
