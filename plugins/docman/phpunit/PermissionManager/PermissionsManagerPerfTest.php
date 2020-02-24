<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\PermissionManager;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Docman_PermissionsManager;
use Mockery;
use PermissionsManager;
use PFUser;
use Project;
use Tuleap\Project\ProjectAccessChecker;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class PermissionsManagerPerfTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\Mock
     */
    private $docmanPm;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = \Mockery::spy(PFUser::class);
        $this->docmanPm = \Mockery::mock(Docman_PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $this->docmanPm->allows(['_itemIsLockedForUser' => false]);
        $this->docmanPm->shouldReceive('getProject')->andReturn($project);
        $this->docmanPm->shouldReceive('getProjectAccessChecker')->andReturn($this->project_access_checker);
    }

    public function testSuperAdminHasAllAccess(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->user->allows(['isSuperUser' => true]);

        // no _isUserDocmanAdmin call
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->never();

        // no userHasPerms call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->expects()->userHasPermission()->never();
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        $this->docmanPm->userCanRead($this->user, 32432413);
        $this->docmanPm->userCanWrite($this->user, 324324234313);
        $this->docmanPm->userCanManage($this->user, 324324423413);
        $this->docmanPm->userCanAdmin($this->user, 324324423413);
    }

    public function testProjectAdminHasAllAccess(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['isAdmin' => true]);

        // no _isUserDocmanAdmin call
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->never();

        // no userHasPerms call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->expects()->userHasPermission()->never();
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        $this->docmanPm->userCanRead($this->user, 32432413);
        $this->docmanPm->userCanWrite($this->user, 324324234313);
        $this->docmanPm->userCanManage($this->user, 324324423413);
        $this->docmanPm->userCanAdmin($this->user, 324324423413);
    }

    public function testDocmanAdminHasAllAccess(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->user->allows(['isSuperUser' => false]);

        // one _isUserDocmanAdmin call
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(true);

        // no userHasPerms call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->expects()->userHasPermission()->never();
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        $this->docmanPm->userCanRead($this->user, 32432413);
        $this->docmanPm->userCanWrite($this->user, 324324234313);
        $this->docmanPm->userCanManage($this->user, 324324423413);
        $this->docmanPm->userCanAdmin($this->user, 324324423413);
    }

    public function testManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['getUgroups' => ['test']]);

        $itemId = 78903;

        // one _isUserDocmanAdmin call
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(false);

        // 1 userHasPerm call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->once()->andReturns(true);
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        // test manage
        $this->docmanPm->userCanManage($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }

    public function testWriteRightGivesReadRights(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['getUgroups' => ['test']]);

        $itemId = 78903;

        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(false);

        // 2 userHasPerm call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->once()->andReturns(true);
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }

    public function testOnReadTestManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(false);

        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['getUgroups' => ['test']]);

        $itemId = 78903;

        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->once()->andReturns(
            true
        );
        $pm->shouldReceive('userHasPermission')->times(2);
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test Manage
        $this->docmanPm->userCanManage($this->user, $itemId);
    }

    public function testOnReadTestWriteRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(false);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['getUgroups' => ['test']]);

        $itemId = 78903;

        // 3 userHasPerm call:
        // userCanRead:
        // 1. one for READ (no matching value found)
        // 2. one for WRITE (one result found), not cached because only test
        //    write perm (not lock).
        // userCanWrite
        // 3. one for WRITE (and eventually lock, but not in this test).
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_WRITE', ['test'])->once()->andReturns(
            true
        );
        $pm->shouldReceive('userHasPermission')->times(3);
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);
    }

    public function testOnWriteTestManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject');
        // user is not docman admin
        $this->docmanPm->shouldReceive('_isUserDocmanAdmin')->once()->andReturns(false);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        $this->user->allows(['getUgroups' => ['test']]);

        $itemId = 78903;

        // 2 userHasPerm call
        $pm = \Mockery::spy(PermissionsManager::class);
        $pm->shouldReceive('userHasPermission')->with($itemId, 'PLUGIN_DOCMAN_MANAGE', ['test'])->once()->andReturns(
            true
        );
        $pm->shouldReceive('userHasPermission')->once();
        $this->docmanPm->allows(['_getPermissionManagerInstance' => $pm]);

        // test write
        $this->docmanPm->userCanWrite($this->user, $itemId);

        // test manage
        $this->docmanPm->userCanManage($this->user, $itemId);

        // test read
        $this->docmanPm->userCanRead($this->user, $itemId);
    }
}
