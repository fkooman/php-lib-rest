<?php

namespace fkooman\Rest;

use PHPUnit_Framework_TestCase;

class PatternMatcherTest extends PHPUnit_Framework_TestCase
{
    public function testIsParameterMatch()
    {
        $this->assertEquals(
            array('one' => 'foo', 'two' => 'bar', 'three' => 'baz'),
            PatternMatcher::isMatch('/foo/bar/baz', '/:one/:two/:three')
        );
        $this->assertEquals(
            array('one' => 'foo', 'two' => 'bar', 'three' => 'baz/foobar'),
            PatternMatcher::isMatch('/foo/bar/baz/foobar', '/:one/:two/:three+')
        );
        $this->assertEquals(
            array('one' => 'foo', 'two' => 'bar/baz'),
            PatternMatcher::isMatch('/foo/bar/baz/foobar', '/:one/:two+/foobar')
        );
        $this->assertEquals(
            array('one' => 'foo', 'two' => 'bar/baz', 'three' => 'foobar'),
            PatternMatcher::isMatch('/foo/bar/baz/foobar', '/:one/:two+/:three')
        );
        $this->assertEquals(
            array('user' => 'admin', 'module' => 'money'),
            PatternMatcher::isMatch('/admin/public/money/', '/:user/public/:module(/:path+)/')
        );
        $this->assertEquals(
            array('user' => 'admin', 'module' => 'money', 'path' => 'a/b/c'),
            PatternMatcher::isMatch('/admin/public/money/a/b/c/', '/:user/public/:module(/:path+)/')
        );
    }

    public function testIsMatch()
    {
        $this->assertEquals(array(), PatternMatcher::isMatch('/foo/bar/baz', '/foo/bar/baz'));
        $this->assertEquals(array(), PatternMatcher::isMatch('/foo/bar/baz/foobar', '*'));
    }

    public function testNoMatch()
    {
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/baz/foobar', '/:one/:two/:three'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/', '/:one/:two/:three+'));
        $this->assertFalse(PatternMatcher::isMatch('/foo', '/foo/bar/:foo/bar/bar'));
        $this->assertFalse(PatternMatcher::isMatch('/foo', '/x'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/', '/foo/:bar'));
        $this->assertFalse(PatternMatcher::isMatch('/foo/bar/baz/foobar', '/:abc+/foobaz'));
    }
}
