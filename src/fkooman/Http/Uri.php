<?php

/**
* Copyright 2013 François Kooman <fkooman@tuxed.net>
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

class Uri
{
    private $_uriParts;

    public function __construct($inputUri)
    {
        $this->_validateUri($inputUri);
        $this->_setUriParts($inputUri);
    }

    private function _validateUri($uri)
    {
        $u = filter_var($uri, FILTER_VALIDATE_URL);
        if ($u === FALSE) {
            throw new UriException("the uri is malformed");
        }
    }

    private function _setUriParts($uri)
    {
        $this->_uriParts = parse_url($uri);
    }

    private function _constructUriFromParts()
    {
        $uri = "";
        if (NULL !== $this->getScheme()) {
            $uri .= $this->getScheme() . "://";
        }
        if (NULL !== $this->getUser()) {
            $uri .= $this->getUser();
            if (NULL !== $this->getPass()) {
                $uri .= ":" . $this->getPass();
            }
            $uri .= "@";
        }
        if (NULL !== $this->getHost()) {
            $uri .= $this->getHost();
        }
        if (NULL !== $this->getPort()) {
            $uri .= ":" . $this->getPort();
        }
        if (NULL !== $this->getPath()) {
            $uri .= $this->getPath();
        }
        if (NULL !== $this->getQuery()) {
            $uri .= "?" . $this->getQuery();
        }
        if (NULL !== $this->getFragment()) {
            $uri .= "#" . $this->getFragment();
        }

        return $uri;
    }

    public function getScheme()
    {
        return array_key_exists("scheme", $this->_uriParts) ? $this->_uriParts['scheme'] : NULL;
    }

    public function getUser()
    {
        return array_key_exists("user", $this->_uriParts) ? $this->_uriParts['user'] : NULL;
    }

    public function getPass()
    {
        return array_key_exists("pass", $this->_uriParts) ? $this->_uriParts['pass'] : NULL;
    }

    public function getHost()
    {
        return array_key_exists("host", $this->_uriParts) ? $this->_uriParts['host'] : NULL;
    }

    public function getPort()
    {
        return array_key_exists("port", $this->_uriParts) ? $this->_uriParts['port'] : NULL;
    }

    public function getPath()
    {
        return array_key_exists("path", $this->_uriParts) ? $this->_uriParts['path'] : NULL;
    }

    public function getQuery()
    {
        return array_key_exists("query", $this->_uriParts) ? $this->_uriParts['query'] : NULL;
    }

    public function setQuery($query)
    {
        $this->_uriParts['query'] = $query;
    }

    public function appendQuery($query)
    {
        if ($this->getQuery() === NULL) {
            $this->setQuery($query);
        } else {
            $this->setQuery($this->getQuery() . "&" . $query);
        }
    }

    public function getFragment()
    {
        return array_key_exists("fragment", $this->_uriParts) ? $this->_uriParts['fragment'] : NULL;
    }

    public function setFragment($fragment)
    {
        $this->_uriParts['fragment'] = $fragment;
    }

    public function getUri()
    {
        $uri = $this->_constructUriFromParts();
        $this->_validateUri($uri);

        return $uri;
    }

}
