<?php

/**
* Copyright 2014 François Kooman <fkooman@tuxed.net>
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

use UnexpectedValueException;
use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\Exception\MethodNotAllowedException;
use fkooman\Http\Exception\NotFoundException;

class Service
{
    /** @var array */
    private $match;

    /** @var array */
    private $supportedMethods;

    /** @var array */
    private $beforeMatchingPlugins;

    /** @var array */
    private $beforeEachMatchPlugins;

    public function __construct()
    {
        $this->match = array();
        $this->supportedMethods = array();
        $this->beforeMatchingPlugins = array();
        $this->beforeEachMatchPlugins = array();
    }

    /**
     * Register a plugin that is always run before the matching starts.
     *
     * @param fkooman\Http\ServicePluginInterface $servicePlugin the plugin to
     *                                                           register
     */
    public function registerBeforeMatchingPlugin(ServicePluginInterface $servicePlugin)
    {
        $this->beforeMatchingPlugins[] = $servicePlugin;
    }

    /**
     * Register a plugin that is run for every match, allowing you to skip it
     * for particular matches.
     *
     * @param fkooman\Http\ServicePluginInterface the plugin to register
     */
    public function registerBeforeEachMatchPlugin(ServicePluginInterface $servicePlugin)
    {
        $this->beforeEachMatchPlugins[] = $servicePlugin;
    }

    public function get($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("GET", $requestPattern, $callback, $skipPlugin);
    }

    public function put($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("PUT", $requestPattern, $callback, $skipPlugin);
    }

    public function post($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("POST", $requestPattern, $callback, $skipPlugin);
    }

    public function delete($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("DELETE", $requestPattern, $callback, $skipPlugin);
    }

    public function options($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("OPTIONS", $requestPattern, $callback, $skipPlugin);
    }

    public function head($requestPattern, $callback, array $skipPlugin = array())
    {
        $this->match("HEAD", $requestPattern, $callback, $skipPlugin);
    }

    /**
     * Register a method/pattern match.
     *
     * @param string   $requestMethod  the request method, e.g. 'GET', 'POST'
     * @param string   $requestPattern the pattern to match
     * @param callback $callback       the callback to execute when this pattern
     *                                 matches
     * @param array    $skipPlugin     the full namespaced names of the plugin classes
     *                                 to skip
     */
    public function match($requestMethod, $requestPattern, $callback, array $skipPlugin = array())
    {
        if (!is_array($requestMethod)) {
            $requestMethod = array($requestMethod);
        }

        $this->match[] = array(
            "requestMethod" => $requestMethod,
            "requestPattern" => $requestPattern,
            "callback" => $callback,
            "skipPlugin" => $skipPlugin,
        );
        foreach ($requestMethod as $r) {
            if (!in_array($r, $this->supportedMethods)) {
                $this->supportedMethods[] = $r;
            }
        }
    }

    /**
     * Run the Service.
     *
     * @return fkooman\Http\Response the HTTP response object after mathing
     *                               is done and the appropriate callback was
     *                               executed. If nothing matches either 404
     *                               or 405 response is returned.
     */
    public function run(Request $request)
    {
        // run the beforeMatchingPlugins
        foreach ($this->beforeMatchingPlugins as $plugin) {
            $response = $plugin->execute($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        foreach ($this->match as $m) {
            // run the beforeEachMatchPlugins
            foreach ($this->beforeEachMatchPlugins as $plugin) {
                // only run when plugin should not be skipped
                if (in_array(get_class($plugin), $m['skipPlugin'])) {
                    continue;
                }
                $response = $plugin->execute($request);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            $response = $this->matchRest(
                $request,
                $m['requestMethod'],
                $m['requestPattern'],
                $m['callback']
            );

            // false indicates not a match
            if (false !== $response) {
                if ($response instanceof Response) {
                    return $response;
                }
                if (!is_string($response)) {
                    throw new UnexpectedValueException("callback MUST return Response object or string");
                }
                $responseObj = new Response();
                $responseObj->setContent($response);

                return $responseObj;
            }
        }

        // handle non matching patterns
        if (in_array($request->getRequestMethod(), $this->supportedMethods)) {
            throw new NotFoundException('url not found');
        }

        throw new MethodNotAllowedException($this->supportedMethods);
    }

    private function matchRest(Request $request, array $requestMethod, $requestPattern, $callback)
    {
        if (!in_array($request->getRequestMethod(), $requestMethod)) {
            return false;
        }
        // if no pattern is defined, all paths are valid
        if (null === $requestPattern || "*" === $requestPattern) {
            return call_user_func_array($callback, array($request->getPathInfo()));
        }
        // both the pattern and request path should start with a "/"
        if (0 !== strpos($request->getPathInfo(), "/") || 0 !== strpos($requestPattern, "/")) {
            return false;
        }

        // handle optional parameters
        $requestPattern = str_replace(')', ')?', $requestPattern);

        // check for variables in the requestPattern
        $pma = preg_match_all('#:([\w]+)\+?#', $requestPattern, $matches);
        if (false === $pma) {
            throw new InternalServerErrorException("regex for variable search failed");
        }
        if (0 === $pma) {
            // no variables in the pattern, pattern and request must be identical
            if ($request->getPathInfo() === $requestPattern) {
                return call_user_func_array($callback, array());
            }
            // FIXME?!
            //return false;
        }
        // replace all the variables with a regex so the actual value in the request
        // can be captured
        foreach ($matches[0] as $m) {
            // determine pattern based on whether variable is wildcard or not
            $mm = str_replace(array(":", "+"), "", $m);
            $pattern = (strpos($m, "+") === strlen($m) -1) ? '(?P<'.$mm.'>(.+?[^/]))' : '(?P<'.$mm.'>([^/]+))';
            $requestPattern = str_replace($m, $pattern, $requestPattern);
        }
        $pm = preg_match("#^".$requestPattern."$#", $request->getPathInfo(), $parameters);
        if (false === $pm) {
            throw new InternalServerErrorException("regex for path matching failed");
        }
        if (0 === $pm) {
            // request path does not match pattern
            return false;
        }
        foreach ($parameters as $k => $v) {
            if (!is_string($k)) {
                unset($parameters[$k]);
            }
        }
        // request path matches pattern!
        return call_user_func_array($callback, array_values($parameters));
    }
}
