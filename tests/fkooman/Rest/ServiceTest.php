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

namespace fkooman\Rest;

use fkooman\Http\Request;
use fkooman\Http\Response;
use StdClass;
use PHPUnit_Framework_TestCase;

class ServiceTest extends PHPUnit_Framework_TestCase
{

    public function requestFromUrl($url, $method, $headers = array(), $post = array())
    {
        $pu = parse_url($url);
                    
        // port
        if (array_key_exists('port', $pu)) {
            $port = $pu['port'];
        } else {
            if ('https' === $pu['scheme']) {
                $port = 443;
            } else {
                $port = 80;
            }
        }
        
        // path
        if (array_key_exists('path', $pu)) {
            $path = $pu['path'];
        } else {
            $path = '/';
        }

        // query
        if (array_key_exists('query', $pu)) {
            $query = $pu['query'];
        } else {
            $query = '';
        }

        $srv = array(
            'REQUEST_METHOD' => $method,
            'SERVER_NAME' => $pu['host'],
            'SERVER_PORT' => $port,
            'QUERY_STRING' => $query,
            'REQUEST_URI' => $path,
            'PATH_INFO' => $path,
            'SCRIPT_NAME' => '/index.php',
            'HTTPS' => 'https' === $pu['scheme'] ? 'on' : 'off'
        );
        $srv = array_merge($srv, $headers);

        return new Request($srv, $post);
    }

