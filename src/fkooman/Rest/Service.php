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
use LogicException;
use BadFunctionCallException;
use Exception;
use ErrorException;

class Service
{
    /** @var array */
    private $match;

    /** @var array */
    private $supportedMethods;

    /** @var array */
    private $onMatchPlugins;

    /** @var array */
    private $defaultDisablePlugins;

    /** @var string */
    private $defaultRoute;

    /** @var boolean */
    private $referrerCheck;

    /** @var boolean */
    private $pathInfoRedirect;   // do not redirect to '/' or default route if set to false
    
    public function __construct()
    {
        $this->match = array();
        $this->supportedMethods = array();
        $this->onMatchPlugins = array();
        $this->defaultDisablePlugins = array();
        $this->defaultRoute = null;
        $this->referrerCheck = false;
        $this->pathInfoRedirect = true;

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

    public function setReferrerCheck($referrerCheck)
    {
        if (!is_bool($referrerCheck)) {
            throw new InvalidArgumentException('parameter must be boolean');
        }
        $this->referrerCheck = $referrerCheck;
    }

    public function setPathInfoRedirect($pathInfoRedirect)
    {
        $this->pathInfoRedirect = (bool) $pathInfoRedirect;
    }

    public function registerOnMatchPlugin(ServicePluginInterface $servicePlugin, array $pluginOptions = array())
    {
        // execute init function if it exists
        if (method_exists($servicePlugin, 'init')) {
            $servicePlugin->init($this);
        }
        $this->onMatchPlugins[] = $servicePlugin;
        if (array_key_exists('defaultDisable', $pluginOptions) && $pluginOptions['defaultDisable']) {
            $this->defaultDisablePlugins[] = get_class($servicePlugin);
        }
    }

    public function setDefaultRoute($defaultRoute)
    {
        if (0 !== strpos($defaultRoute, '/')) {
            throw new InvalidArgumentException('default route needs to start with a /');
        }
        $this->defaultRoute = $defaultRoute;
    }

    public function get($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match(array('GET', 'HEAD'), $requestPattern, $callback, $matchOptions);
    }

    public function put($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match(array("PUT"), $requestPattern, $callback, $matchOptions);
    }

    public function post($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match(array("POST"), $requestPattern, $callback, $matchOptions);
    }

    public function delete($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match(array("DELETE"), $requestPattern, $callback, $matchOptions);
    }

    public function options($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match(array("OPTIONS"), $requestPattern, $callback, $matchOptions);
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
    public function match(array $requestMethod, $requestPattern, $callback, array $matchOptions = array())
    {
        $this->match[] = new Match($requestMethod, $requestPattern, $callback, $matchOptions);

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

        // handle the default route
        if ($this->pathInfoRedirect) {
            if (null === $request->getUrl()->getPathInfo()) {
                // redirect to '/', iff request_uri does not end in /
                if ('/' !== substr($request->getRequestUri()->getPath(), -1)) {
                    return new RedirectResponse(
                        $request->getUrl()->getRootUrl(),
                        302
                    );
                }
                $request->setPathInfo('/');
            }

            // handle root
            if ('/' === $request->getUrl()->getPathInfo()) {
                if (null !== $this->defaultRoute && '/' !== $this->defaultRoute) {
                    // redirect to default route
                    return new RedirectResponse(
                        $request->getUrl()->getRootUrl() . substr($this->defaultRoute, 1),
                        302
                    );
                }
            }
        }

        foreach ($this->match as $m) {
            $response = $this->matchRest(
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

    private function matchRest(Request $request, Match $match)
    {
        if (false === $matcherParameters = $match->isMatch($request->getMethod(), $request->getUrl()->getPathInfo())) {
            return false;
        }

        // add fkooman\Http\Request
        $matcherParameters[get_class($request)] = $request;

        return $this->executeCallback($request, $match, $matcherParameters);
    }

    private function executeCallback(Request $request, Match $m, array $p)
    {
        $o = $m->getOptions();
        if (!$m->getDisableReferrerCheck()) {
            if ($this->referrerCheck) {
                if (!in_array($request->getMethod(), array('GET', 'HEAD', 'OPTIONS'))) {
                    // only for request methods with side effects with perform CSRF protection
                    if (0 !== strpos($request->getHeader('HTTP_REFERER'), $request->getUrl()->getRootUrl())) {
                        throw new BadRequestException('CSRF protection triggered');
                    }
                }
            }
        }

        // run the onMatchPlugins
        foreach ($this->onMatchPlugins as $plugin) {
            // is it disabled by default?
            if (in_array(get_class($plugin), $this->defaultDisablePlugins)) {
                if (!$m->getPluginEnabled(get_class($plugin))) {
                    continue;
                }
            }

            // is it maybe skipped?
            // FIXME: move to match
            if (array_key_exists('skipPlugins', $o)) {
                if (is_array($o['skipPlugins'])) {
                    if (in_array(get_class($plugin), $o['skipPlugins'])) {
                        continue;
                    }
                }
            }

            $routeConfig = $m->getRoutePluginConfig(get_class($plugin));
            $response = $plugin->execute($request, $routeConfig);

            if ($response instanceof Response) {
                return $response;
            } elseif (is_object($response)) {
                $p[get_class($response)] = $response;
            } else {
                // not an object, ignore the return value...
            }
        }

        return $m->executeCallback($p);
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
