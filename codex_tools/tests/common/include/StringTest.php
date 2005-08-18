<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
//require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/include/String.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: StringTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
 *
 * Tests the class String
 */
class StringTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function StringTest($name = 'String test') {
        $this->UnitTestCase($name);
    }

    function testEquals() {
        $s1 =& new String("NTY");
        $s2 =& new String("NTY");
        $s3 =& new String("0");
        $s4 =& new String(4);
        $this->assertTrue($s1->equals($s2));
        $this->assertTrue($s1->equals("NTY"));
        $this->assertTrue($s4->equals("4"));
        $this->assertFalse($s1->equals($s3));
        $this->assertTrue($s3->equals(0));
        $this->assertFalse($s3->equals(false));
        $this->assertFalse($s2->equals($this));
    }
    
    function testHashCode() {
        $s1 =& new String("NTY");
        $s2 =& new String("NTY");
        $this->assertEqual($s1->hashCode(), $s2->hashCode());
    }
    
    function testCompareTo() {
        $s1 =& new String("A");
        $s2 =& new String("B");
        $this->assertEqual($s1->compareTo($s2), -1);
        $this->assertEqual($s2->compareTo($s2), 0);
        $this->assertEqual($s2->compareTo($s1), 1);
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new StringTest();
    $test->run(new CodexReporter());
 }
?>
