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

namespace fkooman\Http;

class RedirectResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testRedirect301Response()
    {
        $h = new RedirectResponse('http://www.example.org/redirect');
        $this->assertSame(
            array(
                'HTTP/1.1 301 Moved Permanently',
                'Content-Type: text/html;charset=UTF-8',
                'Location: http://www.example.org/redirect',
                '',
                '',
            ),
            $h->toArray()
        );

#        $this->assertSame(301, $h->getStatusCode());
#        $this->assertSame('http://www.example.org/redirect', $h->getHeader('Location'));
    }

    public function testRedirect302Response()
    {
        $h = new RedirectResponse('http://www.example.org/redirect302', 302);
        $this->assertSame(
            array(
                'HTTP/1.1 302 Found',
                'Content-Type: text/html;charset=UTF-8',
                'Location: http://www.example.org/redirect302',
                '',
                '',
            ),
            $h->toArray()
        );
#        $this->assertSame(302, $h->getStatusCode());
#        $this->assertSame('http://www.example.org/redirect302', $h->getHeader('Location'));
    }
}
