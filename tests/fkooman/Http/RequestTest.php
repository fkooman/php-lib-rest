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

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $h = new Request("http://www.example.com/request", "POST");
        $h->setPostParameters(array("id" => 5, "action" => "help"));
        $this->assertEquals("http://www.example.com/request", $h->getRequestUri()->getUri());
        $this->assertEquals("POST", $h->getRequestMethod());
        $this->assertEquals("id=5&action=help", $h->getContent());
        $this->assertEquals("application/x-www-form-urlencoded", $h->getHeader("Content-type"));
        $this->assertEquals(array("id" => 5, "action" => "help"), $h->getPostParameters());
    }

    public function testQueryParameters()
    {
        $h = new Request("http://www.example.com/request?action=foo&method=bar", "GET");
        $this->assertEquals(array("action" => "foo", "method" => "bar"), $h->getQueryParameters());
    }

    public function testQueryParametersWithoutParameters()
    {
        $h = new Request("http://www.example.com/request", "GET");
        $this->assertEquals(array(), $h->getQueryParameters());
    }

    public function testUriParametersWithPost()
    {
        $h = new Request("http://www.example.com/request?action=foo&method=bar", "POST");
        $h->setPostParameters(array("id" => 5, "action" => "help"));
        $this->assertEquals(array("action" => "foo", "method" => "bar"), $h->getQueryParameters());
        $this->assertEquals(array("id" => 5, "action" => "help"), $h->getPostParameters());
        $this->assertEquals(5, $h->getPostParameter("id"));
        $this->assertEquals("help", $h->getPostParameter("action"));
    }

    public function testSetHeaders()
    {
        $h = new Request("http://www.example.com/request", "POST");
        $h->setHeader("A", "B");
        $h->setHeader("foo", "bar");
        $this->assertEquals("B", $h->getHeader("A"));
        $this->assertEquals("bar", $h->getHeader("foo"));
        $this->assertEquals(array("A" => "B", "FOO" => "bar"), $h->getHeaders(false));
        $this->assertEquals(array("A: B", "FOO: bar"), $h->getHeaders(true));
    }

    public function testSetGetHeadersCaseInsensitive()
    {
        $h = new Request("http://www.example.com/request", "POST");
        $h->setHeader("Content-type", "application/json");
        $h->setHeader("Content-Type", "text/html"); // this overwrites the previous one
        $this->assertEquals("text/html", $h->getHeader("CONTENT-TYPE"));
    }

    /**
     * @expectedException \fkooman\Http\RequestException
     */
    public function testTryGetPostParametersOnGetRequest()
    {
        $h = new Request("http://www.example.com/request", "GET");
        $h->getPostParameters();
    }

    /**
     * @expectedException \fkooman\Http\RequestException
     */
    public function testTrySetPostParametersOnGetRequest()
    {
        $h = new Request("http://www.example.com/request", "GET");
        $h->setPostParameters(array("action" => "test"));
    }

    /**
     * @expectedException \fkooman\Http\UriException
     */
    public function testInvalidUri()
    {
        $h = new Request("foo");
    }

    /**
     * @expectedException \fkooman\Http\RequestException
     */
    public function testUnsupportedRequestMethod()
    {
        $h = new Request("http://www.example.com/request", "FOO");
    }

    public function testNonExistingHeader()
    {
        $h = new Request("http://www.example.com/request");
        $this->assertNull($h->getHeader("Authorization"));
    }

    public function testForHeaderDoesNotExist()
    {
        $h = new Request("http://www.example.com/request");
        $this->assertNull($h->getHeader("Authorization"));
    }

    public function testForHeaderDoesExist()
    {
        $h = new Request("http://www.example.com/request");
        $h->setHeader("Authorization", "Bla");
        $this->assertNotNull($h->getHeader("Authorization"));
    }

    public function testForNoQueryValue()
    {
        $h = new Request("http://www.example.com/request?foo=&bar=&foobar=xyz");
        $this->assertNull($h->getQueryParameter("foo"));
        $this->assertNull($h->getQueryParameter("bar"));
        $this->assertEquals("xyz", $h->getQueryParameter("foobar"));
    }

    public function testAuthentication()
    {
        $h = new Request("http://www.example.org", "GET");
        $h->setBasicAuthUser("foo");
        $h->setBasicAuthPass("bar");
        $this->assertEquals("foo", $h->getBasicAuthUser());
        $this->assertEquals("bar", $h->getBasicAuthPass());
    }
}
