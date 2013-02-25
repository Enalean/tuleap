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

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once ('requirements.php');
require_once (dirname(__FILE__).'/../../../src/common/user/User.class.php');
Mock::generate('PFUser');
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
require_once (dirname(__FILE__).'/../include/FS/WebDAVProject.class.php');
Mock::generatePartial(
    'WebDAVProject',
    'WebDAVProjectTestVersion',
array('getGroupId', 'getProject', 'getUser', 'getUtils', 'getPackageList','getFRSPackageFromName', 'getWebDAVPackage', 'usesFile', 'userCanWrite')
);
Mock::generate('EventManager');

/**
 * This is the unit test of WebDAVProject
 */
class WebDAVProjectTest extends UnitTestCase {

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    /**
     * Testing when The project have no active services
     */
    function testGetChildrenNoServices() {
        $webDAVProject = new WebDAVProjectTestVersion($this);
        
        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $em = new MockEventManager();
        $utils->setReturnValue('getEventManager', $em);
        
        $webDAVProject->setReturnValue('usesFile', false);
        $this->assertEqual($webDAVProject->getChildren(), array());

    }

    /**
     * Testing when the user can't access to the service
     */
    /*function testGetChildrenFRSActive() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $this->assertEqual($webDAVProject->getChildren(), array());

    }*/

    /**
     * Testing when the service doesn't exist
     */
    function testGetChildFailWithNotExist() {
        $webDAVProject = new WebDAVProjectTestVersion($this);
        
        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $em = new MockEventManager();
        $utils->setReturnValue('getEventManager', $em);
        
        $webDAVProject->setReturnValue('usesFile', false);
        $this->expectException('Sabre_DAV_Exception_FileNotFound');
        $webDAVProject->getChild('Files');

    }

    /**
     * Testing when the user can't access to the service
     */
    /*function testGetChildFailWithUserCanNotRead() {

        $webDAVProject = new WebDAVProjectTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', false);

        $webDAVProject->setReturnValue('getFRSPackageFromName', $FRSPackage);
        $webDAVProject->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $webDAVProject->getChild($WebDAVPackage->getPackageId());

    }*/

    /**
     * Testing when the package exist and user can read
     */
    /*function testSucceedGetChild() {

        $webDAVProject = new WebDAVProjectTestVersion($this);

        $FRSPackage = new MockFRSPackage();
        $WebDAVPackage = new MockWebDAVFRSPackage();
        $WebDAVPackage->setReturnValue('exist', true);
        $WebDAVPackage->setReturnValue('userCanRead', true);

        $webDAVProject->setReturnValue('getFRSPackageFromName', $FRSPackage);
        $webDAVProject->setReturnValue('getWebDAVPackage', $WebDAVPackage);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $this->assertEqual($webDAVProject->getChild($WebDAVPackage->getPackageId()), $WebDAVPackage);

    }*/

    /**
     * Testing when project is not public and user is not member and not restricted
     */
    function testUserCanReadWhenNotPublicNotMemberNotRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', false);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', false);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), false);

    }

    /**
     * Testing when project is not public and user is member and not restricted
     */
    function testUserCanReadWhenNotPublicMemberNotRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', true);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', false);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), true);

    }

    /**
     * Testing when project is not public and user is not member and restricted
     */
    function testUserCanReadWhenNotPublicNotMemberRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', false);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), false);

    }

    /**
     * Testing when project is not public and user is member and restricted
     */
    function testUserCanReadWhenNotPublicMemberRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', false);
        $project->setReturnValue('userIsMember', true);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), true);

    }

    /**
     * Testing when project is public and user is not member and not restricted
     */
    function testUserCanReadWhenPublicNotMemberNotRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', false);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', false);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), true);

    }

    /**
     * Testing when project is public and user is member and not restricted
     */
    function testUserCanReadWhenPublicMemberNotRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', true);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', false);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), true);

    }

    /**
     * Testing when project is public and user is not member and restricted
     */
    function testUserCanReadWhenPublicNotMemberRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', false);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), false);

    }

    /**
     * Testing when project is public and user is member and restricted
     */
    function testUserCanReadWhenPublicMemberRestricted() {

        $webDAVProject = new WebDAVProjectTestVersion($this);
        $project = new MockProject();
        $project->setReturnValue('isPublic', true);
        $project->setReturnValue('userIsMember', true);
        $webDAVProject->setReturnValue('getProject', $project);

        $utils = new MockWebDAVUtils();
        $webDAVProject->setReturnValue('getUtils', $utils);
        $user = mock('PFUser');
        $user->setReturnValue('isRestricted', true);
        $webDAVProject->setReturnValue('getUser', $user);
        $this->assertEqual($webDAVProject->userCanRead(), true);

    }

}

?>