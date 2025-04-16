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

use Docman_PermissionsManager;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsManagerPerfTest extends TestCase
{
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private ProjectAccessChecker&MockObject $project_access_checker;
    private Project $project;

    public function setUp(): void
    {
        $this->permissions_manager    = $this->createPartialMock(Docman_PermissionsManager::class, [
            '_itemIsLockedForUser',
            'getProject',
            'getProjectAccessChecker',
            '_isUserDocmanAdmin',
            '_getPermissionManagerInstance',
        ]);
        $this->project                = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->permissions_manager->method('getProject')->willReturn($this->project);
        $this->permissions_manager->method('getProjectAccessChecker')->willReturn($this->project_access_checker);
    }

    public function testSuperAdminHasAllAccess(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $user = UserTestBuilder::buildSiteAdministrator();

        // no _isUserDocmanAdmin call
        $this->permissions_manager->expects($this->never())->method('_isUserDocmanAdmin');

        // no userHasPerms call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->never())->method('userHasPermission');
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        $this->permissions_manager->userCanRead($user, 32432413);
        $this->permissions_manager->userCanWrite($user, 324324234313);
        $this->permissions_manager->userCanManage($user, 324324423413);
        $this->permissions_manager->userCanAdmin($user);
    }

    public function testProjectAdminHasAllAccess(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->withAdministratorOf($this->project)->build();

        // no _isUserDocmanAdmin call
        $this->permissions_manager->expects($this->never())->method('_isUserDocmanAdmin');

        // no userHasPerms call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->never())->method('userHasPermission');
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        $this->permissions_manager->userCanRead($user, 32432413);
        $this->permissions_manager->userCanWrite($user, 324324234313);
        $this->permissions_manager->userCanManage($user, 324324423413);
        $this->permissions_manager->userCanAdmin($user);
    }

    public function testDocmanAdminHasAllAccess(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        // one _isUserDocmanAdmin call
        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(true);

        // no userHasPerms call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->never())->method('userHasPermission');
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        $this->permissions_manager->userCanRead($user, 32432413);
        $this->permissions_manager->userCanWrite($user, 324324234313);
        $this->permissions_manager->userCanManage($user, 324324423413);
        $this->permissions_manager->userCanAdmin($user);
    }

    public function testManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $user->method('getId');
        $user->method('isAdmin');

        $itemId = 78903;

        // one _isUserDocmanAdmin call
        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(false);

        // 1 userHasPerm call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->once())->method('userHasPermission')->willReturn(true);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        // test manage
        $this->permissions_manager->userCanManage($user, $itemId);

        // test write
        $this->permissions_manager->userCanWrite($user, $itemId);

        // test read
        $this->permissions_manager->userCanRead($user, $itemId);
    }

    public function testWriteRightGivesReadRights(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $user->method('getId');
        $user->method('isAdmin');

        $itemId = 78903;

        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(false);

        // 2 userHasPerm call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->once())->method('userHasPermission')->willReturn(true);
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        // test write
        $this->permissions_manager->userCanWrite($user, $itemId);

        // test read
        $this->permissions_manager->userCanRead($user, $itemId);
    }

    public function testOnReadTestManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(false);

        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $user->method('getId');
        $user->method('isAdmin');

        $itemId = 78903;

        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->exactly(3))->method('userHasPermission')->willReturnCallback(static fn(int $item_id, string $type, array $ugroups) => match (true) {
            $item_id === $itemId && $type === 'PLUGIN_DOCMAN_MANAGE' && $ugroups === ['test'] => true,
            default                                                                           => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        // test read
        $this->permissions_manager->userCanRead($user, $itemId);

        // test write
        $this->permissions_manager->userCanWrite($user, $itemId);

        // test Manage
        $this->permissions_manager->userCanManage($user, $itemId);
    }

    public function testOnReadTestWriteRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // user is not docman admin
        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $user->method('getId');
        $user->method('isAdmin');

        $itemId = 78903;

        // 3 userHasPerm call:
        // userCanRead:
        // 1. one for READ (no matching value found)
        // 2. one for WRITE (one result found), not cached because only test
        //    write perm (not lock).
        // userCanWrite
        // 3. one for WRITE (and eventually lock, but not in this test).
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->exactly(3))->method('userHasPermission')->willReturnCallback(static fn(int $item_id, string $type, array $ugroups) => match (true) {
            $item_id === $itemId && $type === 'PLUGIN_DOCMAN_WRITE' && $ugroups === ['test'] => true,
            default                                                                          => false,
        });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        // test read
        $this->permissions_manager->userCanRead($user, $itemId);

        // test write
        $this->permissions_manager->userCanWrite($user, $itemId);
    }

    public function testOnWriteTestManageRightGivesReadAndWriteRights(): void
    {
        $this->project_access_checker->method('checkUserCanAccessProject');
        // user is not docman admin
        $this->permissions_manager->expects($this->once())->method('_isUserDocmanAdmin')->willReturn(false);
        // user is not super admin
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('getUgroups')->willReturn(['test']);
        $user->method('getId');
        $user->method('isAdmin');

        $itemId = 78903;

        // 2 userHasPerm call
        $pm = $this->createMock(PermissionsManager::class);
        $pm->expects($this->exactly(2))->method('userHasPermission')
            ->willReturnCallback(static fn(int $item_id, string $type, array $ugroups) => match (true) {
                $item_id === $itemId && $type === 'PLUGIN_DOCMAN_MANAGE' && $ugroups === ['test'] => true,
                default                                                                           => false,
            });
        $this->permissions_manager->method('_getPermissionManagerInstance')->willReturn($pm);

        // test write
        $this->permissions_manager->userCanWrite($user, $itemId);

        // test manage
        $this->permissions_manager->userCanManage($user, $itemId);

        // test read
        $this->permissions_manager->userCanRead($user, $itemId);
    }
}
