<?php

namespace fkooman\Rest;

use PHPUnit_Framework_TestCase;
use StdClass;

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

    public function testExecuteCallback()
    {
        $m = new Match(
            array('GET'),
            '/foo/:id',
            function (
                StdClass $s,    // object
                $id,            // native type
                array $x,       // array,
                $foo = 5        // default value
            ) {
                return 'foo';
            }
        );
        $s = new StdClass();
        $this->assertEquals(
            'foo',
            $m->executeCallback(
                array(
                    'stdClass' => $s,
                    'x' => array(),
                    'id' => 5
                )
            )
        );
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage parameter "foo" expected by callback not available
     */
    public function testMissingParameterForCallback()
    {
        $m = new Match(
            array('GET'),
            '/foo',
            function ($foo) {
                return 'foo';
            }
        );
        $m->executeCallback(array());
    }
}
