<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once 'bootstrap.php';


class GitViewsTest extends TuleapTestCase
{

    public function testCanReturnOptionsListOfProjectsTheUserIsAdminOf()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $output = $view->getUserProjectsAsOptions($user, $manager, '50');
        $this->assertPattern('/<option value="123"/', $output);
        $this->assertNoPattern('/<option value="456"/', $output);
    }

    public function testOptionsShouldContainThePublicNameOfTheProject()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldContainTheUnixNameOfTheProjectAsTitle()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig', 'gpig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertPattern('/title="gpig"/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldPurifyThePublicNameOfTheProject()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea < Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertPattern('/Guinea &lt; Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testCurrentProjectMustNotBeInProjectList()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProject('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertNoPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '123'));
    }

    public function testProjectListMustContainsOnlyProjectsWithGitEnabled()
    {
        $user    = $this->GivenAUserWithProjects();
        $project = $this->GivenAProjectWithoutGitService('123', 'Guinea Pig');
        $manager = $this->GivenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertNoPattern('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    private function GivenAProject($id, $name, $unixName = null, $useGit = true)
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getId')->andReturns($id);
        $project->shouldReceive('getPublicName')->andReturns(htmlspecialchars($name)); //see create_project()
        $project->shouldReceive('getUnixName')->andReturns($unixName);
        $project->shouldReceive('usesService')->with(GitPlugin::SERVICE_SHORTNAME)->andReturns($useGit);
        return $project;
    }

    private function GivenAProjectWithoutGitService($id, $name)
    {
        return $this->GivenAProject($id, $name, null, false);
    }

    private function GivenAProjectManager($project)
    {
        $manager = \Mockery::spy(\ProjectManager::class);
        $manager->shouldReceive('getProject')->with($project->getId())->andReturns($project);

        return $manager;
    }

    private function GivenAUserWithProjects()
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getAllProjects')->andReturns(array('123', '456'));
        $user->shouldReceive('isMember')->with('123', 'A')->andReturns(true);
        $user->shouldReceive('isMember')->with('456', 'A')->andReturns(false);
        return $user;
    }
}
