<?php
require_once('SimpleSanitizerTestCase.class.php');
require_once('common/include/SimpleSanitizer.class.php');

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
?>