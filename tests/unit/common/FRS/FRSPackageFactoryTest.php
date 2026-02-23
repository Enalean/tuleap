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

use DataAccessResult;
use ErrorDataAccessResult;
use FRSPackage;
use FRSPackageDao;
use FRSPackageFactory;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ProjectManager;
use ProjectUGroup;
use TestHelper;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\FakeDataAccessResult;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSPackageFactoryTest extends TestCase
{
    protected int $group_id   = 12;
    protected int $package_id = 34;
    protected int $user_id    = 56;

    private PFUser&Stub $user;
    private FRSPackageFactory&Stub $frs_package_factory;
    private UserManager&Stub $user_manager;
    private PermissionsManager&Stub $permission_manager;
    private FRSPermissionManager&Stub $frs_permission_manager;
    private ProjectManager&Stub $project_manager;

    #[\Override]
    public function setUp(): void
    {
        $this->user                   = $this->createStub(PFUser::class);
        $this->frs_package_factory    = $this->getStubBuilder(FRSPackageFactory::class)
            ->onlyMethods([
                'getUserManager',
                'getFRSPermissionManager',
                'getProjectManager',
                'getPermissionsManager',
                '_getFRSPackageDao',
            ])
            ->getStub();
        $this->user_manager           = $this->createStub(UserManager::class);
        $this->permission_manager     = $this->createStub(PermissionsManager::class);
        $this->frs_permission_manager = $this->createStub(FRSPermissionManager::class);
        $this->project_manager        = $this->createConfiguredStub(ProjectManager::class, ['getProject' => $this->createStub(Project::class)]);

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
        $matcher     = $this->exactly(2);
        $data_access->expects($matcher)->method('query')->willReturnCallback(function (...$parameters) use ($matcher, $packageArray1, $packageArray2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 1  ORDER BY `rank` DESC LIMIT 1', $parameters[0]);
                self::assertSame([], $parameters[1]);
                return TestHelper::arrayToDar($packageArray1);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 2  AND p.status_id != 2  ORDER BY `rank` DESC LIMIT 1', $parameters[0]);
                self::assertSame([], $parameters[1]);
                return TestHelper::arrayToDar($packageArray2);
            }
        });
        $data_access->method('escapeInt')->willReturnCallback(function (int $value) {
            return (string) $value;
        });
        $dao = new FRSPackageDao($data_access, FRSPackage::STATUS_DELETED);

        $package_factory = $this->getStubBuilder(FRSPackageFactory::class)
            ->onlyMethods(['_getFRSPackageDao'])
            ->getStub();
        $package_factory->method('_getFRSPackageDao')->willReturn($dao);
        self::assertEquals($package_factory->getFRSPackageFromDb(1, null, 0x0001), $package1);
        self::assertEquals($package_factory->getFRSPackageFromDb(2), $package2);
    }

    private function stubDBResponsesForPackages(int $package_status, int ...$package_ids): void
    {
        $dao = new class ($package_status, $this->group_id, $package_ids) extends FRSPackageDao
        {
            /**
             * @param int[] $package_ids
             */
            public function __construct(private int $package_status, private int $project_id, private readonly array $package_ids)
            {
                // Do nothing on purposes
            }

            #[\Override]
            public function searchById(mixed $id, mixed $extra_flags = 0): DataAccessResult
            {
                if (in_array($id, $this->package_ids)) {
                    return new FakeDataAccessResult([['package_id' => $id, 'group_id' => $this->project_id, 'status_id' => $this->package_status]]);
                }
                return new ErrorDataAccessResult();
            }
        };
        $this->frs_package_factory->method('_getFRSPackageDao')->willReturn($dao);
    }

    private function userCanReadWithSpecificPerms($can_read_package): FRSPackageFactory
    {
        $this->frs_permission_manager->method('userCanRead')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        $this->user->method('getUgroups')->willReturn([1, 2, 76]);

        $this->permission_manager->method('isPermissionExist')->willReturn(true);
        $this->permission_manager->method('userHasPermission')->willReturn($can_read_package);
        $this->frs_package_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        return $this->frs_package_factory;
    }

    public function testUserCanReadWithSpecificPermsHasAccess(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $this->frs_package_factory = $this->userCanReadWithSpecificPerms(true);
        self::assertTrue($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $this->frs_package_factory = $this->userCanReadWithSpecificPerms(false);
        self::assertFalse($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    /**
     * userHasPermissions return false but isPermissionExist return false because no permissions where set, so let user see the gems
     */
    public function testUserCanReadWhenNoPermissionsSet(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $this->frs_permission_manager->method('userCanRead')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin');
        $this->user->method('getUgroups')->willReturn([1, 2, 76]);

        $this->permission_manager = $this->createMock(PermissionsManager::class);
        $this->permission_manager->expects($this->once())->method('isPermissionExist')->with($this->package_id, 'PACKAGE_READ')->willReturn(false);
        $this->permission_manager->expects($this->once())->method('userHasPermission')->with($this->package_id, 'PACKAGE_READ', [1, 2, 76])->willReturn(false);
        $this->frs_package_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        self::assertTrue($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testHiddenPackagesAreNotReadableByNonAdminUsers(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_HIDDEN, $this->package_id);
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        self::assertFalse($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testHiddenPackagesAreReadableByAdminUsers(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_HIDDEN, $this->package_id);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertTrue($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testPackageWithGivenPermissionCanStillBeReadByProjectAdmin(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $this->frs_permission_manager->method('userCanRead')->willReturn(true);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);

        $this->user->method('getUgroups')->willReturn([ProjectUGroup::PROJECT_ADMIN]);

        $this->permission_manager = $this->createStub(PermissionsManager::class);
        $this->permission_manager->method('userHasPermission')->willReturn(false);
        $this->permission_manager->method('isPermissionExist')->willReturn(true);
        $this->frs_package_factory->method('getPermissionsManager')->willReturn($this->permission_manager);

        self::assertTrue($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testDeletedPackagesCannotBeRead(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_DELETED, $this->package_id);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertFalse($this->frs_package_factory->userCanRead($this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysUpdate(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $package = $this->frs_package_factory->getFRSPackageFromDb($this->package_id);
        assert($package !== null);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertTrue($this->frs_package_factory->userCanUpdate($package, UserTestBuilder::buildWithId($this->user_id)));
    }

    public function testMereMortalCannotUpdate(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $package = $this->frs_package_factory->getFRSPackageFromDb($this->package_id);
        assert($package !== null);
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        self::assertFalse($this->frs_package_factory->userCanUpdate($package, UserTestBuilder::buildWithId($this->user_id)));
    }

    public function testAdminCanAlwaysCreate(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $package = $this->frs_package_factory->getFRSPackageFromDb($this->package_id);
        assert($package !== null);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertTrue($this->frs_package_factory->userCanUpdate($package, UserTestBuilder::buildWithId($this->user_id)));
    }

    public function testMereMortalCannotCreate(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $this->frs_permission_manager->method('isAdmin')->willReturn(false);
        self::assertFalse($this->frs_package_factory->userCanCreate($this->group_id, UserTestBuilder::buildWithId($this->user_id)));
    }

    public function testNonActiveOrHiddenPackagesCannotBeUpdated(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_DELETED, $this->package_id);
        $package = $this->frs_package_factory->getFRSPackageFromDb($this->package_id);
        assert($package !== null);
        $this->frs_permission_manager->method('isAdmin')->willReturn(true);
        self::assertFalse($this->frs_package_factory->userCanUpdate($package, UserTestBuilder::buildWithId($this->user_id)));
    }

    public function testDeleteProjectPackagesFail(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $package_factory = $this->getStubBuilder(FRSPackageFactory::class)
            ->onlyMethods(['getFRSPackagesFromDb', 'deleteWithoutPermissionsVerification'])
            ->getStub();
        $package         = $this->createStub(FRSPackage::class);
        $package->method('getPackageID');
        $package_factory->method('getFRSPackagesFromDb')->willReturn([$package, $package, $package]);
        $package_factory->method('deleteWithoutPermissionsVerification')->willReturnOnConsecutiveCalls(true, false, true);
        self::assertFalse($package_factory->deleteProjectPackages(1));
    }

    public function testDeleteProjectPackagesSuccess(): void
    {
        $this->stubDBResponsesForPackages(FRSPackage::STATUS_ACTIVE, $this->package_id);
        $packageFactory = $this->createPartialMock(FRSPackageFactory::class, [
            'getFRSPackagesFromDb',
            'deleteWithoutPermissionsVerification',
        ]);
        $package        = $this->createStub(FRSPackage::class);
        $package->method('getPackageID');
        $packageFactory->method('getFRSPackagesFromDb')->willReturn([$package, $package, $package]);
        $packageFactory->expects($this->exactly(3))->method('deleteWithoutPermissionsVerification')->willReturn(true);
        self::assertTrue($packageFactory->deleteProjectPackages(1));
    }
}
