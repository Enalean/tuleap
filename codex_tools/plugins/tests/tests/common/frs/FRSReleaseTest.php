<?php
//{{{ Ugly fix to by pass error with ugroup_utils
//TODO: Fix it !
require_once('BaseLanguage.class.php');
$name = 'Fake_BaseLanguage_'. md5(uniqid(rand(), true));
eval("class $name extends BaseLanguage {}");
$GLOBALS['Language'] = new $name();
//}}}

require_once('common/frs/FRSRelease.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
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
