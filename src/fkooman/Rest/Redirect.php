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

namespace fkooman\Rest;

class Redirect
{
    /** @var string */
    private $redirectUri;

    /** @var string */
    private $requestMethod;

    public function __construct($redirectUri, $requestMethod = "GET")
    {
        $this->redirectUri = $redirectUri;
        $this->requestMethod = $requestMethod;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function getRequestMethod()
    {
        return $this->requestMethod;
    }
}
