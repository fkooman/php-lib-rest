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

use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\RedirectResponse;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\MethodNotAllowedException;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\NotFoundException;
use InvalidArgumentException;
use RuntimeException;
use BadFunctionCallException;
use ErrorException;
use Exception;

class Service
{
    /** @var array */
    private $routes;

    /** @var array */
    private $supportedMethods;

    /** @var array */
    private $onMatchPlugins;

    /** @var array */
    private $defaultDisablePlugins;

    public function __construct()
    {
        $this->routes = array();
        $this->supportedMethods = array();

        $this->defaultPlugins = array();
        $this->optionalPlugins = array();

        // enable ErrorException
        set_error_handler(
            function ($severity, $message, $file, $line) {
                if (!(error_reporting() & $severity)) {
                    // This error code is not included in error_reporting
                    return;
                }
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );

#        // register global Exception handler
#        set_exception_handler('fkooman\Rest\Service::handleException');
    }

    public function registerDefaultPlugin(ServicePluginInterface $servicePlugin)
    {
        // execute init function if it exists
        if (method_exists($servicePlugin, 'init')) {
            $servicePlugin->init($this);
        }
        $this->defaultPlugins[] = $servicePlugin;
    }

    public function registerOptionalPlugin(ServicePluginInterface $servicePlugin)
    {
        // execute init function if it exists
        if (method_exists($servicePlugin, 'init')) {
            $servicePlugin->init($this);
        }
        $this->optionalPlugins[] = $servicePlugin;
    }

    public function get($requestPattern, $callback, array $matchOptions = array())
    {
        $this->addRoute(array('GET', 'HEAD'), $requestPattern, $callback, $matchOptions);
    }

    public function put($requestPattern, $callback, array $matchOptions = array())
    {
        $this->addRoute(array("PUT"), $requestPattern, $callback, $matchOptions);
    }

    public function post($requestPattern, $callback, array $matchOptions = array())
    {
        $this->addRoute(array("POST"), $requestPattern, $callback, $matchOptions);
    }

    public function delete($requestPattern, $callback, array $matchOptions = array())
    {
        $this->addRoute(array("DELETE"), $requestPattern, $callback, $matchOptions);
    }

    public function options($requestPattern, $callback, array $matchOptions = array())
    {
        $this->addRoute(array("OPTIONS"), $requestPattern, $callback, $matchOptions);
    }

    /**
     * Register a method/pattern match.
     *
     * @param string   $requestMethod  the request method, e.g. 'GET', 'POST'
     * @param string   $requestPattern the pattern to match
     * @param callback $callback       the callback to execute when this pattern
     *                                 matches
     * @param array    $matchOptions   the options for this match
     *
     */
    public function addRoute(array $requestMethod, $requestPattern, $callback, array $matchOptions = array())
    {
        $this->routes[] = new Match($requestMethod, $requestPattern, $callback, $matchOptions);

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
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = new Request($_SERVER);
        }
        
        // support PUT and DELETE method override when _METHOD is set in a form
        // POST
        if ("POST" === $request->getMethod()) {
            if ("PUT" === $request->getPostParameter("_METHOD")) {
                $request->setMethod("PUT");
            }
            if ("DELETE" === $request->getPostParameter("_METHOD")) {
                $request->setMethod("DELETE");
            }
        }

        foreach ($this->routes as $m) {
            $response = $this->matchRoute(
                $request,
                $m
            );

            // false indicates not a match
            if (false !== $response) {
                if ($response instanceof Response) {
                    return $response;
                }
                if (!is_string($response)) {
                    throw new RuntimeException("unsupported callback return value");
                }
                $responseObj = new Response();
                $responseObj->setBody($response);

                return $responseObj;
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

    private function matchRoute(Request $request, Match $match)
    {
        if (false === $matcherParameters = $match->isMatch($request->getMethod(), $request->getUrl()->getPathInfo())) {
            return false;
        }

        // add fkooman\Http\Request
        $matcherParameters[get_class($request)] = $request;

        return $this->executeCallback($request, $match, $matcherParameters);
    }

    private function executeCallback(Request $request, Match $m, array $matcherParameters)
    {
        // FIXME: cleanup the calling of the actual plugin, huge code duplication!

        // run the plugins if not disabled
        foreach ($this->defaultPlugins as $p) {
            $pluginName = substr(get_class($p), strrpos(get_class($p), '\\') + 1);
            $routeConfig = $m->getConfig($pluginName);
            if (array_key_exists('enabled', $routeConfig) && false === $routeConfig['enabled']) {
                continue;    // disabled
            } else {
                $response = $p->execute($request, $routeConfig);
                if ($response instanceof Response) {
                    // received Response from plugin, return this immediately
                    return $response;
                } elseif (is_object($response)) {
                    $matcherParameters[get_class($response)] = $response;
                }
            }
        }
        // run the optional plugins if enabled
        foreach ($this->optionalPlugins as $p) {
            $pluginName = substr(get_class($p), strrpos(get_class($p), '\\') + 1);
            $routeConfig = $m->getConfig($pluginName);
            if (array_key_exists('enabled', $routeConfig) && true === $routeConfig['enabled']) {
                $response = $p->execute($request, $routeConfig);
                if ($response instanceof Response) {
                    // received Response from plugin, return this immediately
                    return $response;
                } elseif (is_object($response)) {
                    $matcherParameters[get_class($response)] = $response;
                }
            } else {
                continue;
            }
        }

        return $m->executeCallback($matcherParameters);
    }

    public static function handleException(Exception $e, $onlyLogServerErrors = true)
    {
        $request = new Request($_SERVER);

        if (!($e instanceof HttpException)) {
            $e = new InternalServerErrorException($e->getMessage());
        }

        if (!$onlyLogServerErrors || $onlyLogServerErrors && 500 === $e->getCode()) {
            error_log(
                sprintf(
                    'ERROR: "%s", DESCRIPTION: "%s", FILE: "%s", LINE: "%d"',
                    $e->getMessage(),
                    $e->getDescription(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
        }

        if (false !== strpos($request->getHeader('Accept'), 'text/html')) {
            return $e->getHtmlResponse();
        }
        if (false !== strpos($request->getHeader('Accept'), 'application/x-www-form-urlencoded')) {
            return $e->getFormResponse();
        }

        // by default we return JSON
        return $e->getJsonResponse();
    }
}
