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
namespace fkooman\Rest;

require_once __DIR__.'/Test/TestRequest.php';

use fkooman\Rest\Test\TestRequest;
use StdClass;
use fkooman\Http\Response;
use PHPUnit_Framework_TestCase;

class PluginRegistryTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultPluginNotDisabledReturnResponse()
    {
        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('Stub')->getMock();
        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue(new Response()));

        $request = TestRequest::get(
            'http://www.example.org/'
        );
        $route = new Route(
            array('GET'),
            '/',
            function () {
            }
        );
        $p = new PluginRegistry();
        $p->registerDefaultPlugin($stub);
        $response = $p->run($request, $route);
        $this->assertInstanceOf('fkooman\Http\Response', $response);
    }

    public function testDefaultPluginNotDisabledReturnObject()
    {
        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('Stub')->getMock();
        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue((object) array('foo' => 'bar')));

        $request = TestRequest::get(
            'http://www.example.org/'
        );
        $route = new Route(
            array('GET'),
            '/',
            function () {
            }
        );
        $p = new PluginRegistry();
        $p->registerDefaultPlugin($stub);
        $response = $p->run($request, $route);
        $this->assertObjectHasAttribute('foo', $response['stdClass']);
    }

    public function testDefaultPluginDisabledReturnObject()
    {
        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('Stub')->getMock();
        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue((object) array('foo' => 'bar')));

        $request = TestRequest::get(
            'http://www.example.org/'
        );
        $route = new Route(
            array('GET'),
            '/',
            function () {
            },
            array('Stub' => array('enabled' => false))
        );
        $p = new PluginRegistry();
        $p->registerDefaultPlugin($stub);
        $response = $p->run($request, $route);
        $this->assertSame(array(), $response);
    }

    public function testOptionalPluginNotEnabledReturnObject()
    {
        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('Stub')->getMock();
        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue((object) array('foo' => 'bar')));

        $request = TestRequest::get(
            'http://www.example.org/'
        );
        $route = new Route(
            array('GET'),
            '/',
            function () {
            }
        );
        $p = new PluginRegistry();
        $p->registerOptionalPlugin($stub);
        $response = $p->run($request, $route);
        $this->assertSame(array(), $response);
    }

    public function testOptionalPluginEnabledReturnObject()
    {
        $stub = $this->getMockBuilder('fkooman\Rest\ServicePluginInterface')->setMockClassName('Stub')->getMock();
        $stub->expects($this->any())
             ->method('execute')
             ->will($this->returnValue((object) array('foo' => 'bar')));

        $request = TestRequest::get(
            'http://www.example.org/'
        );
        $route = new Route(
            array('GET'),
            '/',
            function () {
            },
            array('Stub' => array('enabled' => true))
        );
        $p = new PluginRegistry();
        $p->registerOptionalPlugin($stub);
        $response = $p->run($request, $route);
        $this->assertObjectHasAttribute('foo', $response['stdClass']);
    }
}
