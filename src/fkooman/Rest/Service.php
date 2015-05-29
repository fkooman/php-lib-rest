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

use fkooman\Http\Exception\MethodNotAllowedException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\Request;
use fkooman\Http\Response;
use RuntimeException;

class Service
{
    /** @var array */
    private $routes;

    /** @var array */
    private $supportedMethods;

    /** @var fkooman\Rest\PluginRegistry */
    private $pluginRegistry;

    public function __construct()
    {
        $this->routes = array();
        $this->supportedMethods = array();
        $this->pluginRegistry = new PluginRegistry();
    }

    public function registerDefaultPlugin(ServicePluginInterface $plugin)
    {
        if (method_exists($plugin, 'init')) {
            $plugin->init($this);
        }
        $this->pluginRegistry->registerDefaultPlugin($plugin);
    }

    public function registerOptionalPlugin(ServicePluginInterface $plugin)
    {
        if (method_exists($plugin, 'init')) {
            $plugin->init($this);
        }
        $this->pluginRegistry->registerOptionalPlugin($plugin);
    }

    public function get($requestPattern, $callback, array $routeOptions = array())
    {
        $this->addRoute(array('GET', 'HEAD'), $requestPattern, $callback, $routeOptions);
    }

    public function put($requestPattern, $callback, array $routeOptions = array())
    {
        $this->addRoute(array('PUT'), $requestPattern, $callback, $routeOptions);
    }

    public function post($requestPattern, $callback, array $routeOptions = array())
    {
        $this->addRoute(array('POST'), $requestPattern, $callback, $routeOptions);
    }

    public function delete($requestPattern, $callback, array $routeOptions = array())
    {
        $this->addRoute(array('DELETE'), $requestPattern, $callback, $routeOptions);
    }

    public function options($requestPattern, $callback, array $routeOptions = array())
    {
        $this->addRoute(array('OPTIONS'), $requestPattern, $callback, $routeOptions);
    }

    /**
     * Register a method/pattern route.
     *
     * @param array    $methods      the request methods, e.g. 'GET', 'POST'
     * @param string   $pattern      the pattern to match
     * @param callback $callback     the callback to execute when this pattern
     *                               matches
     * @param array    $routeOptions the options for this route
     *
     */
    public function addRoute(array $methods, $pattern, $callback, array $routeOptions = array())
    {
        $this->routes[] = new Route($methods, $pattern, $callback, $routeOptions);
        foreach ($methods as $method) {
            if (!in_array($method, $this->supportedMethods)) {
                $this->supportedMethods[] = $method;
            }
        }
    }

    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = new Request($_SERVER);
        }
        
        // support PUT and DELETE method override when _METHOD is set in a form
        // POST
        if ('POST' === $request->getMethod()) {
            if ('PUT' === $request->getPostParameter('_METHOD')) {
                $request->setMethod('PUT');
            }
            if ('DELETE' === $request->getPostParameter('_METHOD')) {
                $request->setMethod('DELETE');
            }
        }

        foreach ($this->routes as $route) {
            if (false !== $availableRouteCallbackParameters = $route->isMatch($request->getMethod(), $request->getUrl()->getPathInfo())) {
                return $this->executeCallback($request, $route, $availableRouteCallbackParameters);
            }
        }

        // handle non matching patterns
        if (in_array($request->getMethod(), $this->supportedMethods)) {
            throw new NotFoundException('url not found', $request->getUrl()->getPathInfo());
        }

        if (0 !== count($this->supportedMethods)) {
            $errorDescription = sprintf('only %s allowed', implode(',', $this->supportedMethods));
        } else {
            $errorDescription = 'no methods allowed';
        }

        throw new MethodNotAllowedException(
            sprintf('unsupported method "%s"', $request->getMethod()),
            $errorDescription,
            $this->supportedMethods
        );
    }

    private function executeCallback(Request $request, Route $route, array $availableRouteCallbackParameters)
    {
        $pluginResponse = $this->pluginRegistry->run($request, $route);
        if ($pluginResponse instanceof Response) {
            // received Response from plugin, return this immediately
            return $pluginResponse;
        }

        $availableRouteCallbackParameters = array_merge($availableRouteCallbackParameters, $pluginResponse);
        $availableRouteCallbackParameters[get_class($request)] = $request;
        $response = $route->executeCallback($availableRouteCallbackParameters);
        if (!($response instanceof Response)) {
            throw new RuntimeException('callback return value must be Response object');
        }

        return $response;
    }
}
