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

    public function __construct($ns = 'MySession', $sessionOptions = array())
    {
        $this->ns = $ns;

        $defaultOptions = array(
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true
        );

        // backwards compatibility for disabling 'secure' cookies, the second
        // parameter used to be a boolean...
        if (is_bool($sessionOptions)) {
            $sessionOptions = array(
                'secure' => $sessionOptions
            );
        }
        if (is_array($sessionOptions)) {
            // merge sessionOptions with defaultOptions
            $sessionOptions = array_merge($defaultOptions, $sessionOptions);
        }
        $this->sessionOptions = $sessionOptions;
    }

    private function startSession()
    {
        if ('' === session_id()) {
            // no session active
            session_set_cookie_params(
                $this->sessionOptions['lifetime'],
                $this->sessionOptions['path'],
                $this->sessionOptions['domain'],
                $this->sessionOptions['secure'],
                $this->sessionOptions['httponly']
            );
            session_start();
        }
    }

    public function setValue($key, $value)
    {
        // start session only when a value is set...
        $this->startSession();
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
