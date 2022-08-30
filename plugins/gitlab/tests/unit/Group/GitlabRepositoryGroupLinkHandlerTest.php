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
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\BuildGitlabGroupStub;
use Tuleap\Gitlab\Test\Stubs\CreateGitlabRepositoriesStub;
use Tuleap\Gitlab\Test\Stubs\GitlabRepositoryAlreadyIntegratedDaoStub;
use Tuleap\Gitlab\Test\Stubs\InsertGroupTokenStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

final class GitlabRepositoryGroupLinkHandlerTest extends TestCase
{
    public function testItReturnsTheGitlabGroupRepresentation(): void
    {
        $gitlab_projects = [
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
            new GitlabProject(
                14,
                'Description 3',
                'https://gitlab.example.com',
                '/',
                new \DateTimeImmutable('@0'),
                'main'
            ),
        ];


        $group_data               = [];
        $group_data['id']         = 102;
        $group_data['name']       = "nine-nine";
        $group_data['avatar_url'] = "https://avatar.example.com";
        $group_data['full_path']  = "brookyln/nine-nie";
        $group_data['web_url']    = "https://gitlab.example.com/nine-nine";


        $handler = new GitlabRepositoryGroupLinkHandler(
            new DBTransactionExecutorPassthrough(),
            GitlabRepositoryAlreadyIntegratedDaoStub::build(),
            CreateGitlabRepositoriesStub::buildWithDefault(),
            BuildGitlabGroupStub::buildWithGroupId(45),
            InsertGroupTokenStub::build()
        );

        $credentials = CredentialsTestBuilder::get()->build();
        $project     = ProjectTestBuilder::aProject()->build();

        $result = $handler->integrateGitlabRepositoriesInProject(
            $credentials,
            $gitlab_projects,
            $project,
            GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration(),
            GitlabGroupApiDataRepresentation::buildGitlabGroupFromApi($group_data)
        );

        self::assertSame(45, $result->id);
        self::assertSame(1, $result->number_of_integrations);
    }
}
