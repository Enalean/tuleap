<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) .'/../include/constants.php';
require_once GIT_BASE_DIR .'/GitViews.class.php';

Mock::generate('Project');
Mock::generate('User');
Mock::generate('ProjectManager');


class GitViewsTest extends UnitTestCase {

    public function testCanReturnOptionsListOfProjectsTheUserIsAdminOf() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $output = $view->getUserProjectsAsOptions($user, $manager, '50');
        $this->assertPattern('/<option value="123"/', $output);
        $this->assertNoPattern('/<option value="456"/', $output);
    }

    public function testOptionsShouldContainThePublicNameOfTheProject() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $this->assertPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldContainTheUnixNameOfTheProjectAsTitle() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig', 'gpig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $this->assertPattern('/title="gpig"/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldPurifyThePublicNameOfTheProject() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea < Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $this->assertPattern('/Guinea &lt; Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testCurrentProjectMustNotBeInProjectList() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $this->assertNoPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '123'));


    }

    public function testProjectListMustContainsOnlyProjectsWithGitEnabled() {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProjectWithoutGitService('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = TestHelper::getPartialMock('GitViews', array());
        $this->assertNoPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));

    }

    private function GivenAProject($id, $name, $unixName = null, $useGit = true) {
        $project = new MockProject();
        $project->setReturnValue('getId', $id);
        $project->setReturnValue('getPublicName', htmlspecialchars($name)); //see create_project()
        $project->setReturnValue('getUnixName', $unixName);
        $project->setReturnValue('usesService', $useGit, array(GitPlugin::SERVICE_SHORTNAME));
        return $project;
    }

    private function GivenAProjectWithoutGitService($id, $name) {
        return $this->GivenAProject($id, $name, null, false);
    }

    private function GivenAProjectManager($project) {
        $manager = new MockProjectManager();
        $manager->setReturnValue('getProject', $project, array($project->getId()));

        return $manager;
    }

    private function GivenAUserWithProjects() {
        $user = new MockUser();
        $user->setReturnValue('getAllProjects', array('123', '456'));
        $user->setReturnValue('isMember', true, array('123', 'A'));
        $user->setReturnValue('isMember', false, array('456', 'A'));
        return $user;
    }

}

class GitView_DiffViewTest extends TuleapTestCase {

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function setUp() {
        parent::setUp();
        $this->project_manager = mock('ProjectManager');
        ProjectManager::setInstance($this->project_manager);

        $controller    = mock('Git');
        $request       = mock('HTTPRequest');
        $user          = mock('User');
        $this->project = mock('Project');
        $plugin        = mock('GitPlugin');

        stub($plugin)->getConfigurationParameter()->returns(GIT_BASE_DIR.'/../tests/_fixtures/fakeGitPHP');
        stub($this->project)->getUnixName()->returns('project');
        stub($controller)->getRequest()->returns($request);
        stub($controller)->getUser()->returns($user);
        stub($controller)->getPlugin()->returns($plugin);

        stub($this->project_manager)->getProject()->returns($this->project);

        $this->view = new GitViews($controller);
    }

    public function tearDown() {
        ProjectManager::clearInstance();
        parent::tearDown();
    }

    public function testGetViewInverseURLArgumentIfActionIsBlobdiff() {
        $_REQUEST['a'] = 'blobdiff';
        $src_initial   = 'src';
        $dest_initial  = 'dest';
        $_GET['h']     = $src_initial;
        $_GET['hp']    = $dest_initial;

        $repository = mock('GitRepository');

        stub($repository)->getId()->returns(148);
        stub($repository)->getFullName()->returns('abcd');
        stub($repository)->getProject()->returns($this->project);
        stub($repository)->getGitRootPath()->returns('/home/abcd');

        $this->view->getView($repository);

        $this->assertEqual($_GET['h'], $dest_initial);
        $this->assertEqual($_GET['hp'], $src_initial);

    }

}

?>
