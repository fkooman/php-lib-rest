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

use fkooman\Http\Exception\MethodNotAllowedException;
use fkooman\Http\Exception\NotFoundException;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Request;
use fkooman\Rest\Plugin\ReferrerCheck\ReferrerCheckPlugin;
use fkooman\Http\Response;
use RuntimeException;
use ErrorException;

class Service
{
    /** @var array */
    private $routes;

    /** @var PluginRegistry */
    private $pluginRegistry;

    public function __construct()
    {
        set_error_handler(array('fkooman\Rest\Service', 'handleErrors'));

        $this->routes = array();
        $this->pluginRegistry = new PluginRegistry();

        // enable ReferrerCheck by default
        $this->pluginRegistry->registerDefaultPlugin(new ReferrerCheckPlugin());
    }

    public function getPluginRegistry()
    {
        return $this->pluginRegistry;
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
     */
    public function addRoute(array $methods, $pattern, $callback, array $routeOptions = array())
    {
        $this->routes[] = new Route($methods, $pattern, $callback, $routeOptions);
    }

    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = new Request($_SERVER);
        }
        try {
            return $this->runService($request);
        } catch (HttpException $e) {
            if (false !== strpos($request->getHeader('Accept'), 'text/html')) {
                return $e->getHtmlResponse();
            }
            if (false !== strpos($request->getHeader('Accept'), 'application/x-www-form-urlencoded')) {
                return $e->getFormResponse();
            }

            return $e->getJsonResponse();
        }
    }

    private function runService(Request $request)
    {
        // support method override when _METHOD is set in a form POST
        if ('POST' === $request->getMethod()) {
            $methodOverride = $request->getPostParameter('_METHOD');
            if (null !== $methodOverride) {
                $request->setMethod($methodOverride);
            }
        }

        foreach ($this->routes as $route) {
            if (false !== $availableRouteCallbackParameters = $route->isMatch($request->getMethod(), $request->getUrl()->getPathInfo())) {
                return $this->executeCallback($request, $route, $availableRouteCallbackParameters);
            }
        }

        // figure out all supported methods by all routes
        $supportedMethods = array();
        foreach ($this->routes as $route) {
            $routeMethods = $route->getMethods();
            foreach ($routeMethods as $method) {
                if (!in_array($method, $supportedMethods)) {
                    $supportedMethods[] = $method;
                }
            }
        }

        // requested method supported, document is just not available
        if (in_array($request->getMethod(), $supportedMethods)) {
            throw new NotFoundException('url not found', $request->getUrl()->getRoot().substr($request->getUrl()->getPathInfo(), 1));
        }

        // requested method net supported...
        throw new MethodNotAllowedException($request->getMethod(), $supportedMethods);
    }

    private function executeCallback(Request $request, Route $route, array $availableRouteCallbackParameters)
    {
        if (null !== $this->pluginRegistry) {
            $pluginResponse = $this->pluginRegistry->run($request, $route);
            if ($pluginResponse instanceof Response) {
                // received Response from plugin, return this immediately
                return $pluginResponse;
            }

            $availableRouteCallbackParameters = array_merge($availableRouteCallbackParameters, $pluginResponse);
        }
        $availableRouteCallbackParameters[get_class($request)] = $request;
        $response = $route->executeCallback($availableRouteCallbackParameters);
        if (!($response instanceof Response)) {
            // if the response is a string, we assume it needs to be sent back
            // to the client as text/html
            if (!is_string($response)) {
                throw new RuntimeException('callback return value must be Response object or string');
            }
            $htmlResponse = new Response();
            $htmlResponse->setBody($response);

            return $htmlResponse;
        }

        return $response;
    }

    public static function handleErrors($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}
