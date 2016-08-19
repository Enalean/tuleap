<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/frs/FRSPackageFactory.class.php');
Mock::generatePartial(
    'FRSPackageFactory',
    'FRSPackageFactoryTestVersion',
    array(
        '_getFRSPackageDao',
        'getUserManager',
        'getPermissionsManager',
        'getFRSPermissionManager',
        'getProjectManager',
        'getFRSPackagesFromDb',
        'delete_package'
    )
);
require_once('common/dao/include/DataAccess.class.php');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/dao/FRSPackageDao.class.php');
Mock::generatePartial('FRSPackageDao', 'FRSPackageDaoTestVersion', array('retrieve'));

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('PermissionsManager');
Mock::generate('FRSPackage');

class FRSPackageFactoryTest extends TuleapTestCase
{
    protected $group_id   = 12;
    protected $package_id = 34;
    protected $user_id    = 56;

    private $user;
    private $frs_package_factory;
    private $user_manager;
    private $permission_manager;
    private $frs_permission_manager;

    public function setUp()
    {
        $this->user                   = mock('PFUser');
        $this->frs_package_factory    = new FRSPackageFactoryTestVersion($this);
        $this->user_manager           = new MockUserManager($this);
        $this->permission_manager     = new MockPermissionsManager($this);
        $this->frs_permission_manager = mock('Tuleap\FRS\FRSPermissionManager');
        $this->project_manager        = mock('ProjectManager');

        stub($this->user_manager)->getUserById()->returns($this->user);
        stub($this->frs_package_factory)->getUserManager()->returns($this->user_manager);
        stub($this->frs_package_factory)->getFRSPermissionManager()->returns($this->frs_permission_manager);
        stub($this->frs_package_factory)->getProjectManager()->returns($this->project_manager);
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
        $dar1 = new MockDataAccessResult($this);
        $dar1->setReturnValue('isError', false);
        $dar1->setReturnValue('current', $packageArray1);
        $dar1->setReturnValueAt(0, 'valid', true);
        $dar1->setReturnValueAt(1, 'valid', false);
        $dar1->setReturnValue('rowCount', 1);

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
        $dar2 = new MockDataAccessResult($this);
        $dar2->setReturnValue('isError', false);
        $dar2->setReturnValue('current', $packageArray2);
        $dar2->setReturnValueAt(0, 'valid', true);
        $dar2->setReturnValueAt(1, 'valid', false);
        $dar2->setReturnValue('rowCount', 1);

        $dar3 = new MockDataAccessResult($this);
        $dar3->setReturnValue('isError', false);
        $dar3->setReturnValue('current', array());
        $dar3->setReturnValueAt(0, 'valid', true);
        $dar3->setReturnValueAt(1, 'valid', false);
        $dar3->setReturnValue('rowCount', 0);

        $dao = new FRSPackageDaoTestVersion();
        $dao->da = TestHelper::getPartialMock('DataAccess', array('DataAccess'));
        $dao->setReturnValue('retrieve', $dar1, array('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 1  ORDER BY rank DESC LIMIT 1'));
        $dao->setReturnValue('retrieve', $dar2, array('SELECT p.*  FROM frs_package AS p  WHERE  p.package_id = 2  AND p.status_id != 0  ORDER BY rank DESC LIMIT 1'));
        $dao->setReturnValue('retrieve', $dar3);

        $PackageFactory = new FRSPackageFactoryTestVersion();
        $PackageFactory->setReturnValue('_getFRSPackageDao', $dao);
        $this->assertEqual($PackageFactory->getFRSPackageFromDb(1, null, 0x0001), $package1);
        $this->assertEqual($PackageFactory->getFRSPackageFromDb(2), $package2);
    }

    public function testAdminHasAlwaysAccess()
    {
        stub($this->frs_permission_manager)->isAdmin()->returns(true);

        $this->assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    protected function _userCanReadWithSpecificPerms($can_read_package)
    {
        stub($this->frs_permission_manager)->userCanRead()->returns(true);
        stub($this->frs_permission_manager)->isAdmin()->returns(false);

        $this->user->expectOnce('getUgroups', array($this->group_id, array()));
        $this->user->setReturnValue('getUgroups', array(1,2,76));

        $this->permission_manager->setReturnValue('isPermissionExist', true);
        $this->permission_manager->expectOnce('userHasPermission', array($this->package_id, 'PACKAGE_READ', array(1,2,76)));
        $this->permission_manager->setReturnValue('userHasPermission', $can_read_package);
        $this->frs_package_factory->setReturnValue('getPermissionsManager', $this->permission_manager);

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
        stub($this->frs_permission_manager)->userCanRead()->returns(true);
        $this->user->expectOnce('getUgroups', array($this->group_id, array()));
        $this->user->setReturnValue('getUgroups', array(1,2,76));

        $this->permission_manager = new MockPermissionsManager($this);
        $this->permission_manager->expectOnce('isPermissionExist', array($this->package_id, 'PACKAGE_READ'));
        $this->permission_manager->setReturnValue('isPermissionExist', false);
        $this->permission_manager->expectOnce('userHasPermission', array($this->package_id, 'PACKAGE_READ', array(1,2,76)));
        $this->permission_manager->setReturnValue('userHasPermission', false);
        $this->frs_package_factory->setReturnValue('getPermissionsManager', $this->permission_manager);
        
        $this->assertTrue($this->frs_package_factory->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysUpdate()
    {
        stub($this->frs_permission_manager)->isAdmin()->returns(true);
        $this->assertTrue($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testMereMortalCannotUpdate()
    {
        stub($this->frs_permission_manager)->isAdmin()->returns(false);
        $this->assertFalse($this->frs_package_factory->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreate()
    {
        stub($this->frs_permission_manager)->isAdmin()->returns(true);
        $this->assertTrue($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreate()
    {
        stub($this->frs_permission_manager)->isAdmin()->returns(false);
        $this->assertFalse($this->frs_package_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectPackagesFail()
    {
        $packageFactory = new FRSPackageFactoryTestVersion();
        $package = new MockFRSPackage();
        $packageFactory->setReturnValue('getFRSPackagesFromDb', array($package, $package, $package));
        $packageFactory->setReturnValueAt(0, 'delete_package', true);
        $packageFactory->setReturnValueAt(1, 'delete_package', false);
        $packageFactory->setReturnValueAt(2, 'delete_package', true);
        $packageFactory->expectCallCount('delete_package', 3);
        $this->assertFalse($packageFactory->deleteProjectPackages(1));
    }

    public function testDeleteProjectPackagesSuccess()
    {
        $packageFactory = new FRSPackageFactoryTestVersion();
        $package = new MockFRSPackage();
        $packageFactory->setReturnValue('getFRSPackagesFromDb', array($package, $package, $package));
        $packageFactory->setReturnValueAt(0, 'delete_package', true);
        $packageFactory->setReturnValueAt(1, 'delete_package', true);
        $packageFactory->setReturnValueAt(2, 'delete_package', true);
        $packageFactory->expectCallCount('delete_package', 3);
        $this->assertTrue($packageFactory->deleteProjectPackages(1));
    }
}
