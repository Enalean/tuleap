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

namespace Tuleap\Git\ForkRepositories;

use GitPlugin;
use Tuleap\Git\GitService;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\GitBackendInterfaceStub;
use Tuleap\Git\Tests\Stub\RetrieveAllGitRepositoriesStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForkRepositoriesPresenterBuilderTest extends TestCase
{
    private const int PROJECT_101_ID = 101;
    private const int PROJECT_102_ID = 102;
    private const int PROJECT_103_ID = 103;

    private \PFUser $user;
    private \Project $project;
    private \Project $project_102;
    private \Project $project_103;
    private GitBackendInterfaceStub $git_backend;

    #[\Override]
    protected function setUp(): void
    {
        $this->project     = ProjectTestBuilder::aProject()->withId(self::PROJECT_101_ID)->withServices($this->buildServiceForProject(self::PROJECT_101_ID, GitPlugin::SERVICE_SHORTNAME))->build();
        $this->project_102 = ProjectTestBuilder::aProject()->withId(self::PROJECT_102_ID)->withServices($this->buildServiceForProject(self::PROJECT_102_ID, 'Whatever'))->build();
        $this->project_103 = ProjectTestBuilder::aProject()->withId(self::PROJECT_103_ID)->withServices($this->buildServiceForProject(self::PROJECT_103_ID, GitPlugin::SERVICE_SHORTNAME))->build();

        $this->user = UserTestBuilder::aUser()->withId(114)
            ->withProjects([self::PROJECT_101_ID])
            ->withMemberOf($this->project)
            ->withoutSiteAdministrator()
            ->build();

        $this->git_backend = GitBackendInterfaceStub::build();
    }

    private function buildServiceForProject(int $project_id, string $service_name): GitService
    {
        return new GitService(
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            ['short_name' => $service_name]
        );
    }

    private function getInitializedGitRepository(int $id, string $name): GitRepositoryTestBuilder
    {
        return GitRepositoryTestBuilder::aProjectRepository()
            ->withBackend($this->git_backend)
            ->inProject($this->project)
            ->withId($id)
            ->withName($name);
    }

    /**
     * @param list<\GitRepository> $project_repositories
     * @param list<\Project> $user_projects
     */
    private function buildPresenter(array $project_repositories, array $user_projects): ForkRepositoriesPresenter
    {
        $builder = new ForkRepositoriesPresenterBuilder(
            ProjectByIDFactoryStub::buildWith($this->project, ...$user_projects),
            RetrieveAllGitRepositoriesStub::build()->withRepositories(
                $this->project,
                ...$project_repositories
            ),
        );

        return $builder->build($this->user, $this->project, CSRFSynchronizerTokenStub::buildSelf());
    }

    public function testItBuildsTheListOfForkableRepositories(): void
    {
        $repository_1 = $this->getInitializedGitRepository(1, 'repository-1')->build();
        $repository_2 = $this->getInitializedGitRepository(2, 'repository-2')->build();
        $repository_3 = $this->getInitializedGitRepository(3, 'repository-3')->withDeletionDate('2025-10-30 11:55:00')->build();
        $repository_4 = $this->getInitializedGitRepository(4, 'repository-4')->withScope(\GitRepository::REPO_SCOPE_INDIVIDUAL)->build();

        $this->git_backend->withUsersWhoHaveReadPermission($repository_1, $this->user);
        $this->git_backend->withUsersWhoHaveReadPermission($repository_3, $this->user);
        $this->git_backend->withUsersWhoHaveReadPermission($repository_4, $this->user);

        $presenter = $this->buildPresenter([$repository_1, $repository_2, $repository_3, $repository_4], []);

        self::assertTrue($presenter->has_forkable_repositories);
        self::assertCount(1, $presenter->forkable_repositories);
        self::assertEquals(new ForkableRepositoryPresenter($repository_1->getId(), $repository_1->getName()), $presenter->forkable_repositories[0]);
    }

    public function testNoRepositories(): void
    {
        $presenter = $this->buildPresenter([], []);

        self::assertCount(0, $presenter->forkable_repositories);
        self::assertFalse($presenter->has_forkable_repositories);
    }

    public function testItBuildsTheListOfDestinationProjects(): void
    {
        $this->user = UserTestBuilder::aUser()->withId(114)
            ->withProjects([self::PROJECT_101_ID, self::PROJECT_102_ID, self::PROJECT_103_ID])
            ->withMemberOf($this->project)
            ->withAdministratorOf($this->project_102)
            ->withAdministratorOf($this->project_103)
            ->withoutSiteAdministrator()
            ->build();

        $presenter = $this->buildPresenter([], [$this->project, $this->project_102, $this->project_103]);

        self::assertTrue($presenter->has_user_projects);
        self::assertCount(1, $presenter->user_projects);
        self::assertEquals(new ForkDestinationProjectPresenter((int) $this->project_103->getId(), $this->project_103->getIconAndPublicName(), $this->project_103->getUnixNameLowerCase()), $presenter->user_projects[0]);
    }

    public function testNoDestinationProjects(): void
    {
        $this->user = UserTestBuilder::aUser()->withId(114)
            ->withProjects([self::PROJECT_101_ID])
            ->withMemberOf($this->project)
            ->withoutSiteAdministrator()
            ->build();

        $presenter = $this->buildPresenter([], [$this->project]);

        self::assertCount(0, $presenter->user_projects);
        self::assertFalse($presenter->has_user_projects);
    }
}
