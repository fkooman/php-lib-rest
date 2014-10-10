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

namespace fkooman\Http;

class Uri
{
    private $uriParts;

    public function __construct($inputUri)
    {
        self::validateUri($inputUri);

        $this->uriParts = parse_url($inputUri);

        if (!array_key_exists("port", $this->uriParts)) {
            if ("http" === $this->uriParts['scheme']) {
                $this->uriParts['port'] = 80;
            } elseif ("https" === $this->uriParts['scheme']) {
                $this->uriParts['port'] = 443;
            } else {
                throw new UriException("unsupported scheme");
            }
        }
    }

    private static function validateUri($inputUri)
    {
        $u = filter_var($inputUri, FILTER_VALIDATE_URL);
        if ($u === false) {
            throw new UriException("the uri is malformed");
        }
    }

    private function constructBaseUriFromParts()
    {
        $uri = "";
        if (null !== $this->getScheme()) {
            $uri .= $this->getScheme()."://";
        }
        if (null !== $this->getUser()) {
            $uri .= $this->getUser();
            if (null !== $this->getPass()) {
                $uri .= ":".$this->getPass();
            }
            $uri .= "@";
        }
        if (null !== $this->getHost()) {
            $uri .= $this->getHost();
        }
        if (null !== $this->getPort()) {
            if ("https" === $this->getScheme() && 443 !== $this->getPort()) {
                $uri .= ":".$this->getPort();
            }
            if ("http" === $this->getScheme() && 80 !== $this->getPort()) {
                $uri .= ":".$this->getPort();
            }
        }

        return $uri;
    }

    private function constructUriFromParts()
    {
        $uri = $this->constructBaseUriFromParts();
        if (null !== $this->getPath()) {
            $uri .= $this->getPath();
        }
        if (null !== $this->getQuery()) {
            $uri .= "?".$this->getQuery();
        }
        if (null !== $this->getFragment()) {
            $uri .= "#".$this->getFragment();
        }

        return $uri;
    }

    public function getScheme()
    {
        return array_key_exists("scheme", $this->uriParts) ? $this->uriParts['scheme'] : null;
    }

    public function getUser()
    {
        return array_key_exists("user", $this->uriParts) ? $this->uriParts['user'] : null;
    }

    public function getPass()
    {
        return array_key_exists("pass", $this->uriParts) ? $this->uriParts['pass'] : null;
    }

    public function getHost()
    {
        return array_key_exists("host", $this->uriParts) ? $this->uriParts['host'] : null;
    }

    public function getPort()
    {
        return array_key_exists("port", $this->uriParts) ? $this->uriParts['port'] : null;
    }

    public function getPath()
    {
        return array_key_exists("path", $this->uriParts) ? $this->uriParts['path'] : null;
    }

    public function getQuery()
    {
        return array_key_exists("query", $this->uriParts) ? $this->uriParts['query'] : null;
    }

    public function setQuery($query)
    {
        $this->uriParts['query'] = $query;
    }

    public function appendQuery($query)
    {
        if ($this->getQuery() === null) {
            $this->setQuery($query);
        } else {
            $this->setQuery($this->getQuery()."&".$query);
        }
    }

    public function getFragment()
    {
        return array_key_exists("fragment", $this->uriParts) ? $this->uriParts['fragment'] : null;
    }

    public function setFragment($fragment)
    {
        $this->uriParts['fragment'] = $fragment;
    }

    public function getBaseUri()
    {
        $baseUri = $this->constructBaseUriFromParts();
        self::validateUri($baseUri);

        return $baseUri;
    }

    public function getUri()
    {
        $uri = $this->constructUriFromParts();
        self::validateUri($uri);

        return $uri;
    }
}
