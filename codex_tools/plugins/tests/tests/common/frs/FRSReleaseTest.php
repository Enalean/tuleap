<?php
require_once('common/frs/FRSRelease.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:$
 *
 * Tests the FRSRelease class
 */

class FRSReleaseTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function FRSReleaseTest($name = 'FRSRelease test') {
        $this->UnitTestCase($name);
    }

    function testIsActive() {
        $active_value = 1;
        $hidden_value = 3;
        
        $r =& new FRSRelease();
        $r->setStatusId($active_value);
        $this->assertTrue($r->isActive());
        
        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isActive());
    }

}
?>
