<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:SanitizerTestCase.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
 *
 * abstract
 */
class SanitizerTestCase extends UnitTestCase {
    function SimpleSanitizerTest($test_name = false) {
        $this->UnitTestCase($test_name);
    }

    function testSanitize() {
        trigger_error("testSanitize() not yet implemented");
    }
}

//We just tells SimpleTest to always ignore this testcase
SimpleTestOptions::ignore('SanitizerTestCase');

?>