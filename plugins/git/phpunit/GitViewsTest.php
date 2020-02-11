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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitViewsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanReturnOptionsListOfProjectsTheUserIsAdminOf(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProject('123', 'Guinea Pig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $output = $view->getUserProjectsAsOptions($user, $manager, '50');
        $this->assertRegExp('/<option value="123"/', $output);
        $this->assertNotRegExp('/<option value="456"/', $output);
    }

    public function testOptionsShouldContainThePublicNameOfTheProject(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProject('123', 'Guinea Pig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertRegExp('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldContainTheUnixNameOfTheProjectAsTitle(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProject('123', 'Guinea Pig', 'gpig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertRegExp('/title="gpig"/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testOptionsShouldPurifyThePublicNameOfTheProject(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProject('123', 'Guinea < Pig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertRegExp('/Guinea &lt; Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    public function testCurrentProjectMustNotBeInProjectList(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProject('123', 'Guinea Pig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertNotRegExp('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '123'));
    }

    public function testProjectListMustContainsOnlyProjectsWithGitEnabled(): void
    {
        $user    = $this->givenAUserWithProjects();
        $project = $this->givenAProjectWithoutGitService('123', 'Guinea Pig');
        $manager = $this->givenAProjectManager($project);

        $view = \Mockery::mock(\GitViews::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertNotRegExp('/Guinea Pig/', $view->getUserProjectsAsOptions($user, $manager, '50'));
    }

    private function givenAProject($id, $name, $unixName = null, $useGit = true): Project
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getId')->andReturns($id);
        $project->shouldReceive('getPublicName')->andReturns($name); //see create_project()
        $project->shouldReceive('getUnixName')->andReturns($unixName);
        $project->shouldReceive('usesService')->with(GitPlugin::SERVICE_SHORTNAME)->andReturns($useGit);
        return $project;
    }

    private function givenAProjectWithoutGitService($id, $name): Project
    {
        return $this->givenAProject($id, $name, null, false);
    }

    private function givenAProjectManager($project): ProjectManager
    {
        $manager = \Mockery::spy(\ProjectManager::class);
        $manager->shouldReceive('getProject')->with($project->getId())->andReturns($project);

        return $manager;
    }

    private function givenAUserWithProjects(): PFUser
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getAllProjects')->andReturns(array('123', '456'));
        $user->shouldReceive('isMember')->with('123', 'A')->andReturns(true);
        $user->shouldReceive('isMember')->with('456', 'A')->andReturns(false);
        return $user;
    }
}
