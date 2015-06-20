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

use fkooman\Http\Request;
use fkooman\Rest\ServicePluginInterface;

interface AuthenticationPluginInterface extends ServicePluginInterface
{
    /**
     * Check whether the current request is an attempt at authentication using
     * this mechanism.
     *
     * @param Request $request the incoming HTTP request
     *
     * @return bool true if this is an attempt to authenticate using this
     *              method or false if it is not
     */
    public function isAttempt(Request $request);

    /**
     * Gets the authentication scheme.
     *
     * @return string the name of the authentication scheme, e.g. 'Basic',
     *                'Bearer'
     */
    public function getScheme();

    /**
     * Get the authentication parameters thet should be part of the
     * WWW-Authenticate response.
     *
     * @return array the authentication parameters as array, e.g. realm
     */
    public function getAuthParams();
}
