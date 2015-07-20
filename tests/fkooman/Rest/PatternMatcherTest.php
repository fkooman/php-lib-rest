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
        $this->assertSame(
            array('id' => 'bar'),
            PatternMatcher::isMatch('/foo/bar', '/foo/:id')
        );
        $this->assertSame(
            array('c' => 'bar', 'd' => 'baz'),
            PatternMatcher::isMatch('/foo/bar/baz/', '/foo/:c/:d/')
        );

        // if the parameter value does not start with _ we want to accept it
        $this->assertSame(
            array('id' => 'bar_baz'),
            PatternMatcher::isMatch('/foo/bar_baz', '/foo/:id')
        );

        $this->assertSame(array(), PatternMatcher::isMatch(null, '*'));
        $this->assertSame(array(), PatternMatcher::isMatch('/foo/bar/baz', '/foo/bar/baz'));
        $this->assertSame(array(), PatternMatcher::isMatch('/foo/bar/', '*'));

        $this->assertFalse(PatternMatcher::isMatch(null, '/foo'));
        $this->assertFalse(PatternMatcher::isMatch(null, '/'));
        $this->assertFalse(PatternMatcher::isMatch(null, '/foo/:id'));
        $this->assertFalse(PatternMatcher::isMatch('/foo', '/bar'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/', '/foo'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/baz', '/foo/:id'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/', '/foo/:id'));

        // if the value for a parameter match *starts* with an underscore we
        // treat it as special and do not accept it as a valid match
        $this->assertFalse(PatternMatcher::isMatch('/_indieauth/auth', '/:foo/auth'));
        $this->assertFalse(PatternMatcher::isMatch('/__indieauth/auth', '/:foo/auth'));
    }
}
