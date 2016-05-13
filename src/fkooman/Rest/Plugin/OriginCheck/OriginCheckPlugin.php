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

namespace fkooman\Rest\Plugin\OriginCheck;

use fkooman\Rest\ServicePluginInterface;
use fkooman\Http\Request;
use fkooman\Http\Exception\BadRequestException;

/**
 * Plugin that check the Origin request header on "sensitive" requests that
 * have side effects on the server. This is a protection against Cross Site 
 * Request Forgery (CSRF).
 */
class OriginCheckPlugin implements ServicePluginInterface
{
    public function execute(Request $request, array $routeConfig)
    {
        // only relevant if the request comes from a browser, very lame 
        // browser detection, but it works
        if (false === strpos($request->getHeader('Accept'), 'text/html')) {
            return;
        }

        // these methods do not require CSRF protection as they are not
        // supposed to have side effects on the server
        $safeMethods = array('GET', 'HEAD', 'OPTIONS');

        if (!in_array($request->getMethod(), $safeMethods)) {
            $originValue = $request->getHeader('Origin');

            if (null === $originValue) {
                throw new BadRequestException('HTTP_ORIGIN header missing');
            }
            if ($originValue !== $request->getUrl()->getAuthority()) {
                throw new BadRequestException('HTTP_ORIGIN has unexpected value');
            }
        }
    }
}
