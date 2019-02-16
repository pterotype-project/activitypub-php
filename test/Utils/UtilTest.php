<?php

namespace ActivityPub\Test\Utils;

use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Utils\Util;

class UtilTest extends APTestCase
{
    public function testItFindsAssocArray()
    {
        $arr = array( 'foo' => 'bar' );
        $isAssoc = Util::isAssoc( $arr );
        $this->assertTrue( $isAssoc );
    }

    public function testItReturnsFalseForNonAssoc()
    {
        $arr = array( 'foo', 'bar' );
        $isAssoc = Util::isAssoc( $arr );
        $this->assertFalse( $isAssoc );
    }

    public function testItHandlesMixedArray()
    {
        $arr = array( 'foo' => 'bar', 'baz' );
        $isAssoc = Util::isAssoc( $arr );
        $this->assertTrue( $isAssoc );
    }

    public function testItChecksEmptyArrayIsAssoc()
    {
        $arr = array();
        $isAssoc = Util::isAssoc( $arr );
        $this->assertFalse( $isAssoc );
    }

    public function testArrayKeysExist()
    {
        $arr = array( 'foo' => 'bar', 'baz' => 'qux' );
        $keys = array( 'foo', 'baz' );
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertTrue( $keysExist );
    }

    public function testItChecksForAllKeys()
    {
        $arr = array( 'foo' => 'bar' );
        $keys = array( 'foo', 'baz' );
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertFalse( $keysExist );
    }

    public function testItAllowsExtraKeys()
    {
        $arr = array( 'foo' => 'bar', 'baz' => 'qux' );
        $keys = array( 'foo' );
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertTrue( $keysExist );
    }

    public function testItHandlesEmptyArray()
    {
        $arr = array();
        $keys = array( 'foo' );
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertFalse( $keysExist );
    }

    public function testItHandlesEmptyKeys()
    {
        $arr = array( 'foo' => 'bar', 'baz' => 'qux' );
        $keys = array();
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertTrue( $keysExist );
    }

    public function testItHandlesBothEmpty()
    {
        $arr = array();
        $keys = array();
        $keysExist = Util::arrayKeysExist( $arr, $keys );
        $this->assertTrue( $keysExist );
    }
}

