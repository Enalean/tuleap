<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types = 1);

namespace Tuleap\Docman\PermissionManager;

use Docman_PermissionsManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Project\ProjectAccessChecker;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_PermissionsManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(1234);
        $this->docmanPm  = Mockery::mock(Docman_PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->project   = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn('102');
        $this->docmanPm->shouldReceive('getProject')->andReturn($this->project);
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $this->docmanPm->shouldReceive('getProjectAccessChecker')->andReturn($this->project_access_checker);
    }

    // Functional test (should never change)
    public function testSuperUserHasAllAccess()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(true);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '2231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '2112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '2112231976'));
    }

    public function testAUserNotAbleToAccessTheProjectCanNotDoAnything()
    {
        $this->project_access_checker
            ->shouldReceive('checkUserCanAccessProject')
            ->andThrow(\Project_AccessPrivateException::class);

        $this->assertFalse($this->docmanPm->userCanAdmin($this->user));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, '2231'));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, '2112231'));
        $this->assertFalse($this->docmanPm->userCanManage($this->user, '2112231976'));
    }

    // Functional test (should never change)
    public function testDocmanAdminHasAllAccess()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(true);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->assertTrue($this->docmanPm->userCanAdmin($this->user));

        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->andReturns(false);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertTrue($this->docmanPm->userCanRead($this->user, '42231'));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, '52112231'));
        $this->assertTrue($this->docmanPm->userCanManage($this->user, '82112231976'));
    }

    // Functional test (should never change)
    public function testManageRightGivesReadAndWriteRights()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->andReturns(true);
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
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->andReturns(true);
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
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_READ', ['test'])->andReturns(true);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Functional test (should never change)
    public function testNoRight()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->andReturns(false);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testUserCanWriteButItemIsLockedBySomeoneelse()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // item is locked
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(true);

        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // User has write permission
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->andReturns(true);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
    }

    public function testExpectedQueriesOnRead()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->times(3)->andReturns(false);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_READ', ['test'])->ordered();
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->ordered();
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->ordered();

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testExpectedQueriesOnWrite()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->times(2)->andReturns(false);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->ordered();
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->ordered();

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanWrite($this->user, $itemId));
    }

    public function testExpectedQueriesOnManage()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
         // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        // Start Test
        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->once()->andReturns(false);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->ordered();

        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->assertFalse($this->docmanPm->userCanManage($this->user, $itemId));
    }


    public function testCacheUserCanRead()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns([]);

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
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns([]);

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
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        // user is not super admin
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns([]);

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
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(true);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);

        // No need to fetch perms when admin
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dao->shouldReceive('retrievePermissionsForItems')->never();

        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        // Start Test
        $this->docmanPm->retreiveReadPermissionsForItems(array(1515), $this->user);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '1515'));
    }

    public function testPermissionsBatchRetreivalForSuperUser()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(true);

        // No need to fetch perms when admin
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dao->shouldReceive('retrievePermissionsForItems')->never();

        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        // Start Test
        $this->docmanPm->retreiveReadPermissionsForItems(array(1515), $this->user);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, '1515'));
    }

     // {{{ Test all combination for batch permission settings (see retreiveReadPermissionsForItems)

    public function testSetUserCanManage()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->shouldReceive('isSuperUser')->never();

        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanWrite()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->shouldReceive('isSuperUser')->never();

        $itemId = 1515;
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanRead()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // Ensure everything comes from cache
        $this->docmanPm->shouldNotReceive('_isUserDocmanAdmin');
        $this->docmanPm->shouldNotReceive('_getPermissionManagerInstance');
        $this->user->shouldReceive('isSuperUser')->never();

        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Read comes from cache but must look for write in DB
    public function testSetUserCanWriteAfterCanRead()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->docmanPm->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->andReturns(true);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    // Read comes from cache but must look for manage in DB
    public function testSetUserCanManageAfterCanRead()
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->andReturn(false);
        $this->user->shouldReceive('isSuperUser')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->andReturns(['test']);

        $itemId = 1515;

        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->andReturns(true);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);

        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanReadWrite()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanReadWriteManage()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanReadManage()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanManageWrite()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanWrite($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanManageRead()
    {
        $itemId = 1515;
        $this->docmanPm->_setCanManage($this->user->getId(), $itemId, true);
        $this->docmanPm->_setCanRead($this->user->getId(), $itemId, true);
        $this->assertTrue($this->docmanPm->userCanManage($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanWrite($this->user, $itemId));
        $this->assertTrue($this->docmanPm->userCanRead($this->user, $itemId));
    }

    public function testSetUserCanWriteRead()
    {
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

    public function testGetDocmanManagerUsersError(): void
    {
        $pm  = \Mockery::spy(\PermissionsManager::class);
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);

        $pm->shouldReceive('getUgroupIdByObjectIdAndPermissionType')->once()->andReturns(null);
        $dao->shouldReceive('getUgroupMembers')->never();
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $this->assertEquals(array(), $this->docmanPm->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersDynamicUgroup(): void
    {
        $dar = array(array('ugroup_id' => 101));
        $pm  = \Mockery::mock(\PermissionsManager::class);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $dao     = \Mockery::mock(\Docman_PermissionsManagerDao::class);
        $members = array(array('email'       => 'john.doe@example.com',
                               'language_id' => 'en_US'),
                         array('email'       => 'jane.doe@example.com',
                               'language_id' => 'fr_FR'));
        $dao->shouldReceive('getUgroupMembers')->with(101)->andReturn($members);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $pm->shouldReceive('getUgroupIdByObjectIdAndPermissionType')->once()->andReturns($dar);
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEquals($userArray, $this->docmanPm->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersEmptyDynamicUgroup(): void
    {
        $dar = array(array('ugroup_id' => 101));
        $pm  = \Mockery::mock(\PermissionsManager::class);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm)->once();
        $dao = \Mockery::mock(\Docman_PermissionsManagerDao::class);
        $dao->shouldReceive('getUgroupMembers')->with(101)->andReturn([]);
        $dao->shouldReceive('getDocmanAdminUgroups')->with($this->project)->andReturn([]);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $pm->shouldReceive('getUgroupIdByObjectIdAndPermissionType')->once()->andReturns($dar);
        $this->assertEquals(array(), $this->docmanPm->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanManagerUsersStaticUgroup(): void
    {
        $dar = array(array('ugroup_id' => 100));
        $pm  = \Mockery::spy(\PermissionsManager::class);
        $this->docmanPm->shouldReceive('_getPermissionManagerInstance')->andReturn($pm);
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $pm->shouldReceive('getUgroupIdByObjectIdAndPermissionType')->once()->andReturns($dar);
        $dao->shouldReceive('getUgroupMembers')->never();
        $this->assertEquals(array(), $this->docmanPm->getDocmanManagerUsers(1, $this->project));
    }

    public function testGetDocmanAdminUsersError(): void
    {
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $dao->shouldReceive('getDocmanAdminUgroups')->once()->andReturns(null);
        $dao->shouldReceive('getUgroupMembers')->never();
        $this->assertEquals(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersDynamicUgroup(): void
    {
        $dar     = array(array('ugroup_id' => 101));
        $dao     = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $members = array(array('email'       => 'john.doe@example.com',
                               'language_id' => 'en_US'),
                         array('email'       => 'jane.doe@example.com',
                               'language_id' => 'fr_FR'));
        $dao->shouldReceive('getUgroupMembers')->with(101)->andReturn($members);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->shouldReceive('getDocmanAdminUgroups')->once()->andReturns($dar);
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEquals($userArray, $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersEmptyDynamicUgroup()
    {
        $dar = array(array('ugroup_id' => 101));
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dao->shouldReceive('getUgroupMembers')->with(101)->andReturn([]);
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->shouldReceive('getDocmanAdminUgroups')->once()->andReturns($dar);
        $this->assertEquals(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetDocmanAdminUsersStaticUgroup()
    {
        $dar = array(array('ugroup_id' => 100));
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dao->setReturnvalue('getDocmanAdminUgroups', null);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->shouldReceive('getDocmanAdminUgroups')->once()->andReturns($dar);
        $dao->shouldReceive('getUgroupMembers')->never();
        $this->assertEquals(array(), $this->docmanPm->getDocmanAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersError()
    {
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao);

        $dao->shouldReceive('getProjectAdminMembers')->once()->andReturns(null);
        $this->assertEquals(array(), $this->docmanPm->getProjectAdminUsers($this->project));
    }

    public function testGetProjectAdminUsersSuccess()
    {
        $dao = \Mockery::spy(\Docman_PermissionsManagerDao::class);
        $dar = array(array('email'       => 'john.doe@example.com',
                           'language_id' => 'en_US'),
                     array('email'       => 'jane.doe@example.com',
                           'language_id' => 'fr_FR'));
        $this->docmanPm->shouldReceive('getDao')->andReturn($dao)->once();

        $dao->shouldReceive('getProjectAdminMembers')->once()->andReturns($dar);
        $userArray = array('john.doe@example.com' => 'en_US',
                           'jane.doe@example.com' => 'fr_FR');
        $this->assertEquals($userArray, $this->docmanPm->getProjectAdminUsers($this->project));
    }
}
