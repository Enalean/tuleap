<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\FRS;

use FRSPackageFactory;
use FRSReleaseFactory;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

class FRSReleaseFactoryTest extends TestCase
{
    protected $group_id   = 12;
    protected $package_id = 34;
    protected $release_id = 56;
    protected $user_id    = 78;

    /**
     * @var MockObject&PFUser
     */
    private $user;
    /**
     * @var MockObject&FRSReleaseFactory
     */
    private $frs_release_factory;
    /**
     * @var MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var MockObject&PermissionsManager
     */
    private $permission_manager;

    protected function setUp(): void
    {
        $this->user                = $this->createMock(PFUser::class);
        $this->frs_release_factory = $this->createPartialMock(FRSReleaseFactory::class, [
            'getUserManager',
            'userCanAdmin',
            'getPermissionsManager',
            '_getFRSPackageFactory',
            'getFRSReleasesInfoListFromDb',
            'delete_release',
        ]);
        $this->user_manager        = $this->createMock(UserManager::class);
        $this->permission_manager  = $this->createMock(PermissionsManager::class);
        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->frs_release_factory->method('getUserManager')->willReturn($this->user_manager);
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($this->group_id);
        $project->method('isActive')->willReturn(true);
        $project->method('isPublic')->willReturn(true);
        $project->method('isError');
        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);
        ProjectManager::setInstance($project_manager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ProjectManager::clearInstance();
    }

    public function testAdminHasAlwaysAccessToReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function userCanReadWhenNoPermsOnRelease($canReadPackage): MockObject&FRSReleaseFactory
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        $this->permission_manager->expects(self::once())->method('isPermissionExist')->with($this->release_id, 'RELEASE_READ')->willReturn(false);
        $this->frs_release_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        $frs_package_factory = $this->createMock(FRSPackageFactory::class);
        $frs_package_factory->expects(self::once())->method('userCanRead')->with($this->group_id, $this->package_id, null)->willReturn($canReadPackage);
        $this->frs_release_factory->method('_getFRSPackageFactory')->willReturn($frs_package_factory);

        $this->user->method('isAnonymous');
        $this->user->method('isSuperUser');
        $this->user->method('isMember');
        $this->user->method('isRestricted');
        $this->user->method('getId');

        return $this->frs_release_factory;
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCanReadPackage(): void
    {
        $this->userCanReadWhenNoPermsOnRelease(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCannotReadPackage(): void
    {
        $this->userCanReadWhenNoPermsOnRelease(false);
        self::assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function userCanReadWithSpecificPerms($can_read_release): void
    {
        $this->user->expects(self::once())->method('getUgroups')->with($this->group_id, [])->willReturn([1, 2, 76]);
        $this->user->method('isAnonymous');
        $this->user->method('isSuperUser');
        $this->user->method('isMember');
        $this->user->method('isRestricted');
        $this->permission_manager->expects(self::once())->method('isPermissionExist')->with($this->release_id, 'RELEASE_READ')->willReturn(true);
        $this->permission_manager->expects(self::once())->method('userHasPermission')->with($this->release_id, 'RELEASE_READ', [1, 2, 76])->willReturn($can_read_release);
        $this->frs_release_factory->method('getPermissionsManager')->willReturn($this->permission_manager);
        $this->frs_release_factory->method('userCanAdmin');
    }

    public function testUserCanReadWithSpecificPermsHasAccess(): void
    {
        $this->userCanReadWithSpecificPerms(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess(): void
    {
        $this->userCanReadWithSpecificPerms(false);
        self::assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testAdminCanAlwaysUpdateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(true);
        self::assertTrue($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testMereMortalCannotUpdateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        self::assertFalse($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(true);
        self::assertTrue($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        self::assertFalse($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectReleasesFail(): void
    {
        $release1 = ['release_id' => 1];
        $release2 = ['release_id' => 2];
        $release3 = ['release_id' => 3];
        $this->frs_release_factory->method('getFRSReleasesInfoListFromDb')->willReturn([$release1, $release2, $release3]);
        $this->frs_release_factory->method('delete_release')->withConsecutive(
            [1, 1],
            [1, 2],
            [1, 3]
        )->willReturnOnConsecutiveCalls(true, false, true);
        self::assertFalse($this->frs_release_factory->deleteProjectReleases(1));
    }

    public function testDeleteProjectReleasesSuccess(): void
    {
        $release1 = ['release_id' => 1];
        $release2 = ['release_id' => 2];
        $release3 = ['release_id' => 3];
        $this->frs_release_factory->method('getFRSReleasesInfoListFromDb')->willReturn([$release1, $release2, $release3]);
        $this->frs_release_factory->method('delete_release')->withConsecutive(
            [1, 1],
            [1, 2],
            [1, 3]
        )->willReturn(true);
        self::assertTrue($this->frs_release_factory->deleteProjectReleases(1));
    }
}
