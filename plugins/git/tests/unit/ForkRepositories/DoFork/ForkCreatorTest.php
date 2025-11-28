<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\DoFork;

use CuyZ\Valinor\MapperBuilder;
use DateTimeImmutable;
use Git;
use GitRepositoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Git\ForkRepositories\ForkPathContainsDoubleDotsFault;
use Tuleap\Git\ForkRepositories\Permissions\MissingRequiredParametersFault;
use Tuleap\Git\PathJoinUtil;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\ForkRepositories\DoFork\CheckDoForkRepositoriesCSRFStub;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\Git\Tests\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForkCreatorTest extends TestCase
{
    private const REPOSITORY_1_ID        = 1;
    private const REPOSITORY_2_ID        = 2;
    private const DESTINATION_PROJECT_ID = 104;

    private GitRepositoryManager&MockObject $repository_manager;
    private \ProjectHistoryDao&MockObject $project_history_dao;
    private VerifyUserIsGitAdministratorStub $git_permissions_manager;
    private \Project $source_project;
    private \Project $destination_project;
    private \GitRepository $repository_1;
    private \GitRepository $repository_2;
    private array $permissions;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository_manager      = $this->createMock(GitRepositoryManager::class);
        $this->project_history_dao     = $this->createMock(\ProjectHistoryDao::class);
        $this->git_permissions_manager = VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator();
        $this->source_project          = ProjectTestBuilder::aProject()->build();
        $this->destination_project     = ProjectTestBuilder::aProject()->withId(self::DESTINATION_PROJECT_ID)->build();
        $this->repository_1            = GitRepositoryTestBuilder::aProjectRepository()->withId(self::REPOSITORY_1_ID)->inProject($this->source_project)->build();
        $this->repository_2            = GitRepositoryTestBuilder::aProjectRepository()->withId(self::REPOSITORY_2_ID)->inProject($this->source_project)->build();
        $this->permissions             = [
            Git::PERM_READ => [(string) ProjectUGroup::PROJECT_MEMBERS],
            Git::PERM_WRITE => [(string) ProjectUGroup::PROJECT_MEMBERS],
            Git::PERM_WPLUS => [(string) ProjectUGroup::PROJECT_ADMIN],
        ];
    }

    private function buildForkCreator(): ForkCreator
    {
        return new ForkCreator(
            $this->repository_manager,
            $this->git_permissions_manager,
            $this->project_history_dao,
            ProjectByIDFactoryStub::buildWith(
                $this->source_project,
                $this->destination_project,
            ),
            RetrieveGitRepositoryStub::withGitRepositories(
                $this->repository_1,
                $this->repository_2,
            ),
            new DoForkRepositoriesFormInputsBuilder(new MapperBuilder()->mapper()),
            CheckDoForkRepositoriesCSRFStub::build(),
        );
    }

    private function buildRequestWithParsedBody(array $parsed_body): ServerRequestInterface
    {
        return (new NullServerRequest())->withParsedBody($parsed_body);
    }

    public function testHappyPathPersonalRepositories(): void
    {
        $user      = UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build();
        $fork_path = 'my-forks/';

        $this->repository_manager->expects($this->once())->method('forkRepositories')->with(
            [$this->repository_1, $this->repository_2],
            $this->source_project,
            $user,
            PathJoinUtil::userRepoPath($user->getUserName(), $fork_path),
            \GitRepository::REPO_SCOPE_INDIVIDUAL,
            $this->permissions,
        )->willReturn(Result::ok([]));

        $this->project_history_dao->expects($this->once())->method('addHistory')->with(
            $this->source_project,
            $user,
            self::isInstanceOf(DateTimeImmutable::class),
            'git_fork_repositories',
            (string) $this->source_project->getID(),
        );

        $result = $this->buildForkCreator()->processPersonalFork(
            $user,
            $this->source_project,
            $this->buildRequestWithParsedBody([
                'path' => $fork_path,
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testPersonalForkErrorWhenUserIsNotMemberOfForkDestinationProject(): void
    {
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processPersonalFork(
            UserTestBuilder::aUser()->withoutMemberOfProjects()->withoutSiteAdministrator()->build(),
            $this->source_project,
            $this->buildRequestWithParsedBody([
                'path' => 'my-forks/',
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserIsNotProjectMemberFault::class, $result->error);
    }

    public function testPersonalForkErrorWhenNoRepositoriesIds(): void
    {
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processPersonalFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->source_project,
            $this->buildRequestWithParsedBody([
                'path' => 'my-forks/',
                'repos' => '',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testPersonalForkErrorWhenPathIsNotValid(): void
    {
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processPersonalFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->source_project,
            $this->buildRequestWithParsedBody([
                'path' => '../my-forks/',
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ForkPathContainsDoubleDotsFault::class, $result->error);
    }

    public function testPersonalForkErrorWhenForkDidNotSucceed(): void
    {
        $fault = Fault::fromMessage('Meh');
        $this->repository_manager->expects($this->once())->method('forkRepositories')->willReturn(Result::err($fault));
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processPersonalFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->source_project,
            $this->buildRequestWithParsedBody([
                'path' => 'my-forks/',
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertSame($fault, $result->error);
    }

    public function testHappyPathCrossProjectRepositories(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->repository_manager->expects($this->once())->method('forkRepositories')->with(
            [$this->repository_1, $this->repository_2],
            $this->destination_project,
            $user,
            '',
            \GitRepository::REPO_SCOPE_PROJECT,
            $this->permissions,
        )->willReturn(Result::ok([]));

        $this->project_history_dao->expects($this->once())->method('addHistory')->with(
            $this->destination_project,
            $user,
            self::isInstanceOf(DateTimeImmutable::class),
            'git_fork_repositories',
            (string) $this->destination_project->getID(),
        );

        $result = $this->buildForkCreator()->processCrossProjectsFork(
            $user,
            $this->buildRequestWithParsedBody([
                'to_project' => (string) self::DESTINATION_PROJECT_ID,
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testCrossProjectForkErrorWhenUserIsNotAdminOfDestinationRepository(): void
    {
        $this->git_permissions_manager = VerifyUserIsGitAdministratorStub::withNeverGitAdministrator();
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processCrossProjectsFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->buildRequestWithParsedBody([
                'to_project' => (string) self::DESTINATION_PROJECT_ID,
                'repos' => '1',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserIsNotGitAdminOfDestinationProjectFault::class, $result->error);
    }

    public function testCrossProjectsForkErrorWhenNoRepositoriesIds(): void
    {
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processCrossProjectsFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->buildRequestWithParsedBody([
                'to_project' => (string) self::DESTINATION_PROJECT_ID,
                'repos' => '',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testCrossProjectsForkErrorWhenNoDestinationProjectId(): void
    {
        $this->repository_manager->expects($this->never())->method('forkRepositories');
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processCrossProjectsFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->buildRequestWithParsedBody([
                'to_project' => '',
                'repos' => '1',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testCrossProjectsForkErrorWhenForkDidNotSucceed(): void
    {
        $fault = Fault::fromMessage('Meh');
        $this->repository_manager->expects($this->once())->method('forkRepositories')->willReturn(Result::err($fault));
        $this->project_history_dao->expects($this->never())->method('addHistory');

        $result = $this->buildForkCreator()->processCrossProjectsFork(
            UserTestBuilder::aUser()->withMemberOf($this->source_project)->withoutSiteAdministrator()->build(),
            $this->buildRequestWithParsedBody([
                'to_project' => (string) self::DESTINATION_PROJECT_ID,
                'repos' => '1,2',
                'repo_access' => $this->permissions,
            ])
        );

        self::assertTrue(Result::isErr($result));
        self::assertSame($fault, $result->error);
    }
}
