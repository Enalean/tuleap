<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('LinkedListTestCase.class');
require_once('common/collection/PrioritizedList.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PrioritizedListTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
 *
 * Tests the class PrioritizedList
 */
class PrioritizedListTest extends LinkedListTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PrioritizedListTest($name = 'PrioritizedList test') {
        $this->LinkedListTestCase($name, 'PrioritizedList');
    }
    
    function testSimplePriority() {
        $a1 = 'a1';
        $a2 = 'a2';
        $b  = 'b';
        $c  = 'c';
        $l  =& new PrioritizedList();
        $l->add($a2, 10);
        $l->add($a1, 10);
        $l->add($c, 30);
        $l->add($b, 20);
        $it =& $l->iterator();
        $element =& $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $b);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $a2);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $a1);
    }
    
    function testComplexePriority() {
        $a1 = 'a1';
        $a2 = 'a2';
        $a3 = 'a3';
        $b1 = 'b1';
        $b2 = 'b2';
        $b3 = 'b3';
        $c1 = 'c1';
        $c2 = 'c2';
        $c3 = 'c3';
        $l  =& new PrioritizedList();
        $l->add($a1, 10);
        $l->add($a2, 10);
        $l->add($a3, 10);
        $l->add($c2, 30);
        $l->add($c3, 30);
        $l->add($c1, 30);
        $l->add($b3, 20);
        $l->add($b2, 20);
        $l->add($b1, 20);
        $it =& $l->iterator();
        $element =& $it->current();
        $this->assertReference($element, $c2);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $c3);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $c1);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $b3);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $b2);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $b1);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $a1);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $a2);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $a3);
    }
    
    function testNegativeAndDefaultPriority() {
        $a = 'a';
        $b = 'b';
        $c = 'c';
        $d = 'd';
        $l  =& new PrioritizedList();
        $l->add($a, 10);
        $l->add($b, -10);
        $l->add($d);
        $l->add($c);
        $it =& $l->iterator();
        $element =& $it->current();
        $this->assertReference($element, $a);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $d);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $c);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $b);
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PrioritizedListTest();
    $test->run(new CodexReporter());
 }
?>
