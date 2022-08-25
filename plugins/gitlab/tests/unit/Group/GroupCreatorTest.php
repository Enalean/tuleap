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
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabProjectsStub;
use Tuleap\Gitlab\Test\Stubs\HandleGitlabRepositoryGroupLinkStub;
use Tuleap\Gitlab\Test\Stubs\RetrieveGitlabGroupInformationStub;
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

        $group_creator = new GroupCreator(
            $build_gitlab_project,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            HandleGitlabRepositoryGroupLinkStub::buildDefault()
        );

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }

    public function testItThrowsExceptionIfTheRequestResultHasSomeErrors(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithException(
            new GitlabResponseAPIException("fail")
        );


        $group_creator = new GroupCreator(
            $build_gitlab_project,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            HandleGitlabRepositoryGroupLinkStub::buildDefault()
        );

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }


    public function testItThrowsExceptionIfTheGitlabGroupAlreadyExists(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithDefault();

        $group_creator = new GroupCreator(
            $build_gitlab_project,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            HandleGitlabRepositoryGroupLinkStub::buildWithException(
                new GitlabGroupAlreadyExistsException("my_group")
            )
        );

        self::expectException(RestException::class);
        $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
    }

    public function testItReturnsTheRepresentation(): void
    {
        $build_gitlab_project = BuildGitlabProjectsStub::buildWithDefault();

        $created_group_id                     = 15;
        $number_of_integration                = 3;
        $gitlab_repository_group_link_handler = HandleGitlabRepositoryGroupLinkStub::buildWithRepresentation(
            new GitlabGroupRepresentation(15, 3)
        );

        $group_creator = new GroupCreator(
            $build_gitlab_project,
            RetrieveGitlabGroupInformationStub::buildDefault(),
            $gitlab_repository_group_link_handler
        );

        $result = $group_creator->createGroupAndIntegrations($this->credentials, $this->representation, $this->project);
        self::assertSame($created_group_id, $result->id);
        self::assertSame($number_of_integration, $result->number_of_integrations);
    }
}
