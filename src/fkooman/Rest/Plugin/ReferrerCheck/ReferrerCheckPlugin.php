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
namespace fkooman\Rest\Plugin\ReferrerCheck;

use fkooman\Rest\ServicePluginInterface;
use fkooman\Http\Request;
use fkooman\Http\Exception\BadRequestException;

/**
 * Plugin that check the referrer request header on "sensitive" requests that
 * have side effects on the server. This is rudimentary protection against
 * Cross Site Request Forgery (CSRF).
 */
class ReferrerCheckPlugin implements ServicePluginInterface
{
    public function execute(Request $request, array $matchPluginConfig)
    {
        // these methods do not require CSRF protection as they are not
        // supposed to have side effects on the server
        $safeMethods = array('GET', 'HEAD', 'OPTIONS');

        if (!in_array($request->getMethod(), $safeMethods)) {
            $referrer = $request->getHeader('HTTP_REFERER');
            $rootFolderUrl = $request->getUrl()->getRootFolderUrl();

            if (null === $referrer) {
                throw new BadRequestException('HTTP_REFERER header missing');
            }
            if (0 !== strpos($referrer, $rootFolderUrl)) {
                throw new BadRequestException('HTTP_REFERER has unexpected value');
            }
        }
    }
}
