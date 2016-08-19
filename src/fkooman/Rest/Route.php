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

use ReflectionFunction;
use BadFunctionCallException;
use ReflectionParameter;
use InvalidArgumentException;

class Route
{
    /** @var array */
    private $methods;

    /** @var string */
    private $pattern;

    /** @var callable */
    private $callback;

    /** @var array */
    private $config;

    public function __construct(array $methods, $pattern, $callback, array $config = [])
    {
        $this->methods = $methods;

        if (!is_string($pattern) || 0 >= mb_strlen($pattern)) {
            throw new InvalidArgumentException('pattern must be non-empty string');
        }
        $this->pattern = $pattern;

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('callback is not callable');
        }
        $this->callback = $callback;

        $this->config = $config;
    }

    public function isMatch($method, $pathInfo)
    {
        if (!in_array($method, $this->methods)) {
            return false;
        }

        return PatternMatcher::isMatch($pathInfo, $this->pattern);
    }

    public function getConfig($pluginName)
    {
        if (array_key_exists($pluginName, $this->config)) {
            return $this->config[$pluginName];
        }

        return [];
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function executeCallback(array $availableParameters)
    {
        $callbackParameters = [];
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
     * @param ReflectionParameter $p                   a parameter belonging to the callback
     * @param array               $availableParameters contains a list of parameters available to the callback, where the
     *                                                 key contains either the type hinted name of the class, or the
     *                                                 name of the parameter if the parameter is no class, e.g.:
     *                                                 array('foo' => 5, 'stdClass' => new StdClass())
     *
     * @return string the value of the parameter from $availableParameters, or an
     *                (optional) provided default value by the callback
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
