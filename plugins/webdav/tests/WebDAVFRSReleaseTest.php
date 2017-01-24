k<?php
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
Mock::generate('Project');
Mock::generate('FRSPackage');
Mock::generate('WebDAVFRSPackage');
Mock::generate('FRSReleaseFactory');
Mock::generate('FRSRelease');
Mock::generate('FRSFileFactory');
Mock::generate('FRSFile');
Mock::generate('WebDAVFRSFile');
Mock::generate('WebDAVUtils');
Mock::generatePartial(
    'WebDAVFRSRelease',
    'WebDAVFRSReleaseTestVersion',
array('getChild', 'getRelease', 'getReleaseId', 'getPackage', 'getProject', 'getUser', 'getUtils', 'getMaxFileSize', 'getFileList', 'getWebDAVFRSFile', 'userIsAdmin', 'userCanWrite', 'createFileIntoIncoming')
);

Mock::generatePartial(
    'WebDAVFRSRelease',
    'WebDAVFRSReleaseTestVersion2',
array('getReleaseId', 'getPackage', 'getProject', 'getUtils', 'getMaxFileSize', 'getFRSFileFromId', 'getFileIdFromName', 'getWebDAVFRSFile', 'userIsAdmin', 'unlinkFile', 'openFile', 'streamCopyToStream', 'closeFile')
);

/**
 * This is the unit test of WebDAVFRSRelease
 */
class WebDAVFRSReleaseTest extends TuleapTestCase {


    public function setUp()
    {
        parent::setUp();
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures/incoming';
    }

    public function tearDown()
    {
        unset($GLOBALS['ftp_incoming_dir']);
        parent::tearDown();
    }

    /**
     * Testing when The release have no files
     */
    function testGetChildrenNoFiles() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('getFileList', array());

