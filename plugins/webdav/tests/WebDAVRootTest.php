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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('PFUser');
Mock::generate('Project');
Mock::generate('WebDAVProject');
Mock::generatePartial(
    'WebDAVRoot',
    'WebDAVRootTestVersion',
array('getWebDAVProject', 'getUser', 'getProjectIdByName', 'getPublicProjectList', 'getUserProjectList', 'isWebDAVAllowedForProject')
);

/**
 * This is the unit test of WebDAVRoot
 */
class WebDAVRootTest extends TuleapTestCase {

    /**
     * Testing when There is no public projects
     */
    function testGetChildrenWithNoPublicProjects() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getPublicProjectList', array());
        $this->assertEqual($webDAVRoot->getChildren(), array());

    }

    /**
     * Testing when There is no public project with WebDAV plugin activated
     */
    function testGetChildrenWithNoPublicProjectWithWebDAVActivated() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $webDAVProject = new MockWebDAVProject();

        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', false);
        $this->assertEqual($webDAVRoot->getChildren(), array());

    }

    /**
     * Testing when there is public projects with WebDAV activated
     */
    function testGetChildrenWithPublicProjects() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $webDAVProject = new MockWebDAVProject();

        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVRoot->setReturnValue('getPublicProjectList', array($webDAVProject));

        $this->assertEqual($webDAVRoot->getChildren(), array($webDAVProject));

    }

    /**
     * Testing when The user can see no project
     */
    function testGetChildrenNoUserProjects() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);

        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getUserProjectList', null);
        $this->assertEqual($webDAVRoot->getChildren(), array());

    }

    /**
     * Testing when the user have no projects with WebDAV activated
     */
    function testGetChildrenUserHaveNoProjectsWithWebDAVActivated() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);

        $webDAVProject = new MockWebDAVProject();

        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', false);

        $this->assertEqual($webDAVRoot->getChildren(), array());

    }

    /**
     * Testing when the user have projects
     */
    function testGetChildrenUserHaveProjects() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);

        $webDAVProject = new MockWebDAVProject();

        $webDAVRoot->setReturnValue('getUser', $user);
        $webDAVRoot->setReturnValue('getWebDAVProject', $webDAVProject);
        $webDAVRoot->setReturnValue('getUserProjectList', array($webDAVProject));

        $this->assertEqual($webDAVRoot->getChildren(), array($webDAVProject));

    }

    /**
     * Testing when the project doesn't have WebDAV plugin activated
     */
    function testGetChildFailWithWebDAVNotActivated() {

        $webDAVRoot = new WebDAVRootTestVersion($this);
        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', false);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $project = new MockWebDAVProject();
        $webDAVRoot->getChild($project->getName());

    }

    /**
     * Testing when the project doesn't exist
     */
    function testGetChildFailWithNotExist() {

        $webDAVRoot = new WebDAVRootTestVersion($this);

        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVProject();
        $project->setReturnValue('exist', false);

        $webDAVRoot->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_FileNotFound');

        $webDAVRoot->getChild($project->getName());

    }

    /**
     * Testing when the project is not active
     */
    function testGetChildFailWithNotActive() {

        $webDAVRoot = new WebDAVRootTestVersion($this);

        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', false);

        $webDAVRoot->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVRoot->getChild($project->getName());

    }

    /**
     * Testing when the user can't access the project
     */
    function testGetChildFailWithUserCanNotRead() {

        $webDAVRoot = new WebDAVRootTestVersion($this);

        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', true);
        $project->setReturnValue('userCanRead', false);

        $webDAVRoot->setReturnValue('getWebDAVProject', $project);

        $this->expectException('Sabre_DAV_Exception_Forbidden');

        $webDAVRoot->getChild($project->getName());

    }

    /**
     * Testing when the project exist, is active and user can read
     */
    function testSucceedGetChild() {

        $webDAVRoot = new WebDAVRootTestVersion($this);

        $webDAVRoot->setReturnValue('isWebDAVAllowedForProject', true);
        $project = new MockWebDAVProject();
        $project->setReturnValue('exist', true);
        $project->setReturnValue('isActive', true);

        $user = mock('PFUser');
        $webDAVRoot->setReturnValue('getUser', $user);

        $project->setReturnValue('userCanRead', true);

        $webDAVRoot->setReturnValue('getWebDAVProject', $project);

        $this->assertEqual($webDAVRoot->getChild($project->getName()), $project);

    }
}
