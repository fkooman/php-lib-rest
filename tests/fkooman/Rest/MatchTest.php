<?php

namespace fkooman\Rest;

use PHPUnit_Framework_TestCase;
use fkooman\Http\Request;

class MatchTest extends PHPUnit_Framework_TestCase
{
    public function testMatchHasMethod()
    {
        $m = new Match(array('GET'), '/foo/bar', function () {});
        $this->assertTrue($m->hasMethod('GET'));
        $this->assertFalse($m->hasMethod('POST'));
    }

    public function testIsMatch()
    {
        $m = new Match(array('GET'), '/foo/bar', function () {});
        $this->assertEquals(array(), $m->isMatch('GET', '/foo/bar'));
        $this->assertFalse($m->isMatch('POST', '/foo/bar'));
        $this->assertFalse($m->isMatch('GET', '/foo/baz'));
    }
}
