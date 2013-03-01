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
Mock::generatePartial('FRSPackageFactory', 'FRSPackageFactoryTestVersion', array('_getFRSPackageDao', 'getUserManager', 'getPermissionsManager', 'getFRSPackagesFromDb', 'delete_package'));
require_once('common/dao/include/DataAccess.class.php');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/dao/FRSPackageDao.class.php');
Mock::generatePartial('FRSPackageDao', 'FRSPackageDaoTestVersion', array('retrieve'));

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('PermissionsManager');
Mock::generate('FRSPackage');

class FRSPackageFactoryTest extends UnitTestCase {
    protected $group_id   = 12;
    protected $package_id = 34;
    protected $user_id    = 56;

    function testGetFRSPackageFromDb() {
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

    //
    // userCanRead
    //

    function testFileAdminHasAlwaysAccess() {
        // Setup test
        $frsrf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    function testProjectAdminHasAlwaysAccess() {
        // Setup test
        $frsrf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    function testSiteAdminHasAlwaysAccess() {
        // Setup test
        $frsrf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

   protected function _userCanReadWithSpecificPerms($canReadPackage) {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        // User
        $user = mock('PFUser');
        $user->expectOnce('getUgroups', array($this->group_id, array()));
        $user->setReturnValue('getUgroups', array(1,2,76));
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        // Perms
        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('isPermissionExist', true);
        $pm->expectOnce('userHasPermission', array($this->package_id, 'PACKAGE_READ', array(1,2,76)));
        $pm->setReturnValue('userHasPermission', $canReadPackage);
        $frspf->setReturnValue('getPermissionsManager', $pm);
        
        return $frspf;
    }

    function testUserCanReadWithSpecificPermsHasAccess() {
        $frspf = $this->_userCanReadWithSpecificPerms(true);
        $this->assertTrue($frspf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }
    
    function testUserCanReadWithSpecificPermsHasNoAccess() {
        $frspf = $this->_userCanReadWithSpecificPerms(false);
        $this->assertFalse($frspf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }

    /**
     * userHasPermissions return false but isPermissionExist return false because no permissions where set, so let user see the gems
     */
    function testUserCanReadWhenNoPermissionsSet() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        // User
        $user = mock('PFUser');
        $user->expectOnce('getUgroups', array($this->group_id, array()));
        $user->setReturnValue('getUgroups', array(1,2,76));
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        // Perms
        $pm = new MockPermissionsManager($this);
        $pm->expectOnce('isPermissionExist', array($this->package_id, 'PACKAGE_READ'));
        $pm->setReturnValue('isPermissionExist', false);
        $pm->expectOnce('userHasPermission', array($this->package_id, 'PACKAGE_READ', array(1,2,76)));
        $pm->setReturnValue('userHasPermission', false);
        $frspf->setReturnValue('getPermissionsManager', $pm);
        
        $this->assertTrue($frspf->userCanRead($this->group_id, $this->package_id, $this->user_id));
    }
    
    //
    // userCanUpdate
    //

    function testFileAdminCanAlwaysUpdate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    function testProjectAdminCanAlwaysUpdate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    function testSiteAdminCanAlwaysUpdate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    function testMereMortalCannotUpdate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertFalse($frspf->userCanUpdate($this->group_id, $this->package_id, $this->user_id));
    }

    //
    // userCanCreate
    //

    function testFileAdminCanAlwaysCreate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanCreate($this->group_id, $this->user_id));
    }

    function testProjectAdminCanAlwaysCreate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanCreate($this->group_id, $this->user_id));
    }

    function testSiteAdminCanAlwaysCreate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frspf->userCanCreate($this->group_id, $this->user_id));
    }

    function testMereMortalCannotCreate() {
        // Setup test
        $frspf = new FRSPackageFactoryTestVersion($this);

        $user = mock('PFUser');

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frspf->setReturnValue('getUserManager', $um);
        
        $this->assertFalse($frspf->userCanCreate($this->group_id, $this->user_id));
    }

    function testDeleteProjectPackagesFail() {
        $packageFactory = new FRSPackageFactoryTestVersion();
        $package = new MockFRSPackage();
        $packageFactory->setReturnValue('getFRSPackagesFromDb', array($package, $package, $package));
        $packageFactory->setReturnValueAt(0, 'delete_package', true);
        $packageFactory->setReturnValueAt(1, 'delete_package', false);
        $packageFactory->setReturnValueAt(2, 'delete_package', true);
        $packageFactory->expectCallCount('delete_package', 3);
        $this->assertFalse($packageFactory->deleteProjectPackages(1));
    }

    function testDeleteProjectPackagesSuccess() {
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
?>