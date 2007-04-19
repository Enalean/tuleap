<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * $Id$
 */
require_once(dirname(__FILE__).'/../include/Docman_PermissionsManager.class.php');
require_once('common/include/User.class.php');

Mock::generatePartial('Docman_PermissionsManager', 'Docman_PermissionsManagerTestVersion', array('_getPermissionManagerInstance', '_isUserDocmanAdmin'));
Mock::generate('User');
Mock::generate('PermissionsManager');

class PermissionsManagerTest extends UnitTestCase {
    var $user;
    var $docmanPm;
    var $refOnNull;

    function MetadataTest($name = 'Docman_PermissionsManager test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $this->user =& new MockUser($this);
        $this->docmanPm =& new Docman_PermissionsManagerTestVersion($this);
        $this->refOnNull = null;
    }

    // Functional test (should never change)
    function testSuperUserHasAllAccess() {
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        $this->user->setReturnValue('isSuperUser', true);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '2231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '2112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '2112231976'));
    }

    // Functional test (should never change)
    function testDocmanAdminHasAllAccess() {
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', true);
        $this->user->setReturnValue('isSuperUser', false);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));

        $pm =& new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertTrue($this->docmanPm->userCanRead($this->user, '42231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '52112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '82112231976'));
    }

    // Functional test (should never change)
    function testManageRightGivesReadAndWriteRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);        
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));

        // Test with another value for item_id
        $this->assertFalse($this->docmanPm->userCanManage($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, 123));

    }
    
    // Functional test (should never change)
    function testWriteRightGivesReadRight() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);        
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_WRITE', 'test'));
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));

        // Test with another value for item_id
        $this->assertFalse($this->docmanPm->userCanManage($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, 123));
    }

    // Functional test (should never change)
    function testReadRight() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);        
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_READ', 'test'));
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Functional test (should never change)
    function testNoRight() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);        
        $pm->setReturnValue('userHasPermission', false);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testExpectedQueriesOnRead() {
         // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 3);
        $pm->expectArgumentsAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_READ', 'test'));
        $pm->expectArgumentsAt(1, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_WRITE', 'test'));
        $pm->expectArgumentsAt(2, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));

        $pm->tally();
    }

    function testExpectedQueriesOnWrite() {
         // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 2);
        $pm->expectArgumentsAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_WRITE', 'test'));
        $pm->expectArgumentsAt(1, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));

        $pm->tally();
    }

    function testExpectedQueriesOnManage() {
         // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 1515;

        //
        // Start Test
        // 

        $pm =& new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 1);
        $pm->expectArgumentsAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));

        $pm->tally();
    }


    function testCacheUserCanRead() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $this->refOnNull);

        //
        // Start Test
        //       

        // Read one object
        $pm1 =& new MockPermissionsManager($this);
        $pm1->expectCallCount('userHasPermission', 3);
        $this->docmanPm->setReturnReferenceAt(0, '_getPermissionManagerInstance', $pm1); // call in userCanRead
        $this->docmanPm->setReturnReferenceAt(1, '_getPermissionManagerInstance', $pm1); // call in userCanWrite
        $this->docmanPm->setReturnReferenceAt(2, '_getPermissionManagerInstance', $pm1); // call in userCanManage
        $pm1->setReturnValue('userHasPermission', false);
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));
        $pm1->tally();

        // Test cache read og this object
        $pm2 =& new MockPermissionsManager($this);
        $pm2->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));
        $pm2->tally();
        
        // Read perm for another object
        $pm3 =& new MockPermissionsManager($this);
        $pm3->setReturnValue('userHasPermission', true);
        $pm3->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReferenceAt(3, '_getPermissionManagerInstance', $pm3);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));
        $pm3->tally();

        // Read 2nd time perm for second object
        $pm4 =& new MockPermissionsManager($this);
        $pm4->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));
        $pm4->tally();
        
        // Read 3rd time first object perms
        $pm5 =& new MockPermissionsManager($this);
        $pm5->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));
        $pm5->tally();

        // Read 3rd time second object perms
        $pm6 =& new MockPermissionsManager($this);
        $pm6->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));
        $pm6->tally();
    }

    function testCacheUserCanWrite() { 
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $this->refOnNull);

        //
        // Start Test
        //       

        // Read one object
        $pm1 =& new MockPermissionsManager($this);
        $pm1->expectCallCount('userHasPermission', 2);
        $this->docmanPm->setReturnReferenceAt(0, '_getPermissionManagerInstance', $pm1); // userCanWrite call
        $this->docmanPm->setReturnReferenceAt(1, '_getPermissionManagerInstance', $pm1); // userCanManage call
        $pm1->setReturnValue('userHasPermission', false);
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));
        $pm1->tally();

        // Test cache read og this object
        $pm2 =& new MockPermissionsManager($this);
        $pm2->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));
        $pm2->tally();
        
        // Read perm for another object
        $pm3 =& new MockPermissionsManager($this);
        $pm3->setReturnValue('userHasPermission', true);
        $pm3->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReferenceAt(2, '_getPermissionManagerInstance', $pm3);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));
        $pm3->tally();

        // Read 2nd time perm for second object
        $pm4 =& new MockPermissionsManager($this);
        $pm4->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));
        $pm4->tally();
        
        // Read 3rd time first object perms
        $pm5 =& new MockPermissionsManager($this);
        $pm5->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));
        $pm5->tally();

        // Read 3rd time second object perms
        $pm6 =& new MockPermissionsManager($this);
        $pm6->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));
        $pm6->tally();
    }

    function testCacheUserCanManage() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $this->refOnNull);

        //
        // Start Test
        //       

        // Read one object
        $pm1 =& new MockPermissionsManager($this);
        $pm1->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReferenceAt(0, '_getPermissionManagerInstance', $pm1);
        $pm1->setReturnValue('userHasPermission', false);
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));
        $pm1->tally();

        // Test cache read og this object
        $pm2 =& new MockPermissionsManager($this);
        $pm2->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));
        $pm2->tally();
        
        // Read perm for another object
        $pm3 =& new MockPermissionsManager($this);
        $pm3->setReturnValue('userHasPermission', true);
        $pm3->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReferenceAt(1, '_getPermissionManagerInstance', $pm3);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));
        $pm3->tally();

        // Read 2nd time perm for second object
        $pm4 =& new MockPermissionsManager($this);
        $pm4->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));
        $pm4->tally();
        
        // Read 3rd time first object perms
        $pm5 =& new MockPermissionsManager($this);
        $pm5->expectNever('userHasPermission');        
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));
        $pm5->tally();

        // Read 3rd time second object perms
        $pm6 =& new MockPermissionsManager($this);
        $pm6->expectNever('userHasPermission');        
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));
        $pm6->tally();
    }
}
?>
