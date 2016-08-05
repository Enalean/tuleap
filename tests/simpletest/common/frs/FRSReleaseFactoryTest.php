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
Mock::generatePartial(
    'FRSReleaseFactory',
    'FRSReleaseFactoryTestVersion',
    array(
        'getUserManager',
        'getPermissionsManager',
        '_getFRSPackageFactory',
        'getFRSReleasesInfoListFromDb',
        'delete_release',
        'userCanAdmin'
    )
);

class FRSReleaseFactoryTest extends TuleapTestCase
{
    protected $group_id   = 12;
    protected $package_id = 34;
    protected $release_id = 56;
    protected $user_id    = 78;

    private $user;
    private $frs_release_factory;
    private $user_manager;
    private $permission_manager;

    public function setUp()
    {
        $this->user                = mock('PFUser');
        $this->frs_release_factory = new FRSReleaseFactoryTestVersion($this);
        $this->user_manager        = new MockUserManager($this);
        $this->permission_manager  = new MockPermissionsManager($this);
        stub($this->user_manager)->getUserById()->returns($this->user);
        stub($this->frs_release_factory)->getUserManager()->returns($this->user_manager);
    }

    public function testAdminHasAlwaysAccessToReleases()
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWhenNoPermsOnRelease($canReadPackage)
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(false);

        $this->permission_manager->expectOnce('isPermissionExist', array($this->release_id, 'RELEASE_READ'));
        $this->permission_manager->setReturnValue('isPermissionExist', false);
        $this->frs_release_factory->setReturnValue('getPermissionsManager', $this->permission_manager);

        $frs_package_factory = new MockFRSPackageFactory($this);
        $frs_package_factory->expectOnce('userCanRead', array($this->group_id, $this->package_id, null));
        $frs_package_factory->setReturnValue('userCanRead', $canReadPackage);
        $this->frs_release_factory->setReturnValue('_getFRSPackageFactory', $frs_package_factory);

        return $this->frs_release_factory;
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCanReadPackage()
    {
        $this->_userCanReadWhenNoPermsOnRelease(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWhenNoPermsOnReleaseButCannotReadPackage()
    {
        $this->_userCanReadWhenNoPermsOnRelease(false);
        $this->assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    protected function _userCanReadWithSpecificPerms($can_read_release)
    {
        $this->user->expectOnce('getUgroups', array($this->group_id, array()));
        $this->user->setReturnValue('getUgroups', array(1,2,76));

        $this->permission_manager->expectOnce('isPermissionExist', array($this->release_id, 'RELEASE_READ'));
        $this->permission_manager->setReturnValue('isPermissionExist', true);
        $this->permission_manager->expectOnce('userHasPermission', array($this->release_id, 'RELEASE_READ', array(1,2,76)));
        $this->permission_manager->setReturnValue('userHasPermission', $can_read_release);
        $this->frs_release_factory->setReturnValue('getPermissionsManager', $this->permission_manager);
    }

    public function testUserCanReadWithSpecificPermsHasAccess()
    {
        $this->_userCanReadWithSpecificPerms(true);
        $this->assertTrue($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }

    public function testUserCanReadWithSpecificPermsHasNoAccess()
    {
        $this->_userCanReadWithSpecificPerms(false);
        $this->assertFalse($this->frs_release_factory->userCanRead($this->group_id, $this->package_id, $this->release_id, $this->user_id));
    }


    public function testAdminCanAlwaysUpdateReleases()
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(true);
        $this->assertTrue($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testMereMortalCannotUpdateReleases()
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(false);
        $this->assertFalse($this->frs_release_factory->userCanUpdate($this->group_id, $this->release_id, $this->user_id));
    }

    public function testAdminCanAlwaysCreateReleases()
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(true);
        $this->assertTrue($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testMereMortalCannotCreateReleases()
    {
        stub($this->frs_release_factory)->userCanAdmin()->returns(false);
        $this->assertFalse($this->frs_release_factory->userCanCreate($this->group_id, $this->user_id));
    }

    public function testDeleteProjectReleasesFail()
    {
        $release = array('release_id' => 1);
        $this->frs_release_factory->setReturnValue('getFRSReleasesInfoListFromDb', array($release, $release, $release));
        $this->frs_release_factory->setReturnValueAt(0, 'delete_release', true);
        $this->frs_release_factory->setReturnValueAt(1, 'delete_release', false);
        $this->frs_release_factory->setReturnValueAt(2, 'delete_release', true);
        $this->frs_release_factory->expectCallCount('delete_release', 3);
        $this->assertFalse($this->frs_release_factory->deleteProjectReleases(1));
    }

    public function testDeleteProjectReleasesSuccess()
    {
        $release = array('release_id' => 1);
        $this->frs_release_factory->setReturnValue('getFRSReleasesInfoListFromDb', array($release, $release, $release));
        $this->frs_release_factory->setReturnValueAt(0, 'delete_release', true);
        $this->frs_release_factory->setReturnValueAt(1, 'delete_release', true);
        $this->frs_release_factory->setReturnValueAt(2, 'delete_release', true);
        $this->frs_release_factory->expectCallCount('delete_release', 3);
        $this->assertTrue($this->frs_release_factory->deleteProjectReleases(1));
    }
}
