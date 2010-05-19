<?php
/**
 * This is the unit test of WebDAVFRS
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
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSProject.class.php');
Mock::generate('WebDAVFRSProject');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRS.class.php');
Mock::generatePartial(
    'WebDAVFRS',
    'WebDAVFRSTestVersion',
array('getWebDAVProject', 'getUser', 'getPublicProjectList', 'getUserProjectList', 'isWebDAVAllowedForProject')
);

class WebDAVFRSTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVFRSTest($name = 'WebDAVFRSTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    /**
     * Testing when There is no public projects
     */
    function testGetChildrenWithNoPublicProjects() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', true);
        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getPublicProjectList', array());
        $this->assertEqual($webDAVFRS->getChildren(), array());

    }

    /**
     * Testing when There is no public project with WebDAV plugin activated
     */
    function testGetChildrenWithNoPublicProjectWithWebDAVActivated() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', true);

        $webDAVProject = new MockWebDAVFRSProject();
        $webDAVProject->setReturnValue('usesFile', true);

        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', false);
        $this->assertEqual($webDAVFRS->getChildren(), array());

    }

    /**
     * Testing when there is public projects with WebDAV activated
     */
    function testGetChildrenWithPublicProjects() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', true);

        $webDAVProject = new MockWebDAVFRSProject();
        $webDAVProject->setReturnValue('usesFile', true);

        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVFRS->setReturnValue('getPublicProjectList', array($webDAVProject));

        $this->assertEqual($webDAVFRS->getChildren(), array($webDAVProject));

    }

    /**
     * Testing when The user can see no project
     */
    function testGetChildrenNoUserProjects() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', false);

        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getUserProjectList', null);
        $this->assertEqual($webDAVFRS->getChildren(), array());

    }

    /**
     * Testing when the user have no projects with WebDAV activated
     */
    function testGetChildrenUserHaveNoProjectsWithWebDAVActivated() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', false);

        $webDAVProject = new MockWebDAVFRSProject();
        $webDAVProject->setReturnValue('usesFile', true);

        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', false);

        $this->assertEqual($webDAVFRS->getChildren(), array());

    }

    /**
     * Testing when the user have projects
     */
    function testGetChildrenUserHaveProjects() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isAnonymous', false);

        $webDAVProject = new MockWebDAVFRSProject();
        $webDAVProject->setReturnValue('usesFile', true);

        $webDAVFRS->setReturnValue('getUser', $user);
        $webDAVFRS->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVFRS->setReturnValue('getUserProjectList', array($webDAVProject));

        $this->assertEqual($webDAVFRS->getChildren(), array($webDAVProject));

    }

    /**
     * Testing when the project doesn't have WebDAV plugin activated
     */
    function testGetChildFailWithWebDAVNotActivated() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);
        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $project = new MockWebDAVFRSProject();
        $webDAVFRS->getChild($project->getGroupId());

    }

    /**
     * Testing when the project doesn't exist
     */
    function testGetChildFailWithNotExist() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVFRSProject();
        $project->setReturnValue('exist', false);

        $webDAVFRS->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVFRS->getChild($project->getGroupId());

    }

    /**
     * Testing when the package is not active
     */
    function testGetChildFailWithNotActive() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVFRSProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', false);

        $webDAVFRS->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRS->getChild($project->getGroupId());

    }

    /**
     * Testing when the user can't read the package
     */
    function testGetChildFailWithUserCanNotRead() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVFRSProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', true);
        $project->setReturnValue('userCanRead', false);

        $webDAVFRS->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRS->getChild($project->getGroupId());

    }
    
    /**
     * Testing when the project have no file release activated
     */
    function testGetChildFailWithNoFRSActivated() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVFRSProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', true);
        $project->setReturnValue('userCanRead', true);
        $project->setReturnValue('usesFile', false);

        $webDAVFRS->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVFRS->getChild($project->getGroupId());

    }

    /**
     * Testing when the package exist, is active and user can read
     */
    function testSucceedGetChild() {

        $webDAVFRS = new WebDAVFRSTestVersion($this);

        $webDAVFRS->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVFRSProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', true);

        $user = new MockUser($this);
        $webDAVFRS->setReturnValue('getUser', $user);

        $project->setReturnValue('userCanRead', true);
        $project->setReturnValue('usesFile', true);

        $webDAVFRS->setReturnValue('getWebDAVProject', $project);

        $this->assertEqual($webDAVFRS->getChild($project->getGroupId()), $project);

    }

}
?>