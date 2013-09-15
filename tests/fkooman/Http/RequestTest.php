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

    public function testMatchRest()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:one/:two/:three",
                function ($one, $two, $three) use ($self) {
                    $self->assertEquals($one, "foo");
                    $self->assertEquals($two, "bar");
                    $self->assertEquals($three, "baz");
                }
            )
        );
    }

    public function testMatchRestNoReplacement()
    {
        $h = new Request("http://www.example.org/api.php", "POST");
        $h->setPathInfo("/foo/bar/baz");
        $this->assertTrue(
            $h->matchRest(
                "POST",
                "/foo/bar/baz",
                function () {
                }
            )
        );
    }

    public function testMatchRestWrongMethod()
    {
        $h = new Request("http://www.example.org/api.php", "POST");
        $h->setPathInfo("/");
        $this->assertFalse($h->matchRest("GET", "/:one/:two/:three", null));
    }

    public function testMatchRestNoMatch()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $this->assertFalse($h->matchRest("GET", "/:one/:two/:three", null));
    }

    public function testMatchRestMatchWildcardToShort()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/");
        $this->assertFalse($h->matchRest("GET", "/:one/:two/:three+", null));
    }

    public function testMatchRestMatchWildcard()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:one/:two/:three+",
                function ($one, $two, $three) use ($self) {
                    $self->assertEquals($one, "foo");
                    $self->assertEquals($two, "bar");
                    $self->assertEquals($three, "baz/foobar");
                }
            )
        );
    }

    public function testMatchRestMatchWildcardSomewhere()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:one/:two+/foobar",
                function ($one, $two) use ($self) {
                    $self->assertEquals($one, "foo");
                    $self->assertEquals($two, "bar/baz");
                }
            )
        );
    }

    public function testMatchRestWrongWildcard()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $this->assertFalse($h->matchRest("GET", "/:abc+/foobaz", null));
    }

    public function testMatchRestMatchWildcardInMiddle()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:one/:two+/:three",
                function ($one, $two, $three) use ($self) {
                    $self->assertEquals($one, "foo");
                    $self->assertEquals($two, "bar/baz");
                    $self->assertEquals($three, "foobar");
                }
            )
        );
    }

    public function testMatchRestNoAbsPath()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("foo");
        $this->assertFalse($h->matchRest("GET", "foo", null));
    }

    public function testMatchRestEmptyPath()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("");
        $this->assertFalse($h->matchRest("GET", "", null));
    }

    public function testMatchRestNoPatternPath()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRest("GET", "x", null));
    }

    public function testMatchRestNoMatchWithoutReplacement()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRest("GET", "/bar", null));
    }

    public function testMatchRestNoMatchWithoutReplacementLong()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar/foo/bar/baz");
        $this->assertFalse($h->matchRest("GET", "/foo/bar/foo/bar/bar", null));
    }

    public function testMatchRestTooShortRequest()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo");
        $this->assertFalse($h->matchRest("GET", "/foo/bar/:foo/bar/bar", null));
    }

    public function testMatchRestEmptyResource()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/");
        $this->assertFalse($h->matchRest("GET", "/foo/:bar", null));
        $this->assertFalse($h->matchRest("POST", "/foo/:bar", null));
        $this->assertFalse($h->matchRest("PUT", "/foo/:bar", null));
        $self = &$this;
        $h->matchRestDefault(
            function ($methodMatch, $patternMatch) use ($self) {
                $self->assertEquals(array("GET", "POST", "PUT"), $methodMatch);
                $self->assertFalse($patternMatch);
            }
        );
    }

    public function testMatchRestVootGroups()
    {
        $h = new Request("http://localhost/oauth/php-voot-proxy/voot.php", "GET");
        $h->setPathInfo("/groups/@me");
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/groups/@me",
                function () {
                }
            )
        );
    }

    public function testMatchRestVootPeople()
    {
        $h = new Request("http://localhost/oauth/php-voot-proxy/voot.php", "GET");
        $h->setPathInfo("/people/@me/urn:groups:demo:member");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/people/@me/:groupId",
                function ($groupId) use ($self) {
                    $self->assertEquals("urn:groups:demo:member", $groupId);
                }
            )
        );
    }

    public function testMatchRestAllPaths()
    {
        $h = new Request("http://www.example.org/api.php", "OPTIONS");
        $h->setPathInfo("/foo/bar/baz/foobar");
        $this->assertTrue(
            $h->matchRest(
                "OPTIONS",
                null,
                function () {
                }
            )
        );
    }

    public function testMultipleMatches()
    {
        $h = new Request("http://www.example.org/api.php", "GET");
        $h->setPathInfo("/foo/bar");
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/foo/bar",
                function () {
                }
            )
        );
        $this->assertFalse(
            $h->matchRest(
                "GET",
                "/foo/bar",
                function () {
                }
            )
        );
    }

    public function testOptionalMatch()
    {
        $h = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:user/public/:module(/:path+)/",
                function ($user, $module, $path = null) use ($self) {
                    $self->assertEquals("admin", $user);
                    $self->assertEquals("money", $module);
                    $self->assertNull($path);
                }
            )
        );
    }

    public function testOtherOptionalMatch()
    {
        $h = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/public/money/a/b/c/");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:user/public/:module(/:path+)/",
                function ($user, $module, $path = null) use ($self) {
                    $self->assertEquals("admin", $user);
                    $self->assertEquals("money", $module);
                    $self->assertEquals("a/b/c", $path);
                }
            )
        );
    }

    public function testWildcardShouldNotMatchDir()
    {
        $h = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/money/a/b/c/");
        $this->assertFalse(
            $h->matchRest(
                "GET",
                "/:user/:module/:path+",
                function () {
                }
            )
        );
    }

    public function testWildcardShouldMatchDir()
    {
        $h = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $h->setPathInfo("/admin/money/a/b/c/");
        $self = &$this;
        $this->assertTrue(
            $h->matchRest(
                "GET",
                "/:user/:module/:path+/",
                function ($user, $module, $path) use ($self) {
                    $self->assertEquals("admin", $user);
                    $self->assertEquals("money", $module);
                    $self->assertEquals("a/b/c", $path);
                }
            )
        );
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
