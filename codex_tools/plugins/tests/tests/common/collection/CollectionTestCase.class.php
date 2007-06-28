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
 */
class CollectionTestCase extends UnitTestCase {
    
    var $collection_class_name;
    
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function CollectionTestCase($name = 'Collection test', $collection_class_name = 'you_must_define_classname') {
        $this->UnitTestCase($name);
        $this->collection_class_name = $collection_class_name;
    }

    function testEmptyCollection() {
        $c =& new $this->collection_class_name();
        $this->assertTrue($c->isEmpty());
    }
    function testNonEmptyCollection() {
        $c =& new $this->collection_class_name();
        $a = 'a';
        $c->add($a);
        $this->assertFalse($c->isEmpty());
    }
    function testContains() {
        $col =& new $this->collection_class_name();
        $a = 'a';
        $b = 'b';
        $c = 'c';
        $d = 'd';
        $col->add($a);
        $col->add($b);
        $col->add($c);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
        $this->assertTrue($col->contains($c));
        $this->assertFalse($col->contains($d));
        
        $key1 =& new String('key');
        $key2 =& new String('key');
        $col =& new $this->collection_class_name();
        $this->assertFalse($col->contains($key2));
        $col->add($key1);
        $this->assertTrue($key1->equals($key2)); //Just to remember see StringTest
        $this->assertTrue($col->contains($key2));
        $GLOBALS['debug'] = true;
        $key3_val = 'key';
        $key3 =& new String($key3_val);
        $col =& new $this->collection_class_name();
        $col->add($key3);
        $this->assertTrue($col->contains($key3_val));
        unset($GLOBALS['debug']);
    }
    function testReference() {
        $col =& new $this->collection_class_name();
        $elem1 =& new FakeValue();
        $elem2 =& new FakeValue();
        $col->add($elem1);
        $this->assertTrue($col->contains($elem1));
        $this->assertFalse($col->contains($elem2));
    }
    function testSize() {
        $a = 'a';
        $b = 'b';
        $col =& new $this->collection_class_name();
        $this->assertEqual($col->size(), 0);
        $col->add($a);
        $this->assertEqual($col->size(), 1);
        $col->add($a);
        $this->assertEqual($col->size(), 2);
        $col->add($b);
        $this->assertEqual($col->size(), 3);
    }
    
    function testNotEqualsNotCollection() {
        $a = 'a';
        $col1 =& new $this->collection_class_name();
        $this->assertFalse($col1->equals($a));
    }
    
    function testEqualsNoElements() {
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $this->assertTrue($col1->equals($col2));
    }
    
    function testNotEqualsOneElement() {
        $a = 'a';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsOneElement() {
        $a = 'a';
        $b = 'b';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testNotEqualsTwoElements() {
        $a = 'a';
        $b = 'b';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $this->assertFalse($col1->equals($col2));
    }
    function testEqualsTwoElements() {
        $a = 'a';
        $b = 'b';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($b);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testEqualsDifferentOrder() {
        $a = 'a';
        $b = 'b';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col1->add($b);
        $col2->add($b);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testEqualsDifferentSizes() {
        $a = 'a';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsSameAndDifferentElements() {
        $a = 'a';
        $b = 'b';
        $c = 'c';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($c);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsUniqueAndNonUniqueElements() {
        $a = 'a';
        $b = 'b';
        $col1 =& new $this->collection_class_name();
        $col2 =& new $this->collection_class_name();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $col2->add($b);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testInitialArray() {
        $a = 'a';
        $b = 'b';
        $arr = array();
        $arr[] =& $a;
        $arr[] =& $b;
        $col =& new $this->collection_class_name($arr);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
    }
    
    function testRemove() {
        $a = 'a';
        $col =& new $this->collection_class_name();
        $col->add($a);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->remove($a));
        $this->assertFalse($col->contains($a));
        $col->remove($a);
        $this->assertFalse($col->remove($a));
    }
}
//We just tells SimpleTest to always ignore this testcase
SimpleTestOptions::ignore('CollectionTestCase');

?>