        $this->assertEqual($webDAVFRSRelease->getChildren(), array());

    }

    /**
     * Testing when the release contains files
     */
    function testGetChildrenContainFiles() {

        $file = new MockWebDAVFRSFile();

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('getChild', $file);

        $FRSFile = new MockFRSFile();
        $webDAVFRSRelease->setReturnValue('getFileList', array($FRSFile));
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $file);

        $this->assertEqual($webDAVFRSRelease->getChildren(), array($file));

    }

    /**
     * Testing when the file is null
     */
    function testGetChildFailureWithFileNull() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();

        $webDAVFile->setReturnValue('getFile', null);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the file returns isActive == false
     */
    function testGetChildFailureWithNotActive() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', false);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the user don't have the right to download
     */
    function testGetChildFailureWithUserCanNotDownload() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', false);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the file doesn't exist
     */
    function testGetChildFailureWithNotExist() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', false);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the file don't belong to the given package
     */
    function testGetChildFailureWithNotBelongToPackage() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 3);
        $package = new MockWebDAVFRSPackage($this);
        $package->setReturnValue('getPackageID', 2);
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getReleaseId', 3);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the file don't belong to the given relaese
     */
    function testGetChildFailureWithNotBelongToRelease() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $package = new MockWebDAVFRSPackage($this);
        $package->setReturnValue('getPackageID', 1);
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getReleaseId', 3);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when the file size exceed max file size
     */
    function testGetChildFailureWithBigFile() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $package = new MockWebDAVFRSPackage($this);
        $package->setReturnValue('getPackageID', 1);
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getReleaseId', 2);

        $webDAVFile->setReturnValue('getSize', 65);
        $webDAVFRSRelease->setReturnValue('getMaxFileSize', 64);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');

        $webDAVFRSRelease->getChild('fileName');

    }

    /**
     * Testing when GetChild succeede
     */
    function testGetChildSucceede() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFile = new MockWebDAVFRSFile();
        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $package = new MockWebDAVFRSPackage($this);
        $package->setReturnValue('getPackageID', 1);
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getReleaseId', 2);

        $webDAVFile->setReturnValue('getSize', 64);
        $webDAVFRSRelease->setReturnValue('getMaxFileSize', 64);
        $webDAVFRSRelease->setReturnValue('getWebDAVFRSFile', $webDAVFile);

        $this->assertEqual($webDAVFRSRelease->getChild('fileName'), $webDAVFile);

    }

    /**
     * Testing when the release is deleted and the user have no permissions
     */
    function testUserCanReadFailureReleaseDeletedUserHaveNoPermissions() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when the release is active and user can not read
     */
    function testUserCanReadFailureActiveUserCanNotRead() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', true);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when the release is not active and the user can read
     */
    function testUserCanReadFailureDeletedUserCanRead() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when the release is active and the user can read
     */
    function testUserCanReadSucceedActiveUserCanRead() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', true);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), true);

    }

    /**
     * Testing when the release is hidden and the user is not admin an can not read
     */
    function testUserCanReadFailureHiddenNotAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', true);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when the release is hidden and the user can read and is not admin
     */
    function testUserCanReadFailureHiddenNotAdminUserCanRead() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', true);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', false);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when release is deleted and the user is admin
     */
    function testUserCanReadFailureDeletedUserIsAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when the release is active but the admin can not read ????
     * TODO: verify this in a real case
     */
    function testUserCanReadFailureAdminHaveNoPermission() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', true);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when release is deleted and user is admin and can read
     */
    function testUserCanReadFailureDeletedCanReadIsAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), false);

    }

    /**
     * Testing when release is active and user can read and is admin
     */
    function testUserCanReadSucceedActiveUserCanReadIsAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', true);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', false);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), true);

    }

    /**
     * Testing when release is hidden and user is admin
     */
    function testUserCanReadSucceedHiddenUserIsAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', false);
        $release->setReturnValue('isHidden', true);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), true);

    }

    /**
     * Testing when release is hidden and user is admin and can read
     */
    function testUserCanReadSucceedHiddenUserIsAdminCanRead() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $release = new MockFRSRelease();
        $release->setReturnValue('isActive', false);
        $release->setReturnValue('userCanRead', true);
        $release->setReturnValue('isHidden', true);
        $webDAVFRSRelease->setReturnValue('userIsAdmin', true);

        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        $user = mock('PFUser');

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), true);

    }

    /**
     * Testing delete when user is not admin
     */
    function testDeleteFailWithUserNotAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->delete();

    }

    /**
     * Testing delete when release doesn't exist
     */
    function testDeleteReleaseNotExist() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('delete_release', 0);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->delete();

    }

    /**
     * Testing succeeded delete
     */
    function testDeleteSucceede() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('delete_release', 1);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);

        $webDAVFRSRelease->delete();

    }

    /**
     * Testing setName when user is not admin
     */
    function testSetNameFailWithUserNotAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', false);
        $frsrf = new MockFRSReleaseFactory();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $package = new MockFRSPackage();
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->setName('newName');

    }

    /**
     * Testing setName when name already exist
     */
    function testSetNameFailWithNameExist() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('isReleaseNameExist', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $package = new MockFRSPackage();
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFRSRelease->setName('newName');

    }

    /**
     * Testing setName succeede
     */
    function testSetNameSucceede() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);
        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $package = new MockFRSPackage();
        $webDAVFRSRelease->setReturnValue('getPackage', $package);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $webDAVFRSRelease->setReturnValue('getRelease', $release);
        
        $webDAVFRSRelease->setName('newName');

    }

    function testMoveFailNotAdminOnSource() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $destination->setReturnValue('getPackage', $package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNotAdminOnDestination() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', false);
        $package = new MockFRSPackage();
        $destination->setReturnValue('getPackage', $package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNotAdminOnBoth() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', false);
        $package = new MockFRSPackage();
        $destination->setReturnValue('getPackage', $package);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNameExist() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $frsrf->setReturnValue('isReleaseNameExist', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $destination->setReturnValue('getPackage', $package);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $source->move($destination);
    }

    function testMoveFailPackageHiddenReleaseNotHidden() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $release->setReturnValue('isHidden', false);
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $package->setReturnValue('isHidden', true);
        $destination->setReturnValue('getPackage', $package);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $source->move($destination);
    }

    function testMoveSucceedPackageAndReleaseHidden() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $release->setReturnValue('isHidden', true);
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $package->setReturnValue('isHidden', true);
        $destination->setReturnValue('getPackage', $package);

        $source->move($destination);
    }

    function testMoveSucceedReleaseHidden() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $release->setReturnValue('isHidden', true);
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $package->setReturnValue('isHidden', false);
        $destination->setReturnValue('getPackage', $package);

        $source->move($destination);
    }

    function testMoveSucceed() {
        $source = new WebDAVFRSReleaseTestVersion($this);
        $frsrf = new MockFRSReleaseFactory();
        $frsrf->setReturnValue('userCanUpdate', true);
        $frsrf->setReturnValue('isReleaseNameExist', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getReleaseFactory', $frsrf);
        $source->setReturnValue('getUtils', $utils);
        $project = new MockProject();
        $source->setReturnValue('getProject', $project);
        $release = new MockFRSRelease();
        $release->setReturnValue('isHidden', false);
        $source->setReturnValue('getRelease', $release);
        $destination = new MockWebDAVFRSPackage();
        $destination->setReturnValue('userCanWrite', true);
        $package = new MockFRSPackage();
        $package->setReturnValue('isHidden', false);
        $destination->setReturnValue('getPackage', $package);

        $source->move($destination);
    }

    /**
     * Testing creation of file when user is not admin
     */
    function testCreateFileFailWithUserNotAdmin() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);

        $webDAVFRSRelease->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRSRelease->createFile('release');

    }

    /**
     * Testing creation of file when the file size is bigger than permitted
     */
    function testCreateFileFailWithFileSizeLimitExceeded() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);

        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $utils->setReturnValue('getIncomingFileSize', 65);
        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);
        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');
        $data = fopen(dirname(__FILE__).'/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->setReturnValue('getMaxFileSize', 64);

        $webDAVFRSRelease->createFile('release', $data);

    }

    /**
     * Testing creation of file succeed
     */
    function testCreateFilesucceed() {

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion($this);

        $webDAVFRSRelease->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $frsff->setReturnValue('createFile', true);

        $release = new MockFRSRelease($this);
        $release->setReturnValue('getReleaseID', 1234);
        $webDAVFRSRelease->setReturnValue('getRelease', $release);

        $frsrf = new MockFRSReleaseFactory($this);
        $frsrf->expectOnce('emailNotification');

        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $utils->setReturnValue('getIncomingFileSize', 64);
        $utils->setReturnValue('getReleaseFactory', $frsrf);

        $project = new MockProject();
        $webDAVFRSRelease->setReturnValue('getProject', $project);
        $user = mock('PFUser');
        $webDAVFRSRelease->setReturnValue('getUser', $user);
        $webDAVFRSRelease->setReturnValue('getUtils', $utils);
        
        $data = fopen(dirname(__FILE__).'/_fixtures/test.txt', 'r');
        $webDAVFRSRelease->setReturnValue('getMaxFileSize', 64);

        $webDAVFRSRelease->createFile('release', $data);

    }

    function testcreateFileIntoIncomingUnlinkFail() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFRSRelease->setReturnValue('unlinkFile', false);

        $webDAVFRSRelease->expectOnce('unlinkFile');
        $webDAVFRSRelease->expectNever('openFile');
        $webDAVFRSRelease->expectNever('streamCopyToStream');
        $webDAVFRSRelease->expectNever('closeFile');
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }

    function testcreateFileIntoIncomingCreateFail() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFRSRelease->setReturnValue('unlinkFile', true);
        $webDAVFRSRelease->setReturnValue('openFile', false);

        $webDAVFRSRelease->expectNever('unlinkFile');
        $webDAVFRSRelease->expectOnce('openFile');
        $webDAVFRSRelease->expectNever('streamCopyToStream');
        $webDAVFRSRelease->expectNever('closeFile');
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    function testcreateFileIntoIncomingCloseFail() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFRSRelease->setReturnValue('unlinkFile', true);
        $webDAVFRSRelease->setReturnValue('openFile', true);
        $webDAVFRSRelease->setReturnValue('closeFile', false);

        $webDAVFRSRelease->expectNever('unlinkFile');
        $webDAVFRSRelease->expectOnce('openFile');
        $webDAVFRSRelease->expectOnce('closeFile');
        $this->expectException('Sabre_DAV_Exception');
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    function testcreateFileIntoIncomingSucceed() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFRSRelease->setReturnValue('openFile', true);
        $webDAVFRSRelease->setReturnValue('closeFile', true);

        $webDAVFRSRelease->expectNever('unlinkFile');
        $webDAVFRSRelease->expectOnce('openFile');
        $webDAVFRSRelease->expectOnce('closeFile');

        $webDAVFRSRelease->createFileIntoIncoming('toto.txt', 'text');
    }

    function testcreateFileIntoIncomingSucceedWithFileExist() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';

        $webDAVFRSRelease = new WebDAVFRSReleaseTestVersion2($this);
        $webDAVFRSRelease->setReturnValue('unlinkFile', true);
        $webDAVFRSRelease->setReturnValue('openFile', true);
        $webDAVFRSRelease->setReturnValue('closeFile', true);

        $webDAVFRSRelease->expectOnce('unlinkFile');
        $webDAVFRSRelease->expectOnce('openFile');
        $webDAVFRSRelease->expectOnce('closeFile');

        $webDAVFRSRelease->createFileIntoIncoming('test.txt', 'text');
    }
}
