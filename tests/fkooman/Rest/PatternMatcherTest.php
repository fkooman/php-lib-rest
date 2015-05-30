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

class PatternMatcherTest extends PHPUnit_Framework_TestCase
{
    public function testMatches()
    {
        $this->assertEquals(
            array('id' => 'bar'),
            PatternMatcher::isMatch('/foo/bar', '/foo/:id')
        );
        $this->assertEquals(
            array('c' => 'bar', 'd' => 'baz'),
            PatternMatcher::isMatch('/foo/bar/baz/', '/foo/:c/:d/')
        );

        $this->assertEquals(array(), PatternMatcher::isMatch('/foo/bar/baz', '/foo/bar/baz'));
        $this->assertEquals(array(), PatternMatcher::isMatch('/foo/bar/', '*'));

        $this->assertFalse(PatternMatcher::isMatch('/foo', '/bar'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/', '/foo'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/baz', '/foo/:id'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/', '/foo/:id'));
    }
}
