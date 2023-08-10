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

use FRSPackage;
use FRSPackageDao;
use FRSPackageFactory;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use TestHelper;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

final class FRSPackageFactoryTest extends TestCase
{
    protected int $group_id   = 12;
    protected int $package_id = 34;
    protected int $user_id    = 56;

    /**
     * @var MockObject&PFUser
     */
    private $user;
    /**
     * @var MockObject&FRSPackageFactory
     */
    private $frs_package_factory;
    /**
     * @var MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var MockObject&PermissionsManager
     */
    private $permission_manager;
    /**
     * @var MockObject&FRSPermissionManager
     */
    private $frs_permission_manager;
    /**
     * @var MockObject&ProjectManager
     */
    private $project_manager;

    public function setUp(): void
    {
        $this->user                   = $this->createMock(PFUser::class);
        $this->frs_package_factory    = $this->createPartialMock(FRSPackageFactory::class, [
            'getUserManager',
            'getFRSPermissionManager',
            'getProjectManager',
            'getPermissionsManager',
        ]);
        $this->user_manager           = $this->createMock(UserManager::class);
        $this->permission_manager     = $this->createMock(PermissionsManager::class);
        $this->frs_permission_manager = $this->createMock(FRSPermissionManager::class);
        $this->project_manager        = $this->createConfiguredMock(ProjectManager::class, ['getProject' => $this->createMock(Project::class)]);

        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->frs_package_factory->method('getUserManager')->willReturn($this->user_manager);
        $this->frs_package_factory->method('getFRSPermissionManager')->willReturn($this->frs_permission_manager);
        $this->frs_package_factory->method('getProjectManager')->willReturn($this->project_manager);
    }

    public function testGetFRSPackageFromDb(): void
    {
        $packageArray1       = ['package_id'       => 1,
            'group_id'         => 1,
            'name'             => 'pkg1',
            'status_id'        => 2,
            'rank'             => null,
            'approve_license'  => null,
            'data_array'       => null,
            'package_releases' => null,
            'error_state'      => null,
            'error_message'    => null,
        ];
        $frs_package_factory = new FRSPackageFactory();
        $package1            = $frs_package_factory->getFRSPackageFromArray($packageArray1);

        $packageArray2 = ['package_id'       => 2,
            'group_id'         => 2,
            'name'             => 'pkg2',
            'status_id'        => 1,
            'rank'             => null,
            'approve_license'  => null,
            'data_array'       => null,
            'package_releases' => null,
            'error_state'      => null,
            'error_message'    => null,
        ];
        $package2      = $frs_package_factory->getFRSPackageFromArray($packageArray2);

        $data_access = $this->createMock(LegacyDataAccessInterface::class);
        $data_access->method('query')
            ->withConsecutive(
                ['SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 1  ORDER BY `rank` DESC LIMIT 1', []],
                ['SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 2  AND p.status_id != 2  ORDER BY `rank` DESC LIMIT 1', []]
            )->willReturnOnConsecutiveCalls(
                TestHelper::arrayToDar($packageArray1),
                TestHelper::arrayToDar($packageArray2)
            );
        $data_access->method('escapeInt')->willReturnCallback(function (int $value) {
            return (string) $value;
        });
        $dao = new FRSPackageDao($data_access, FRSPackage::STATUS_DELETED);

        $PackageFactory = $this->createPartialMock(FRSPackageFactory::class, [
            '_getFRSPackageDao',
        ]);
        $PackageFactory->method('_getFRSPackageDao')->willReturn($dao);
        self::assertEquals($PackageFactory->getFRSPackageFromDb(1, null, 0x0001), $package1);
        self::assertEquals($PackageFactory->getFRSPackageFromDb(2), $package2);
    }

    public function testAdminHasAlwaysAccess(): void
    {
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);

        self::assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    private function userCanReadWithSpecificPerms($can_read_package): FRSPackageFactory
    {
        $this->frs_permission_manager->method('userCanRead')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        $this->user->expects(self::once())->method('getUgroups')->with($this->group_id, [])->willReturn([1, 2, 76]);

        $this->permission_manager->method('isPermissionExist')->willReturn(true);
        $this->permission_manager->expects(self::once())->method('userHasPermission')->with($this->package_id, 'PACKAGE_READ', [1, 2, 76])->willReturn($can_read_package);
        $this->frs_package_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        return $this->frs_package_factory;
    }

    public function testUserCanReadWithSpecificPermsHasAccess(): void
    {
        $this->frs_package_factory = $this->userCanReadWithSpecificPerms(true);
        self::assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess(): void
    {
        $this->frs_package_factory = $this->userCanReadWithSpecificPerms(false);
        self::assertFalse($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    /**
     * userHasPermissions return false but isPermissionExist return false because no permissions where set, so let user see the gems
     */
    public function testUserCanReadWhenNoPermissionsSet(): void
    {
        $this->frs_permission_manager->method('userCanRead')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin');
        $this->user->expects(self::once())->method('getUgroups')->with($this->group_id, [])->willReturn([1, 2, 76]);

        $this->permission_manager = $this->createMock(PermissionsManager::class);
        $this->permission_manager->expects(self::once())->method('isPermissionExist')->with($this->package_id, 'PACKAGE_READ')->willReturn(false);
        $this->permission_manager->expects(self::once())->method('userHasPermission')->with($this->package_id, 'PACKAGE_READ', [1, 2, 76])->willReturn(false);
        $this->frs_package_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        self::assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysUpdate(): void
    {
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertTrue($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testMereMortalCannotUpdate(): void
    {
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        self::assertFalse($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreate(): void
    {
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertTrue($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreate(): void
    {
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        self::assertFalse($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectPackagesFail(): void
    {
        $packageFactory = $this->createPartialMock(FRSPackageFactory::class, [
            'getFRSPackagesFromDb',
            'delete_package',
        ]);
        $package        = $this->createMock(FRSPackage::class);
        $package->method('getPackageID');
        $packageFactory->method('getFRSPackagesFromDb')->willReturn([$package, $package, $package]);
        $packageFactory->method('delete_package')->willReturnOnConsecutiveCalls(true, false, true);
        self::assertFalse($packageFactory->deleteProjectPackages(1));
    }

    public function testDeleteProjectPackagesSuccess(): void
    {
        $packageFactory = $this->createPartialMock(FRSPackageFactory::class, [
            'getFRSPackagesFromDb',
            'delete_package',
        ]);
        $package        = $this->createMock(FRSPackage::class);
        $package->method('getPackageID');
        $packageFactory->method('getFRSPackagesFromDb')->willReturn([$package, $package, $package]);
        $packageFactory->expects(self::exactly(3))->method('delete_package')->willReturn(true);
        self::assertTrue($packageFactory->deleteProjectPackages(1));
    }
}
