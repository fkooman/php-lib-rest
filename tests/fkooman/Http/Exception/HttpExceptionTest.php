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

class HttpExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testHttpException()
    {
        $e = new HttpException("foo", "foo_description", 404);
        $this->assertEquals(404, $e->getCode());
        $this->assertEquals("foo", $e->getMessage());
        $this->assertEquals("foo_description", $e->getDescription());
    }

    public function testHttpExceptionHtmlMessageEscaping()
    {
        $e = new HttpException('xyz&\'', "foo_description", 404);
        $this->assertEquals('<!DOCTYPE HTML><html><head><meta charset="utf-8"><title>404 Not Found</title></head><body><h1>Not Found</h1><h2>xyz&amp;&#039;</h2><p>foo_description</p></body></html>', $e->getHtmlResponse()->getContent());
    }
}
