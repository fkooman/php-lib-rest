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

use PHPUnit_Framework_TestCase;
use StdClass;

class RouteTest extends PHPUnit_Framework_TestCase
{
    public function testIsMatch()
    {
        $m = new Route(array('GET'), '/foo/bar', function () {
        });
        $this->assertEquals(array(), $m->isMatch('GET', '/foo/bar'));
        $this->assertFalse($m->isMatch('POST', '/foo/bar'));
        $this->assertFalse($m->isMatch('GET', '/foo/baz'));
    }

    public function testExecuteCallback()
    {
        $m = new Route(
            array('GET'),
            '/foo/:id',
            function (
                StdClass $s,
                // object
                $id,
                // native type
                array $x,
                // array,
                $foo = 5        // default value
) {
                return 'foo';
            }
        );
        $s = new StdClass();
        $this->assertEquals(
            'foo',
            $m->executeCallback(
                array(
                    'stdClass' => $s,
                    'x' => array(),
                    'id' => 5,
                )
            )
        );
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage parameter "foo" expected by callback not available
     */
    public function testMissingParameterForCallback()
    {
        $m = new Route(
            array('GET'),
            '/foo',
            function ($foo) {
                return 'foo';
            }
        );
        $m->executeCallback(array());
    }
}
