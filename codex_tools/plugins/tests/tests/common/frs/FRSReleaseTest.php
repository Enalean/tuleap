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
        $deleted_value = 2;
        $hidden_value = 3;
        
        $r =& new FRSRelease();
        $r->setStatusId($active_value);
        $this->assertTrue($r->isActive());
        
        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isActive());
        
        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isActive());
    }
    
    function testIsHidden() {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $r =& new FRSRelease();
        $r->setStatusId($hidden_value);
        $this->assertTrue($r->isHidden());
        
        $r->setStatusId($active_value);
        $this->assertFalse($r->isHidden());
        
        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isHidden());
    }
    
    function testIsDeleted() {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $r =& new FRSRelease();
        $r->setStatusId($deleted_value);
        $this->assertTrue($r->isDeleted());
        
        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isDeleted());
        
        $r->setStatusId($active_value);
        $this->assertFalse($r->isDeleted());
    }

}
?>
