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

namespace fkooman\Rest;

use fkooman\Rest\Plugin\BasicAuthentication;
use fkooman\Http\Request;
use fkooman\Http\Response;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service($request);
        $service->get(
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run();
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasicAuthCorrectCredentials()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");
        $request->setBasicAuthUser("foo");
        $request->setBasicAuthPass("bar");
        $service = new Service($request);
        $service->registerBeforeMatchingPlugin(new BasicAuthentication("foo", "bar", "Foo Realm"));
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run();
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasicAuthIncorrectCredentials()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");
        $request->setBasicAuthUser("foo");
        $request->setBasicAuthPass("baz");
        $service = new Service($request);
        $service->registerBeforeMatchingPlugin(new BasicAuthentication("foo", "bar", "Foo Realm"));
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Foo Realm"', $response->getHeader("WWW-Authenticate"));
        $this->assertEquals(array('code' => 401, 'error' => 'Unauthorized'), $response->getContent());
    }

    public function testBasicAuthNoCredentials()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");
        $service = new Service($request);
        $service->registerBeforeMatchingPlugin(new BasicAuthentication("foo", "bar", "Foo Realm"));
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Foo Realm"', $response->getHeader("WWW-Authenticate"));
        $this->assertEquals(array('code' => 401, 'error' => 'Unauthorized'), $response->getContent());
    }

    public function testBeforeEachMatchPluginNoSkip()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");
        $request->setBasicAuthUser("foo");
        $request->setBasicAuthPass("baz");
        $service = new Service($request);
        $service->registerBeforeEachMatchPlugin(new BasicAuthentication("foo", "bar", "Foo Realm"));
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            }
        );
        $response = $service->run();
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Basic realm="Foo Realm"', $response->getHeader("WWW-Authenticate"));
        $this->assertEquals(array('code' => 401, 'error' => 'Unauthorized'), $response->getContent());
    }

    public function testBeforeEachMatchPluginSkip()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");
        $request->setBasicAuthUser("foo");
        $request->setBasicAuthPass("baz");
        $service = new Service($request);
        $service->registerBeforeEachMatchPlugin(new BasicAuthentication("foo", "bar", "Foo Realm"));
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "plain/text");
                $response->setContent("Hello World");

                return $response;
            },
            array('fkooman\Rest\Plugin\BasicAuthentication')
        );
        $response = $service->run();
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNonMethodMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service($request);
        $service->post("/foo/bar/baz.txt", null);
        $service->delete("/foo/bar/baz.txt", null);
        $response = $service->run();
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals("POST,DELETE", $response->getHeader("Allow"));
    }

    public function testNonPatternMatch()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/bar/foo.txt");

        $service = new Service($request);
        $service->match("GET", "/foo/:xyz", null);
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testNonResponseReturn()
    {
        $request = new Request("http://www.example.org/foo", "GET");
        $request->setPathInfo("/foo/bar/baz.txt");

        $service = new Service($request);
        $service->match(
            "GET",
            "/foo/bar/baz.txt",
            function () {
                return "Hello World";
            }
        );
        $response = $service->run();
        $this->assertEquals("text/html", $response->getContentType());
        $this->assertEquals("Hello World", $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMatchRest()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz");

        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two/:three",
            function ($one, $two, $three) {
                return json_encode(array($one, $two, $three));
            }
        );
        $response = $service->run();
        $this->assertEquals('["foo","bar","baz"]', $response->getContent());
    }

    public function testMatchRestNoReplacement()
    {
        $request = new Request("http://www.example.org/api.php", "POST");
        $request->setPathInfo("/foo/bar/baz");

        $service = new Service($request);
        $service->match(
            "POST",
            "/foo/bar/baz",
            function () {
                return "match";
            }
        );
        $response = $service->run();
        $this->assertEquals("match", $response->getContent());
    }

    public function testMatchRestWrongMethod()
    {
        $request = new Request("http://www.example.org/api.php", "POST");
        $request->setPathInfo("/");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two/:three",
            null
        );
        $response = $service->run();
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals("GET", $response->getHeader("Allow"));
    }

    public function testMatchRestNoMatch()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two/:three",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestMatchWildcardToShort()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two/:three+",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestMatchWildcard()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two/:three+",
            function ($one, $two, $three) {
                return json_encode(array($one, $two, $three));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar","baz\/foobar"]', $response->getContent());
    }

    public function testMatchRestMatchWildcardSomewhere()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two+/foobar",
            function ($one, $two) {
                return json_encode(array($one, $two));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar\/baz"]', $response->getContent());
    }

    public function testMatchRestWrongWildcard()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:abc+/foobaz",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestMatchWildcardInMiddle()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:one/:two+/:three",
            function ($one, $two, $three) {
                return json_encode(array($one, $two, $three));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar\/baz","foobar"]', $response->getContent());
    }

    public function testMatchRestNoAbsPath()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("foo");
        $service = new Service($request);
        $service->match(
            "GET",
            "foo",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestEmptyPath()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("");
        $service = new Service($request);
        $service->match(
            "GET",
            "",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestNoPatternPath()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo");
        $service = new Service($request);
        $service->match(
            "GET",
            "x",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestNoMatchWithoutReplacement()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo");
        $service = new Service($request);
        $service->match(
            "GET",
            "/bar",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestNoMatchWithoutReplacementLong()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/bar/foo/bar/baz");
        $service = new Service($request);
        $service->match(
            "GET",
            "/foo/bar/foo/bar/bar",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestTooShortRequest()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo");
        $service = new Service($request);
        $service->match(
            "GET",
            "/foo/bar/:foo/bar/bar",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestEmptyResource()
    {
        $request = new Request("http://www.example.org/api.php", "GET");
        $request->setPathInfo("/foo/");
        $service = new Service($request);
        $service->get(
            "/foo/:bar",
            null
        );
        $service->post(
            "/foo/:bar",
            null
        );
        $service->put(
            "/foo/:bar",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testMatchRestVootGroups()
    {
        $request = new Request("http://localhost/oauth/php-voot-proxy/voot.php", "GET");
        $request->setPathInfo("/groups/@me");
        $service = new Service($request);
        $service->match(
            "GET",
            "/groups/@me",
            function () {
                return "match";
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("match", $response->getContent());
    }

    public function testMatchRestVootPeople()
    {
        $request = new Request("http://localhost/oauth/php-voot-proxy/voot.php", "GET");
        $request->setPathInfo("/people/@me/urn:groups:demo:member");
        $service = new Service($request);
        $service->match(
            "GET",
            "/people/@me/:groupId",
            function ($groupId) {
                return $groupId;
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("urn:groups:demo:member", $response->getContent());
    }

    public function testMatchRestAllPaths()
    {
        $request = new Request("http://www.example.org/api.php", "OPTIONS");
        $request->setPathInfo("/foo/bar/baz/foobar");
        $service = new Service($request);
        $service->options(
            null,
            function () {
                return "match";
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("match", $response->getContent());
    }

    public function testOptionalMatch()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/public/money/");
        $service = new Service($request);
        $service->get(
            "/:user/public/:module(/:path+)/",
            function ($user, $module, $path = null) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money",null]', $response->getContent());
    }

    public function testOtherOptionalMatch()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/public/money/a/b/c/");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:user/public/:module(/:path+)/",
            function ($user, $module, $path = null) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money","a\/b\/c"]', $response->getContent());
    }

    public function testWildcardShouldNotMatchDir()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:user/:module/:path+",
            null
        );
        $response = $service->run();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWildcardShouldMatchDir()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->match(
            "GET",
            "/:user/:module/:path+/",
            function ($user, $module, $path) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money","a\/b\/c"]', $response->getContent());
    }

    public function testMatchAllWithParameter()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->match(
            "GET",
            null,
            function ($all) {
                return $all;
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getContent());
    }

    public function testMatchAllWithStarParameter()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "DELETE");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->delete(
            "*",
            function ($all) {
                return $all;
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getContent());
    }

    public function testHeadRequest()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "HEAD");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->head(
            "*",
            function ($all) {
                return "";
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(0, strlen($response->getContent()));
    }

    public function testMultipleMethodMatchGet()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "GET");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->match(array("GET", "HEAD"),
            "*",
            function ($all) use ($request) {
                return "HEAD" === $request->getRequestMethod() ? "" : $all;
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getContent());
    }

    public function testMultipleMethodMatchHead()
    {
        $request = new Request("http://localhost/php-remoteStorage/api.php", "HEAD");
        $request->setPathInfo("/admin/money/a/b/c/");
        $service = new Service($request);
        $service->match(array("GET", "HEAD"),
            "*",
            function ($all) use ($request) {
                return "HEAD" === $request->getRequestMethod() ? "" : $all;
            }
        );
        $response = $service->run();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("", $response->getContent());
    }
}
