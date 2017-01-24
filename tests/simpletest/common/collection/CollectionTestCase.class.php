<?php

if (!class_exists("FakeValue")) {
    class FakeValue {}
}

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 */
class CollectionTestCase extends TuleapTestCase {
    
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
        $c = new $this->collection_class_name();
        $this->assertTrue($c->isEmpty());
    }
    function testNonEmptyCollection() {
        $c = new $this->collection_class_name();
        $a = new StdClass();
        $c->add($a);
        $this->assertFalse($c->isEmpty());
    }
    function testContains() {
        $col = new $this->collection_class_name();
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $c = new StdClass();$c->toto = 3;
        $d = new StdClass();$d->toto = 4;
        $col->add($a);
        $col->add($b);
        $col->add($c);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
        $this->assertTrue($col->contains($c));
        $this->assertFalse($col->contains($d));
        
        $key1 = 'key';
        $key2 = 'key';
        $col = new $this->collection_class_name();
        $this->assertFalse($col->contains($key2));
        $col->add($key1);
        $this->assertTrue($col->contains($key2));
        
        $key3_val = 'key';
        $key3 = $key3_val;
        $col = new $this->collection_class_name();
        $col->add($key3);
        $this->assertTrue($col->contains($key3_val));
        unset($GLOBALS['debug']);
    }
    function testReference() {
        $col = new $this->collection_class_name();
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col->add($a);
        $this->assertTrue($col->contains($a));
        $this->assertFalse($col->contains($b));
    }
    function testSize() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col = new $this->collection_class_name();
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
        $col1 = new $this->collection_class_name();
        $this->assertFalse($col1->equals($a));
    }
    
    function testEqualsNoElements() {
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $this->assertTrue($col1->equals($col2));
    }
    
    function testNotEqualsOneElement() {
        $a = new StdClass();
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsOneElement() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testNotEqualsTwoElements() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $this->assertFalse($col1->equals($col2));
    }
    function testEqualsTwoElements() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($b);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testEqualsDifferentOrder() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col1->add($b);
        $col2->add($b);
        $col2->add($a);
        $this->assertTrue($col1->equals($col2));
    }
    
    function testEqualsDifferentSizes() {
        $a = new StdClass();
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsSameAndDifferentElements() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $c = new StdClass();$c->toto = 3;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col2->add($a);
        $col1->add($b);
        $col2->add($c);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testEqualsUniqueAndNonUniqueElements() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $col1 = new $this->collection_class_name();
        $col2 = new $this->collection_class_name();
        $col1->add($a);
        $col1->add($a);
        $col2->add($a);
        $col2->add($b);
        $this->assertFalse($col1->equals($col2));
    }
    
    function testInitialArray() {
        $a = new StdClass();$a->toto = 1;
        $b = new StdClass();$b->toto = 2;
        $arr = array();
        $arr[] = $a;
        $arr[] = $b;
        $col = new $this->collection_class_name($arr);
        $this->assertTrue($col->contains($a));
        $this->assertTrue($col->contains($b));
    }
    
    function testRemove() {
        $a = new StdClass();
        $col = new $this->collection_class_name();
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
