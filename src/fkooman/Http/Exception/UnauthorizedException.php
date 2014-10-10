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

namespace fkooman\Http\Exception;

class UnauthorizedException extends HttpException
{
    /** @var string */
    private $authType;

    /** @var string */
    private $authRealm;

    public function __construct($message, $authRealm = 'My Realm', $authType = 'Basic', $code = 0, Exception $previous = null)
    {
        $this->authType = $authType;
        $this->authRealm = $authRealm;
        parent::__construct($message, 401, $previous);
    }

    protected function getResponse($getJsonResponse)
    {
        $response = parent::getResponse($getJsonResponse);
        $response->setHeader(
            'WWW-Authenticate',
            sprintf('%s realm="%s"', $this->authType, $this->authRealm)
        );

        return $response;
    }
}
