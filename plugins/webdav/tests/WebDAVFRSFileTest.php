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

require_once (dirname(__FILE__).'/../../../src/common/include/CookieManager.class.php');
require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once ('requirements.php');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSFileFactory.class.php');
Mock::generate('FRSFileFactory');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSFile.class.php');
Mock::generate('FRSFile');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
Mock::generate('WebDAVFRSRelease');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generate('WebDAVUtils');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSFile.class.php');
Mock::generatePartial(
    'WebDAVFRSFile',
    'WebDAVFRSFileTestVersion',
array('getFileLocation', 'getFile', 'getFileId', 'getProject', 'getUtils', 'logDownload', 'userCanWrite', 'copyFile')
);

/**
 * This is the unit test of WebDAVFRSFile
 */
class WebDAVFRSFileTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVFRSFileTest($name = 'WebDAVFRSFileTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    /**
     * Testing if the download method works perfectly
     */
    function testSucceedGet() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);

        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $path = dirname(__FILE__).'/_fixtures/test.txt';
        $webDAVFile->setReturnValue('getFileLocation', $path);

        $this->assertNotEqual($webDAVFile->get(), false);

    }

    /**
     * Testing delete when user is not admin
     */
    function testDeleteFailWithUserNotAdmin() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->delete();

    }

    /**
     * Testing delete when file doesn't exist
     */
    function testDeleteFileNotExist() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('delete_file', 0);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->delete();

    }

    /**
     * Testing succeeded delete
     */
    function testDeleteSucceede() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('delete_file', 1);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);

        $this->assertNoErrors();

        $webDAVFile->delete();

    }

    /**
     * Testing setName when user is not admin
     */
    /*function testSetNameFailWithUserNotAdmin() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', false);
        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->setName('newName');

    }*/

    /**
     * Testing setName when filename is not valid
     */
    /*function testSetNameFailWithFilenameNotValid() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isValidFileName', false);
        $webDAVFile->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_BadRequest');

        $webDAVFile->setName('newName');

    }*/

    /**
     * Testing setName when name already exist
     */
    /*function testSetNameFailWithNameExist() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $utils->setReturnValue('isValidFileName', true);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $webDAVFile->setName('newName');

    }*/

    /**
     * Testing setName when cpying into incoming fail
     */
    /*function testSetNameCopyIntoIncomingFail() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $utils->setReturnValue('isValidFileName', true);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);
        $file = new MockFRSFile();
        $webDAVFile->setReturnValue('getFile', $file);
        $webDAVFile->setReturnValue('getFileLocation', dirname(__FILE__).'/_fixtures/newName');
        $webDAVFile->setReturnValue('copyFile', false);
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFile->setName('newName');

    }*/

    /**
     * Testing setName when fileforge could not copy the file
     */
    /*function testSetNameFileforgeFail() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $frsff->setReturnValue('moveFileForge', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $utils->setReturnValue('isValidFileName', true);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);
        $file = new MockFRSFile();
        $webDAVFile->setReturnValue('getFile', $file);
        $webDAVFile->setReturnValue('getFileLocation', dirname(__FILE__).'/_fixtures/newName');
        $webDAVFile->setReturnValue('copyFile', true);
        $this->expectException('Sabre_DAV_Exception');

        $webDAVFile->setName('newName');

    }*/

    /**
     * Testing setName succeede
     */
    /*function testSetNameSucceede() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $webDAVFile->setReturnValue('userCanWrite', true);
        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $frsff->setReturnValue('moveFileForge', false);
        $utils->setReturnValue('isValidFileName', true);
        $project = new MockProject();
        $webDAVFile->setReturnValue('getProject', $project);
        $webDAVFile->setReturnValue('getUtils', $utils);
        $file = new MockFRSFile();
        $webDAVFile->setReturnValue('getFile', $file);
        $webDAVFile->setReturnValue('getFileLocation', dirname(__FILE__).'/_fixtures/newName');
        $webDAVFile->setReturnValue('copyFile', true);
        $this->assertNoErrors();

        $webDAVFile->setName('newName');

    }*/

    function testMoveFailNotAdminOnSource() {
        $file = new MockFRSFile();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', false);
        $source->setReturnValue('getFile', $file);
        $destination->setReturnValue('userCanWrite', true);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNotAdminOnDestination() {
        $file = new MockFRSFile();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', true);
        $source->setReturnValue('getFile', $file);
        $destination->setReturnValue('userCanWrite', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNotAdminOnBoth() {
        $file = new MockFRSFile();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', false);
        $source->setReturnValue('getFile', $file);
        $destination->setReturnValue('userCanWrite', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $source->move($destination);
    }

    function testMoveFailNameExist() {
        $file = new MockFRSFile();

        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', true);
        $source->setReturnValue('getUtils', $utils);
        $source->setReturnValue('getFile', $file);
        $destination->setReturnValue('userCanWrite', true);
        $destination->setReturnValue('getProject', $project);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $source->move($destination);
    }

    function testMoveCopyFileFail() {
        $file = new MockFRSFile();

        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', true);
        $source->setReturnValue('getUtils', $utils);
        $source->setReturnValue('getFile', $file);
        $source->setReturnValue('copyFile', false);
        $destination->setReturnValue('userCanWrite', true);
        $destination->setReturnValue('getProject', $project);

        $this->expectException('Sabre_DAV_Exception');

        $source->move($destination);
    }

    function testMoveMoveFileForgeFail() {
        $file = new MockFRSFile();

        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $frsff->setReturnValue('moveFileForge', true);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', true);
        $source->setReturnValue('getUtils', $utils);
        $source->setReturnValue('getFile', $file);
        $source->setReturnValue('copyFile', true);
        $destination->setReturnValue('userCanWrite', true);
        $destination->setReturnValue('getProject', $project);

        $this->expectException('Sabre_DAV_Exception');

        $source->move($destination);
    }

    function testMoveSucceed() {
        $GLOBALS['ftp_incoming_dir'] = dirname(__FILE__).'/_fixtures';
        unlink($GLOBALS['ftp_incoming_dir'].'/.delete_files');
        $file = new MockFRSFile();
        $file->setReturnValue('getFileName', 'p0_r0/fileFooBar');

        $frsff = new MockFRSFileFactory();
        $frsff->setReturnValue('isFileBaseNameExists', false);
        $frsff->setReturnValue('moveFileForge', false);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('getFileFactory', $frsff);
        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'projectFooBar');

        $source = new WebDAVFRSFileTestVersion($this);
        $destination = new MockWebDAVFRSRelease();
        $source->setReturnValue('userCanWrite', true);
        $source->setReturnValue('getUtils', $utils);
        $source->setReturnValue('getFile', $file);
        $source->setReturnValue('copyFile', true);
        $source->setReturnValue('getProject', $project);
        $destination->setReturnValue('userCanWrite', true);
        $destination->setReturnValue('getProject', $project);

        $this->assertNoErrors();

        $source->move($destination);
    }

}

?>