<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 */

require_once 'bootstrap.php';

/**
 * This is the unit test of WebDAVProject
 */
class WebDAVFRSTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->frs_permission_manager = \Mockery::spy(\Tuleap\FRS\FRSPermissionManager::class);
    }

    /**
     * Testing when The project have no packages
     */
    function testGetChildrenNoPackages()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVFRS->shouldReceive('getPackageList')->andReturns(array());
        $this->assertEqual($webDAVFRS->getChildren(), array());
    }

    /**
     * Testing when the user can't read packages
     */
    function testGetChildrenUserCanNotRead()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('userCanRead')->andReturns(false);
        $webDAVFRS->shouldReceive('getWebDAVPackage')->andReturns($package);

        $FRSPackage = \Mockery::spy(\FRSPackage::class);
        $webDAVFRS->shouldReceive('getPackageList')->andReturns(array($FRSPackage));

        $this->assertEqual($webDAVFRS->getChildren(), array());
    }

    /**
     * Testing when the user can read packages
     */
    function testGetChildrenUserCanRead()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $package = \Mockery::spy(\WebDAVFRSPackage::class);
        $package->shouldReceive('userCanRead')->andReturns(true);

        $webDAVFRS->shouldReceive('getWebDAVPackage')->andReturns($package);

        $FRSPackage = \Mockery::spy(\FRSPackage::class);
        $webDAVFRS->shouldReceive('getPackageList')->andReturns(array($FRSPackage));

        $this->assertEqual($webDAVFRS->getChildren(), array($package));
    }

    /**
     * Testing when the package doesn't exist
     */
    function testGetChildFailWithNotExist()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $FRSPackage = \Mockery::spy(\FRSPackage::class);
        $WebDAVPackage = \Mockery::spy(\WebDAVFRSPackage::class);
        $WebDAVPackage->shouldReceive('exist')->andReturns(false);
        $webDAVFRS->shouldReceive('getFRSPackageFromName')->andReturns($FRSPackage);
        $webDAVFRS->shouldReceive('getWebDAVPackage')->andReturns($WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRS->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRS->getChild($WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the user can't read the package
     */
    function testGetChildFailWithUserCanNotRead()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $FRSPackage = \Mockery::spy(\FRSPackage::class);
        $WebDAVPackage = \Mockery::spy(\WebDAVFRSPackage::class);
        $WebDAVPackage->shouldReceive('exist')->andReturns(true);
        $WebDAVPackage->shouldReceive('userCanRead')->andReturns(false);

        $webDAVFRS->shouldReceive('getFRSPackageFromName')->andReturns($FRSPackage);
        $webDAVFRS->shouldReceive('getWebDAVPackage')->andReturns($WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRS->shouldReceive('getUtils')->andReturns($utils);
        $webDAVFRS->getChild($WebDAVPackage->getPackageId());
    }

    /**
     * Testing when the package exist and user can read
     */
    function testSucceedGetChild()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $FRSPackage = \Mockery::spy(\FRSPackage::class);
        $WebDAVPackage = \Mockery::spy(\WebDAVFRSPackage::class);
        $WebDAVPackage->shouldReceive('exist')->andReturns(true);
        $WebDAVPackage->shouldReceive('userCanRead')->andReturns(true);

        $webDAVFRS->shouldReceive('getFRSPackageFromName')->andReturns($FRSPackage);
        $webDAVFRS->shouldReceive('getWebDAVPackage')->andReturns($WebDAVPackage);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $webDAVFRS->shouldReceive('getUtils')->andReturns($utils);
        $this->assertEqual($webDAVFRS->getChild($WebDAVPackage->getPackageId()), $WebDAVPackage);
    }

    /**
     * Testing creation of package when user is not admin
     */
    function testCreateDirectoryFailWithUserNotAdmin()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRS->shouldReceive('userCanWrite')->andReturns(false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package when the name already exist
     */
    function testCreateDirectoryFailWithNameExist()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class, [new PFUser(['language_id' => 'en_US']), new Project(['group_id' => 101]), 1])->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRS->shouldReceive('userCanWrite')->andReturns(true);
        $frspf = \Mockery::spy(\FRSPackageFactory::class);
        $frspf->shouldReceive('isPackageNameExist')->andReturns(true);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($frspf);
        $webDAVFRS->shouldReceive('getUtils')->andReturns($utils);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRS->createDirectory('pkg');
    }

    /**
     * Testing creation of package succeed
     */
    function testCreateDirectorysucceed()
    {
        $webDAVFRS = \Mockery::mock(\WebDAVFRS::class, [new PFUser(['language_id' => 'en_US']), new Project(['group_id' => 101]), 1])->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVFRS->shouldReceive('userCanWrite')->andReturns(true);
        $frspf = \Mockery::spy(\FRSPackageFactory::class);
        $frspf->shouldReceive('isPackageNameExist')->andReturns(false);
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('getPackageFactory')->andReturns($frspf);
        $pm = \Mockery::spy(\PermissionsManager::class);
        $utils->shouldReceive('getPermissionsManager')->andReturns($pm);
        $webDAVFRS->shouldReceive('getUtils')->andReturns($utils);

        $webDAVFRS->createDirectory('pkg');
    }
}
