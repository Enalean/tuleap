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
use PHPUnit\Framework\MockObject\Stub;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSReleaseFactoryTest extends TestCase
{
    private \FRSRelease $release;
    private PFUser $user;
    private FRSReleaseFactory&Stub $frs_release_factory;
    private UserManager&Stub $user_manager;
    private PermissionsManager&Stub $permission_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->frs_release_factory = $this->getStubBuilder(FRSReleaseFactory::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->onlyMethods([
                'getUserManager',
                'userCanAdmin',
                'getPermissionsManager',
                '_getFRSPackageFactory',
                'getFRSReleasesInfoListFromDb',
                'delete_release',
            ])->getStub();
        $this->release             = new \FRSRelease(['release_id' => 56, 'package_id' => 34, 'status_id' => 1]);
        $this->release->setGroupID(12);
        $project    = ProjectTestBuilder::aProject()->withId($this->release->getGroupID())->build();
        $this->user = $this->createStub(PFUser::class);
        $this->user->method('getId')->willReturn(78);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('isRestricted')->willReturn(false);
        $this->user->method('getUgroups')->willReturn([1, 2, 76]);
        $this->user_manager       = $this->createStub(UserManager::class);
        $this->permission_manager = $this->createStub(PermissionsManager::class);
        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->frs_release_factory->method('getUserManager')->willReturn($this->user_manager);
        $project_manager = $this->createStub(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);
        ProjectManager::setInstance($project_manager);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        ProjectManager::clearInstance();
    }

    public function testAdminHasAlwaysAccessToReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->release, $this->user->getId()));
    }

    protected function userCanReadWhenNoPermsOnRelease($canReadPackage): FRSReleaseFactory&Stub
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        $this->permission_manager->method('isPermissionExist')->willReturn(false);
        $this->frs_release_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        $frs_package_factory = $this->createMock(FRSPackageFactory::class);
        $frs_package_factory->expects($this->once())->method('userCanRead')->willReturn($canReadPackage);
        $this->frs_release_factory->method('_getFRSPackageFactory')->willReturn($frs_package_factory);

        return $this->frs_release_factory;
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCanReadPackage(): void
    {
        $this->userCanReadWhenNoPermsOnRelease(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->release, $this->user->getId()));
    }

    public function testUserCannotReadWhenSpecificPermissionsOnReleasesButCannotAccessPackage(): void
    {
        $this->userCanReadWhenNoPermsOnRelease(false);
        self::assertFalse($this->frs_release_factory->userCanRead($this->release, $this->user->getId()));
    }

    protected function userCanReadWithSpecificPerms($can_read_release): void
    {
        $this->permission_manager->method('isPermissionExist')->willReturn(true);
        $this->permission_manager->method('userHasPermission')->willReturn($can_read_release);
        $this->frs_release_factory->method('getPermissionsManager')->willReturn($this->permission_manager);
        $frs_package_factory = $this->createStub(FRSPackageFactory::class);
        $frs_package_factory->method('userCanRead')->willReturn(true);
        $this->frs_release_factory->method('_getFRSPackageFactory')->willReturn($frs_package_factory);
        $this->frs_release_factory->method('userCanAdmin');
    }

    public function testUserCanReadWithSpecificPermsHasAccess(): void
    {
        $this->userCanReadWithSpecificPerms(true);
        self::assertTrue($this->frs_release_factory->userCanRead($this->release, $this->user->getId()));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess(): void
    {
        $this->userCanReadWithSpecificPerms(false);
        self::assertFalse($this->frs_release_factory->userCanRead($this->release, (int) $this->user->getId()));
    }

    public function testAdminCanAlwaysUpdateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(true);
        self::assertTrue($this->frs_release_factory->userCanUpdate($this->release, (int) $this->user->getId()));
    }

    public function testMereMortalCannotUpdateReleases(): void
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        self::assertFalse($this->frs_release_factory->userCanUpdate($this->release, (int) $this->user->getId()));
    }

    public function testDeleteProjectReleasesFail(): void
    {
        $release1 = ['release_id' => 1];
        $release2 = ['release_id' => 2];
        $release3 = ['release_id' => 3];
        $this->frs_release_factory->method('getFRSReleasesInfoListFromDb')->willReturn([$release1, $release2, $release3]);
        $this->frs_release_factory->method('delete_release')->willReturnCallback(function (int $group_id, int $release_id) {
            if ($group_id !== 1) {
                $this->fail('Expected group_id to be 1');
            }

            return match ($release_id) {
                1, 3 => true,
                2 => false,
            };
        });
        self::assertFalse($this->frs_release_factory->deleteProjectReleases(1));
    }

    public function testDeleteProjectReleasesSuccess(): void
    {
        $release1 = ['release_id' => 1];
        $release2 = ['release_id' => 2];
        $release3 = ['release_id' => 3];
        $this->frs_release_factory->method('getFRSReleasesInfoListFromDb')->willReturn([$release1, $release2, $release3]);
        $this->frs_release_factory->method('delete_release')->willReturnCallback(function (int $group_id, int $release_id) {
            if ($group_id !== 1) {
                $this->fail('Expected group_id to be 1');
            }

            return match ($release_id) {
                1, 2, 3 => true,
            };
        });
        self::assertTrue($this->frs_release_factory->deleteProjectReleases(1));
    }
}
