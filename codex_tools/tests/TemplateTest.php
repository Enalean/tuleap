<?php
require_once('tests/simpletest/unit_tester.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Test the class ...
 */
class TemplateTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function TemplateTest() {
        $this->UnitTestCase('Template test');
    }

    /**
     * A test
     */
    function testSomething() {
        $this->pass();
        //$this->fail();
    }	
}

//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', true);
    require_once('tests/CodexReporter.class');	
        	
    $test = &new TemplateTest();
    $test->run(new CodexReporter());
 }
?>
