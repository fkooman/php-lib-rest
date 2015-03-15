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

use InvalidArgumentException;

class Uri
{
    /** @var array */
    private $components;
   
    public function __construct($inputUri)
    {
        if (false === filter_var($inputUri, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('malformed url');
        }

        $components = array(
            'scheme' => null,
            'host'=> null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
            'fragment' => null
        );

        // normalize the scheme and host
        $components = array_merge($components, parse_url($inputUri));
        if (null !== $components['scheme']) {
            $components['scheme'] = strtolower($components['scheme']);
            $components['host'] = strtolower($components['host']);
        }
    
        // we only accept http and https schema
        if ('http' !== $components['scheme'] && 'https' !== $components['scheme']) {
            throw new InvalidArgumentException('unsupported scheme');
        }

        // if path is missing add a default path of '/'
        if (null === $components['path']) {
            $components['path'] = '/';
        }

        $this->components = $components;
    }

    public function getBaseUri()
    {
        $userPass = '';
        if (null !== $this->getUser()) {
            if (null !== $this->getPass()) {
                $userPass = sprintf('%s:%s@', $this->getUser(), $this->getPass());
            } else {
                $userPass = sprintf('%s@', $this->getUser());
            }
        }
        
        $port = '';
        if (null !== $this->getPort()) {
            if (('http' === $this->getScheme() && 80 !== $this->getPort()) || ('https' === $this->getScheme() && 443 !== $this->getPort())) {
                $port = sprintf(':%s', $this->getPort());
            }
        }

        return $this->getScheme() . '://' . $userPass . $this->getHost() . $port;
    }

    public function getUri()
    {
        $query = '';
        if (null !== $this->getQuery()) {
            $query = sprintf('?%s', $this->getQuery());
        }
        $fragment = '';
        if (null !== $this->getFragment()) {
            $fragment = sprintf('#%s', $this->getFragment());
        }

        return $this->getBaseUri() . $this->getPath() . $query . $fragment;
    }
   
    public function getScheme()
    {
        return $this->components['scheme'];
    }

    public function getUser()
    {
        return $this->components['user'];
    }

    public function getPass()
    {
        return $this->components['pass'];
    }

    public function getHost()
    {
        return $this->components['host'];
    }

    public function getPort()
    {
        return $this->components['port'];
    }

    public function getPath()
    {
        return $this->components['path'];
    }

    public function getQuery()
    {
        return $this->components['query'];
    }

    public function setQuery($query)
    {
        $this->components['query'] = $query;
    }

    public function appendQuery($query)
    {
        if (null === $this->getQuery()) {
            $this->setQuery($query);
        } else {
            $this->setQuery(sprintf('%s&%s', $this->getQuery(), $query));
        }
    }

    public function getFragment()
    {
        return $this->components['fragment'];
    }

    public function setFragment($fragment)
    {
        $this->components['fragment'] = $fragment;
    }

    public static function isValid($inputUri)
    {
        try {
            new Uri($inputUri);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
