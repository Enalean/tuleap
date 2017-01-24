<?php

require_once('common/frs/FRSPackage.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the FRSPackage class
 */
class FRSPackageTest extends TuleapTestCase {

    function testIsActive() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p =& new FRSPackage();
        $p->setStatusId($active_value);
        $this->assertTrue($p->isActive());
        
        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isActive());
        
        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isActive());
    }
    
    function testIsDeleted() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p =& new FRSPackage();
        $p->setStatusId($deleted_value);
        $this->assertTrue($p->isDeleted());
        
        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isDeleted());
        
        $p->setStatusId($active_value);
        $this->assertFalse($p->isDeleted());
    }
    
    function testIsHidden() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p =& new FRSPackage();
        $p->setStatusId($hidden_value);
        $this->assertTrue($p->isHidden());
        
        $p->setStatusId($active_value);
        $this->assertFalse($p->isHidden());
        
        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isHidden());
    }
    
    

}
?>
