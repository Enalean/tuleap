<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('FRSPackageFactory');
Mock::generate('FRSReleaseFactory');
Mock::generate('PFUser');
Mock::generate('Project');
Mock::generate('FRSPackage');
Mock::generate('FRSRelease');
Mock::generate('WebDAVFRSRelease');
Mock::generate('PermissionsManager');
Mock::generate('WebDAVUtils');
Mock::generatePartial(
    'WebDAVFRSPackage',
    'WebDAVFRSPackageTestVersion',
array('getPackage', 'getPackageId', 'getProject', 'getUtils', 'getReleaseList', 'getFRSReleaseFromName', 'getWebDAVRelease', 'userIsAdmin', 'userCanWrite')
);

/**
 * This is the unit test of WebDAVFRSPackage
 */
class WebDAVFRSPackageTest extends TuleapTestCase {

    /**
     * Testing when The package have no releases
     */
    function testGetChildrenNoReleases() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getReleaseList', array());

        $this->assertEqual($webDAVFRSPackage->getChildren(), array());

    }

    /**
     * Testing when the user can't read the release
     */
    function testGetChildrenUserCanNotRead() {

        $release = new MockWebDAVFRSRelease();
        $release->setReturnValue('userCanRead', false);

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $release);

        $FRSRelease = new MockFRSRelease();
        $webDAVFRSPackage->setReturnValue('getReleaseList', array($FRSRelease));

        $this->assertEqual($webDAVFRSPackage->getChildren(), array());

    }

    /**
     * Testing when the user can read the release
     */
    function testGetChildrenUserCanRead() {

        $release = new MockWebDAVFRSRelease();
        $release->setReturnValue('userCanRead', true);

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $release);

        $FRSRelease = new MockFRSRelease();
        $webDAVFRSPackage->setReturnValue('getReleaseList', array($FRSRelease));

        $this->assertEqual($webDAVFRSPackage->getChildren(), array($release));

    }

    /**
     * Testing when the release doesn't exist
     */
    function testGetChildFailWithNotExist() {

        $FRSRelease = new MockFRSRelease();
        $WebDAVRelease = new MockWebDAVFRSRelease();
        $WebDAVRelease->setReturnValue('exist', false);

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromName', $FRSRelease);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $WebDAVRelease);
        $utils = new MockWebDAVUtils();
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId());

    }

    /**
     * Testing when the user can't read the release
     */
    function testGetChildFailWithUserCanNotRead() {

        $FRSRelease = new MockFRSRelease();
        $WebDAVRelease = new MockWebDAVFRSRelease();
        $WebDAVRelease->setReturnValue('exist', true);
        $WebDAVRelease->setReturnValue('userCanRead', false);

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromName', $FRSRelease);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $WebDAVRelease);
        $utils = new MockWebDAVUtils();
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId());

    }

    /**
     * Testing when the release exist and user can read
     */
    function testSucceedGetChild() {

        $FRSRelease = new MockFRSRelease();
        $WebDAVRelease = new MockWebDAVFRSRelease();
        $WebDAVRelease->setReturnValue('exist', true);
        $WebDAVRelease->setReturnValue('userCanRead', true);

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromName', $FRSRelease);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $WebDAVRelease);
        $utils = new MockWebDAVUtils();
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);

        $this->assertEqual($webDAVFRSPackage->getChild($WebDAVRelease->getReleaseId()), $WebDAVRelease);

    }

    /**
     * Testing when the package is deleted and the user have no permissions
     */
    function testUserCanReadFailurePackageDeletedUserHaveNoPermissions() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when the release is active and user can not read
     */
    function testUserCanReadFailureActiveUserCanNotRead() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', true);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when the release is not active and the user can read
     */
    function testUserCanReadFailureDeletedUserCanRead() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when the release is active and the user can read
     */
    function testUserCanReadSucceedActiveUserCanRead() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', true);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), true);

    }

    /**
     * Testing when the release is hidden and the user is not admin and can not read
     */
    function testUserCanReadFailureHiddenNotAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', true);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    function testUserCanReadFailureHiddenNotAdminUserCanRead() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', true);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', false);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when release is deleted and the user is admin
     */
    function testUserCanReadFailureDeletedUserIsAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    function testUserCanReadFailureAdminHaveNoPermission() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', true);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    function testUserCanReadFailureDeletedCanReadIsAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), false);

    }

    /**
     * Testing when release is active and user can read and is admin
     */
    function testUserCanReadSucceedActiveUserCanReadIsAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', true);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', false);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), true);

    }

    /**
     * Testing when release is hidden and user is admin
     */
    function testUserCanReadSucceedHiddenUserIsAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', false);
        $package->setReturnValue('isHidden', true);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), true);

    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    function testUserCanReadSucceedHiddenUserIsAdminCanRead() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $package = new MockFRSPackage();
        $package->setReturnValue('isActive', false);
        $package->setReturnValue('userCanRead', true);
        $package->setReturnValue('isHidden', true);
        $webDAVFRSPackage->setReturnValue('userIsAdmin', true);

        $webDAVFRSPackage->setReturnValue('getPackage', $package);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), true);

    }

    /**
     * Testing delete when user is not admin
     */
    function testDeleteFailWithUserNotAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();

    }

    /**
     * Testing delete when the package is not empty
     */
    function testDeleteFailWithPackageNotEmpty() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('userCanWrite', true);
        $release = new MockFRSRelease();
        $webDAVFRSPackage->setReturnValue('getReleaseList', array($release));
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();

    }

    /**
     * Testing delete when package doesn't exist
     */
    function testDeletePackageNotExist() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('userCanWrite', true);
        $webDAVFRSPackage->setReturnValue('getReleaseList', array());
        $packageFactory = new MockFRSPackageFactory();
        $packageFactory->setReturnValue('delete_package', 0);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getPackageFactory', $packageFactory);
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $webDAVFRSPackage->setReturnValue('getProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->delete();

    }

    /**
     * Testing succeeded delete
     */
    function testDeleteSucceede() {

    $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
    $webDAVFRSPackage->setReturnValue('userCanWrite', true);
    $webDAVFRSPackage->setReturnValue('getReleaseList', array());
    $packageFactory = new MockFRSPackageFactory();
    $packageFactory->setReturnValue('delete_package', 1);
    $utils = new MockWebDAVUtils();
    $utils->setReturnValue('getPackageFactory', $packageFactory);
    $webDAVFRSPackage->setReturnValue('getUtils', $utils);
    $project = new MockProject();
    $webDAVFRSPackage->setReturnValue('getProject', $project);

    $webDAVFRSPackage->delete();

    }

    /**
     * Testing setName when user is not admin
     */
    function testSetNameFailWithUserNotAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('userCanWrite', false);
        $packageFactory = new MockFRSPackageFactory();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getPackageFactory', $packageFactory);
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $webDAVFRSPackage->setReturnValue('getProject', $project);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->setName('newName');

    }

    /**
     * Testing setName when name already exist
     */
    function testSetNameFailWithNameExist() {

    $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
    $webDAVFRSPackage->setReturnValue('userCanWrite', true);
    $packageFactory = new MockFRSPackageFactory();
    $packageFactory->setReturnValue('isPackageNameExist', true);
    $utils = new MockWebDAVUtils();
    $utils->setReturnValue('getPackageFactory', $packageFactory);
    $webDAVFRSPackage->setReturnValue('getUtils', $utils);
    $project = new MockProject();
    $webDAVFRSPackage->setReturnValue('getProject', $project);
    $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

    $webDAVFRSPackage->setName('newName');

    }

    /**
     * Testing setName succeede
     */
    function testSetNameSucceede() {

    $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
    $webDAVFRSPackage->setReturnValue('userCanWrite', true);
    $packageFactory = new MockFRSPackageFactory();
    $packageFactory->setReturnValue('isPackageNameExist', false);
    $utils = new MockWebDAVUtils();
    $utils->setReturnValue('getPackageFactory', $packageFactory);
    $webDAVFRSPackage->setReturnValue('getUtils', $utils);
    $project = new MockProject();
    $webDAVFRSPackage->setReturnValue('getProject', $project);
    $package = new MockFRSPackage();
    $webDAVFRSPackage->setReturnValue('getPackage', $package);

    $webDAVFRSPackage->setName('newName');

    }

    /**
     * Testing creation of release when user is not admin
     */
    function testCreateDirectoryFailWithUserNotAdmin() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);

        $webDAVFRSPackage->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSPackage->createDirectory('release');

    }

    /**
     * Testing creation of release when the name already exist
     */
    function testCreateDirectoryFailWithNameExist() {

        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);

        $webDAVFRSPackage->setReturnValue('userCanWrite', true);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('isReleaseNameExist', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRSPackage->createDirectory('release');

    }

    /**
     * Testing creation of release succeed
     */
    function testCreateDirectorysucceed() {

        // Values we expect for the package to create
        $refPackageToCreate = array('name'       => 'release',
                                    'package_id' => 42,
                                    'notes'      => '',
                                    'changes'    => '',
                                    'status_id'  => 1);
        // Values we expect for the package once created
        $refPackage = $refPackageToCreate;
        $refPackage['release_id'] = 15;


        $webDAVFRSPackage = new WebDAVFRSPackageTestVersion($this);
        $webDAVFRSPackage->setReturnValue('getPackageId', 42);

        $webDAVFRSPackage->setReturnValue('userCanWrite', true);

        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $frsrf->expectOnce('create', array($refPackageToCreate));
        $frsrf->setReturnValue('create', 15);
        $frsrf->expectOnce('setDefaultPermissions', array(new FRSRelease($refPackage)));

        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);

        $pm = new MockPermissionsManager();
        $utils->setReturnValue('getPermissionsManager', $pm);

        $webDAVFRSPackage->setReturnValue('getUtils', $utils);

        $webDAVFRSPackage->createDirectory('release');
    }
}
