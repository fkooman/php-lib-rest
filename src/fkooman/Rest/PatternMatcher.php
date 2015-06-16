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

use LogicException;
use InvalidArgumentException;

class PatternMatcher
{
    /**
     * Determines if the provided path matches the provided pattern and returns
     * matched parameters if specified.
     *
     * @param string $path    typically the PATH_INFO from the request
     * @param string $pattern the pattern to match against
     *
     * E: isMatch('/foo/bar',     '/foo/:id')     ==> array('id' => 'bar')
     *    isMatch('/foo/bar/baz', '/foo/bar/baz') ==> array()
     *    isMatch('/foo/bar/',    '*')            ==> array()
     *    isMatch('/foo',         '/bar')         ==> false
     *    isMatch('/foo/',        '/foo')         ==> false
     *    isMatch('/foo/bar/baz', '/foo/:id')     ==> false
     *    isMatch('/foo/bar/',    '/foo/:id')     ==> false
     *
     * @returns false|array array containing the values matching the variables
     *                      if there are any variables, empty array for an
     *                      exact match, false if there is no match
     */
    public static function isMatch($path, $pattern)
    {
        // validate path
        if (null !== $path && (!is_string($path) || 0 >= strlen($path) || 0 !== strpos($path, '/'))) {
            throw new InvalidArgumentException('invalid path');
        }

        // validate pattern
        if (!is_string($pattern) || 0 >= strlen($pattern) || (0 !== strpos($pattern, '/') && '*' !== $pattern)) {
            throw new InvalidArgumentException('invalid pattern');
        }

        // wildcard match, every path allowed
        if ('*' === $pattern) {
            return array();
        }

        // exact match
        if ($path === $pattern) {
            return array();
        }

        if (false === strpos($pattern, ':')) {
            // no variables defined in pattern, so it is always false...
            return false;
        }

        // replace all occurences of :var with (?P<var>([^/]+))
        $pattern = preg_replace('/:([\w]+)/i', '(?P<${1}>([^/_]+))', $pattern);
        if (null === $pattern) {
            throw new LogicException('regular expression for parameter replacement failed');
        }

        // match the path with this regexp
        $pm = preg_match(
            sprintf(
                '#^%s$#',
                $pattern
            ),
            $path,
            $parameters
        );

        if (false === $pm) {
            throw new LogicException('regular expression for path matching failed');
        }

        if (0 === $pm) {
            // request path does not match pattern
            return false;
        }

        $patternParameters = array();
        if (null !== $parameters) {
            foreach ($parameters as $k => $v) {
                // find the name of the parameter in the callback and set it to
                // the value
                if (is_string($k)) {
                    $patternParameters[$k] = $v;
                }
            }
        }

        return $patternParameters;
    }
}
