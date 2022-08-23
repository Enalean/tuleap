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
use Project;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabProjectsStub;
use Tuleap\Gitlab\Test\Stubs\CreateGitlabRepositoriesStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GroupCreatorTest extends TestCase
{
    private Credentials $credentials;
    private GitlabGroupPOSTRepresentation $representation;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentials    = CredentialsTestBuilder::get()->build();
        $this->representation = new GitlabGroupPOSTRepresentation(101, 1, "azertyuiop", "https://gitlab.example.com");
        $this->project        = ProjectTestBuilder::aProject()->build();
    }

    public function testItThrowsExceptionIfTheGitlabRepositoryIsInError(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithException(
            new GitlabRequestException(
                500,
                "What a fail !",
                null
            )
        );

        $gitlab_repository_creator = CreateGitlabRepositoriesStub::buildWithDefault();

        $group_creator = new GroupCreator($gitlab_repository_creator, $build_gitlab_project);

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }

    public function testItThrowsExceptionIfTheResponseFromAPIIsNotOk(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithDefault();

        $gitlab_repository_creator = CreateGitlabRepositoriesStub::buildWithException(
            new GitlabResponseAPIException('fail')
        );

        $group_creator = new GroupCreator($gitlab_repository_creator, $build_gitlab_project);

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }

    public function testItThrowsExceptionIfRepositoryWithTheSameNameExists(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithDefault();

        $gitlab_repository_creator = CreateGitlabRepositoriesStub::buildWithException(
            new GitlabRepositoryWithSameNameAlreadyIntegratedInProjectException('fail')
        );


        $group_creator = new GroupCreator($gitlab_repository_creator, $build_gitlab_project);

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }

    public function testItReturnsTheGroupRepresentation(): void
    {
        $build_gitlab_project      = BuildGitlabProjectsStub::buildWithGitlabProjects(
            [
                new GitlabProject(
                    9,
                    'Description',
                    'https://gitlab.example.com',
                    '/',
                    new \DateTimeImmutable('@0'),
                    'main'
                ),
                new GitlabProject(
                    10,
                    'Description 2',
                    'https://gitlab.example.com',
                    '/',
                    new \DateTimeImmutable('@0'),
                    'main'
                ),
            ]
        );
        $gitlab_repository_creator = CreateGitlabRepositoriesStub::buildWithIntegrations(
            [
                new GitlabRepositoryIntegration(
                    18,
                    9,
                    'root/repo01',
                    'Description',
                    'https://gitlab.example.com',
                    new \DateTimeImmutable(),
                    $this->project,
                    false
                ),
                new GitlabRepositoryIntegration(
                    18,
                    10,
                    'root/repo01',
                    'Description 2',
                    'https://gitlab.example.com',
                    new \DateTimeImmutable(),
                    $this->project,
                    false
                ),
            ]
        );

        $group_creator = new GroupCreator($gitlab_repository_creator, $build_gitlab_project);

        $result = $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);

        $excepted_gitlab_group_id       = 1;
        $expected_number_of_integration = 2;
        self::assertSame($excepted_gitlab_group_id, $result->id);
        self::assertSame($expected_number_of_integration, $result->number_of_integrations);
    }
}
