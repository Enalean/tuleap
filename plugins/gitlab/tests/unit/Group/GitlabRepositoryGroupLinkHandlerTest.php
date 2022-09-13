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

use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\GitlabRepositoryGroupLinkHandler;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\AddNewGroupStub;
use Tuleap\Gitlab\Test\Stubs\CreateGitlabRepositoriesStub;
use Tuleap\Gitlab\Test\Stubs\LinkARepositoryIntegrationToAGroupStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGitlabRepositoryIsIntegratedStub;
use Tuleap\Gitlab\Test\Stubs\InsertGroupTokenStub;
use Tuleap\Gitlab\Test\Stubs\VerifyGroupIsAlreadyLinkedStub;
use Tuleap\Gitlab\Test\Stubs\VerifyProjectIsAlreadyLinkedStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

final class GitlabRepositoryGroupLinkHandlerTest extends TestCase
{
    private const GROUP_ID                    = 45;
    private const SECOND_GITLAB_REPOSITORY_ID = 10;
    private const FIRST_GITLAB_REPOSITORY_ID  = 9;
    private VerifyGitlabRepositoryIsIntegratedStub $verify_gitlab_repository_is_integrated;
    private LinkARepositoryIntegrationToAGroupStub $link_integration_to_group;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->verify_gitlab_repository_is_integrated = VerifyGitlabRepositoryIsIntegratedStub::withNeverIntegrated();

        $this->link_integration_to_group = LinkARepositoryIntegrationToAGroupStub::withCallCount();
    }

    private function integrate(): \Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation
    {
        $gitlab_projects = [
            new GitlabProject(
                self::FIRST_GITLAB_REPOSITORY_ID,
                'Description',
                'https://gitlab.example.com',
                '/',
                new \DateTimeImmutable('@0'),
                'main'
            ),
            new GitlabProject(
                self::SECOND_GITLAB_REPOSITORY_ID,
                'Description 2',
                'https://gitlab.example.com',
                '/',
                new \DateTimeImmutable('@0'),
                'main'
            ),
        ];

        $gitlab_group = GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi([
            'id'         => 102,
            'name'       => 'nine-nine',
            'avatar_url' => 'https://avatar.example.com',
            'full_path'  => 'brookyln/nine-nine',
            'web_url'    => 'https://gitlab.example.com/nine-nine',
        ]);

        $gitlab_repository_creator = CreateGitlabRepositoriesStub::withSuccessiveIntegrations(
            new GitlabRepositoryIntegration(
                1,
                self::FIRST_GITLAB_REPOSITORY_ID,
                'irrelevant',
                'irrelevant',
                'irrelevant',
                new \DateTimeImmutable('@0'),
                $this->project,
                false,
            ),
            new GitlabRepositoryIntegration(
                2,
                self::SECOND_GITLAB_REPOSITORY_ID,
                'name',
                'desc',
                'repo_url',
                new \DateTimeImmutable('@0'),
                $this->project,
                false
            )
        );

        $handler = new GitlabRepositoryGroupLinkHandler(
            new DBTransactionExecutorPassthrough(),
            $this->verify_gitlab_repository_is_integrated,
            $gitlab_repository_creator,
            new GitlabGroupFactory(
                VerifyGroupIsAlreadyLinkedStub::withNeverLinked(),
                VerifyProjectIsAlreadyLinkedStub::withNeverLinked(),
                AddNewGroupStub::withGroupId(self::GROUP_ID)
            ),
            InsertGroupTokenStub::build(),
            $this->link_integration_to_group
        );

        $credentials = CredentialsTestBuilder::get()->build();

        return $handler->integrateGitlabRepositoriesInProject(
            $credentials,
            $gitlab_projects,
            $this->project,
            GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration(),
            $gitlab_group
        );
    }

    public function testItReturnsTheNewGroupIDAndTheNumberOfRepositoriesIntegrated(): void
    {
        $result = $this->integrate();

        self::assertSame(self::GROUP_ID, $result->id);
        self::assertSame(2, $result->number_of_integrations);
        self::assertSame(2, $this->link_integration_to_group->getCallCount());
    }

    public function testItDoesNotRecreateRepositoriesThatWereAlreadyIntegrated(): void
    {
        $this->verify_gitlab_repository_is_integrated = VerifyGitlabRepositoryIsIntegratedStub::withAlwaysIntegrated();

        $result = $this->integrate();

        self::assertSame(self::GROUP_ID, $result->id);
        self::assertSame(2, $result->number_of_integrations);
        self::assertSame(2, $this->link_integration_to_group->getCallCount());
    }
}
