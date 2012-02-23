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

require_once dirname(__FILE__) .'/../include/GitViews.class.php';

Mock::generate('Project');
Mock::generate('User');
Mock::generate('ProjectManager');


class GitViewsTest extends UnitTestCase {
	public function setUp() {
		
	}
	public function testCanReturnOptionsListOfProjectsTheUserIsAdminOf() {
		$user    = $this->GivenAUserWithProjects();
		$project = $this->GivenAProject('123', 'Guinea Pig');
		$manager = $this->GivenAProjectManager($project);
		
		$view = TestHelper::getPartialMock('GitViews', array());
		$output = $view->getUserProjectsAsOptions($user, $manager, '50');
		$this->assertPattern('/<option value="123">/', $output);
		$this->assertNoPattern('/<option value="456">/', $output);
	}
	
	public function testOptionsShouldContainThePublicNameOfTheProject() {
		$user    = $this->GivenAUserWithProjects();
		$project = $this->GivenAProject('123', 'Guinea Pig');
		$manager = $this->GivenAProjectManager($project);
		
		$view = TestHelper::getPartialMock('GitViews', array());
		$this->assertPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
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
	
	private function GivenAProject($id, $name) {
		$project = new MockProject();
		$project->setReturnValue('getId', $id);
		$project->setReturnValue('getPublicName', $name);
		return $project;
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

?>