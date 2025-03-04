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
use Exception;
use Git;
use GitPermissionsManager;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use ProjectManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitForkCrossProjectTest extends TestCase
{
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
        $groupId         = 101;
        $project         = ProjectTestBuilder::aProject()->withId($groupId)->build();
        $forkPermissions = [];
        $toProjectId     = 100;
        $toProject       = ProjectTestBuilder::aProject()->withId($toProjectId)->withUnixName('toproject')->build();

        $repo     = new GitRepository();
        $repos    = [$repo];
        $repo_ids = '200';

        $user = UserTestBuilder::aUser()->withMemberOf($toProject)->build();

        $usermanager = $this->createMock(UserManager::class);
        $usermanager->method('getCurrentUser')->willReturn($user);

        $projectManager = $this->createMock(ProjectManager::class);
        $projectManager->method('getProject')->with($toProjectId)->willReturn($toProject);

        $repositoryFactory = $this->createMock(GitRepositoryFactory::class);
        $repositoryFactory->method('getRepositoryById')->with($repo_ids)->willReturn($repo);

        $request         = new HTTPRequest();
        $request->params = [
            'choose_destination' => 'project',
            'to_project'         => $toProjectId,
            'repos'              => $repo_ids,
            'repo_access'        => $forkPermissions,
        ];

        $permissions_manager = $this->createMock(GitPermissionsManager::class);
        $permissions_manager->method('userIsGitAdmin')->with($user, $toProject)->willReturn(true);

        $git = $this->createPartialMock(Git::class, ['addAction', 'addView']);

        $git->setProject($project);
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setProjectManager($projectManager);
        $git->setFactory($repositoryFactory);
        $git->setPermissionsManager($permissions_manager);
        $matcher = self::exactly(2);

        $git->expects($matcher)->method('addAction')->willReturnCallback(function (...$parameters) use ($matcher, $repos, $toProject, $user, $forkPermissions, $groupId) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('fork', $parameters[0]);
                self::assertSame([$repos, $toProject, '', GitRepository::REPO_SCOPE_PROJECT, $user, $GLOBALS['Response'], '/plugins/git/toproject/', $forkPermissions], $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('getProjectRepositoryList', $parameters[0]);
                self::assertSame($groupId, (int) $parameters[1][0]);
            }
        });
        $git->expects(self::once())->method('addView')->with('forkRepositories');

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, $user);
    }

    public function testAddsErrorWhenRepositoriesAreMissing(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(11)->withUnixName('projectname')->build();

        $git = $this->createPartialMock(Git::class, ['addError', 'redirect']);
        $git->setProject($project);
        $git->setFactory($this->createMock(GitRepositoryFactory::class));
        $git->expects(self::once())->method('addError')->with('No repository selected for the fork');
        $git->expects(self::once())->method('redirect')->with('/plugins/git/projectname/');

        $request = new Codendi_Request(['to_project' => 234, 'repo_access' => []]);

        $git->_doDispatchForkCrossProject($request, null);
    }

    public function testAddsErrorWhenDestinationProjectIsMissing(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(11)->withUnixName('projectname')->build();

        $git = $this->createPartialMock(Git::class, ['addError', 'redirect']);
        $git->setProject($project);
        $git->expects(self::once())->method('addError')->with('No project selected for the fork');
        $git->expects(self::once())->method('redirect')->with('/plugins/git/projectname/');

        $request = new Codendi_Request([
            'repos'       => ['qdfj'],
            'repo_access' => [],
        ]);

        $git->_doDispatchForkCrossProject($request, null);
    }

    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks(): void
    {
        $git       = $this->createPartialMock(Git::class, ['checkSynchronizerToken']);
        $exception = new Exception();
        $git->method('checkSynchronizerToken')->willThrowException($exception);
        $this->expectExceptionObject($exception);
        $git->_doDispatchForkCrossProject(null, null);
    }

    public function testUserMustBeAdminOfTheDestinationProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->withUnixName('projectname')->build();

        $user = UserTestBuilder::aUser()->build();

        $to_project      = ProjectTestBuilder::aProject()->build();
        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->with(666)->willReturn($to_project);

        $permissions_manager = $this->createMock(GitPermissionsManager::class);
        $permissions_manager->method('userIsGitAdmin')->with($user, $to_project)->willReturn(false);

        $request = new Codendi_Request([
            'to_project'  => 666,
            'repos'       => '1',
            'repo_access' => [],
        ]);

        $git = $this->createPartialMock(Git::class, ['addError', 'addAction']);
        $git->setProject($project);
        $git->setPermissionsManager($permissions_manager);
        $git->setProjectManager($project_manager);
        $git->expects(self::once())->method('addError')->with('Only project administrator can create repositories');
        $git->expects(self::never())->method('addAction');

        $git->_doDispatchForkCrossProject($request, $user);
    }
}
