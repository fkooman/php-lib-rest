<?php

/**
* Copyright 2013 François Kooman <fkooman@tuxed.net>
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

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service();
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run($request);
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNonMethodMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service();
        $service->match("POST", "/foo/bar/baz.txt", null);
        $service->match("DELETE", "/foo/bar/baz.txt", null);
        $response = $service->run($request);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals("POST,DELETE", $response->getHeader("Allow"));
    }

    public function testNonPatternMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/bar/foo.txt");

        $service = new Service();
        $service->match("GET", "/foo/:xyz", null);
        $response = $service->run($request);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testNonResponseReturn()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service();
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                return "Hello World";
            }
        );
        $response = $service->run($request);
        $this->assertEquals("text/html", $response->getContentType());
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
