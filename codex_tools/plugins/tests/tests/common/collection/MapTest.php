<?php
require_once('common/collection/Map.class.php');
require_once('common/include/String.class.php');
require_once('common/collection/Collection.class.php');
if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: MapTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class Map
 */
class MapTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function MapTest($name = 'Map test') {
        $this->UnitTestCase($name);
    }

    function testEmptyMap() {
        $m =& new Map();
        $this->assertTrue($m->isEmpty());
    }	
    function testNonEmptyMap() {
        $key =& new String('key');
        $m =& new Map();
        $value = 'value';
        $m->put($key, $value);
        $this->assertFalse($m->isEmpty());
    }
    function testOneValue() {
        $key =& new String('key');
        $m =& new Map();
        $value =& new FakeValue();
        $m->put($key, $value);
        $this->assertReference($value, $m->get($key));
    }
    function testNoValue() {
        $key =& new String('key');
        $false_key =& new String('false_key');
        $m =& new Map();
        $value =& new FakeValue();
        $m->put($key, $value);
        $this->assertFalse($m->get($false_key));
    }
    function testTwoValues() {
        $key1 =& new String('key1');
        $key2 =& new String('key2');
        $m =& new Map();
        $value1 =& new FakeValue();
        $m->put($key1, $value1);
        $value2 =& new FakeValue();
        $m->put($key2, $value2);
        $this->assertReference($value1, $m->get($key1));
        $this->assertReference($value2, $m->get($key2));
    }
    function testSize() {
        $key1 =& new String('key1');
        $key2 =& new String('key2');
        $m =& new Map();
        $value1 =& new FakeValue();
        $m->put($key1, $value1);
        $value2 =& new FakeValue();
        $m->put($key2, $value2);
        $this->assertEqual($m->size(), 2);
    }
    function testGetKeys() {
        $key1 =& new String('key1');
        $key2 =& new String('key2');
        $m =& new Map();
        $value1 =& new FakeValue();
        $m->put($key1, $value1);
        $value2 =& new FakeValue();
        $m->put($key2, $value2);
        $keys =& $m->getKeys();
        $expected =& new Collection();
        $expected->add($key1);
        $expected->add($key2);
        $this->assertTrue($keys->equals($expected));
    }
    function testContains() {
        $key            =& new String('key');
        $value          = 'value';
        $does_not_exist =& new String('does not exist');
        $m =& new Map();
        $m->put($key, $value);
        $this->assertTrue($m->containsKey($key));
        $this->assertTrue($m->containsValue($value));
        $this->assertFalse($m->containsKey($does_not_exist));
        $this->assertFalse($m->containsValue($does_not_exist));
    }
    function testEquals() {
        $key            =& new String('key');
        $value          = 'value';
        $m1 =& new Map();
        $m2 =& new Map();
        $this->assertTrue($m1->equals($m2));
        $m1->put($key, $value);
        $this->assertFalse($m1->equals($m2));
        $m2->put($key, $value);
        $this->assertTrue($m1->equals($m2));
        $key2 =& new String('key2');
        $m1->put($key2, $value);
        $m2->put($key2, $value);
        $this->assertTrue($m1->equals($m2));
    }
    function testRemove() {
        $key            =& new String('key');
        $value          = 'value';
        $m =& new Map();
        $m->put($key, $value);
        $this->assertTrue($m->containsKey($key));
        $this->assertTrue($m->containsValue($value));
        $this->assertTrue($m->remove($key, $value));
        $this->assertFalse($m->containsKey($key));
        $this->assertFalse($m->containsValue($value));
        $this->assertFalse($m->remove($key, $value));
        
        $key    =& new String('key');
        $value1 =& new String('value');
        $value2 =& new String('value');
        $m =& new Map();
        $m->put($key, $value1);
        $this->assertTrue($m->remove($key, $value2));
    }
    
    function testRemoveKey() {
        $key            =& new String('key');
        $value          = 'value';
        $m =& new Map();
        $m->put($key, $value);
        $this->assertEqual($m->size(), 1);
        $this->assertTrue($m->removeKey($key));
        $this->assertEqual($m->size(), 0);
    }
}
?>
