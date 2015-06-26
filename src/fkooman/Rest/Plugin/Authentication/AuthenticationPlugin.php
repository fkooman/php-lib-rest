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

namespace fkooman\Rest\Plugin\Authentication;

use fkooman\Rest\ServicePluginInterface;
use fkooman\Http\Request;
use fkooman\Http\Exception\UnauthorizedException;
use fkooman\Rest\Service;

/**
 * Authentication Plugin to implement supporting multiple authentication
 * mechanisms. For example allow both Basic and Bearer authentication. The
 * authentication mechanisms will be tried one by one in the order they were
 * registered. At least one needs to be valid.
 */
class AuthenticationPlugin implements ServicePluginInterface
{
    /** @var array */
    private $plugins;

    public function __construct()
    {
        $this->plugins = array();
    }

    public function init(Service $service)
    {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'init')) {
                $plugin->init($service);
            }
        }
    }

    public function registerAuthenticationPlugin(AuthenticationPluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }

    public function execute(Request $request, array $routeConfig)
    {
        if (0 === count($this->plugins)) {
            // no authentication plugins registered
            return;
        }

        foreach ($this->plugins as $plugin) {
            // first check to see if it is an attempt based on Request
            if ($plugin->isAttempt($request)) {
                // it is an attempt, so it MUST succeed
                return $plugin->execute($request, array());
            }
        }

        if (array_key_exists('requireAuth', $routeConfig)) {
            if (!$routeConfig['requireAuth']) {
                return;
            }
        }

        $e = new UnauthorizedException(
            'no_credentials',
            'credentials must be provided'
        );

        foreach ($this->plugins as $plugin) {
            $e->addScheme($plugin->getScheme(), $plugin->getAuthParams());
        }

        throw $e;
    }
}
