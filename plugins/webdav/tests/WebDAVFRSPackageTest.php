<?php
/**
 * This is the unit test of WebDAVFRSPackage
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Forbidden.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSPackageFactory.class.php');
Mock::generate('FRSPackageFactory');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSPackage.class.php');
Mock::generate('FRSPackage');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSRelease.class.php');
Mock::generate('FRSRelease');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
Mock::generate('WebDAVFRSRelease');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generate('WebDAVUtils');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSPackage.class.php');
Mock::generatePartial(
    'WebDAVFRSPackage',
    'WebDAVFRSPackageTestVersion',
array('getPackage', 'getProject', 'getUtils', 'getReleaseList', 'getFRSReleaseFromId', 'getWebDAVRelease', 'userIsAdmin')
);

class WebDAVFRSPackageTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVFRSPackageTest($name = 'WebDAVFRSPackageTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

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
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromId', $FRSRelease);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $WebDAVRelease);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $utils = new MockWebDAVUtils();
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);
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
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromId', $FRSRelease);
        $webDAVFRSPackage->setReturnValue('getWebDAVRelease', $WebDAVRelease);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $utils = new MockWebDAVUtils();
        $webDAVFRSPackage->setReturnValue('getUtils', $utils);
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
        $webDAVFRSPackage->setReturnValue('getFRSReleaseFromId', $FRSRelease);
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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

        $this->assertEqual($webDAVFRSPackage->userCanRead($user), true);

    }

}

?>