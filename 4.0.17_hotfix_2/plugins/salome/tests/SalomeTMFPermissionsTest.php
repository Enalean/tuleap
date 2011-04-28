<?php

require_once(dirname(__FILE__).'/../include/SalomeTMFPermissions.class.php');


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class SalomeTMFPermissions
 */
class SalomeTMFPermissionsTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SalomePermissionsTest($name = 'SalomeTMFPermissions test') {
        $this->UnitTestCase($name);
    }
    
    function testCanAddSuite() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canAddSuite(), "Should be able to add suite with permissions=".$perms);
        
        $perms = 2;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canAddSuite(), "Should be able to add suite with permissions=".$perms);
        
        $perms = 4;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canAddSuite(), "Should not be able to add suite with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canAddSuite(), "Should not be able to add suite with permissions=".$perms);
        
        $perms = 254 - 2;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canAddSuite(), "Should not be able to add suite with permissions=".$perms);
        
        $perms = 2 + 4 + 64 + 128;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canAddSuite(), "Should be able to add suite with permissions=".$perms);
        
    }
    
    function testCanModifySuite() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canModifySuite(), "Should be able to modify suite with permissions=".$perms);
        
        $perms = 4;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canModifySuite(), "Should be able to modify suite with permissions=".$perms);
        
        $perms = 2;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canModifySuite(), "Should not be able to modify suite with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canModifySuite(), "Should not be able to modify suite with permissions=".$perms);
        
        $perms = 254 - 4;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canModifySuite(), "Should not be able to modify suite with permissions=".$perms);
        
        $perms = 2 + 4 + 64 + 128;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canModifySuite(), "Should be able to modify suite with permissions=".$perms);
        
    }
    
    function testCanDeleteSuite() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canDeleteSuite(), "Should be able to delete suite with permissions=".$perms);
        
        $perms = 8;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canDeleteSuite(), "Should be able to delete suite with permissions=".$perms);
        
        $perms = 2;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canDeleteSuite(), "Should not be able to delete suite with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canDeleteSuite(), "Should not be able to delete suite with permissions=".$perms);
        
        $perms = 254 - 8;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canDeleteSuite(), "Should not be able to delete suite with permissions=".$perms);
        
        $perms = 8 + 16 + 32 + 64 + 128;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canDeleteSuite(), "Should be able to delete suite with permissions=".$perms);
    }
    
    function testCanAddCampaign() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canAddCampaign(), "Should be able to add campaign with permissions=".$perms);
        
        $perms = 16;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canAddCampaign(), "Should be able to add campaign with permissions=".$perms);
        
        $perms = 4;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canAddCampaign(), "Should not be able to add campaign with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canAddCampaign(), "Should not be able to add campaign with permissions=".$perms);
        
        $perms = 254 - 16;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canAddCampaign(), "Should not be able to add campaign with permissions=".$perms);
        
        $perms = 8 + 16 + 32;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canAddCampaign(), "Should be able to add campaign with permissions=".$perms);
    }
    
    function testCanModifyCampaign() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canModifyCampaign(), "Should be able to modify campaign with permissions=".$perms);
        
        $perms = 32;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canModifyCampaign(), "Should be able to modify campaign with permissions=".$perms);
        
        $perms = 2;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canModifyCampaign(), "Should not be able to modify campaign with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canModifyCampaign(), "Should not be able to modify campaign with permissions=".$perms);
        
        $perms = 254 - 32;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canModifyCampaign(), "Should not be able to modify campaign with permissions=".$perms);
        
        $perms = 2 + 4 + 32;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canModifyCampaign(), "Should be able to modify campaign with permissions=".$perms);
    }
    
    function testCanDeleteCampaign() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canDeleteCampaign(), "Should be able to delete campaign with permissions=".$perms);
        
        $perms = 64;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canDeleteCampaign(), "Should be able to delete campaign with permissions=".$perms);
        
        $perms = 2;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canDeleteCampaign(), "Should not be able to delete campaign with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canDeleteCampaign(), "Should not be able to delete campaign with permissions=".$perms);
        
        $perms = 254 - 64;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canDeleteCampaign(), "Should not be able to delete campaign with permissions=".$perms);
        
        $perms = 2 + 4 + 64;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canDeleteCampaign(), "Should be able to delete campaign with permissions=".$perms);
    }
    
    function testCanExecuteCampaign() {
        $perms = 254;
        $p1 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p1->canExecuteCampaign(), "Should be able to execute campaign with permissions=".$perms);
        
        $perms = 128;
        $p2 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p2->canExecuteCampaign(), "Should be able to execute campaign with permissions=".$perms);
        
        $perms = 2;
        $p3 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p3->canExecuteCampaign(), "Should not be able to execute campaign with permissions=".$perms);
        
        $perms = 0;
        $p4 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p4->canExecuteCampaign(), "Should not be able to execute campaign with permissions=".$perms);
        
        $perms = 254 - 128;
        $p5 =& new SalomeTMFPermissions($perms);
        $this->assertFalse($p5->canExecuteCampaign(), "Should not be able to execute campaign with permissions=".$perms);
        
        $perms = 2 + 4 + 8 + 128;
        $p6 =& new SalomeTMFPermissions($perms);
        $this->assertTrue($p6->canExecuteCampaign(), "Should be able to execute campaign with permissions=".$perms);
    }
    
    function testGetPermissionFromCheckbox() {
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox('on', null, 'on', null, 'on', null, 'on');
        $this->assertEqual($perm->getIntPermissions(), 170, "Should be equal to 170, but is equal to ".$perm->getIntPermissions());
        $this->assertEqual($perm->getBinaryPermissions(), '10101010', "Should be equal to 10101010, but is equal to ".$perm->getBinaryPermissions());
        
        $perm2 = SalomeTMFPermissions::getPermissionFromCheckbox(null, null, null, null, null, null, null);
        $this->assertEqual($perm2->getIntPermissions(), 0, "Should be equal to 0, but is equal to ".$perm2->getIntPermissions());
        $this->assertEqual($perm2->getBinaryPermissions(), '00000000', "Should be equal to 00000000, but is equal to ".$perm2->getBinaryPermissions());
        
        $perm3 = SalomeTMFPermissions::getPermissionFromCheckbox('on', 'on', 'on', 'on', 'on', 'on', 'on');
        $this->assertEqual($perm3->getIntPermissions(), 254, "Should be equal to 254, but is equal to ".$perm3->getIntPermissions());
        $this->assertEqual($perm3->getBinaryPermissions(), '11111110', "Should be equal to 11111110, but is equal to ".$perm3->getBinaryPermissions());
    }
    
    function testIsAllowedValue() {
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox('on', 'on', 'on', null, 'on', null, 'on');
        $this->assertTrue($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox('on', null, 'on', null, 'on', null, 'on');
        $this->assertFalse($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should not be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox('on', 'on', 'on', 'on', null, null, 'on');
        $this->assertFalse($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should not be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox(null, null, null, null, null, null, 'on');
        $this->assertTrue($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox(null, 'on', null, null, null, null, 'on');
        $this->assertTrue($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox(null, 'on', 'on', null, null, null, 'on');
        $this->assertFalse($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should not be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox('on', 'on', 'on', null, null, null, 'on');
        $this->assertTrue($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should be allowed");
        
        $perm = SalomeTMFPermissions::getPermissionFromCheckbox(null, null, null, null, null, null, null);
        $this->assertTrue($perm->isAllowedValue(), "Permissions ".$perm->getIntPermissions()." should be allowed");
    }
}

?>
