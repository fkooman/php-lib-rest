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

use fkooman\Rest\Plugin\ReferrerCheckPlugin;
use fkooman\Http\Request;
use PHPUnit_Framework_TestCase;

class PluginRegistryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER header missing
     */
    public function testDefaultPluginNotDisabled()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'POST',
        );
        $request = new Request($srv);
        $route = new Route(
            array('GET'),
            '/',
            function () {
            }
        );
        $p = new PluginRegistry();
        $p->registerDefaultPlugin(new ReferrerCheckPlugin());
        $p->run($request, $route);
    }

    public function testDefaultPluginDisabled()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'POST',
        );
        $request = new Request($srv);
        $route = new Route(
            array('GET'),
            '/',
            function () {
            },
            array(
                'fkooman\Rest\Plugin\ReferrerCheckPlugin' => array('enabled' => false)
            )
        );
        $p = new PluginRegistry();
        $p->registerDefaultPlugin(new ReferrerCheckPlugin());
        $p->run($request, $route);
    }

    public function testOptionalPluginNotEnabled()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'POST',
        );
        $request = new Request($srv);
        $route = new Route(
            array('GET'),
            '/',
            function () {
            }
        );
        $p = new PluginRegistry();
        $p->registerOptionalPlugin(new ReferrerCheckPlugin());
        $p->run($request, $route);
    }

    /**
     * @expectedException fkooman\Http\Exception\BadRequestException
     * @expectedExceptionMessage HTTP_REFERER header missing
     */
    public function testOptionalPluginEnabled()
    {
        $srv = array(
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PORT' => 80,
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_METHOD' => 'POST',
        );
        $request = new Request($srv);
        $route = new Route(
            array('GET'),
            '/',
            function () {
            },
            array(
                'fkooman\Rest\Plugin\ReferrerCheckPlugin' => array('enabled' => true)
            )
        );
        $p = new PluginRegistry();
        $p->registerOptionalPlugin(new ReferrerCheckPlugin());
        $p->run($request, $route);
    }
}
