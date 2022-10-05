<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use Project;
use Tuleap\Gitlab\API\GitlabRequestFault;
use Tuleap\Gitlab\API\GitlabResponseAPIFault;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Builder\GitlabProjectBuilder;
use Tuleap\Gitlab\Test\Builder\GroupLinkBuilder;
use Tuleap\Gitlab\Test\Builder\RepositoryIntegrationBuilder;
use Tuleap\Gitlab\Test\Stubs\CreateGitlabRepositoriesStub;
use Tuleap\Gitlab\Test\Stubs\LinkARepositoryIntegrationToAGroupStub;
use Tuleap\Gitlab\Test\Stubs\SaveIntegrationBranchPrefixStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGitlabRepositoryIsIntegratedStub;
use Tuleap\Gitlab\Test\Stubs\VerifyRepositoryIntegrationsAlreadyLinkedStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GitlabProjectIntegratorTest extends TestCase
{
    private const PROJECT_ID = 101;

    private Project $project;

    private VerifyGitlabRepositoryIsIntegratedStub $is_gitlab_repository_integrated;
    private CreateGitlabRepositoriesStub $gitlab_repository_creator;
    private VerifyRepositoryIntegrationsAlreadyLinkedStub $is_repository_integration_already_linked;
    private SaveIntegrationBranchPrefixStub $branch_prefix_saver;
    private LinkARepositoryIntegrationToAGroupStub $repository_integration_group_link;
    private GroupLink $group_link;


    protected function setUp(): void
    {
        $this->is_gitlab_repository_integrated = VerifyGitlabRepositoryIsIntegratedStub::withNeverIntegrated();

        $this->project      = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withPublicName('exegetist')->build();
        $first_integration  = RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(91)
                                                          ->inProject($this->project)
                                                          ->build();
        $second_integration = RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(92)
                                                          ->inProject($this->project)
                                                          ->build();

        $this->is_repository_integration_already_linked = VerifyRepositoryIntegrationsAlreadyLinkedStub::withNeverLinked();

        $this->branch_prefix_saver               = SaveIntegrationBranchPrefixStub::withCallCount();
        $this->repository_integration_group_link = LinkARepositoryIntegrationToAGroupStub::withCallCount();

        $this->gitlab_repository_creator = CreateGitlabRepositoriesStub::withSuccessiveIntegrations(
            $first_integration,
            $second_integration
        );

        $this->group_link = GroupLinkBuilder::aGroupLink(3)->build();
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function integrateGitlabProject(): Ok|Err
    {
        $gitlab_project_integrator = new GitlabProjectIntegrator(
            $this->is_gitlab_repository_integrated,
            $this->gitlab_repository_creator,
            $this->branch_prefix_saver,
            $this->repository_integration_group_link,
            $this->is_repository_integration_already_linked
        );

        $gitlab_project_1 = GitlabProjectBuilder::aGitlabProject(10)->build();
        $gitlab_project_2 = GitlabProjectBuilder::aGitlabProject(11)->build();
        $credentials      = CredentialsTestBuilder::get()->build();


        return $gitlab_project_integrator->integrateSeveralProjects([$gitlab_project_1, $gitlab_project_2], $this->project, $credentials, $this->group_link);
    }

    public function testItReturnsOkIfTheGitlabProjectIsAlreadyGroupLinkedAndIntegrated(): void
    {
        $this->is_gitlab_repository_integrated          = VerifyGitlabRepositoryIsIntegratedStub::withAlwaysIntegrated();
        $this->is_repository_integration_already_linked = VerifyRepositoryIntegrationsAlreadyLinkedStub::withAlreadyLinked();

        $result = $this->integrateGitlabProject();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(0, $this->branch_prefix_saver->getCallCount());
        self::assertSame(0, $this->repository_integration_group_link->getCallCount());
    }

    public function testItReturnsAnErrorIfTheRequestToGitlabFails(): void
    {
        $this->gitlab_repository_creator = CreateGitlabRepositoriesStub::withGitlabRequestException();

        $result = $this->integrateGitlabProject();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitlabRequestFault::class, $result->error);
    }

    public function testItReturnsAnErrorIfTheGitlabResponseIsNot200(): void
    {
        $this->gitlab_repository_creator = CreateGitlabRepositoriesStub::withGitlabResponseAPIException();

        $result = $this->integrateGitlabProject();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitlabResponseAPIFault::class, $result->error);
    }

    public function testItIntegrateAndLinkTheGitlabProjectWithBranchPrefix(): void
    {
        $result = $this->integrateGitlabProject();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(2, $this->branch_prefix_saver->getCallCount());
        self::assertSame(2, $this->repository_integration_group_link->getCallCount());
    }

    public function testLinksAnAlreadyExistingIntegratedRepository(): void
    {
        $this->is_gitlab_repository_integrated          = VerifyGitlabRepositoryIsIntegratedStub::withAlwaysIntegrated();
        $this->is_repository_integration_already_linked = VerifyRepositoryIntegrationsAlreadyLinkedStub::withNeverLinked();

        $result = $this->integrateGitlabProject();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(0, $this->branch_prefix_saver->getCallCount());
        self::assertSame(2, $this->repository_integration_group_link->getCallCount());
    }
}