    public function testSimpleMatch()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $service = new Service();
        $service->get(
            "/foo/bar/baz.txt",
            function () {
                $response = new Response(200, "text/plain");
                $response->setBody("Hello World");

                return $response;
            }
        );
        $response = $service->run($request);
        $this->assertEquals("Hello World", $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOnMatchPluginNoSkip()
    {
        $service = new Service();

        $stub = $this->getMock('fkooman\Rest\ServicePluginInterface');
        $stub->method('execute')
             ->willReturn((object) array("foo" => "bar"));

        $service->registerOnMatchPlugin($stub);
        $service->get(
            "/foo/bar/baz.txt",
            function (StdClass $x) {
                $response = new Response(200, "text/plain");
                $response->setBody($x->foo);

                return $response;
            }
        );
        $service->get(
            "/foo/bar/bazzz.txt",
            function (StdClass $x) {
                $response = new Response(200, "text/plain");
                $response->setBody($x->foo);

                return $response;
            }
        );
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("bar", $response->getBody());

        $request = $this->requestFromUrl("http://www.example.org/foo/bar/bazzz.txt", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("bar", $response->getBody());
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage parameter expected by callback not available
     */
    public function testOnMatchPluginSkip()
    {
        $service = new Service();

        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')
                     ->setMockClassName('FooPlugin')
                     ->getMock();
        $stub->method('execute')
             ->willReturn((object) array("foo" => "bar"));
        $service->registerOnMatchPlugin($stub);

        $service->get(
            "/foo/bar/foobar.txt",
            function (StdClass $x) {
            }
        );

        // because the plugin is skipped, the StdClass should not be available!
        $service->get(
            "/foo/bar/baz.txt",
            function (StdClass $x) {
                $response = new Response(200, "text/plain");
                $response->setBody($x->foo);

                return $response;
            },
            array('skipPlugins' => array('FooPlugin'))
        );

        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\MethodNotAllowedException
     * @expectedExceptionMessage unsupported method
     */
    public function testNonMethodMatch()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");

        $service = new Service();
        $service->post("/foo/bar/baz.txt", null);
        $service->delete("/foo/bar/baz.txt", null);
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testNonPatternMatch()
    {
        $request = $this->requestFromUrl("http://www.example.org/bar/foo.txt", "GET");
        $service = new Service();
        $service->match(array("GET"), "/foo/:xyz", null);
        $service->run($request);
    }

    public function testNonResponseReturn()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/foo/bar/baz.txt",
            function () {
                return "Hello World";
            }
        );
        $response = $service->run($request);
        $this->assertEquals("text/html", $response->getHeader('Content-Type'));
        $this->assertEquals("Hello World", $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException fkooman\Http\Exception\MethodNotAllowedException
     * @expectedExceptionMessage unsupported method
     */
    public function testMatchRestWrongMethod()
    {
        $request = $this->requestFromUrl("http://www.example.org/", "POST");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:one/:two/:three",
            null
        );
        $service->run($request);
    }

    public function testMatchRestMatchWildcard()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz/foobar", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:one/:two/:three+",
            function ($one, $two, $three) {
                return json_encode(array($one, $two, $three));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar","baz\/foobar"]', $response->getBody());
    }

    public function testMatchRestMatchWildcardSomewhere()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz/foobar", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:one/:two+/foobar",
            function ($one, $two) {
                return json_encode(array($one, $two));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar\/baz"]', $response->getBody());
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestWrongWildcard()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz/foobar", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:abc+/foobaz",
            null
        );
        $service->run($request);
    }

    public function testEndingSlashWildcard()
    {
        $request = $this->requestFromUrl("http://www.example.org/admin/public/calendar/42/16/", "GET");
        $service = new Service();
        $service->get(
            '/:userId/public/:moduleName/:path+/',
            function (Request $request, $userId, $moduleName, $path) {
                return $request->getUrl()->getPathInfo();
            }
        );
        $response = $service->run($request);
        $this->assertEquals('/admin/public/calendar/42/16/', $response->getBody());
    }

    public function testMatchRestMatchWildcardInMiddle()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz/foobar", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:one/:two+/:three",
            function ($one, $two, $three) {
                return json_encode(array($one, $two, $three));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["foo","bar\/baz","foobar"]', $response->getBody());
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestNoAbsPath()
    {
        $request = $this->requestFromUrl("http://www.example.org", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "foo",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestEmptyPath()
    {
        $request = $this->requestFromUrl("http://www.example.org", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestNoPatternPath()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "x",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestNoMatchWithoutReplacement()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/bar",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestNoMatchWithoutReplacementLong()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/foo/bar/baz", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/foo/bar/foo/bar/bar",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestTooShortRequest()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/foo/bar/:foo/bar/bar",
            null
        );
        $service->run($request);
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testMatchRestEmptyResource()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/", "GET");
        $service = new Service();
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
        $service->run($request);
    }

    public function testMatchRestVootGroups()
    {
        $request = $this->requestFromUrl("http://localhost/groups/@me", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/groups/@me",
            function () {
                return "match";
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("match", $response->getBody());
    }

    public function testMatchRestVootPeople()
    {
        $request = $this->requestFromUrl("http://localhost/people/@me/urn:groups:demo:member", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/people/@me/:groupId",
            function ($groupId) {
                return $groupId;
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("urn:groups:demo:member", $response->getBody());
    }

    public function testMatchRestAllPaths()
    {
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz/foobar", "OPTIONS");
        $service = new Service();
        $service->options(
            "*",
            function () {
                return "match";
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("match", $response->getBody());
    }

    public function testOptionalMatch()
    {
        $request = $this->requestFromUrl("http://localhost/admin/public/money/", "GET");
        $service = new Service();
        $service->get(
            "/:user/public/:module(/:path+)/",
            function ($user, $module, $path = null) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money",null]', $response->getBody());
    }

    public function testOtherOptionalMatch()
    {
        $request = $this->requestFromUrl("http://localhost/admin/public/money/a/b/c/", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:user/public/:module(/:path+)/",
            function ($user, $module, $path = null) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money","a\/b\/c"]', $response->getBody());
    }

    /**
     * @expectedException fkooman\Http\Exception\NotFoundException
     * @expectedExceptionMessage url not found
     */
    public function testWildcardShouldNotMatchDir()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:user/:module/:path+",
            null
        );
        $service->run($request);
    }

    public function testWildcardShouldMatchDir()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "/:user/:module/:path+/",
            function ($user, $module, $path) {
                return json_encode(array($user, $module, $path));
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('["admin","money","a\/b\/c"]', $response->getBody());
    }

    public function testMatchAllWithParameter()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "GET");
        $service = new Service();
        $service->match(
            array("GET"),
            "*",
            function (Request $request) {
                return $request->getUrl()->getPathInfo();
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getBody());
    }

    public function testMatchAllWithStarParameter()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "DELETE");
        $service = new Service();
        $service->delete(
            "*",
            function (Request $request) {
                return $request->getUrl()->getPathInfo();
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getBody());
    }

    public function testMultipleMethodMatchGet()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "GET");
        $service = new Service();
        $service->match(
            array(
                "GET",
                "HEAD",
            ),
            "*",
            function (Request $request) {
                return "HEAD" === $request->getMethod() ? "" : $request->getUrl()->getPathInfo();
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/admin/money/a/b/c/', $response->getBody());
    }

    public function testMultipleMethodMatchHead()
    {
        $request = $this->requestFromUrl("http://localhost/admin/money/a/b/c/", "HEAD");
        $service = new Service();
        $service->match(
            array(
                "GET",
                "HEAD",
            ),
            "*",
            function (Request $request) {
                return "HEAD" === $request->getMethod() ? "" : $request->getUrl()->getPathInfo();
            }
        );
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("", $response->getBody());
    }

    public function testCallbackRequestParameter()
    {
        $service = new Service();
        $t = &$this;    // needed for PHP 5.3, together with the 'use ($t) below'
        $service->get('/:foo', function (Request $r, $foo) use ($t) {
            // $t is needed for PHP 5.3, in PHP >5.3 you can just use $this
            $t->assertEquals('GET', $r->getMethod());
            $t->assertEquals('xyz', $foo);

            return 'foo';
        });
        $request = $this->requestFromUrl('http://www.example.org/xyz', 'GET');
        $service->run($request);
    }

    public function testNonMatchAllParameterWithWildcard()
    {
        $service = new Service();
        $service->get(
            "*",
            function () {
                return "foobar";
            }
        );
        $request = $this->requestFromUrl("http://example.org/foo/bar/baz", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("foobar", $response->getBody());
    }

    public function testMatchRequestParameterOrder()
    {
        $service = new Service();
        $service->get(
            "/:foo/:bar/baz",
            function ($bar, $foo, Request $request) {
                return $foo.$bar.$request->getMethod();
            }
        );
        $request = $this->requestFromUrl("http://example.org/xxx/yyy/baz", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("xxxyyyGET", $response->getBody());
    }

    public function testMatchRequestParameterMatchAll()
    {
        $service = new Service();
        $service->get(
            "*",
            function (Request $request) {
                return $request->getUrl()->getPathInfo() . $request->getMethod();
            }
        );
        $request = $this->requestFromUrl("http://example.org/xxx/yyy/baz", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/xxx/yyy/bazGET", $response->getBody());
    }

    public function testMatchRequestParameterMatchExactNoVariablesRequest()
    {
        $service = new Service();
        $service->get(
            "/foo/bar/baz",
            function (Request $request) {
                return $request->getMethod();
            }
        );
        $request = $this->requestFromUrl("http://example.org/foo/bar/baz", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("GET", $response->getBody());
    }

    public function testFormMethodOverrideDelete()
    {
        $service = new Service();
        $service->delete(
            "/foo/bar/baz",
            function (Request $request) {
                return "hello, delete!";
            }
        );

        $headers = array(
            'HTTP_REFERER' => 'http://example.org/',
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
        $post = array(
            '_METHOD' => 'DELETE'
        );

        $request = $this->requestFromUrl("http://example.org/foo/bar/baz", "POST", $headers, $post);
        $response = $service->run($request);
        $this->assertEquals("hello, delete!", $response->getBody());
    }

#    public function testDefaultRouteNoPathInfo()
#    {
#        $service = new Service();
#        $service->setDefaultRoute('/');
#        $service->get(
#            '/',
#            function () {
#                return 'index';
#            }
#        );
#        $request = $this->requestFromUrl("http://www.example.org/index.php", "GET");
#        $request->setRoot('/index.php/');
#        $response = $service->run($request);
#        $this->assertEquals(302, $response->getStatusCode());
#        $this->assertEquals("http://www.example.org/index.php/", $response->getHeader('Location'));

#        $request = $this->requestFromUrl("http://www.example.org/index.php/", "GET");
#        $request->setRoot('/index.php/');
#        $request->setPathInfo('/');
#        $response = $service->run($request);
#        $this->assertEquals(200, $response->getStatusCode());
#        $this->assertEquals('index', $response->getBody());
#    }

#    public function testDefaultRoute()
#    {
#        $service = new Service();
#        $service->setDefaultRoute('/manage/');
#        $service->get(
#            '/manage/',
#            function () {
#                return "default_route_works";
#            }
#        );
#        $request = $this->requestFromUrl("http://www.example.org/index.php", "GET");
#        $request->setRoot('/index.php/');
#        $response = $service->run($request);
#        $this->assertEquals(302, $response->getStatusCode());
#        $this->assertEquals("http://www.example.org/index.php/", $response->getHeader('Location'));

#        $request = $this->requestFromUrl("http://www.example.org/index.php", "GET");
#        $request->setRoot('/index.php/');
#        $request->setPathInfo('/');
#        $response = $service->run($request);
#        $this->assertEquals(302, $response->getStatusCode());
#        $this->assertEquals("http://www.example.org/index.php/manage/", $response->getHeader('Location'));

#        $request = $this->requestFromUrl("http://www.example.org/index.php", "GET");
#        $request->setRoot('/index.php/');
#        $request->setPathInfo('/manage/');
#        $response = $service->run($request);
#        $this->assertEquals(200, $response->getStatusCode());
#        $this->assertEquals('default_route_works', $response->getBody());
#    }

#    public function testNoPathInfo()
#    {
#        $service = new Service();
#        $service->get(
#            '/foo',
#            function () {
#                return 'foo';
#            }
#        );
#        $request = $this->requestFromUrl("http://www.example.org/index.php", "GET");
#        $request->setRoot('/index.php/');
#        $response = $service->run($request);
#        $this->assertEquals(302, $response->getStatusCode());
#        $this->assertEquals('http://www.example.org/index.php/', $response->getHeader('Location'));
#    }

#    public function testUrlEncodedIndex()
#    {
#        $service = new Service();
#        $service->get(
#            '/info/:url',
#            function ($url) {
#                return $url;
#            }
#        );
#        $request = $this->requestFromUrl('http://www.example.org/info/?_index=https://www.example.org/foo/bar/baz');
#        $request->setPathInfo('/info/');
#        $response = $service->run($request);
#        $this->assertEquals('https%3A%2F%2Fwww.example.org%2Ffoo%2Fbar%2Fbaz', $response->getBody());
#    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage parameter expected by callback not available
     */
    public function testDefaultDisablePlugins()
    {
        $service = new Service();

        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')
                     ->setMockClassName('FooPlugin')
                     ->getMock();
        $stub->method('execute')
             ->willReturn((object) array("foo" => "bar"));
        $service->registerOnMatchPlugin($stub, array('defaultDisable' => true));

        // because the plugin is skipped by default, the StdClass should not be available!
        $service->get(
            "/foo/bar/baz.txt",
            function (StdClass $x) {
                return $x->foo;
            }
        );
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $service->run($request);
    }

    public function testDefaultDisablePluginsEnableForRoute()
    {
        $service = new Service();

        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')
                     ->setMockClassName('FooPlugin')
                     ->getMock();
        $stub->method('execute')
             ->willReturn((object) array("foo" => "bar"));
        $service->registerOnMatchPlugin($stub, array('defaultDisable' => true));

        // because the plugin is skipped by default, the StdClass should not be available!
        $service->get(
            "/foo/bar/baz.txt",
            function (StdClass $x) {
                return $x->foo;
            },
            array(
                'enablePlugins' => array(
                    'FooPlugin'
                )
            )
        );
        $request = $this->requestFromUrl("http://www.example.org/foo/bar/baz.txt", "GET");
        $response = $service->run($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bar', $response->getBody());
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage CSRF protection triggered
     */
    public function testReferrerCheck()
    {
        $service = new Service();
        $service->setReferrerCheck(true);
        $service->post(
            '/foo',
            function (Request $request) {
                return 'foo';
            }
        );

        $request = $this->requestFromUrl('http://example.org/foo', 'POST');
        $service->run($request);
    }

    public function testReferrerCheckDisabled()
    {
        $service = new Service();
        $service->setReferrerCheck(true);
        $service->post(
            '/foo',
            function (Request $request) {
                return 'foo';
            },
            array('disableReferrerCheck' => true)
        );

        $request = $this->requestFromUrl('http://example.org/foo', 'POST');
        $response = $service->run($request);
        $this->assertEquals('foo', $response->getBody());
    }
}
