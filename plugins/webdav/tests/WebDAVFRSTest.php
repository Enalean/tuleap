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
Mock::generate('PFUser');
Mock::generate('FRSPackage');
Mock::generate('FRSPackageFactory');
Mock::generate('PermissionsManager');
Mock::generate('WebDAVUtils');
Mock::generate('WebDAVFRSPackage');
Mock::generate('Project');
Mock::generatePartial(
    'WebDAVFRS',
    'WebDAVFRSTestVersion',
    array(
        'getGroupId',
        'getProject',
        'getUser',
        'getUtils',
        'getPackageList',
        'getFRSPackageFromName',
        'getWebDAVPackage',
        'userCanWrite',
        'getPermissionsManager'
    )
);

/**
 * This is the unit test of WebDAVProject
 */
class WebDAVFRSTest extends TuleapTestCase {

    public function setUp()
    {
        parent::setUp();
        $this->frs_permission_manager = mock('Tuleap\FRS\FRSPermissionManager');
    }

    /**
     * Testing when The project have no packages
     */
    function testGetChildrenNoPackages() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $webDAVFRS->setReturnValue('getPackageList', array());
        $this->assertEqual($webDAVFRS->getChildren(), array());
    }

    /**
     * Testing when the user can't read packages
     */
    function testGetChildrenUserCanNotRead() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $package = new MockWebDAVFRSPackage();
        $package->setReturnValue('userCanRead', false);
        $webDAVFRS->setReturnValue('getWebDAVPackage', $package);

        $FRSPackage = new MockFRSPackage();
        $webDAVFRS->setReturnValue('getPackageList', array($FRSPackage));

        $this->assertEqual($webDAVFRS->getChildren(), array());
    }

    /**
     * Testing when the user can read packages
     */
    function testGetChildrenUserCanRead() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $package = new MockWebDAVFRSPackage();
        $package->setReturnValue('userCanRead', true);

        $webDAVFRS->setReturnValue('getWebDAVPackage', $package);

        $FRSPackage = new MockFRSPackage();
        $webDAVFRS->setReturnValue('getPackageList', array($FRSPackage));

        $this->assertEqual($webDAVFRS->getChildren(), array($package));
    }

    /**
     * Testing when the package doesn't exist
     */
    function testGetChildFailWithNotExist() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', false);
        $webDAVFRS->setReturnValue('getFRSPackageFromName', $FRSPackage);
        $webDAVFRS->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $utils = new MockWebDAVUtils();
        $webDAVFRS->setReturnValue('getUtils', $utils);
        $webDAVFRS->getChild($WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the user can't read the package
     */
    function testGetChildFailWithUserCanNotRead() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', false);

        $webDAVFRS->setReturnValue('getFRSPackageFromName', $FRSPackage);
        $webDAVFRS->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $utils = new MockWebDAVUtils();
        $webDAVFRS->setReturnValue('getUtils', $utils);
        $webDAVFRS->getChild($WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the package exist and user can read
     */
    function testSucceedGetChild() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', true);

        $webDAVFRS->setReturnValue('getFRSPackageFromName', $FRSPackage);
        $webDAVFRS->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $utils = new MockWebDAVUtils();
        $webDAVFRS->setReturnValue('getUtils', $utils);
        $this->assertEqual($webDAVFRS->getChild($WebDAVPackage->getPackageId()), $WebDAVPackage);
    }

    /**
     * Testing creation of package when user is not admin
     */
    function testCreateDirectoryFailWithUserNotAdmin() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package when the name already exist
     */
    function testCreateDirectoryFailWithNameExist() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('userCanWrite', true);
        $frspf = new MockFRSPackageFactory();
        $frspf->setReturnValue('isPackageNameExist', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getPackageFactory', $frspf);
        $webDAVFRS->setReturnValue('getUtils', $utils);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package succeed
     */
    function testCreateDirectorysucceed() {
        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('userCanWrite', true);
        $frspf = new MockFRSPackageFactory();
        $frspf->setReturnValue('isPackageNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getPackageFactory', $frspf);
        $pm = new MockPermissionsManager();
        $utils->setReturnValue('getPermissionsManager', $pm);
        $webDAVFRS->setReturnValue('getUtils', $utils);

        $webDAVFRS->createDirectory('pkg');
    }
}
