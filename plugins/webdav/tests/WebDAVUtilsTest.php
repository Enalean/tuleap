<?php
/**
 * This is the unit test of WebDAVFRS
 */

require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('User');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generatePartial(
    'WebDAVUtils',
    'WebDAVUtilsTestVersion',
array()
);

class WebDAVUtilsTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVUtilsTest($name = 'WebDAVUtilsTest') {
        $this->UnitTestCase($name);
    }

    /**
     * Testing when The user is not member and is not super user
     */
    function testUserIsAdminNotSuperUserNotmember() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', false);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), false);

    }

    /**
     * Testing when The user is super user
     */
    function testUserIsAdminSuperUser() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', false);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin
     */
    function testUserIsAdminGroupAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin and super user
     */
    function testUserIsAdminSuperUserGroupAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is file release admin
     */
    function testUserIsAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is file release admin and super user
     */
    function testUserIsAdminSuperuserFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', false, array('0' => $project->getGroupId(), '1' => 'A'));
        $user->setReturnValue('isMember', true, array('0' => $project->getGroupId(), '1' => 'R2'));

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin and file release admin
     */
    function testUserIsAdminGroupAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', false);
        $project = new MockProject();
        $user->setReturnValue('isMember', true);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

    /**
     * Testing when The user is group admin filerelease admin and super user
     */
    function testUserIsAdminSuperUserGroupAdminFRSAdmin() {

        $utils = new WebDAVUtilsTestVersion($this);
        $user = new MockUser($this);
        $user->setReturnValue('isSuperUser', true);
        $project = new MockProject();
        $user->setReturnValue('isMember', true);

        $this->assertEqual($utils->UserIsAdmin($user, $project->getGroupId()), true);

    }

}
?>