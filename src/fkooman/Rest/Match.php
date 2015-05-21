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

use ReflectionFunction;
use BadFunctionCallException;

class Match
{
    public function __construct(array $methods, $pattern, $callback, array $options = array())
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->options = $options;
    }

    /**
     * FIXME: try to make obsolete
     */
    public function hasMethod($method)
    {
        return in_array($method, $this->methods);
    }

    public function isMatch($method, $pathInfo)
    {
        if (!$this->hasMethod($method)) {
            return false;
        }

        return PatternMatcher::isMatch($pathInfo, $this->getPattern());
    }

#    public function getMethods()
#    {
#        return $this->methods;
#    }

    /**
     * FIXME: try to make obsolete!
     */
    public function getPattern()
    {
        return $this->pattern;
    }

#    public function getCallback()
#    {
#        return $this->callback;
#    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getDisableReferrerCheck()
    {
        if (array_key_exists('disableReferrerCheck', $this->getOptions())) {
            return $this->options['disableReferrerCheck'];
        }
        return false;
    }

    public function getPluginEnabled($pluginName)
    {
        $o = $this->getOptions();
        if (array_key_exists('enablePlugins', $o)) {
            if (is_array($o['enablePlugins'])) {
                return in_array($pluginName, $o['enablePlugins']);
            }
        }
        return false;
    }

    public function getRoutePluginConfig($pluginName)
    {
        $o = $this->getOptions();
        if (array_key_exists($pluginName, $o)) {
            if (is_array($o[$pluginName])) {
                return $o[$pluginName];
            }
        }
        return array();
    }

    public function executeCallback(array $p)
    {
        $cbParams = array();
        if (null !== $this->callback) {
            $reflectionFunction = new ReflectionFunction($this->callback);
            foreach ($reflectionFunction->getParameters() as $pp) {
                if (null !== $pp->getClass()) {
                    // object
                    if (!array_key_exists($pp->getClass()->getName(), $p)) {
                        if (!$pp->isDefaultValueAvailable()) {
                            throw new BadFunctionCallException("parameter expected by callback not available");
                        } else {
                            // add default value to cbParams
                            $cbParams[] = $pp->getDefaultValue();
                        }
                    } else {
                        $cbParams[] = $p[$pp->getClass()->getName()];
                    }
                } else {
                    // internal type
                    if (!array_key_exists($pp->getName(), $p)) {
                        if (!$pp->isDefaultValueAvailable()) {
                            throw new BadFunctionCallException("parameter expected by callback not available");
                        } else {
                            // add default value to cbParams
                            $cbParams[] = $pp->getDefaultValue();
                        }
                    } else {
                        $cbParams[] = $p[$pp->getName()];
                    }
                }
                // FIXME: are there other types we should consider?
            }
        }
        return call_user_func_array($this->callback, array_values($cbParams));
    }
}
