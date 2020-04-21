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

class FRSPackageFactoryTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $group_id   = 12;
    protected $package_id = 34;
    protected $user_id    = 56;

    private $user;
    private $frs_package_factory;
    private $user_manager;
    private $permission_manager;
    private $frs_permission_manager;

    public function setUp(): void
    {
        $this->user                   = \Mockery::spy(PFUser::class);
        $this->frs_package_factory    = \Mockery::mock(FRSPackageFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->user_manager           = \Mockery::spy(UserManager::class);
        $this->permission_manager     = \Mockery::spy(PermissionsManager::class);
        $this->frs_permission_manager = \Mockery::spy(Tuleap\FRS\FRSPermissionManager::class);
        $this->project_manager        = \Mockery::spy(ProjectManager::class, ['getProject' => \Mockery::spy(Project::class)]);

        $this->user_manager->shouldReceive('getUserById')->andReturns($this->user);
        $this->frs_package_factory->shouldReceive('getUserManager')->andReturns($this->user_manager);
        $this->frs_package_factory->shouldReceive('getFRSPermissionManager')->andReturns($this->frs_permission_manager);
        $this->frs_package_factory->shouldReceive('getProjectManager')->andReturns($this->project_manager);
    }

    public function testGetFRSPackageFromDb()
    {
        $packageArray1 = array('package_id'       => 1,
                               'group_id'         => 1,
                               'name'             => 'pkg1',
                               'status_id'        => 2,
                               'rank'             => null,
                               'approve_license'  => null,
                               'data_array'       => null,
                               'package_releases' => null,
                               'error_state'      => null,
                               'error_message'    => null
                               );
        $package1 = FRSPackageFactory::getFRSPackageFromArray($packageArray1);

        $packageArray2 = array('package_id'       => 2,
                               'group_id'         => 2,
                               'name'             => 'pkg2',
                               'status_id'        => 1,
                               'rank'             => null,
                               'approve_license'  => null,
                               'data_array'       => null,
                               'package_releases' => null,
                               'error_state'      => null,
                               'error_message'    => null
                               );
        $package2 = FRSPackageFactory::getFRSPackageFromArray($packageArray2);

        $data_access = \Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_access->shouldReceive('query')
            ->with('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 1  ORDER BY rank DESC LIMIT 1', [])
            ->andReturns(TestHelper::arrayToDar($packageArray1));
        $data_access->shouldReceive('query')
            ->with('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 2  AND p.status_id != 2  ORDER BY rank DESC LIMIT 1', [])
            ->andReturns(TestHelper::arrayToDar($packageArray2));
        $data_access->shouldReceive('escapeInt')->andReturnUsing(function ($value) {
            return $value;
        });
        $dao = new FRSPackageDao($data_access, FRSPackage::STATUS_DELETED);

        $PackageFactory = \Mockery::mock(FRSPackageFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $PackageFactory->shouldReceive('_getFRSPackageDao')->andReturns($dao);
        $this->assertEquals($PackageFactory->getFRSPackageFromDb(1, null, 0x0001), $package1);
        $this->assertEquals($PackageFactory->getFRSPackageFromDb(2), $package2);
    }

    public function testAdminHasAlwaysAccess()
    {
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(true);

        $this->assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    protected function _userCanReadWithSpecificPerms($can_read_package)
    {
        $this->frs_permission_manager->shouldReceive('userCanRead')->andReturns(true);
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(false);
        $this->user->shouldReceive('getUgroups')->with($this->group_id, array())->once()->andReturns(array(1,2,76));

        $this->permission_manager->shouldReceive('isPermissionExist')->andReturns(true);
        $this->permission_manager->shouldReceive('userHasPermission')->with($this->package_id, 'PACKAGE_READ', array(1,2,76))->once()->andReturns($can_read_package);
        $this->frs_package_factory->shouldReceive('getPermissionsManager')->andReturns($this->permission_manager);

        return $this->frs_package_factory;
    }

    public function testUserCanReadWithSpecificPermsHasAccess()
    {
        $this->frs_package_factory = $this->_userCanReadWithSpecificPerms(true);
        $this->assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess()
    {
        $this->frs_package_factory = $this->_userCanReadWithSpecificPerms(false);
        $this->assertFalse($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    /**
     * userHasPermissions return false but isPermissionExist return false because no permissions where set, so let user see the gems
     */
    public function testUserCanReadWhenNoPermissionsSet()
    {
        $this->frs_permission_manager->shouldReceive('userCanRead')->andReturns(true);
        $this->user->shouldReceive('getUgroups')->with($this->group_id, array())->once()->andReturns(array(1,2,76));

        $this->permission_manager = \Mockery::spy(PermissionsManager::class);
        $this->permission_manager->shouldReceive('isPermissionExist')->with($this->package_id, 'PACKAGE_READ')->once()->andReturns(false);
        $this->permission_manager->shouldReceive('userHasPermission')->with($this->package_id, 'PACKAGE_READ', array(1,2,76))->once()->andReturns(false);
        $this->frs_package_factory->shouldReceive('getPermissionsManager')->andReturns($this->permission_manager);

        $this->assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysUpdate()
    {
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(true);
        $this->assertTrue($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testMereMortalCannotUpdate()
    {
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(false);
        $this->assertFalse($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreate()
    {
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(true);
        $this->assertTrue($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreate()
    {
        $this->frs_permission_manager->shouldReceive('isAdmin')->andReturns(false);
        $this->assertFalse($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectPackagesFail()
    {
        $packageFactory = \Mockery::mock(FRSPackageFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = \Mockery::spy(FRSPackage::class);
        $packageFactory->shouldReceive('getFRSPackagesFromDb')->andReturns(array($package, $package, $package));
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(true);
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(false);
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(true);
        $this->assertFalse($packageFactory->deleteProjectPackages(1));
    }

    public function testDeleteProjectPackagesSuccess()
    {
        $packageFactory = \Mockery::mock(FRSPackageFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package = \Mockery::spy(FRSPackage::class);
        $packageFactory->shouldReceive('getFRSPackagesFromDb')->andReturns(array($package, $package, $package));
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(true);
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(true);
        $packageFactory->shouldReceive('delete_package')->once()->andReturns(true);
        $this->assertTrue($packageFactory->deleteProjectPackages(1));
    }
}
