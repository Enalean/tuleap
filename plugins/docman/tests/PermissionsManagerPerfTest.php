<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_PermissionsManager', 'Docman_PermissionsManagerTestPerfVersion', array('_getPermissionManagerInstance', '_isUserDocmanAdmin', '_itemIsLockedForUser'));
Mock::generate('PFUser');
Mock::generate('PermissionsManager');

class PermissionsManagerPerfTest extends TuleapTestCase {
    var $user;
    var $docmanPm;
    var $refOnNull;

    public function setUp()
    {
        parent::setUp();
        $this->user     = mock('PFUser');
        $this->docmanPm = new Docman_PermissionsManagerTestPerfVersion($this);
        $this->docmanPm->setReturnValue('_itemIsLockedForUser', false);
        $this->refOnNull = null;
    }

    function testSuperAdminHasAllAccess() {
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        $this->user->setReturnValue('isSuperUser', true);


        // no _isUserDocmanAdmin call
        $this->docmanPm->expectNever('_isUserDocmanAdmin');

        // no userHasPerms call
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectNever('userHasPermission');
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->docmanPm->userCanRead($this->user, 32432413);
        $this->docmanPm->userCanWrite($this->user, 324324234313);
        $this->docmanPm->userCanManage($this->user, 324324423413);
        $this->docmanPm->userCanAdmin($this->user, 324324423413);
    }

    function testDocmanAdminHasAllAccess() {
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', true);
        $this->user->setReturnValue('isSuperUser', false);


        // one _isUserDocmanAdmin call
        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        // no userHasPerms call
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectNever('userHasPermission');
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        $this->docmanPm->userCanRead($this->user, 32432413);
        $this->docmanPm->userCanWrite($this->user, 324324234313);
        $this->docmanPm->userCanManage($this->user, 324324423413);
        $this->docmanPm->userCanAdmin($this->user, 324324423413);
    }

    function testManageRightGivesReadAndWriteRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $itemId = 78903;

        // one _isUserDocmanAdmin call
        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        // 1 userHasPerm call
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true);
        $pm->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        // test manage
        $this->docmanPm->userCanManage($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }

    function testWriteRightGivesReadRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $itemId = 78903;

        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        // 2 userHasPerm call
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true);
        $pm->expectCallCount('userHasPermission', 1);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }

    function testOnReadTestManageRightGivesReadAndWriteRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 78903;

        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));
        $pm->expectCallCount('userHasPermission', 3);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test Manage
        $this->docmanPm->userCanManage($this->user, $itemId);
    }

    function testOnReadTestWriteRightGivesReadAndWriteRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 78903;

        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        // 3 userHasPerm call:
        // userCanRead:
        // 1. one for READ (no matching value found)
        // 2. one for WRITE (one result found), not cached because only test
        //    write perm (not lock).
        // userCanWrite
        // 3. one for WRITE (and eventually lock, but not in this test).
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_WRITE', 'test'));
        $pm->expectCallCount('userHasPermission', 3);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);
    }

    function testOnWriteTestManageRightGivesReadAndWriteRights() {
        // user is not docman admin
        $this->docmanPm->setReturnValue('_isUserDocmanAdmin', false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', 'test');

        $itemId = 78903;

        $this->docmanPm->expectCallCount('_isUserDocmanAdmin', 1);

        // 2 userHasPerm call
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_MANAGE', 'test'));
        $pm->expectCallCount('userHasPermission', 2);
        $this->docmanPm->setReturnReference('_getPermissionManagerInstance', $pm);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test manage
        $this->docmanPm->userCanManage($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }
}
