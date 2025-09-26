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
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use ProjectManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitForkRepositoriesTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    #[\Override]
    protected function setUp(): void
    {
        $GLOBALS['HTML'] = $GLOBALS['Response'];
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['HTML'], $_SESSION);
    }

    public function testRendersForkRepositoriesView(): void
    {
        $request = HTTPRequestBuilder::get()->withParams(['choose_destination' => 'personal', 'repos' => '10'])->build();

        $git = $this->createPartialMock(Git::class, ['addView']);
        $git->setRequest($request);
        $git->expects($this->once())->method('addView')->with('forkRepositories');

        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->method('getRepositoryById')->willReturn(GitRepositoryTestBuilder::aProjectRepository()->build());
        $git->setFactory($factory);

        $user = $this->createMock(PFUser::class);
        $user->method('getId');
        $user->method('isMember')->willReturn(true);
        $user->method('getUserName')->willReturn('testman');
        $git->user = $user;

        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $git->setProjectManager($project_manager);

        $git->_dispatchActionAndView('do_fork_repositories', null, null, null, $user);
    }

    public function testExecutesForkRepositoriesActionWithAListOfRepos(): void
    {
        $groupId = 101;
        $repo    = new GitRepository();
        $repos   = [$repo];
        $user    = new PFUser();
        $user->setId(42);
        $user->setUserName('Ben');
        $path            = PathJoinUtil::userRepoPath('Ben', 'toto');
        $forkPermissions = [];

        $project = ProjectTestBuilder::aProject()->withId($groupId)->withUnixName('projectname')->build();

        $projectManager = $this->createMock(ProjectManager::class);
        $projectManager->method('getProject')->with($groupId)->willReturn($project);

        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->method('getRepositoryById')->willReturn($repo);

        $git = $this->createPartialMock(Git::class, ['addAction']);
        $git->setProject($project);
        $git->setProjectManager($projectManager);
        $matcher = self::atLeast(2);
        $git->expects($matcher)->method('addAction')->willReturnCallback(function (...$parameters) use ($matcher, $groupId, $repos, $project, $path, $user, $forkPermissions) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('getProjectRepositoryList', $parameters[0]);
                self::assertSame($groupId, (int) $parameters[1][0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('fork', $parameters[0]);
                self::assertSame([$repos, $project, $path, GitRepository::REPO_SCOPE_INDIVIDUAL, $user, $GLOBALS['HTML'], '/plugins/git/?group_id=101&user=42', $forkPermissions], $parameters[1]);
            }
        });
        $request = new Codendi_Request([
            'repos'       => '1001',
            'path'        => 'toto',
            'repo_access' => $forkPermissions,
        ]);
        $git->setFactory($factory);
        $git->_doDispatchForkRepositories($request, $user);
    }

    public function testItUsesTheSynchronizerTokenToAvoidDuplicateForks(): void
    {
        $git       = $this->createPartialMock(Git::class, ['checkSynchronizerToken']);
        $exception = new Exception();
        $git->method('checkSynchronizerToken')->willThrowException($exception);
        $this->expectExceptionObject($exception);
        $git->_doDispatchForkRepositories(null, null);
    }
}
