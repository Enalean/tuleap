<?php
/**
 * This is the unit test of WebDAVFRSRelease
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSRelease.class.php');
Mock::generate('FRSRelease');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSFile.class.php');
Mock::generate('FRSFile');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSFile.class.php');
Mock::generate('WebDAVFRSFile');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
Mock::generatePartial(
    'WebDAVFRSRelease',
    'WebDAVFRSReleaseTestVersion',
array('getChild', 'getRelease', 'getFileList', 'getWebDAVFRSFile', 'userIsAdmin')
);

class WebDAVFRSReleaseTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVFRSReleaseTest($name = 'WebDAVFRSReleaseTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

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
        $user = new MockUser();

        $this->assertEqual($webDAVFRSRelease->userCanRead($user), true);

    }

}

?>