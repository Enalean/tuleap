<?php
require_once('common/include/String.class.php');
if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Test the class MultiMap
 */
class MultiMapTestCase extends UnitTestCase {
    
    var $class_name;
    
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function MultiMapTestCase($name = 'MultiMap test', $class_name) {
        $this->UnitTestCase($name);
        $this->class_name = $class_name;
    }

    function testEmptyMultiMap() {
        $m =& new $this->class_name();
        $this->assertTrue($m->isEmpty());
    }	
    function testNonEmptyMultiMap() {
        $m =& new $this->class_name();
        $value = 'value';
        $key   =& new String('key');
        $m->put($key, $value);
        $this->assertFalse($m->isEmpty());
    }
    function testOneValue() {
        $m =& new $this->class_name();
        $value =& new FakeValue();
        $key   =& new String('key');
        $m->put($key, $value);
        $col =& $m->get($key);
        $this->assertTrue($col->contains($value));
    }
    function testNoValue() {
        $m =& new $this->class_name();
        $value =& new FakeValue();
        $key   =& new String('key');
        $m->put($key, $value);
        $false_key = 'false_key';
        $this->assertFalse($m->get($false_key));
    }
    function testTwoValues() {
        $m =& new $this->class_name();
        $value1 =& new FakeValue();
        $key1   =& new String('key1');
        $m->put($key1, $value1);
        $value2 =& new FakeValue();
        $key2   =& new String('key2');
        $m->put($key2, $value2);
        
        $col1 =& $m->get($key1);
        $col2 =& $m->get($key2);
        $this->assertTrue($col1->contains($value1));
        $this->assertTrue($col2->contains($value2));
    }
}
//We just tells SimpleTest to always ignore this testcase
SimpleTestOptions::ignore('MultiMapTestCase');
?>
