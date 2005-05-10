<?php
require_once('SimpleSanitizerTestCase.class');
require_once('common/include/SimpleSanitizer.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Test the class SimpleSanitizer
 */
class SimpleSanitizerTest extends SimpleSanitizerTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SimpleSanitizerTest() {
        $this->SimpleSanitizerTestCase('SimpleSanitizer test');
    }

    /**
     * initialize variables for tests
     */
    function setUp() {
        $this->sanitizer =& new SimpleSanitizer();
    }	

}

//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', true);
    require_once('tests/CodexReporter.class');	
        	
    $test = &new SimpleSanitizerTest();
    $test->run(new CodexReporter());
 }
?>