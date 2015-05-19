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

use LogicException;

class PatternMatcher
{
    /**
     * Determines if the provided PATH_INFO matches the defined pattern.
     *
     * @param pathInfo string the PATH_INFO from the request
     * @param requestPattern the pattern to match against
     *
     * @returns array containing the matched parameters
     */
    public static function isMatch($pathInfo, $requestPattern)
    {
        // if no pattern is defined, all paths are valid
        if (null === $requestPattern || '*' === $requestPattern) {
            // FIXME return $this->executeCallback($request, $callback, $paramsAvailableForCallback, $matchOptions);
            return array();
        }
        // both the pattern and request path should start with a '/'
        if (0 !== strpos($requestPattern, '/')) {
            return false;
        }

        // handle optional parameters
        $requestPattern = str_replace(')', ')?', $requestPattern);

        // check for variables in the requestPattern
        $pma = preg_match_all('#:([\w]+)\+?#', $requestPattern, $matches);
        if (false === $pma) {
            throw new LogicException('regex for variable search failed');
        }
        if (0 === $pma) {
            // no variables in the pattern, pattern and request must be identical
            if ($pathInfo === $requestPattern) {
                // FIXME: return $this->executeCallback($request, $callback, $paramsAvailableForCallback, $matchOptions);
                return array();
            }

            return false;
        }
        // replace all the variables with a regex so the actual value in the request
        // can be captured
        foreach ($matches[0] as $m) {
            // determine pattern based on whether variable is wildcard or not
            $mm = str_replace(array(':', '+'), '', $m);
            $pattern = (strpos($m, '+') === strlen($m) -1) ? '(?P<'.$mm.'>(.+?[^/]))' : '(?P<'.$mm.'>([^/]+))';
            $requestPattern = str_replace($m, $pattern, $requestPattern);
        }

        $parameters = array();
        $pm = preg_match('#^'.$requestPattern.'$#', $pathInfo, $parameters);
        if (false === $pm) {
            throw new LogicException('regex for path matching failed');
        }
        if (0 === $pm) {
            // request path does not match pattern
            return false;
        }

        $callbackParams = array();
        foreach ($parameters as $k => $v) {
            // find the name of the parameter in the callback and set it to
            // the value
            if (is_string($k)) {
                $callbackParams[$k] = $v;
            }
        }

        return $callbackParams;
    }
}
