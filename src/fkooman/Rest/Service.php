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
        $this->match("PUT", $requestPattern, $callback, $matchOptions);
    }

    public function post($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match("POST", $requestPattern, $callback, $matchOptions);
    }

    public function delete($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match("DELETE", $requestPattern, $callback, $matchOptions);
    }

    public function options($requestPattern, $callback, array $matchOptions = array())
    {
        $this->match("OPTIONS", $requestPattern, $callback, $matchOptions);
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
    public function match($requestMethod, $requestPattern, $callback, array $matchOptions = array())
    {
        if (!is_array($requestMethod)) {
            $requestMethod = array($requestMethod);
        }

        $this->match[] = array(
            "requestMethod" => $requestMethod,
            "requestPattern" => $requestPattern,
            "callback" => $callback,
            "matchOptions" => $matchOptions,
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

        // if there is a query parameter _index take the value, urlencode it and
        // add it to the end of the path info. This is to support e.g. URLs as
        // part of the path info. PHP or Apache decodes the PATH INFO, it is not
        // possible to disable this, so we lose the URL...
        if (null !== $request->getUrl()->getQueryParameter('_index')) {
            $request->setPathInfo($request->getUrl()->getPathInfo().urlencode($request->getQueryParameter('_index')));
        }

        $paramsAvailableForCallback = array();
        // make Request always available
        $paramsAvailableForCallback[get_class($request)] = $request;
        $paramsAvailableForCallback['matchAll'] = $request->getUrl()->getPathInfo();

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
                $m['requestMethod'],
                $m['requestPattern'],
                $m['callback'],
                $paramsAvailableForCallback,
                $m['matchOptions']
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
                $responseObj->setContent($response);

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

    private function matchRest(Request $request, array $requestMethod, $requestPattern, $callback, array $paramsAvailableForCallback, array $matchOptions)
    {
        if (!in_array($request->getMethod(), $requestMethod)) {
            return false;
        }

        $matcherParams = PatternMatcher::isMatch($request->getUrl()->getPathInfo(), $requestPattern);
        if (false === $matcherParams) {
            return false;
        }
        $paramsAvailableForCallback = array_merge($paramsAvailableForCallback, $matcherParams);

        return $this->executeCallback($request, $callback, $paramsAvailableForCallback, $matchOptions);
    }

    private function executeCallback(Request $request, $callback, array $paramsAvailableForCallback, array $matchOptions)
    {
        if (!array_key_exists('disableReferrerCheck', $matchOptions) || !$matchOptions['disableReferrerCheck']) {
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
                // check if it is enabled for this route
                if (!array_key_exists('enablePlugins', $matchOptions)) {
                    continue;
                }
                if (!is_array($matchOptions['enablePlugins'])) {
                    continue;
                }
                if (!in_array(get_class($plugin), $matchOptions['enablePlugins'])) {
                    continue;
                }
            }

            if (array_key_exists('skipPlugins', $matchOptions)) {
                if (is_array($matchOptions['skipPlugins'])) {
                    if (in_array(get_class($plugin), $matchOptions['skipPlugins'])) {
                        continue;
                    }
                }
            }

            // if config is available in matchOptions for this plugin, provide
            // it
            $routeConfig = array();
            if (array_key_exists(get_class($plugin), $matchOptions)) {
                if (is_array($matchOptions[get_class($plugin)])) {
                    $routeConfig = $matchOptions[get_class($plugin)];
                }
            }
            $response = $plugin->execute($request, $routeConfig);

            if ($response instanceof Response) {
                return $response;
            } elseif (is_object($response)) {
                $paramsAvailableForCallback[get_class($response)] = $response;
            } else {
                // not an object, ignore the return value...
            }
        }

        // determine the parameters in the callback and match them with the
        // available parameters
        $cbParams = array();
        if (null !== $callback) {
            $reflectionFunction = new ReflectionFunction($callback);
            foreach ($reflectionFunction->getParameters() as $p) {
                if (null !== $p->getClass()) {
                    // object
                    if (!array_key_exists($p->getClass()->getName(), $paramsAvailableForCallback)) {
                        if (!$p->isDefaultValueAvailable()) {
                            throw new BadFunctionCallException("parameter expected by callback not available");
                        } else {
                            // add default value to cbParams
                            $cbParams[] = $p->getDefaultValue();
                        }
                    } else {
                        $cbParams[] = $paramsAvailableForCallback[$p->getClass()->getName()];
                    }
                } else {
                    // internal type
                    if (!array_key_exists($p->getName(), $paramsAvailableForCallback)) {
                        if (!$p->isDefaultValueAvailable()) {
                            throw new BadFunctionCallException("parameter expected by callback not available");
                        } else {
                            // add default value to cbParams
                            $cbParams[] = $p->getDefaultValue();
                        }
                    } else {
                        $cbParams[] = $paramsAvailableForCallback[$p->getName()];
                    }
                }
                // FIXME: are there other types we should consider?
            }
        }

        return call_user_func_array($callback, array_values($cbParams));
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
