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

namespace Tuleap\Gitlab\Group;

use Luracast\Restler\RestException;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Builder\GitlabProjectBuilder;
use Tuleap\Gitlab\Test\Builder\RepositoryIntegrationBuilder;
use Tuleap\Gitlab\Test\Stubs\AddNewGroupStub;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabProjectsStub;
use Tuleap\Gitlab\Test\Stubs\CreateGitlabRepositoriesStub;
use Tuleap\Gitlab\Test\Stubs\InsertGroupTokenStub;
use Tuleap\Gitlab\Test\Stubs\LinkARepositoryIntegrationToAGroupStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGitlabGroupInformationStub;
use Tuleap\Gitlab\Test\Stubs\SaveIntegrationBranchPrefixStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGitlabRepositoryIsIntegratedStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGroupIsAlreadyLinkedStub;
use Tuleap\Gitlab\Test\Stubs\VerifyProjectIsAlreadyLinkedStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

final class GroupCreatorTest extends TestCase
{
    private const INTEGRATED_GROUP_ID = 15;
    private const PROJECT_ID          = 101;

    private BuildGitlabProjectsStub $project_builder;
    private VerifyGroupIsAlreadyLinkedStub $group_integrated_verifier;
    private VerifyProjectIsAlreadyLinkedStub $project_linked_verifier;
    private ?string $branch_prefix;

    protected function setUp(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::withProjects([
            GitlabProjectBuilder::aGitlabProject(19)->build(),
            GitlabProjectBuilder::aGitlabProject(15)->build(),
            GitlabProjectBuilder::aGitlabProject(21)->build(),

        ]);
        $this->group_integrated_verifier = VerifyGroupIsAlreadyLinkedStub::withNeverLinked();
        $this->project_linked_verifier   = VerifyProjectIsAlreadyLinkedStub::withNeverLinked();

        $this->branch_prefix = 'dev-';
    }

    private function createGroup(): GitlabGroupRepresentation
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withPublicName('exegetist')->build();

        $first_integration  = RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(91)
            ->inProject($project)
            ->build();
        $second_integration = RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(92)
            ->inProject($project)
            ->build();
        $third_integration  = RepositoryIntegrationBuilder::aGitlabRepositoryIntegration(93)
            ->inProject($project)
            ->build();

        $creator = new GroupCreator(
            $this->project_builder,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            new GitlabRepositoryGroupLinkHandler(
                new DBTransactionExecutorPassthrough(),
                VerifyGitlabRepositoryIsIntegratedStub::withNeverIntegrated(),
                CreateGitlabRepositoriesStub::withSuccessiveIntegrations(
                    $first_integration,
                    $second_integration,
                    $third_integration
                ),
                new GitlabGroupFactory(
                    $this->group_integrated_verifier,
                    $this->project_linked_verifier,
                    AddNewGroupStub::withGroupId(self::INTEGRATED_GROUP_ID),
                ),
                InsertGroupTokenStub::build(),
                LinkARepositoryIntegrationToAGroupStub::withCallCount(),
                SaveIntegrationBranchPrefixStub::withCallCount()
            )
        );

        $credentials = CredentialsTestBuilder::get()->build();
        $post        = new GitlabGroupPOSTRepresentation(
            self::PROJECT_ID,
            1,
            'azertyuiop',
            'https://gitlab.example.com',
            true,
            $this->branch_prefix
        );

        return $creator->createGroupAndIntegrations($credentials, $post, $project);
    }

    public function testItReturnsTheRepresentation(): void
    {
        $result = $this->createGroup();
        self::assertSame(self::INTEGRATED_GROUP_ID, $result->id);
        self::assertSame(3, $result->number_of_integrations);
    }

    public function testItThrowsExceptionIfTheGitlabRepositoryIsInError(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabRequestException(
                500,
                "What a fail !",
                null
            )
        );

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsExceptionIfTheRequestResultHasSomeErrors(): void
    {
        $this->project_builder = BuildGitlabProjectsStub::buildWithException(
            new GitlabResponseAPIException("fail")
        );

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsExceptionIfTheGitlabGroupAlreadyExists(): void
    {
        $this->group_integrated_verifier = VerifyGroupIsAlreadyLinkedStub::withAlwaysLinked();

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsIfTheProjectIsAlreadyLinkedToAGitlabGroup(): void
    {
        $this->project_linked_verifier = VerifyProjectIsAlreadyLinkedStub::withAlwaysLinked();

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsIfTheBranchPrefixProducesInvalidBranchNames(): void
    {
        $this->branch_prefix = 'invalid:';

        $this->expectException(RestException::class);
        $this->createGroup();
    }

    public function testItThrowsWhenTheBranchPrefixIsNull(): void
    {
        $this->branch_prefix = null;

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->createGroup();
    }
}
