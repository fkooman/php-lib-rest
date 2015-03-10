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

namespace fkooman\Http;

class Session
{
    /** @var string */
    private $ns;

    public function __construct($ns = "foo", $secureCookie = true)
    {
        $this->ns = $ns;

        if ("" === session_id()) {
            // no session currently exists, start a new one
            if ($secureCookie) {
                session_set_cookie_params(0, "/", "", true, true);
            }
            session_start();
        }
    }

    public function setValue($key, $value)
    {
        $_SESSION[$this->ns][$key] = $value;
    }

    public function deleteKey($key)
    {
        if ($this->hasKey($key)) {
            unset($_SESSION[$this->ns][$key]);
        }
    }

    public function hasKey($key)
    {
        // check ns exists
        if (array_key_exists($this->ns, $_SESSION)) {
            // check key in ns exists
            return array_key_exists($key, $_SESSION[$this->ns]);
        }

        return false;
    }

    public function getValue($key)
    {
        // check ns exists
        if (array_key_exists($this->ns, $_SESSION)) {
            // check key in ns exists
            if (array_key_exists($key, $_SESSION[$this->ns])) {
                return $_SESSION[$this->ns][$key];
            }
        }

        return null;
    }

    public function destroy()
    {
        if (array_key_exists($this->ns, $_SESSION)) {
            unset($_SESSION[$this->ns]);
        }
    }
}
