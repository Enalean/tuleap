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
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSReleaseFactoryTest extends TestCase
{
    private \FRSRelease $release;
    private PFUser $user;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->frs_release_factory = $this->createPartialMock(FRSReleaseFactory::class, [
            'getUserManager',
            'userCanAdmin',
            'getPermissionsManager',
            '_getFRSPackageFactory',
            'getFRSReleasesInfoListFromDb',
            'delete_release',
        ]);
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
        $this->user_manager       = $this->createMock(UserManager::class);
        $this->permission_manager = $this->createMock(PermissionsManager::class);
        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->frs_release_factory->method('getUserManager')->willReturn($this->user_manager);
        $project_manager = $this->createMock(ProjectManager::class);
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

    protected function userCanReadWhenNoPermsOnRelease($canReadPackage): MockObject&FRSReleaseFactory
    {
        $this->frs_release_factory->method('userCanAdmin')->willReturn(false);
        $this->permission_manager->method('isPermissionExist')->with($this->release->getReleaseID(), 'RELEASE_READ')->willReturn(false);
        $this->frs_release_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        $frs_package_factory = $this->createMock(FRSPackageFactory::class);
        $frs_package_factory->expects($this->once())->method('userCanRead')->with($this->release->getPackageID(), 78)->willReturn($canReadPackage);
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
        $this->permission_manager->expects($this->once())->method('isPermissionExist')->with($this->release->getReleaseID(), 'RELEASE_READ')->willReturn(true);
        $this->permission_manager->expects($this->once())->method('userHasPermission')->with($this->release->getReleaseID(), 'RELEASE_READ', [1, 2, 76])->willReturn($can_read_release);
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
        $matcher = $this->exactly(3);
        $this->frs_release_factory->expects($matcher)->method('delete_release')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(1, $parameters[1]);
                return true;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(2, $parameters[1]);
                return false;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(3, $parameters[1]);
                return true;
            }
        });
        self::assertFalse($this->frs_release_factory->deleteProjectReleases(1));
    }

    public function testDeleteProjectReleasesSuccess(): void
    {
        $release1 = ['release_id' => 1];
        $release2 = ['release_id' => 2];
        $release3 = ['release_id' => 3];
        $this->frs_release_factory->method('getFRSReleasesInfoListFromDb')->willReturn([$release1, $release2, $release3]);
        $matcher = $this->exactly(3);
        $this->frs_release_factory->expects($matcher)->method('delete_release')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(1, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(2, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(1, $parameters[0]);
                self::assertSame(3, $parameters[1]);
            }
            return true;
        });
        self::assertTrue($this->frs_release_factory->deleteProjectReleases(1));
    }
}
