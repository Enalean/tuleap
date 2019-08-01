<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

Mock::generate('PFUser');
Mock::generate('PermissionsManager');
Mock::generate('Docman_PermissionsManagerDao');
Mock::generate('DataAccessResult');

class Docman_PermissionsManagerTest extends TuleapTestCase {
    private $user;
    /**
     * @var \Mockery\MockInterface|Docman_PermissionsManager
     */
    private $docmanPm;
    /**
     * @var \Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\MockInterface|URLVerification
     */
    private $url_verification;

    public function setUp()
    {
        parent::setUp();
        $this->user = mock('PFUser');
        $this->user->setReturnValue('getId', 1234);
        $this->docmanPm  = Mockery::mock(Docman_PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->project   = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn('102');
        $this->docmanPm->shouldReceive('getProject')->andReturn($this->project);
        $this->url_verification = Mockery::mock(URLVerification::class);
        $this->docmanPm->shouldReceive('getURLVerification')->andReturn($this->url_verification);
    }

    // Functional test (should never change)
    public function testSuperUserHasAllAccess()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->setReturnValue('isSuperUser', true);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '2231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '2112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '2112231976'));
    }

    public function testAUserNotAbleToAccessTheProjectCanNotDoAnything()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(false);

        $this->assertFalse($this->docmanPm->userCanAdmin($this->user));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '2231'));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '2112231'));
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '2112231976'));
    }

    // Functional test (should never change)
    public function testDocmanAdminHasAllAccess()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(true);
        $this->user->setReturnValue('isSuperUser', false);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));

        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertTrue($this->docmanPm->userCanRead($this->user, '42231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '52112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '82112231976'));
    }

    // Functional test (should never change)
    public function testManageRightGivesReadAndWriteRights()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));

        // Test with another value for item_id
        $this->assertFalse($this->docmanPm->userCanManage($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, 123));

    }

    // Functional test (should never change)
    public function testWriteRightGivesReadRight()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_WRITE', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));

        // Test with another value for item_id
        $this->assertFalse($this->docmanPm->userCanManage($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, 123));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, 123));
    }

    // Functional test (should never change)
    public function testReadRight()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_READ', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Functional test (should never change)
    public function testNoRight()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testUserCanWriteButItemIsLockedBySomeoneelse()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        // item is locked
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(true);

        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // User has write permission
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_WRITE', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
    }

    public function testExpectedQueriesOnRead()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 3);
        $pm->expectAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_READ', ['test']));
        $pm->expectAt(1, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_WRITE', ['test']));
        $pm->expectAt(2, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test']));

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testExpectedQueriesOnWrite()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 2);
        $pm->expectAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_WRITE', ['test']));
        $pm->expectAt(1, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test']));

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
    }

    public function testExpectedQueriesOnManage()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);

        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        // Start Test
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', false);
        $pm->expectCallCount('userHasPermission', 1);
        $pm->expectAt(0, 'userHasPermission', array($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test']));

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
    }


    public function testCacheUserCanRead()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', []);

        $permission_manager = Mockery::mock(PermissionsManager::class);
        $permission_manager->shouldReceive('userHasPermission')->with('1515', Mockery::any(), Mockery::any())
            ->andReturn(false)->times(3);
        $permission_manager->shouldReceive('userHasPermission')->with('6667', Mockery::any(), Mockery::any())
            ->andReturn(true)->once();
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($permission_manager);

        // Start Test
        // Read one object
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));

        // Test cache read og this object
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));

        // Read perm for another object
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));

        // Read 2nd time perm for second object
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));

        // Read 3rd time first object perms
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '1515'));

        // Read 3rd time second object perms
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '6667'));
    }

    public function testCacheUserCanWrite()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', []);

        $permission_manager = Mockery::mock(PermissionsManager::class);
        $permission_manager->shouldReceive('userHasPermission')->with('1515', Mockery::any(), Mockery::any())
            ->andReturn(false)->times(2);
        $permission_manager->shouldReceive('userHasPermission')->with('6667', Mockery::any(), Mockery::any())
            ->andReturn(true)->once();
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($permission_manager);

        // Start Test
        // Read one object
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));

        // Test cache read og this object
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));

        // Read perm for another object
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));

        // Read 2nd time perm for second object
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));

        // Read 3rd time first object perms
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '1515'));

        // Read 3rd time second object perms
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '6667'));
    }

    public function testCacheUserCanManage()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', []);

        $permission_manager = Mockery::mock(PermissionsManager::class);
        $permission_manager->shouldReceive('userHasPermission')->with('1515', Mockery::any(), Mockery::any())
            ->andReturn(false)->once();
        $permission_manager->shouldReceive('userHasPermission')->with('6667', Mockery::any(), Mockery::any())
            ->andReturn(true)->once();
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($permission_manager);

        // Start Test
        // Read one object
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));

        // Test cache read og this object
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));

        // Read perm for another object
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));

        // Read 2nd time perm for second object
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));

        // Read 3rd time first object perms
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '1515'));

        // Read 3rd time second object perms
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '6667'));
    }

    public function testPermissionsBatchRetreivalForDocmanAdmin()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(true);
        $this->user->setReturnValue('isSuperUser', false);

        // No need to fetch perms when admin
        $dao = new MockDocman_PermissionsManagerDao($this);
        $dao->expectCallCount('retrievePermissionsForItems', 0);

        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        // Start Test
        $this->docmanPm->retreiveReadPermissionsForItems(array(1515), $this->user);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '1515'));
    }

    public function testPermissionsBatchRetreivalForSuperUser()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->setReturnValue('isSuperUser', true);

        // No need to fetch perms when admin
        $dao = new MockDocman_PermissionsManagerDao($this);
        $dao->expectCallCount('retrievePermissionsForItems', 0);

        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        // Start Test
        $this->docmanPm->retreiveReadPermissionsForItems(array(1515), $this->user);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '1515'));
    }

     // {{{ Test all combination for batch permission settings (see retreiveReadPermissionsForItems)

    public function testSetUserCanManage()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->expectCallCount('isSuperUser', 0);

        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanWrite()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->expectCallCount('isSuperUser', 0);

        $itemId = 1515;
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanRead()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->expectCallCount('isSuperUser', 0);

        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Read comes from cache but must look for write in DB
    public function testSetUserCanWriteAfterCanRead()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_WRITE', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Read comes from cache but must look for manage in DB
    public function testSetUserCanManageAfterCanRead()
    {
        $this->url_verification->shouldReceive('userCanAccessProject')->andReturn(true);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('getUgroups', ['test']);

        $itemId = 1515;

        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('userHasPermission', true, array($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test']));
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanReadWrite() {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanReadWriteManage() {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanReadManage() {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanManageWrite() {
        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanManageRead() {
        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    function testSetUserCanWriteRead() {
        $itemId = 1515;
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // }}} Test all combination for batch permission settings (see retreiveReadPermissionsForItems)

    public function testSetUserCanManageButCannotRead()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, false);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCannotReadButCanManage()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, false);
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testGetDocmanManagerUsersError()
    {
        $pm = new MockPermissionsManager();
        $pm->setReturnValue('getUgroupIdByObjectIdAndPermissionType', null);
        $dao = new MockDocman_PermissionsManagerDao();

        $pm->expectOnce('getUgroupIdByObjectIdAndPermissionType');
        $dao->expectNever('getUgroupMembers');
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $this->assertEqual(array(), $this->docmanPm->getDocmanManagerUsers(1, 1));
    }

    public function testGetDocmanManagerUsersDynamicUgroup()
    {
        $dar = array(array('ugroup_id' => 101));
        $pm = new MockPermissionsManager();
        $pm->setReturnValue('getUgroupIdByObjectIdAndPermissionType', $dar);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $dao = new MockDocman_PermissionsManagerDao();
        $members = array(array('email'       => 'john.doe@example.com',
                               'language_id' => 'en_US'),
                         array('email'       => 'jane.doe@example.com',
                               'language_id' => 'fr_FR'));
        $dao->setReturnvalue('getUgroupMembers', $members);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $pm->expectOnce('getUgroupIdByObjectIdAndPermissionType');
        $dao->expectOnce('getUgroupMembers');
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEqual($userArray, $this->docmanPm->getDocmanManagerUsers(1, 1));
    }

    public function testGetDocmanManagerUsersEmptyDynamicUgroup()
    {
        $dar = array(array('ugroup_id' => 101));
        $pm = new MockPermissionsManager();
        $pm->setReturnValue('getUgroupIdByObjectIdAndPermissionType', $dar);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnvalue('getUgroupMembers', array());
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $pm->expectOnce('getUgroupIdByObjectIdAndPermissionType');
        $dao->expectOnce('getUgroupMembers');
        $this->assertEqual(array(), $this->docmanPm->getDocmanManagerUsers(1, 1));
    }

    function testGetDocmanManagerUsersStaticUgroup() {
        $dar = array(array('ugroup_id' => 100));
        $pm = new MockPermissionsManager();
        $pm->setReturnValue('getUgroupIdByObjectIdAndPermissionType', $dar);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $pm->expectOnce('getUgroupIdByObjectIdAndPermissionType');
        $dao->expectNever('getUgroupMembers');
        $this->assertEqual(array(), $this->docmanPm->getDocmanManagerUsers(1, 1));
    }

    function testGetDocmanAdminUsersError() {
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnValue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $dao->expectOnce('getDocmanAdminUgroups');
        $dao->expectNever('getUgroupMembers');
        $this->assertEqual(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersDynamicUgroup()
    {
        $dar = array(array('ugroup_id' => 101));
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnValue('getDocmanAdminUgroups', $dar);
        $members = array(array('email'       => 'john.doe@example.com',
                               'language_id' => 'en_US'),
                         array('email'       => 'jane.doe@example.com',
                               'language_id' => 'fr_FR'));
        $dao->setReturnvalue('getUgroupMembers', $members);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->expectOnce('getDocmanAdminUgroups');
        $dao->expectOnce('getUgroupMembers');
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEqual($userArray, $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersEmptyDynamicUgroup()
    {
        $dar = array(array('ugroup_id' => 101));
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnValue('getDocmanAdminUgroups', $dar);
        $dao->setReturnvalue('getUgroupMembers', array());
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->expectOnce('getDocmanAdminUgroups');
        $dao->expectOnce('getUgroupMembers');
        $this->assertEqual(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersStaticUgroup()
    {
        $dar = array(array('ugroup_id' => 100));
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnValue('getDocmanAdminUgroups', $dar);
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->expectOnce('getDocmanAdminUgroups');
        $dao->expectNever('getUgroupMembers');
        $this->assertEqual(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersError()
    {
        $dao = new MockDocman_PermissionsManagerDao();
        $dao->setReturnValue('getProjectAdminMembers', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $dao->expectOnce('getProjectAdminMembers');
        $this->assertEqual(array(), $this->docmanPm->getProjectAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersSuccess()
    {
        $dao = new MockDocman_PermissionsManagerDao();
        $dar = array(array('email'       => 'john.doe@example.com',
                           'language_id' => 'en_US'),
                     array('email'       => 'jane.doe@example.com',
                           'language_id' => 'fr_FR'));
        $dao->setReturnValue('getProjectAdminMembers', $dar);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->expectOnce('getProjectAdminMembers');
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEqual($userArray, $this->docmanPm->getProjectAdminUsers($this->project));
    }
}
