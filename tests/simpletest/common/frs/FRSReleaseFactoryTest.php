<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/frs/FRSReleaseFactory.class.php');

Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generate('PermissionsManager');
Mock::generate('FRSPackageFactory');
Mock::generatePartial('FRSReleaseFactory', 'FRSReleaseFactoryTestVersion', array('getUserManager', 'getPermissionsManager', '_getFRSPackageFactory', 'getFRSReleasesInfoListFromDb', 'delete_release'));

class FRSReleaseFactoryTest extends UnitTestCase {
    protected $group_id   = 12;
    protected $package_id = 34;
    protected $release_id = 56;
    protected $user_id    = 78;

    //
    // userCanRead
    //

    function testFileReleaseAdminHasAlwaysAccessToReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    function testProjectAdminHasAlwaysAccessToReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    function testSiteAdminHasAlwaysAccessToReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWhenNoPermsOnRelease($canReadPackage) {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        // User
        $user = mock('PFUser');
        $user->setReturnValue('getId', $this->user_id);
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        // Perms
        $pm = new MockPermissionsManager($this);
        $pm->expectOnce('isPermissionExist', array($this->release_id, 'RELEASE_READ'));
        $pm->setReturnValue('isPermissionExist', false);
        $frsrf->setReturnValue('getPermissionsManager', $pm);
        
        // PackageFactory
        $frspf = new MockFRSPackageFactory($this);
        $frspf->expectOnce('userCanRead', array($this->group_id, $this->package_id, $this->user_id));
        $frspf->setReturnValue('userCanRead', $canReadPackage);
        $frsrf->setReturnValue('_getFRSPackageFactory', $frspf);

        return $frsrf;
    }

    function testUserCanReadWhenNoPermsOnReleaseButCanReadPackage() {
        $frsrf = $this->_userCanReadWhenNoPermsOnRelease(true);
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    function testUserCanReadWhenNoPermsOnReleaseButCannotReadPackage() {
        $frsrf = $this->_userCanReadWhenNoPermsOnRelease(false);
        $this->assertFalse($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWithSpecificPerms($canReadRelease) {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        // User
        $user = mock('PFUser');
        $user->expectOnce('getUgroups', array($this->group_id, array()));
        $user->setReturnValue('getUgroups', array(1,2,76));
        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        // Perms
        $pm = new MockPermissionsManager($this);
        $pm->expectOnce('isPermissionExist', array($this->release_id, 'RELEASE_READ'));
        $pm->setReturnValue('isPermissionExist', true);
        $pm->expectOnce('userHasPermission', array($this->release_id, 'RELEASE_READ', array(1,2,76)));
        $pm->setReturnValue('userHasPermission', $canReadRelease);
        $frsrf->setReturnValue('getPermissionsManager', $pm);
        
        return $frsrf;
    }

    function testUserCanReadWithSpecificPermsHasAccess() {
        $frsrf = $this->_userCanReadWithSpecificPerms(true);
        $this->assertTrue($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    function testUserCanReadWithSpecificPermsHasNoAccess() {
        $frsrf = $this->_userCanReadWithSpecificPerms(false);
        $this->assertFalse($frsrf->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    //
    // userCanUpdate
    //
    function testFileReleaseAdminCanAlwaysUpdateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    function testProjectAdminCanAlwaysUpdateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    function testSiteAdminCanAlwaysUpdateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    function testMereMortalCannotUpdateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertFalse($frsrf->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    //
    // userCanCreate
    //

    function testFileReleaseAdminCanAlwaysCreateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'R2'));

        $um = new MockUserManager($this);
        $um->expectOnce('getUserById', array($this->user_id));
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanCreate($this->group_id, $this->user_id));
    }

    function testProjectAdminCanAlwaysCreateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', false);
        $user->setReturnValue('isMember', true, array($this->group_id, 'A'));

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanCreate($this->group_id, $this->user_id));
    }

    function testSiteAdminCanAlwaysCreateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');
        $user->setReturnValue('isSuperUser', true);

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertTrue($frsrf->userCanCreate($this->group_id, $this->user_id));
    }

    function testMereMortalCannotCreateReleases() {
        // Setup test
        $frsrf = new FRSReleaseFactoryTestVersion($this);

        $user = mock('PFUser');

        $um = new MockUserManager($this);
        $um->setReturnValue('getUserById', $user);
        $frsrf->setReturnValue('getUserManager', $um);
        
        $this->assertFalse($frsrf->userCanCreate($this->group_id, $this->user_id));
    }

    function testDeleteProjectReleasesFail() {
        $releaseFactory = new FRSReleaseFactoryTestVersion();
        $release = array('release_id' => 1);
        $releaseFactory->setReturnValue('getFRSReleasesInfoListFromDb', array($release, $release, $release));
        $releaseFactory->setReturnValueAt(0, 'delete_release', true);
        $releaseFactory->setReturnValueAt(1, 'delete_release', false);
        $releaseFactory->setReturnValueAt(2, 'delete_release', true);
        $releaseFactory->expectCallCount('delete_release', 3);
        $this->assertFalse($releaseFactory->deleteProjectReleases(1));
    }

    function testDeleteProjectReleasesSuccess() {
        $releaseFactory = new FRSReleaseFactoryTestVersion();
        $release = array('release_id' => 1);
        $releaseFactory->setReturnValue('getFRSReleasesInfoListFromDb', array($release, $release, $release));
        $releaseFactory->setReturnValueAt(0, 'delete_release', true);
        $releaseFactory->setReturnValueAt(1, 'delete_release', true);
        $releaseFactory->setReturnValueAt(2, 'delete_release', true);
        $releaseFactory->expectCallCount('delete_release', 3);
        $this->assertTrue($releaseFactory->deleteProjectReleases(1));
    }

}

?>