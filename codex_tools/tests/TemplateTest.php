<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Tests the class Template
 */
class TemplateTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function TemplateTest($name = 'Template test') {
        $this->UnitTestCase($name);
    }

    /**
     * A test
     */
    function testSomething() {
        $this->pass();
        //$this->fail();
    }	
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new TemplateTest();
    $test->run(new CodexReporter());
 }
?>
