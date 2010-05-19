<?php
/**
 * This is the unit test of WebDAVFRSFile
 */

require_once (dirname(__FILE__).'/../../../src/common/include/CookieManager.class.php');
require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Forbidden.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/RequestedRangeNotSatisfiable.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSFile.class.php');
Mock::generate('FRSFile');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSFile.class.php');
Mock::generatePartial(
    'WebDAVFRSFile',
    'WebDAVFRSFileTestVersion',
array('getSize', 'getFileLocation', 'getFile', 'getReleaseId', 'getGivenReleaseId', 'getPackageId', 'getGivenPackageId', 'getUser', 'isActive', 'userCanDownload', 'fileExists', 'logDownload')
);

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
     * Testing when the file is null
     */
    function testGetFailureWithFileNull() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);

        $webDAVFile->setReturnValue('getFile', null);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFile->get();

    }

    /**
     * Testing when the file returns isActive == false
     */
    function testGetFailureWithNotActive() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->get();

    }

    /**
     * Testing when the user don't have the right to download
     */
    function testGetFailureWithUserCanNotDownload() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFile->get();

    }

    /**
     * Testing when the file doesn't exist
     */
    function testGetFailureWithNotExist() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', false);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFile->get();

    }

    /**
     * Testing when the file don't belong to the given package
     */
    function testGetFailureWithNotBelongToPackage() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);

        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 3);
        $webDAVFile->setReturnValue('getGivenPackageId', 2);
        $webDAVFile->setReturnValue('getGivenReleaseId', 3);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');

        $webDAVFile->get();

    }

    /**
     * Testing when the file don't belong to the given relaese
     */
    function testGetFailureWithNotBelongToRelease() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $webDAVFile->setReturnValue('getGivenPackageId', 1);
        $webDAVFile->setReturnValue('getGivenReleaseId', 3);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');

        $webDAVFile->get();

    }

    /**
     * Testing when the file size exceed 2GB
     */
    function testGetFailureWithBigFile() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);
        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $webDAVFile->setReturnValue('getGivenPackageId', 1);
        $webDAVFile->setReturnValue('getGivenReleaseId', 2);

        $webDAVFile->setReturnValue('getSize', 3000000000);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');

        $webDAVFile->get();

    }

    /**
     * Testing if the download method works perfectly
     */
    function testSucceedGet() {

        $webDAVFile = new WebDAVFRSFileTestVersion($this);

        $file = new MockFRSFile($this);
        $webDAVFile->setReturnValue('getFile', $file);

        $webDAVFile->setReturnValue('isActive', true);

        $webDAVFile->setReturnValue('userCanDownload', true);

        $webDAVFile->setReturnValue('fileExists', true);
        $user = new MockUser($this);
        $webDAVFile->setReturnValue('getUser', $user);

        $webDAVFile->setReturnValue('getPackageId', 1);
        $webDAVFile->setReturnValue('getReleaseId', 2);
        $webDAVFile->setReturnValue('getGivenPackageId', 1);
        $webDAVFile->setReturnValue('getGivenReleaseId', 2);

        $webDAVFile->setReturnValue('getSize', 10);

        $path = dirname(__FILE__).'/_fixtures/test.txt';
        $webDAVFile->setReturnValue('getFileLocation', $path);

        $this->assertNotEqual($webDAVFile->get(), false);

    }
}

?>