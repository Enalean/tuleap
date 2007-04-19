<?php
require_once('SimpleSanitizerTestCase.class.php');
require_once('common/include/SimpleSanitizer.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SimpleSanitizerTest.php 4433 2006-12-07 09:43:33 +0000 (Thu, 07 Dec 2006) ahardyau $
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