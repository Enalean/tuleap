<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/permission/PermissionsManager.class.php';

Mock::generate('PermissionsDao');

class PermissionsManagerTest extends UnitTestCase {
    
    function testDuplicatePermissionsPassParamters() {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        $ugroup_mapping   = array(110 => 210,
                                  120 => 220);
        $duplicate_type  = PermissionsDao::DUPLICATE_SAME_PROJECT;
        
        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, $duplicate_type, $ugroup_mapping));
        
        $permissionsManager = new PermissionsManager($dao);
        
        $permissionsManager->duplicatePermissions($source, $target, $permission_types, $ugroup_mapping, $duplicate_type);
    }
    
    function testDuplicateSameProjectShouldNotHaveUgroupMapping() {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        
        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_SAME_PROJECT, false));
        
        $permissionsManager = new PermissionsManager($dao);
        
        $permissionsManager->duplicateWithStatic($source, $target, $permission_types);
    }
    
    function testDuplicateNewProjectShouldHaveUgroupMapping() {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        $ugroup_mapping   = array(110 => 210,
                                  120 => 220);
        
        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_NEW_PROJECT, $ugroup_mapping));
        
        $permissionsManager = new PermissionsManager($dao);
        
        $permissionsManager->duplicateWithStaticMapping($source, $target, $permission_types, $ugroup_mapping);
    }
    
    function testDuplicateOtherProjectShouldNotHaveUgroupMapping() {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        
        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_OTHER_PROJECT, false));
        
        $permissionsManager = new PermissionsManager($dao);
        
        $permissionsManager->duplicateWithoutStatic($source, $target, $permission_types);
    }
}

?>
