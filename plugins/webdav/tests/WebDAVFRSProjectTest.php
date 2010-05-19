<?php
/**
 * This is the unit test of WebDAVFRSProject
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Forbidden.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/MethodNotAllowed.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSPackage.class.php');
Mock::generate('FRSPackage');
require_once (dirname(__FILE__).'/../../../src/common/frs/FRSPackageFactory.class.php');
Mock::generate('FRSPackageFactory');
Mock::generate('PermissionsManager');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generate('WebDAVUtils');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSPackage.class.php');
Mock::generate('WebDAVFRSPackage');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSProject.class.php');
Mock::generatePartial(
    'WebDAVFRSProject',
    'WebDAVFRSProjectTestVersion',
array('getGroupId', 'getProject', 'getUtils', 'getPackageList','getFRSPackageFromId', 'getWebDAVPackage', 'userIsAdmin')
);

class WebDAVFRSProjectTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVFRSProjectTest($name = 'WebDAVFRSProjectTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    /**
     * Testing when The project have no packages
     */
    function testGetChildrenNoPackages() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);
        $webDAVFRSProject->setReturnValue('getPackageList', array());
        $this->assertEqual($webDAVFRSProject->getChildren(), array());

    }

    /**
     * Testing when the user can't read packages
     */
    function testGetChildrenUserCanNotRead() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);

        $package = new MockWebDAVFRSPackage();
        $package->setReturnValue('userCanRead', false);
        $webDAVFRSProject->setReturnValue('getWebDAVPackage', $package);

        $FRSPackage = new MockFRSPackage();
        $webDAVFRSProject->setReturnValue('getPackageList', array($FRSPackage));

        $this->assertEqual($webDAVFRSProject->getChildren(), array());

    }

    /**
     * Testing when the user can read packages
     */
    function testGetChildrenUserCanRead() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);

        $package = new MockWebDAVFRSPackage();
        $package->setReturnValue('userCanRead', true);

        $webDAVFRSProject->setReturnValue('getWebDAVPackage', $package);

        $FRSPackage = new MockFRSPackage();
        $webDAVFRSProject->setReturnValue('getPackageList', array($FRSPackage));

        $this->assertEqual($webDAVFRSProject->getChildren(), array($package));

    }

    /**
     * Testing when the package doesn't exist
     */
    function testGetChildFailWithNotExist() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', false);
        $webDAVFRSProject->setReturnValue('getFRSPackageFromId', $FRSPackage);
        $webDAVFRSProject->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $webDAVFRSProject->getChild($WebDAVPackage->getPackageId());

    }

    /**
     * Testing when the user can't read the package
     */
    function testGetChildFailWithUserCanNotRead() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', false);

        $webDAVFRSProject->setReturnValue('getFRSPackageFromId', $FRSPackage);
        $webDAVFRSProject->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $webDAVFRSProject->getChild($WebDAVPackage->getPackageId());

    }

    /**
     * Testing when the package exist and user can read
     */
    function testSucceedGetChild() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', true);

        $webDAVFRSProject->setReturnValue('getFRSPackageFromId', $FRSPackage);
        $webDAVFRSProject->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $this->assertEqual($webDAVFRSProject->getChild($WebDAVPackage->getPackageId()), $WebDAVPackage);

    }

    /**
     * Testing when project is not public and user is not member
     */
    function testUserCanReadWhenNotPublicNotMember() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', false);
        $webDAVFRSProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $user = new MockUser();
        $this->assertEqual($webDAVFRSProject->userCanRead($user), false);

    }

    /**
     * Testing when project is public and user is not member
     */
    function testUserCanReadWhenPublicNotMember() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', false);
        $webDAVFRSProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $user = new MockUser();
        $this->assertEqual($webDAVFRSProject->userCanRead($user), true);

    }

    /**
     * Testing when project is not public and user is member
     */
    function testUserCanReadWhenNotPublicMember() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', true);
        $webDAVFRSProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $user = new MockUser();
        $this->assertEqual($webDAVFRSProject->userCanRead($user), true);

    }

    /**
     * Testing when project is public and user is member
     */
    function testUserCanReadWhenPublicMember() {

        $webDAVFRSProject = new WebDAVFRSProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', true);
        $webDAVFRSProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVFRSProject->setReturnValue('getUtils', $utils);
        $user = new MockUser();
        $this->assertEqual($webDAVFRSProject->userCanRead($user), true);

    }

}

?>