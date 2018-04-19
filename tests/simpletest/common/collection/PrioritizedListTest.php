<?php
require_once('LinkedListTestCase.class.php');
require_once('common/collection/PrioritizedList.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class PrioritizedList
 */
class PrioritizedListTest extends LinkedListTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function __construct($name = 'PrioritizedList test') {
        parent::__construct($name, 'PrioritizedList');
    }
    
    function testSimplePriority() {
        $a1 = new StdClass();
        $a2 = new StdClass();
        $b  = new StdClass();
        $c  = new StdClass();
        $l  = new PrioritizedList();
        $l->add($a2, 10);
        $l->add($a1, 10);
        $l->add($c, 30);
        $l->add($b, 20);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a2);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a1);
    }
    
    function testComplexePriority() {
        $a1 = new StdClass();
        $a2 = new StdClass();
        $a3 = new StdClass();
        $b1 = new StdClass();
        $b2 = new StdClass();
        $b3 = new StdClass();
        $c1 = new StdClass();
        $c2 = new StdClass();
        $c3 = new StdClass();
        $l  = new PrioritizedList();
        $l->add($a1, 10);
        $l->add($a2, 10);
        $l->add($a3, 10);
        $l->add($c2, 30);
        $l->add($c3, 30);
        $l->add($c1, 30);
        $l->add($b3, 20);
        $l->add($b2, 20);
        $l->add($b1, 20);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertReference($element, $c2);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $c3);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $c1);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b3);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b2);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b1);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a1);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a2);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $a3);
    }
    
    function testNegativeAndDefaultPriority() {
        $a = new StdClass();
        $b = new StdClass();
        $c = new StdClass();
        $d = new StdClass();
        $l  = new PrioritizedList();
        $l->add($a, 10);
        $l->add($b, -10);
        $l->add($d);
        $l->add($c);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertReference($element, $a);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $d);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b);
    }
    
    function testNegativeAndDefaultPriority2() {
        $a = new StdClass(); //'#1 (10)';
        $b = new StdClass(); //'#2 (-5)';
        $c = new StdClass(); //'#3 (-5)';
        $d = new StdClass(); //'#4 (-100)';
        $e = new StdClass(); //'#5 (-100)';
        $f = new StdClass(); //'#6 (-500)';
        $l = new PrioritizedList();
        $l->add($d, -100);
        $l->add($b, -5);
        $l->add($a, 10);
        $l->add($e, -100);
        $l->add($f, -500);
        $l->add($c, -5);
        $it = $l->iterator();
        $element = $it->current();
        $this->assertReference($element, $a);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $b);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $d);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $e);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $f);
    }
}
?>
