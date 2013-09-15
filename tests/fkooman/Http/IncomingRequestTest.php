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

class IncomingRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDataProvider
     */
    public function testGetRequests($port, $name, $request, $https, $request_uri)
    {
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_NAME'] = $name;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['PATH_INFO'] = "/foo/bar";
        $_SERVER['HTTPS'] = $https;
        $_SERVER['PHP_AUTH_USER'] = "user";
        $_SERVER['PHP_AUTH_PW'] = "pass";

        $stub = $this->getMock('\fkooman\Http\IncomingRequest', array('getRequestHeaders'));
        $stub->expects($this->any())
                ->method('getRequestHeaders')
                ->will($this->returnValue(array("A" => "B")));
        $request = Request::fromIncomingRequest($stub);
        $this->assertEquals($request_uri, $request->getRequestUri()->getUri());
        $this->assertEquals("GET", $request->getRequestMethod());
        $this->assertEquals("/foo/bar", $request->getPathInfo());
        $this->assertEquals("user", $request->getBasicAuthUser());
        $this->assertEquals("pass", $request->getBasicAuthPass());
    }

    public function getDataProvider()
    {
        return array(
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request"),
            array("443", "www.example.com", "/request", "off", "http://www.example.com:443/request"),
            array("443", "www.example.com", "/request", "on", "https://www.example.com/request"),
            array("80", "www.example.com", "/request", "on", "https://www.example.com:80/request"),
                // can not do IPv6 literals :(
                // PHP missing feature (bug)
                // array ("80", "2001:610::4", "/request", "off", "http://[2001:610::4]/request"),
                // array ("443", "2001:610::4", "/request", "on", "https://[2001:610::4]/request"),
                // array ("8080", "2001:610::4", "/request", "off", "http://[2001:610::4]:8080/request"),
        );
    }

    /**
     * @dataProvider postDataProvider
     */
    public function testPostRequests($port, $name, $request, $https, $request_uri, $content)
    {
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_NAME'] = $name;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['REQUEST_METHOD'] = "POST";
        $_SERVER['CONTENT_LENGTH'] = strlen($content);
        $_SERVER['HTTPS'] = $https;

        $stub = $this->getMock('\fkooman\Http\IncomingRequest', array('getRequestHeaders', 'getRawContent'));
        $stub->expects($this->any())
                ->method('getRequestHeaders')
                ->will($this->returnValue(array("A" => "B")));

        $stub->expects($this->any())
                ->method('getRawContent')
                ->will($this->returnValue($content));

        $request = Request::fromIncomingRequest($stub);
        $this->assertEquals($request_uri, $request->getRequestUri()->getUri());
        $this->assertEquals("POST", $request->getRequestMethod());
        $this->assertEquals($content, $request->getContent());
    }

    public function postDataProvider()
    {
        return array(
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", ""),
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", "action=foo"),
            array("443", "www.example.com", "/request", "on", "https://www.example.com/request", "action=foo"),
            array("80", "www.example.com", "/request", "off", "http://www.example.com/request", pack("nvc*", 0x1234, 0x5678, 65, 66)),
        );
    }

    /**
     * @expectedException \fkooman\Http\IncomingRequestException
     */
    public function testNoServer()
    {
        $i = new IncomingRequest();
    }

    public function testNormalization()
    {
        $_SERVER['SERVER_NAME'] = "foo.example.org";
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = "/resource";
        $_SERVER['REQUEST_METHOD'] = "GET";
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer xyz';
        $_SERVER['HTTP_USER_AGENT'] = 'Foo/Bar 1.0.0';
        $h = Request::fromIncomingRequest(new IncomingRequest());
        $this->assertEquals("Bearer xyz", $h->getHeader("AuThOrIzAtIoN"));
        $this->assertEquals("Bearer xyz", $h->getHeader("HTTP-AUTHORIZATION"));
        $this->assertEquals("Bearer xyz", $h->getHeader("HTTP_authorization"));
        $this->assertEquals("Foo/Bar 1.0.0", $h->getHeader("HTTP_USER_AGENT"));
        $this->assertEquals("Foo/Bar 1.0.0", $h->getHeader("USER_AGENT"));
        $this->assertEquals("Foo/Bar 1.0.0", $h->getHeader("USER-AGENT"));
    }
}
