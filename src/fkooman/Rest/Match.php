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
use ReflectionParameter;

class Match
{
    public function __construct(array $methods, $pattern, $callback, array $options = array())
    {
        // FIXME: validate input, pattern must be string, callback must be callable etc.
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

    /**
     * FIXME: try to make obsolete!
     */
    public function getPattern()
    {
        return $this->pattern;
    }

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

    public function executeCallback(array $availableParameters)
    {
        // FIXME: we assume that callback is callable!
        $callbackParameters = array();
        $reflectionFunction = new ReflectionFunction($this->callback);
        foreach ($reflectionFunction->getParameters() as $parameter) {
            $callbackParameters[] = $this->findAvailableParameterValue(
                $parameter,
                $availableParameters
            );
        }
        return call_user_func_array(
            $this->callback,
            array_values($callbackParameters)
        );
    }

    /**
     * Find the parameter required by the callback.
     *
     * @param ReflectionParameter p a parameter belonging to the callback
     * @param array availableParameters
     *      contains a list of parameters available to the callback, where the
     *      key contains either the type hinted name of the class, or the
     *      name of the parameter if the parameter is no class, e.g.:
     *          array('foo' => 5, 'stdClass' => new StdClass())
     *
     * @return the value of the parameter from $availableParameters, or an
     *         (optional) provided default value by the callback
     */
    private function findAvailableParameterValue(ReflectionParameter $p, array $availableParameters)
    {
        // determine the name, in case of a class this is the type hint, in
        // case of a non-class parameter it is the actual name
        $isClass = null !== $p->getClass();
        if ($isClass) {
            $parameterName = $p->getClass()->getName();
        } else {
            $parameterName = $p->getName();
        }

        if (array_key_exists($parameterName, $availableParameters)) {
            // we found the parameter!
            return $availableParameters[$parameterName];
        }
        if ($p->isDefaultValueAvailable()) {
            // we have a default value!
            return $p->getDefaultValue();
        }
        throw new BadFunctionCallException(
            sprintf(
                'parameter "%s" expected by callback not available',
                $parameterName
            )
        );
    }
}
