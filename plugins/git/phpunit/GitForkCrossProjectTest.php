<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git;

use Codendi_Request;
use GitPermissionsManager;
use GitRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use ProjectManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

final class GitForkCrossProjectTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['HTML'] = $GLOBALS['Response'];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML'], $_SESSION);
    }

    public function testExecutesForkCrossProjectActionWithForkRepositoriesView(): void
    {
        $groupId = 101;
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns($groupId);
        $forkPermissions = array();
        $toProjectId = 100;
        $toProject = \Mockery::spy(\Project::class);
        $toProject->shouldReceive('getId')->andReturns($toProjectId);
        $toProject->shouldReceive('getUnixNameLowerCase')->andReturns('toproject');

        $repo  = new GitRepository();
        $repos = array($repo);
        $repo_ids = '200';

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isMember')->andReturns(true);

        $usermanager = \Mockery::spy(\UserManager::class);
        $usermanager->shouldReceive('getCurrentUser')->andReturns($user);

        $projectManager = \Mockery::spy(\ProjectManager::class);
        $projectManager->shouldReceive('getProject')->with($toProjectId)->andReturns($toProject);

        $repositoryFactory = \Mockery::spy(\GitRepositoryFactory::class);
        $repositoryFactory->shouldReceive('getRepositoryById')->with($repo_ids)->andReturns($repo);

        $request = new Codendi_Request(array(
                                        'choose_destination' => 'project',
                                        'to_project' => $toProjectId,
                                        'repos' => $repo_ids,
                                        'repo_access' => $forkPermissions));

        $permissions_manager = \Mockery::spy(\GitPermissionsManager::class)->shouldReceive('userIsGitAdmin')->with($user, $toProject)->andReturns(true)->getMock();

        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $git->setProject($project);
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setProjectManager($projectManager);
        $git->setFactory($repositoryFactory);
        $git->setPermissionsManager($permissions_manager);

        $git->shouldReceive('addAction')->with('fork', array($repos, $toProject, '', GitRepository::REPO_SCOPE_PROJECT, $user, $GLOBALS['Response'], '/plugins/git/toproject/', $forkPermissions))->once();
        $git->shouldReceive('addAction')->with('getProjectRepositoryList', array($groupId))->once();
        $git->shouldReceive('addView')->with('forkRepositories')->once();

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, $user);
    }

    public function testAddsErrorWhenRepositoriesAreMissing(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns(11);
        $project->shouldReceive('getUnixNameLowerCase')->andReturns('projectname');

        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git->setProject($project);
        $git->setFactory(\Mockery::spy(\GitRepositoryFactory::class));
        $git->shouldReceive('addError')->with('No repository selected for the fork')->once();
        $git->shouldReceive('redirect')->with('/plugins/git/projectname/')->once();

        $request = new Codendi_Request(array('to_project' => 234, 'repo_access' => array()));

        $git->_doDispatchForkCrossProject($request, null);
    }

    public function testAddsErrorWhenDestinationProjectIsMissing(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns(11);
        $project->shouldReceive('getUnixNameLowerCase')->andReturns('projectname');

        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git->setProject($project);
        $git->shouldReceive('addError')->with('No project selected for the fork')->once();
        $git->shouldReceive('redirect')->with('/plugins/git/projectname/')->once();

        $request = new Codendi_Request(array(
            'repos'       => array('qdfj'),
            'repo_access' => array()
        ));

        $git->_doDispatchForkCrossProject($request, null);
    }

    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks(): void
    {
        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $exception = new \Exception();
        $git->shouldReceive('checkSynchronizerToken')->andThrows($exception);
        $this->expectExceptionObject($exception);
        $git->_doDispatchForkCrossProject(null, null);
    }

    public function testUserMustBeAdminOfTheDestinationProject(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns(123);
        $project->shouldReceive('getUnixNameLowerCase')->andReturns('projectname');

        $user = \Mockery::spy(\PFUser::class);

        $to_project = Mockery::mock(Project::class);
        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->with(666)->andReturn($to_project);

        $permissions_manager = Mockery::mock(GitPermissionsManager::class);
        $permissions_manager->shouldReceive('userIsGitAdmin')->with($user, $to_project)->andReturn(false);

        $request = new Codendi_Request(array(
            'to_project'  => 666,
            'repos'       => "1",
            'repo_access' => array()
        ));

        $git = \Mockery::mock(\Git::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $git->setProject($project);
        $git->setPermissionsManager($permissions_manager);
        $git->setProjectManager($project_manager);
        $git->shouldReceive('addError')->with('Only project administrator can create repositories')->once();
        $git->shouldReceive('addAction')->never();

        $git->_doDispatchForkCrossProject($request, $user);
    }
}
