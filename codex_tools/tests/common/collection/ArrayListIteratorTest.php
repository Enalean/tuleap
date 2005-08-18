<?php
require_once('tests/simpletest/unit_tester.php');
require_once('common/collection/ArrayListIterator.class');


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: TemplateTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class ArrayListIterator
 */
class ArrayListIteratorTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArrayListIteratorTest($name = 'ArrayListIterator test') {
        $this->UnitTestCase($name);
    }

    /**
     * test an empty array
     */
    function testEmptyArray() {
        $array = array();
        $it = new ArrayListIterator($array);
        $this->assertFalse($it->hasNext());
        $it->next();
        $this->assertError();
    }

    /**
     * test an array with only one element
     */
    function testOneElement() {
        $array = array('a');
        $it = new ArrayListIterator($array);
        $this->assertTrue($it->hasNext());
        $value = $it->next();
        $this->assertNoErrors();
        $this->assertIdentical($value, 'a');
        $this->assertFalse($it->hasNext());
    }

    /**
     * test an array with two elements
     */
    function testTwoElement() {
        $array = array('1', '2');
        $it = new ArrayListIterator($array);
        $this->assertTrue($it->hasNext());
        $value = $it->next();
        $this->assertIdentical($value, '1');
        $this->assertTrue($it->hasNext());
        $value = $it->next();
        $this->assertIdentical($value, '2');
        $this->assertFalse($it->hasNext());
    }

    /**
     * test more than two element
     */
    function testMoreThanTwoElments() {
        $array1 = array('F','E','D','C','B','A','1','2','3','4','5','6','7','8','9','0');
        $array2 = array();
        $it = new ArrayListIterator($array1);
        while($it->hasNext()) {
            $array2[] = $it->next();
        }
        $this->assertIdentical($array1,$array2);
    }
    
    /**
     * test associative array
     */
    function testAssociative() {
        $array1 = array(
                       'fruit' => 'banana',
                       'color' => 'yellow',
                       'weight' => 23
                       );
        $array2 = array();
        $it = new ArrayListIterator($array1);
        while($it->hasNext()) {
            $array2[] = $it->next();
        }
        $this->assertIdentical(array_values($array1),$array2);
    }

    /**
     * test booleans
     */
     function testBooleansElement() {
        $array = array(true, false, true, false);
        $it = new ArrayListIterator($array);
        $this->assertTrue($it->next());
        $this->assertFalse($it->next());
        $this->assertTrue($it->next());
        $this->assertFalse($it->next());
        $this->assertFalse($it->hasNext());
     }
     
     /**
      * test references
      */
     function testReferences() {
         $arr = array();
         $foo1 =& new ArrayListIterator($arr);
         $array = array(&$foo1);
         $it = new ArrayListIterator($array);
         $foo2 =& $it->next();
         $this->assertReference($foo1, $foo2);
     }
}

//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', true);
    require_once('tests/CodexReporter.class');	
        	
    $test = &new ArrayListIteratorTest();
    $test->run(new CodexReporter());
 }
?>
